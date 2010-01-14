<?php

class JSON_API_Controller {
  
  function JSON_API_Controller() {
    // The query object determines whether the current request is for the JSON API
    $this->query = new JSON_API_Query();
    
    // This action is called from wp-includes/template-loader.php
    add_action('template_redirect', array(&$this, 'template_redirect'));
  }
  
  function template_redirect() {
    // Check to see if there's an appropriate API method
    $method = $this->query->get_method();
    
    if ($method) {
      // Looks like this is an API request
      $this->setup();
      $this->query->setup();
      
      // Run the method
      $result = $this->$method();
      
      // Handle the result
      $this->response->respond($result);
      
      // Done!
      exit;
    }
  }
  
  function setup() {
    global $json_api_dir;
    
    // Setup additional singletons
    require_once "$json_api_dir/singletons/response.php";
    require_once "$json_api_dir/singletons/introspector.php";
    $this->response = new JSON_API_Response();
    $this->introspector = new JSON_API_Introspector();
    
    // Models used by introspection methods
    require_once "$json_api_dir/models/post.php";
    require_once "$json_api_dir/models/comment.php";
    require_once "$json_api_dir/models/category.php";
    require_once "$json_api_dir/models/tag.php";
    require_once "$json_api_dir/models/author.php";
  }
  
  function error($message, $status = 'error') {
    $result = $this->response->get_json(array(
      'error' => $message
    ), $status);
    $this->response->respond($result);
  }
  
  function get_recent_posts() {
    $posts = $this->introspector->get_posts('');
    return $this->response->get_posts_json($posts);
  }
  
  function get_post() {
    $query = '';
    if ($this->query->post_id) {
      $query = "p={$this->query->post_id}";
    } else if ($this->query->post_slug) {
      $query = "name={$this->query->post_slug}";
    } else {
      $this->error("No post specified. Include 'post_id' or 'post_slug' var in your request.");
    }
    $posts = $this->introspector->get_posts($query);
    if (count($posts) == 1) {
      return $this->response->get_json(array(
        'post' => $posts[0]
      ));
    } else {
      $this->error("No post was found.");
    }
  }
  
  function get_page() {
    $query = '';
    if ($this->query->page_id) {
      $query = "page_id={$this->query->page_id}";
    } else if ($this->query->page_slug) {
      $query = "pagename={$this->query->page_slug}";
    } else {
      $this->error("No page specified. Include 'page_id' or 'page_slug' var in your request.");
    }
    $pages = $this->introspector->get_posts($query);
    if (count($pages) == 1) {
      return $this->response->get_json(array(
        'page' => $pages[0]
      ));
    } else {
      $this->error("No page was found.");
    }
  }
  
  function get_date_posts() {
    $query = '';
    if ($this->query->date) {
      $query = "m={$this->query->date}";
    } else {
      $this->error("No date specified. Include 'date' var in your request.");
    }
    $posts = $this->introspector->get_posts($query);
    return $this->response->get_posts_json($posts);
  }
  
  function get_category_posts() {
    $query = '';
    if ($this->query->catgegory_id) {
      $query = "cat={$this->query->category_id}";
    } else if ($this->query->category_slug) {
      $query = "category_name={$this->query->category_slug}";
    } else {
      $this->error("No category specified. Include 'category_id' or 'category_slug' var in your request.");
    }
    $posts = $this->introspector->get_posts($query);
    $category = $this->introspector->get_current_category();
    return $this->response->get_posts_object_json($posts, $category);
  }
  
  function get_tag_posts() {
    $query = '';
    if ($this->query->tag_slug) {
      $query = "tag={$this->query->tag_slug}";
    } else if ($this->query->tag_id) {
      $query = "tag_id={$this->query->tag_id}";
    } else {
      $this->error("No tag specified. Include 'tag_id' or 'tag_slug' var in your request.");
    }
    $posts = $this->introspector->get_posts($query);
    $tag = $this->introspector->get_current_tag();
    return $this->response->get_posts_object_json($posts, $tag);
  }
  
  function get_author_posts() {
    $query = '';
    if ($this->query->author_id) {
      $query = "author={$this->query->author_id}";
    } else if ($this->query->author_slug) {
      $query = "author_name={$this->query->author_slug}";
    } else {
      $this->error("No author specified. Include 'author_id' or 'author_slug' var in your request.");
    }
    $posts = $this->introspector->get_posts($query);
    $author = $this->introspector->get_current_author();
    return $this->response->get_posts_object_json($posts, $author);
  }
  
  function get_search_results() {
    $query = '';
    if ($this->query->search) {
      $query = "s={$this->query->search}";
    } else {
      $this->error("No search query specified. Include 'search' var in your request.");
    }
    $posts = $this->introspector->get_posts($query);
    return $this->response->get_posts_json($posts);
  }
  
  function get_date_index() {
    $permalinks = $this->introspector->get_date_archive_permalinks();
    $tree = $this->introspector->get_date_archive_tree($permalinks);
    return $this->response->get_json(array(
      'permalinks' => $permalinks,
      'tree' => $tree
    ));
  }
  
  function get_category_index() {
    $categories = $this->introspector->get_categories();
    return $this->response->get_json(array(
      'count' => count($categories),
      'categories' => $categories
    ));
  }
  
  function get_tag_index() {
    $tags = $this->introspector->get_tags();
    return $this->response->get_json(array(
      'count' => count($tags),
      'tags' => $tags
    ));
  }
  
  function get_author_index() {
    $authors = $this->introspector->get_authors();
    return $this->response->get_json(array(
      'count' => count($authors),
      'authors' => array_values($authors)
    ));
  }
  
  function submit_comment() {
    nocache_headers();
    $required = array(
      'post_id',
      'name',
      'email',
      'content'
    );
    if (empty($_REQUEST['post_id'])) {
      $this->error("No post specified. Include 'post_id' var in your request.");
    } else if (empty($_REQUEST['name']) ||
               empty($_REQUEST['email']) ||
               empty($_REQUEST['content'])) {
      $this->error("Please include all required arguments (name, email, content).");
    } else if (!is_email($_REQUEST['email'])) {
      $this->error("Please enter a valid email address.");
    }
    $pending = new JSON_API_Comment();
    return $pending->handle_submission();
  }
  
  function include_value($key) {
    return $this->response->is_value_included($key);
  }
  
}

?>
