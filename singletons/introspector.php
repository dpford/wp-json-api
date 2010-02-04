<?php

class JSON_API_Introspector {
  
  function get_posts($query = '') {
    global $post;
    // Returns an array of JSON_API_Post objects
    $this->set_posts_query($query);
    $output = array();
    while (have_posts()) {
      the_post();
      $output[] = new JSON_API_Post($post);
    }
    return $output;
  }
  
  function get_date_archive_permalinks() {
    $archives = wp_get_archives('echo=0');
    preg_match_all("/href='([^']+)'/", $archives, $matches);
    return $matches[1];
  }
  
  function get_date_archive_tree($permalinks) {
    $tree = array();
    foreach ($permalinks as $url) {
      if (preg_match('#(\d{4})/(\d{2})#', $url, $date)) {
        $year = $date[1];
        $month = $date[2];
      } else if (preg_match('/(\d{4})(\d{2})/', $url, $date)) {
        $year = $date[1];
        $month = $date[2];
      } else {
        continue;
      }
      $count = $this->get_date_archive_count($year, $month);
      if (empty($tree[$year])) {
        $tree[$year] = array(
          $month => $count
        );
      } else {
        $tree[$year][$month] = $count;
      }
    }
    return $tree;
  }
  
  function get_date_archive_count($year, $month) {
    if (!isset($this->month_archives)) {
      global $wpdb;
      $post_counts = $wpdb->get_results("
        SELECT DATE_FORMAT(post_date, '%Y%m') AS month,
               COUNT(ID) AS post_count
        FROM $wpdb->posts
        WHERE post_status = 'publish'
          AND post_type = 'post'
        GROUP BY month
      ");
      $this->month_archives = array();
      foreach ($post_counts as $post_count) {
        $this->month_archives[$post_count->month] = $post_count->post_count;
      }
    }
    return $this->month_archives["$year$month"];
  }
  
  function get_categories() {
    $wp_categories = get_categories();
    $categories = array();
    foreach ($wp_categories as $wp_category) {
      if ($wp_category->term_id == 1 && $wp_category->slug == 'uncategorized') {
        continue;
      }
      $categories[] = $this->get_category_object($wp_category);
    }
    return $categories;
  }
  
  function get_current_category() {
    global $json_api;
    if (!empty($json_api->query->category_id)) {
      return $this->get_category_by_id($json_api->query->category_id);
    } else if (!empty($json_api->query->category_slug)) {
      return $this->get_category_by_slug($json_api->query->category_slug);
    }
    return null;
  }
  
  function get_category_object($wp_category) {
    return new JSON_API_Category($wp_category);
  }
  
  function get_category_by_id($category_id) {
    $wp_category = get_term_by('id', $category_id, 'category');
    return $this->get_category_object($wp_category);
  }
  
  function get_category_by_slug($category_slug) {
    $wp_category = get_term_by('slug', $category_slug, 'category');
    return $this->get_category_object($wp_category);
  }
  
  function get_tags() {
    $wp_tags = get_tags();
    return array_map(array(&$this, 'get_tag_object'), $wp_tags);
  }
  
  function get_current_tag() {
    global $json_api;
    if (!empty($json_api->query->tag_id)) {
      return $this->get_tag_by_id($json_api->query->tag_id);
    } else if (!empty($json_api->query->tag_slug)) {
      return $this->get_tag_by_slug($json_api->query->tag_slug);
    }
    return null;
  }
  
  function get_tag_object($wp_tag) {
    return new JSON_API_Tag($wp_tag);
  }
  
  function get_tag_by_id($tag_id) {
    $wp_tag = get_term_by('id', $tag_id, 'post_tag');
    return $this->get_tag_object($wp_tag);
  }
  
  function get_tag_by_slug($tag_slug) {
    $wp_tag = get_term_by('slug', $tag_slug, 'post_tag');
    return $this->get_tag_object($wp_tag);
  }
  
  function get_authors() {
    global $wpdb;
    $author_ids = $wpdb->get_col($wpdb->prepare("
      SELECT u.ID, m.meta_value AS last_name
      FROM $wpdb->users AS u,
           $wpdb->usermeta AS m
      WHERE m.user_id = u.ID
        AND m.meta_key = 'last_name'
      ORDER BY last_name
    "));
    $all_authors = array_map(array(&$this, 'get_author'), $author_ids);
    $active_authors = array_filter($all_authors, array(&$this, 'is_active_author'));
    return $active_authors;
  }
  
  function get_author($id) {
    return $this->get_author_by_id($id);
  }
  
  function get_author_by_id($id) {
    return new JSON_API_Author($id);
  }
  
  function get_author_by_login($login) {
    $id = $wpdb->get_var($wpdb->prepare("
      SELECT ID
      FROM $wpdb->users
      WHERE user_login = %s
    ", $login));
    return $this->get_author_by_id($id);
  }
  
  function get_current_author() {
    $author_id = get_query_var('author');
    return $this->get_author($author_id);
  }
  
  function is_active_author($author) {
    if (!isset($this->active_authors)) {
      $this->active_authors = explode(',', wp_list_authors('html=0&echo=0'));
      $this->active_authors = array_map('trim', $this->active_authors);
    }
    return in_array($author->name, $this->active_authors);
  }
  
  function set_posts_query($query = '') {
    // Returns a query string to pass to WP's query_posts() function
    if (get_query_var('page')) {
      $amp = empty($query) ? '' : '&';
      $query .= "{$amp}paged=" . get_query_var('page');
    }
    if (get_query_var('count')) {
      $amp = empty($query) ? '' : '&';
      $query .= "{$amp}posts_per_page=" . get_query_var('count');
    }
    if (!empty($query)) {
      query_posts($query);
    }
  }
  
  function get_comments($post_id) {
    global $wpdb;
    $wp_comments = $wpdb->get_results($wpdb->prepare("
      SELECT *
      FROM $wpdb->comments
      WHERE comment_post_ID = %d
        AND comment_approved = 1
        AND comment_type = ''
      ORDER BY comment_date
    ", $post_id));
    $comments = array();
    foreach ($wp_comments as $wp_comment) {
      $comments[] = new JSON_API_Comment($wp_comment);
    }
    return $comments;
  }
  
  function get_attachments($post_id) {
    $wp_attachments = get_children("post_type=attachment&post_parent=$post_id");
    $attachments = array();
    if (!empty($wp_attachments)) {
      foreach ($wp_attachments as $wp_attachment) {
        $attachments[] = new JSON_API_Attachment($wp_attachment);
      }
    }
    return $attachments;
  }
  
}

?>
