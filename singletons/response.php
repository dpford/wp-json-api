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
  
  function get_json($data, $status = 'ok') {
    // Include a status value with the response
    if (is_array($data)) {
      $data = array_merge(array('status' => $status), $data);
    } else if (is_object($data)) {
      $data = get_object_vars($data);
      $data = array_merge(array('status' => $status), $data);
    }
    
    $data = apply_filters('json_api_encode', $data);
    
    if (function_exists('json_encode')) {
      // Use the built-in json_encode function if it's available
      return json_encode($data);
    } else {
      // Use PEAR's Services_JSON encoder otherwise
      if (!class_exists('Services_JSON')) {
        $dir = dirname(dirname(__FILE__));
        require_once "$dir/library/JSON.php";
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
    $json = $this->get_json($result, $status);
    $status_redirect = "redirect_$status";
    if ($json_api->query->dev || !empty($_REQUEST['dev'])) {
      // Output the result in a human-redable format
      if (!headers_sent()) {
        header('Content-Type: text/plain; charset: UTF-8', true);
      } else {
        echo '<pre>';
      }
      echo $this->prettify($json);
    } else if (!empty($_REQUEST[$status_redirect])) {
      wp_redirect($_REQUEST[$status_redirect]);
    } else if ($json_api->query->redirect) {
      $url = $this->add_status_query_var($json_api->query->redirect, $status);
      wp_redirect($url);
    } else if ($json_api->query->callback) {
      // Run a JSONP-style callback with the result
      $this->callback($json_api->query->callback, $json);
    } else {
      // Output the result
      $this->output($json);
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
  
  function prettify($ugly) {
    $pretty = "";
    $indent = "";
    $last = '';
    $pos = 0;
    $level = 0;
    $string = false;
    while ($pos < strlen($ugly)) {
      $char = substr($ugly, $pos++, 1);
      if (!$string) {
        if ($char == '{' || $char == '[') {
          if ($char == '[' && substr($ugly, $pos, 1) == ']') {
            $pretty .= "[]";
            $pos++;
          } else if ($char == '{' && substr($ugly, $pos, 1) == '}') {
            $pretty .= "{}";
            $pos++;
          } else {
            $pretty .= "$char\n";
            $indent = str_repeat('  ', ++$level);
            $pretty .= "$indent";
          }
        } else if ($char == '}' || $char == ']') {
          $indent = str_repeat('  ', --$level);
          if ($last != '}' && $last != ']') {
            $pretty .= "\n$indent";
          } else if (substr($pretty, -2, 2) == '  ') {
            $pretty = substr($pretty, 0, -2);
          }
          $pretty .= $char;
          if (substr($ugly, $pos, 1) == ',') {
            $pretty .= ",";
            $last = ',';
            $pos++;
          }
          $pretty .= "\n$indent";
        } else if ($char == ':') {
          $pretty .= ": ";
        } else if ($char == ',') {
          $pretty .= ",\n$indent";
        } else if ($char == '"') {
          $pretty .= '"';
          $string = true;
        } else {
          $pretty .= $char;
        }
      } else {
        if ($last != '\\' && $char == '"') {
          $string = false;
        }
        $pretty .= $char;
      }
      $last = $char;
    }
    return $pretty;
  }
  
}

?>
