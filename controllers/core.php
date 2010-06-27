<?php
/*
Name: Core
Description: Basic introspection methods
URL: http://wordpress.org/extend/plugins/json-api/other_notes/
*/

class JSON_API_Core_Controller {
  
  function info() {
    global $json_api;
    if (!empty($_REQUEST['controller'])) {
      return $json_api->controller_info($_REQUEST['controller']);
    } else {
      $dir = dirname(dirname(__FILE__));
      $php = file_get_contents("$dir/json-api.php");
      if (preg_match('/^\s*Version:\s*(.+)$/m', $php, $matches)) {
        return array(
          'json_api_version' => $matches[1],
          'controllers' => explode(',', get_option('json_api_controllers', 'core'))
        );
      }
      $json_api->error("Unknown JSON API version");
    }
  }
  
  function get_recent_posts() {
    global $json_api;
    $posts = $json_api->introspector->get_posts();
    return $this->posts_result($posts);
  }
  
  function get_post() {
    global $json_api;
    if ($json_api->query->post_id) {
      $posts = $json_api->introspector->get_posts(array(
        'p' => $json_api->query->post_id
      ));
    } else if ($json_api->query->post_slug) {
      $posts = $json_api->introspector->get_posts(array(
        'name' => $json_api->query->post_slug
      ));
    } else {
      $json_api->error("No post specified. Include 'post_id' or 'post_slug' var in your request.");
    }
    if (count($posts) == 1) {
      return array(
        'post' => $posts[0]
      );
    } else {
      $json_api->error("No post was found.");
    }
  }
  
  function get_page() {
    global $json_api;
    if ($json_api->query->page_id) {
      $posts = $json_api->introspector->get_posts(array(
        'page_id' => $json_api->query->page_id
      ));
    } else if ($json_api->query->page_slug) {
      $posts = $json_api->introspector->get_posts(array(
        'pagename' => $json_api->query->page_slug
      ));
    } else {
      $json_api->error("No page specified. Include 'page_id' or 'page_slug' var in your request.");
    }
    
    // Workaround for https://core.trac.wordpress.org/ticket/12647
    if (empty($posts)) {
      $url = $_SERVER['REQUEST_URI'];
      $parsed_url = parse_url($url);
      $path = $parsed_url['path'];
      if (preg_match('#^http://[^/]+(/.+)$#', get_bloginfo('url'), $matches)) {
        $blog_root = $matches[1];
        $path = preg_replace("#^$blog_root#", '', $path);
      }
      if (substr($path, 0, 1) == '/') {
        $path = substr($path, 1);
      }
      $posts = $json_api->introspector->get_posts(array('pagename' => $path));
    }
    
    if (count($posts) == 1) {
      if (!empty($json_api->query->children)) {
        $json_api->introspector->attach_child_posts($posts[0]);
      }
      return array(
        'post' => $posts[0]
      );
    } else {
      $json_api->error("No page was found.");
    }
  }
  
  function get_date_posts() {
    global $json_api;
    if ($json_api->query->date) {
      $posts = $json_api->introspector->get_posts(array(
        'm' => $json_api->query->date
      ));
    } else {
      $json_api->error("No date specified. Include 'date' var in your request.");
    }
    return $json_api->response->get_posts_json($posts);
  }
  
  function get_category_posts() {
    global $json_api;
    if ($json_api->query->category_id) {
      $posts = $json_api->introspector->get_posts(array(
        'cat' => $json_api->query->category_id
      ));
    } else if ($json_api->query->category_slug) {
      $posts = $json_api->introspector->get_posts(array(
        'category_name' => $json_api->query->category_slug
      ));
    } else {
      $json_api->error("No category specified. Include 'category_id' or 'category_slug' var in your request.");
    }
    $category = $json_api->introspector->get_current_category();
    return $this->posts_object_result($posts, $category);
  }
  
  function get_tag_posts() {
    global $json_api;
    if ($json_api->query->tag_slug) {
      $posts = $json_api->introspector->get_posts(array(
        'tag' => $json_api->query->tag_slug
      ));
    } else if ($json_api->query->tag_id) {
      $posts = $json_api->introspector->get_posts(array(
        'tag_id' => $json_api->query->tag_id
      ));
    } else {
      $json_api->error("No tag specified. Include 'tag_id' or 'tag_slug' var in your request.");
    }
    $tag = $json_api->introspector->get_current_tag();
    return $this->posts_object_result($posts, $tag);
  }
  
  function get_author_posts() {
    global $json_api;
    if ($json_api->query->author_id) {
      $posts = $json_api->introspector->get_posts(array(
        'author' => $json_api->query->author_id
      ));
    } else if ($json_api->query->author_slug) {
      $posts = $json_api->introspector->get_posts(array(
        'author_name' => $json_api->query->author_slug
      ));
    } else {
      $json_api->error("No author specified. Include 'author_id' or 'author_slug' var in your request.");
    }
    $author = $json_api->introspector->get_current_author();
    return $this->posts_object_result($posts, $author);
  }
  
  function get_search_results() {
    global $json_api;
    if ($json_api->query->search) {
      $posts = $json_api->introspector->get_posts(array(
        's' => $json_api->query->search
      ));
    } else {
      $json_api->error("No search query specified. Include 'search' var in your request.");
    }
    return $this->posts_result($posts);
  }
  
  function get_date_index() {
    global $json_api;
    $permalinks = $json_api->introspector->get_date_archive_permalinks();
    $tree = $json_api->introspector->get_date_archive_tree($permalinks);
    return array(
      'permalinks' => $permalinks,
      'tree' => $tree
    );
  }
  
  function get_category_index() {
    global $json_api;
    $categories = $json_api->introspector->get_categories();
    return array(
      'count' => count($categories),
      'categories' => $categories
    );
  }
  
  function get_tag_index() {
    global $json_api;
    $tags = $json_api->introspector->get_tags();
    return array(
      'count' => count($tags),
      'tags' => $tags
    );
  }
  
  function get_author_index() {
    global $json_api;
    $authors = $json_api->introspector->get_authors();
    return array(
      'count' => count($authors),
      'authors' => array_values($authors)
    );
  }
  
  function get_page_index() {
    global $json_api;
    $pages = array();
    $wp_posts = get_posts(array(
      'post_type' => 'page',
      'post_parent' => 0,
      'order' => 'ASC',
      'orderby' => 'menu_order'
    ));
    foreach ($wp_posts as $wp_post) {
      $pages[] = new JSON_API_Post($wp_post);
    }
    foreach ($pages as $page) {
      $json_api->introspector->attach_child_posts($page);
    }
    return array(
      'pages' => $pages
    );
  }
  
  function get_nonce() {
    global $json_api;
    $controller = $json_api->query->controller;
    $method = $json_api->query->method;
    return array(
      'nonce' => wp_create_nonce("json_api-$controller-$method")
    );
  }
  
  private function posts_result($posts) {
    global $wp_query;
    return array(
      'count' => count($posts),
      'count_total' => (int) $wp_query->found_posts,
      'pages' => $wp_query->max_num_pages,
      'posts' => $posts
    );
  }
  
  private function posts_object_result($posts, $object) {
    global $wp_query;
    // Convert something like "JSON_API_Category" into "category"
    $object_key = strtolower(substr(get_class($object), 9));
    return array(
      'count' => count($posts),
      'pages' => (int) $wp_query->max_num_pages,
      $object_key => $object,
      'posts' => $posts
    );
  }
  
}

?>
