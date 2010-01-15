<?php

class JSON_API_Query {
  
  var $vars = array(
    'json',          // Determines which API controller method will be called
                     //   Expects one of the following values:
                     //     * 'get_recent_posts'
                     //     * 'get_post'
                     //     * 'get_page'
                     //     * 'get_date_posts'
                     //     * 'get_category_posts'
                     //     * 'get_tag_posts'
                     //     * 'get_author_posts'
                     //     * 'get_date_index'
                     //     * 'get_category_index'
                     //     * 'get_tag_index'
                     //     * 'get_author_index'
                     //     * 'get_search_results'
                     //     * 'submit_comment'
                     //     * Any non-empty value to trigger the API implicitly
    'callback',      // Triggers a JSONP-style callback with the resulting data
    'dev',           // Triggers a developer-friendly print_r() output
    'redirect',      // Triggers a redirect response to the specified URL
    'page',          // Returns a specific page of results
    'count',         // Controls the number of posts returned
    'post_id',       // Used by the get_post API method
    'post_slug',     // Used by the get_post API method
    'page_id',       // Used by the get_page API method
    'page_slug',     // Used by the get_page API method
    'search',        // Used by the get_search_results API method
    'category_id',   // Used by get_category_posts API method
    'category_slug', // Used by get_category_posts API method
    'tag_id',        // Used by get_tag_posts API method
    'tag_slug',      // Used by get_tag_posts API method
    'author_id',     // Used by get_author_posts API method
    'author_slug',   // Used by get_author_posts API method
    'date',          // Used by get_date_posts API method
                     //   Expects 'YYYY', 'YYYYMM' or 'YYYYMMDD'
    'date_format',   // Changes the format of date values
                     //   Uses the same syntax as PHP's date() function
                     //   Default value is Y-m-d H:i:s
    'read_more',     // Changes the 'read more' link text in post content
    'include',       // Specifies which post data fields to include
                     //   Expects a comma-separated list of post fields
                     //   Leaving this empty includes *all* fields
    'exclude',       // Specifies which post data fields to exclude
                     //   Expects a comma-separated list of post fields
    'custom_fields', // Includes values from posts' Custom Fields
                     //   Expects a comma-separated list of custom field keys
    'author_meta'    // Includes additional author metadata
                     //   Should be a comma-separated list of metadata fields
    
    // Note about include/exclude vars:
    //   The default behavior includes all post values. You only need to
    //   specify one of include or exclude -- the former will implicitly leave
    //   out those fields you haven't specified and the latter will implicitly
    //   include them. For example, a query that uses exclude=comments will
    //   include everything *except* the comments, so there's no need to also
    //   specify anything with the include argument.

  );
  
  // Default values
  var $date_format = 'Y-m-d H:i:s';
  var $read_more = 'Read more';
  
  function JSON_API_Query() {
    // Register JSON API query vars
    add_filter('query_vars', array(&$this, 'query_vars'));
  }
  
  function query_vars($wp_vars) {
    // This makes our variables available from the get_query_var WP function
    return array_merge($wp_vars, $this->vars);
  }
  
  function setup() {
    // Translation between WordPress vars and natively understood vars
    $wp_translation = array(
      'p' =>              'post_id',
      'name' =>           'post_slug',
      'page_id' =>        'page_id',
      'pagename' =>       'page_slug',
      'cat' =>            'category_id',
      'category_name' =>  'category_slug',
      'tag' =>            'tag_slug',
      'author' =>         'author_id',
      'author_name' =>    'author_slug',
      'm' =>              'date',
      's' =>              'search'
    );
    
    foreach ($wp_translation as $wp_var => $var) {
      // Assign WP query vars to natively understood vars
      $this->$var = get_query_var($wp_var);
    }
    
    foreach ($this->vars as $var) {
      // Assign query vars as object properties for convenience
      $value = get_query_var($var);
      if ($value) {
        $this->$var = $value;
      } else if (!isset($this->$var)) {
        $this->$var = null;
      }
    }
  }
  
  function get_method() {
    // Returns an appropriate API method name or false. Four possible outcomes:
    //   1. API isn't being invoked at all (return false)
    //   2. A specific API method was requested (return method name)
    //   3. A method is chosen implicitly on a given WordPress page
    //   4. API invoked incorrectly (return "error" method)
    //
    // Note:
    //   The implicit outcome (3) is invoked by setting the json query var to a
    //   non-empty value on any WordPress page:
    //     * http://example.org/2009/11/10/hello-world/?json=1
    //     * http://example.org/2009/11/?json=1
    //     * http://example.org/category/foo?json=1
    
    $this->method = get_query_var('json');
    
    if (empty($this->method)) {
      // Case 1: we're not being invoked (done!)
      return false;
    } else if (method_exists('JSON_API_Controller', $this->method)) {
      // Case 2: an explicit method was specified
      return $this->method;
      
    // Case 3: choose the method implicitly based on which page we're on...
    
    } else if (is_search()) {
      return 'get_search_results';
    } else if (is_home()) {
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
    } else {
      // Case 4: either the method doesn't exist or we don't support this page
      return 'error';
    }
  }
  
}
