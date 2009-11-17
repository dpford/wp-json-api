<?php

class JSON_API_Introspector {
  
  function get_posts($query = '') {
    // Returns an array of JSON_API_Post objects
    $this->set_posts_query($query);
    $output = array();
    while (have_posts()) {
      the_post();
      $output[] = new JSON_API_Post();
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
      $categories[] = $this->get_category($wp_category);
    }
    return $categories;
  }
  
  function get_category($arg) {
    if (is_object($arg)) {
      return new JSON_API_Category($arg);
    } else if (is_numeric($arg)) {
      $wp_category = get_term_by('id', $arg, 'category');
      return $this->get_category($wp_category);
    } else if (is_string($arg)) {
      $wp_category = get_term_by('slug', $arg, 'category');
      return $this->get_category($wp_category);
    } else {
      return null;
    }
  }
  
  function get_current_category() {
    global $json_api;
    $category = $json_api->query->category_id;
    if (empty($category)) {
      $category = $json_api->query->category_slug;
    }
    return $this->get_category($category);
  }
  
  function get_tags() {
    $wp_tags = get_tags();
    return array_map(array(&$this, 'get_tag'), $wp_tags);
  }
  
  function get_tag($arg) {
    if (is_object($arg)) {
      return new JSON_API_Tag($arg);
    } else if (is_numeric($arg)) {
      $wp_tag = get_term_by('id', $arg, 'post_tag');
      return $this->get_tag($wp_tag);
    } else if (is_string($arg)) {
      $wp_tag = get_term_by('slug', $arg, 'post_tag');
      return $this->get_tag($wp_tag);
    } else {
      return null;
    }
  }
  
  function get_current_tag() {
    global $json_api;
    $tag = $json_api->query->tag_id;
    if (empty($tag)) {
      $tag = $json_api->query->tag_slug;
    }
    return $this->get_tag($tag);
  }
  
  function get_authors() {
    global $wpdb;
    $author_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->users"));
    $all_authors = array_map(array(&$this, 'get_author'), $author_ids);
    $active_authors = array_filter($all_authors, array(&$this, 'is_active_author'));
    return $active_authors;
  }
  
  function get_author($id) {
    return new JSON_API_Author($id);
  }
  
  function get_current_author() {
    $author_id = get_query_var('author');
    return $this->get_author($author_id);
  }
  
  function is_active_author($author) {
    if (!isset($this->active_authors)) {
      $this->active_authors = explode(',', wp_list_authors('html=0&echo=0'));
    }
    return in_array($author->slug, $this->active_authors);
  }
  
  function set_posts_query($query = '') {
    // Returns a query string to pass to WP's query_posts() function
    if (get_query_var('page')) {
      $amp = empty($query) ? '' : '&';
      $query .= "{$amp}paged=" . get_query_var('page');
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
  
}

?>
