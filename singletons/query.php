<?php

class JSON_API_Query {
  
  // Default values
  protected $defaults = array(
    'date_format' => 'Y-m-d H:i:s',
    'read_more' => 'Read more'
  );
  
  function JSON_API_Query() {
    // Register JSON API query vars
    add_filter('query_vars', array(&$this, 'query_vars'));
  }
  
  function get($key) {
    $query_var = (isset($_REQUEST[$key])) ? $_REQUEST[$key] : null;
    $wp_query_var = $this->wp_query_var($key);
    if ($wp_query_var) {
      return $wp_query_var;
    } else if ($query_var) {
      return $this->strip_magic_quotes($query_var);
    } else if (isset($this->defaults[$key])) {
      return $this->defaults[$key];
    } else {
      return null;
    }
  }
  
  function __get($key) {
    return $this->get($key);
  }
  
  function wp_query_var($key) {
    $wp_translation = array(
      'p' =>              'post_id',
      'name' =>           'post_slug',
      'page_id' =>        'page_id',
      'pagename' =>       'page_slug',
      'cat' =>            'category_id',
      'category_name' =>  'category_slug',
      'tag_id' =>         'tag_id',
      'tag' =>            'tag_slug',
      'author' =>         'author_id',
      'author_name' =>    'author_slug',
      'm' =>              'date',
      's' =>              'search',
      'order' =>          'order',
      'orderby' =>        'order_by'
    );
    if ($key == 'json' || in_array($key, $wp_translation)) {
      return get_query_var($key);
    } else {
      return null;
    }
  }
  
  function strip_magic_quotes($value) {
    if (get_magic_quotes_gpc()) {
      return stripslashes($value);
    } else {
      return $value;
    }
  }
  
  function query_vars($wp_vars) {
    $wp_vars[] = 'json';
    return $wp_vars;
  }
  
  function get_controller() {
    $json = $this->get('json');
    if (empty($json)) {
      return false;
    }
    if (strpos($json, '/') === false) {
      $controller = 'core';
    } else {
      list($controller, $method) = explode('/', $json);
    }
    return $controller;
  }
  
  function get_method($controller) {
    
    global $json_api;
    
    // Returns an appropriate API method name or false. Four possible outcomes:
    //   1. API isn't being invoked at all (return false)
    //   2. A specific API method was requested (return method name)
    //   3. A method is chosen implicitly on a given WordPress page
    //   4. API invoked incorrectly (return "error" method)
    //
    // Note:
    //   The implicit outcome (3) is invoked by setting the json query var to a
    //   non-empty value on any WordPress page:
    //     * http://example.org/2009/11/10/hello-world/?json=1 (get_post)
    //     * http://example.org/2009/11/?json=1 (get_date_posts)
    //     * http://example.org/category/foo?json=1 (get_category_posts)
    
    $method = $this->get('json');
    
    if (empty($method)) {
      // Case 1: we're not being invoked (done!)
      return false;
    } else if (method_exists("JSON_API_{$controller}_Controller", $method)) {
      // Case 2: an explicit method was specified
      return $method;
    } else if ($controller == 'core') {
      // Case 3: choose the method implicitly based on which page we're on...
      if (is_search()) {
        return 'get_search_results';
      } else if (is_home()) {
        if (empty($_GET['json'])) {
          $json_api->error("Uknown method '$method'.");
        }
        return 'get_recent_posts';
      } else if (is_page()) {
        return 'get_page';
      } else if (is_single()) {
        return 'get_post';
      } else if (is_category()) {
        return 'get_category_posts';
      } else if (is_tag()) {
        return 'get_tag_posts';
      } else if (is_author()) {
        return 'get_author_posts';
      } else if (is_date()) {
        return 'get_date_posts';
      } else if (is_404()) {
        return '404';
      }
    }
    // Case 4: either the method doesn't exist or we don't support the page implicitly
    return 'error';
  }
  
}
