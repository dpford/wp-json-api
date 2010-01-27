<?php
/*
Plugin Name: JSON API
Plugin URI: http://wordpress.org/extend/plugins/json-api/
Description: A RESTful API for WordPress
Version: 0.8.2
Author: Dan Phiffer
Author URI: http://phiffer.org/
*/

global $json_api_dir;
$json_api_dir = WP_PLUGIN_DIR . '/json-api';

function json_api_init() {
  // Initialize the controller and query inspector
  global $json_api, $json_api_dir;
  require_once "$json_api_dir/singletons/controller.php";
  require_once "$json_api_dir/singletons/query.php";
  $json_api = new JSON_API_Controller();
}

function json_api_activation() {
  // Add the rewrite rule on activation
  global $wp_rewrite;
  add_filter('rewrite_rules_array', 'json_api_rewrites');
  $wp_rewrite->flush_rules();
}

function json_api_deactivation() {
  // Remove the rewrite rule on deactivation
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
}

function json_api_rewrites($wp_rules) {
  // Register the rewrite rule /api/[method] => ?json=[method]
  $json_api_rules = array(
    'api/(.+)$' => 'index.php?json=$matches[1]'
  );
  return array_merge($json_api_rules, $wp_rules);
}

// Add initialization and activation hooks
add_action('init', 'json_api_init');
register_activation_hook("$json_api_dir/json-api.php", 'json_api_activation');
register_deactivation_hook("$json_api_dir/json-api.php", 'json_api_deactivation');

?>
