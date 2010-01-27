<?php

class JSON_API_Response {
  
  function JSON_API_Response() {
    $this->setup_include_values();
  }
  
  function setup_include_values() {
    $this->include_values = array();
    if (get_query_var('include')) {
      $this->include_values = explode(',', get_query_var('include'));
    }
    if (get_query_var('exclude')) {
      $exclude = explode(',', get_query_var('exclude'));
      $this->include_values = array_diff($this->include_values, $exclude);
    }
  }
  
  function get_posts_json($posts, $status = 'ok') {
    global $wp_query;
    return $this->get_json(array(
      'count' => count($posts),
      'count_total' => $wp_query->found_posts,
      'pages' => $wp_query->max_num_pages,
      'posts' => $posts
    ), $status);
  }
  
  function get_posts_object_json($posts, $object, $status = 'ok') {
    global $wp_query;
    $object_key = strtolower(substr(get_class($object), 9));
    return $this->get_json(array(
      'count' => count($posts),
      'pages' => $wp_query->max_num_pages,
      $object_key => $object,
      'posts' => $posts
    ), $status);
  }
  
  function get_json($data, $status = 'ok') {
    // Include a status value with the response
    if (is_array($data)) {
      $data = array_merge(array('status' => $status), $data);
    } else if (is_object($data)) {
      $data = get_object_vars($data);
      $data = array_merge(array('status' => $status), $data);
    }
    
    $data = apply_filters('json_api_encode', $data);
    
    if (!empty($_REQUEST['dev'])) {
      // Don't JSON encode the data in dev mode
      return $data;
    } else if (function_exists('json_encode')) {
      // Use the built-in json_encode function if it's available
      return json_encode($data);
    } else {
      // Use PEAR's Services_JSON encoder otherwise
      if (!class_exists('Services_JSON')) {
        global $json_api_dir;
        require_once "$json_api_dir/library/JSON.php";
      }
      $json = new Services_JSON();
      return $json->encode($data);
    }
  }
  
  function is_value_included($key) {
    if (empty($this->include_values)) {
      return true;
    } else {
      return in_array($key, $this->include_values);
    }
  }
  
  function respond($result, $status = 'ok') {
    global $json_api;
    $status_redirect = "redirect_$status";
    if ($json_api->query->dev) {
      // Output the result with print_r
      if (!headers_sent()) {
        header('Content-Type: text/plain; charset: UTF-8', true);
      }
      print_r($result);
    } else if (!empty($_REQUEST[$status_redirect])) {
      wp_redirect($_REQUEST[$status_redirect]);
    } else if ($json_api->query->redirect) {
      $url = $this->add_status_query_var($json_api->query->redirect, $status);
      wp_redirect($url);
    } else if ($json_api->query->callback) {
      // Run a JSONP-style callback with the result
      $this->callback($json_api->query->callback, $result);
    } else {
      // Output the result in JSON format
      $this->output($result);
    }
    exit;
  }
  
  function output($result) {
    $charset = get_option('blog_charset');
    if (!headers_sent()) {
      header("Content-Type: application/json; charset=$charset", true);
      header("Content-Disposition: attachment; filename=\"json_api.json\"", true);
    }
    echo $result;    
  }
  
  function callback($callback, $result) {
    $charset = get_option('blog_charset');
    if (!headers_sent()) {
      header("Content-Type: application/javascript; charset=$charset", true);
    }
    echo "$callback($result)";
  }
  
  function add_status_query_var($url, $status) {
    if (strpos($url, '#')) {
      // Remove the anchor hash for now
      $pos = strpos($url, '#');
      $anchor = substr($url, $pos);
      $url = substr($url, 0, $pos);
    }
    if (strpos($url, '?')) {
      $url .= "&status=$status";
    } else {
      $url .= "?status=$status";
    }
    if (!empty($anchor)) {
      // Add the anchor hash back in
      $url .= $anchor;
    }
    return $url;
  }
  
}

?>
