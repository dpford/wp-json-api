<?php
/*
Name: Posts
Description: Data manipulation methods for posts
URL: 
*/

class JSON_API_Posts_Controller {

  function create_post() {
    global $json_api;
    if (!current_user_can('edit_posts')) {
      $json_api->error("You need to login with a user capable of creating posts.");
    }
    nocache_headers();
    $post = new JSON_API_Post();
    $id = $post->create($_REQUEST);
    if (empty($id)) {
      $json_api->error("Could not create post.");
    }
    return array(
      'post' => $post
    );
  }
  
}

?>
