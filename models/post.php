<?php

class JSON_API_Post {
  
  // Note:
  //   JSON_API_Post objects must be instantiated within The Loop.
  
  var $id;              // Integer
  var $slug;            // String
  var $url;             // String
  var $title;           // String
  var $title_plain;     // String
  var $content;         // String (modified by read_more query var)
  var $excerpt;         // String
  var $date;            // String (modified by date_format query var)
  var $modified;        // String (modified by date_format query var)
  var $categories;      // Array of objects
  var $tags;            // Array of objects
  var $author;          // Object
  var $comments;        // Array of objects
  var $attachments;     // Array of objects
  var $comment_count;   // Integer
  var $comment_status;  // String ("open" or "closed")
  var $custom_fields;   // Object (included by using custom_fields query var)
  
  function JSON_API_Post() {
    global $json_api, $post;
    $date_format = $json_api->query->date_format;
    $this->id = (int) get_the_ID();
    $this->set_value('slug', $post->post_name);
    $this->set_value('url', get_permalink());
    $this->set_value('title', get_the_title());
    $this->set_value('title_plain', strip_tags(get_the_title()));
    $this->set_content_value();
    $this->set_value('excerpt', get_the_excerpt());
    $this->set_value('date', get_the_time($date_format));
    $this->set_value('modified', date($date_format, strtotime($post->post_modified)));
    $this->set_categories_value();
    $this->set_tags_value();
    $this->set_author_value();
    $this->set_comments_value();
    $this->set_attachments_value();
    $this->set_value('comment_count', (int) $post->comment_count);
    $this->set_value('comment_status', $post->comment_status);
    $this->set_custom_fields_value();
  }
  
  function set_value($key, $value) {
    global $json_api;
    if ($json_api->include_value($key)) {
      $this->$key = $value;
    } else {
      unset($this->$key);
    }
  }
    
  function set_content_value() {
    global $json_api;
    if ($json_api->include_value('content')) {
      $content = get_the_content($json_api->query->read_more);
      $content = apply_filters('the_content', $content);
      $content = str_replace(']]>', ']]&gt;', $content);
      $this->content = $content;
    }
  }
  
  function set_categories_value() {
    global $json_api;
    if ($json_api->include_value('categories')) {
      $this->categories = array();
      if ($wp_categories = get_the_category()) {
        foreach ($wp_categories as $wp_category) {
          $category = new JSON_API_Category($wp_category);
          if ($category->id == 1 && $category->slug == 'uncategorized') {
            // Skip the 'uncategorized' category
            continue;
          }
          $this->categories[] = $category;
        }
      }
    }
  }
  
  function set_tags_value() {
    global $json_api;
    if ($json_api->include_value('tags')) {
      $this->tags = array();
      if ($wp_tags = get_the_tags()) {
        foreach ($wp_tags as $wp_tag) {
          $this->tags[] = new JSON_API_Tag($wp_tag);
        }
      }
    }
  }
  
  function set_author_value() {
    global $json_api;
    if ($json_api->include_value('author')) {
      $this->author = new JSON_API_Author();
    }
  }
  
  function set_comments_value() {
    global $json_api;
    if ($json_api->include_value('comments')) {
      $this->comments = $json_api->introspector->get_comments($this->id);
    }
  }
  
  function set_attachments_value() {
    global $json_api;
    if ($json_api->include_value('attachments')) {
      $this->attachments = $json_api->introspector->get_attachments($this->id);
    }
  }
  
  function set_custom_fields_value() {
    global $json_api;
    if ($json_api->include_value('custom_fields') &&
        $json_api->query->custom_fields) {
      $keys = explode(',', $json_api->query->custom_fields);
      $wp_custom_fields = get_post_custom($this->id);
      $this->custom_fields = new stdClass();
      foreach ($keys as $key) {
        if (isset($wp_custom_fields[$key])) {
          $this->custom_fields->$key = $wp_custom_fields[$key];
        }
      }
    } else {
      unset($this->custom_fields);
    }
  }
  
}

?>
