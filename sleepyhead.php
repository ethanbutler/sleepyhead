<?php

/*
Plugin Name: Sleepyhead
Plugin URI: https://github.com/ethanbutler/sleepyhead
Description: Automated transient management for your WordPress REST API.
Version: 0.1
Author: Ethan Butler
Author URI: https://github.com/ethanbutler
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

TODO:  Document functions
FIXME: Need to invalidate caching for taxonomies, users, etc
*/

class Sleepyhead {

  function __construct(){

    $this->namespace      = 'sleepy_';
    $this->types_transient_name = $this->namespace . 'types';

    define('OVERRIDE_PARAM', 'MOCK');

    add_action('save_post', array($this, 'invalidate_transients_for_post'));

    add_filter('rest_dispatch_request', array($this, 'check_for_transient_before_dispatch'), 20, 2);

    add_filter('rest_post_dispatch', array($this, 'wrap_dispatch_with_transient'), 20, 3);

  }

  /**
   * Replaces dispatch with a pre-calculated transient if such a transient exists.
   * @param  bool $dispatch       False. Will be used as return value if not hijacked.
   * @param  WP_REST_Request $req REST request.
   * @return mixed                Value of transient matching route or false
   */
  function check_for_transient_before_dispatch($dispatch, $req){
    if($req[OVERRIDE_PARAM]) return $dispatch; // allow override for benchmarking
    $name = $this->namespace . $this->get_path_with_params($req);
    $transient = get_transient($name);
    return $transient ? $transient : $dispatch;
  }

  /**
   * Sets a transient based on a REST dispatch's path and return value.
   * @param  WP_REST_Response $res  Server response
   * @param  WP_Post          $post Post object. Unused, only here because we need the third arg.
   * @param  WP_REST_Request  $req  Server request. Include "?MOCK" in query params to bypass this function.
   * @return WP_REST_Response       Returns unchanged response object.
   */
  function wrap_dispatch_with_transient($res, $post, $req){
    if($req[OVERRIDE_PARAM]) return $res; // allow override for benchmarking

    $name = $this->namespace . $this->get_path_with_params($req);
    set_transient($name, $res, 0);

    return $res;
  }

  /**
   * Deletes any transient objects that include modified post data.
   * @param  integer           $id Post ID;
   * @return void
   */
  function invalidate_transients_for_post($id){
    global $wpdb;
    $type = get_post_type_object(get_post_type($id));

    if(!$type->show_in_rest) return;

    $base = $type->rest_base ? $type->rest_base : $type->name;

    $transients = $wpdb->get_results($wpdb->prepare(
      "
      SELECT option_name
      FROM wp_options
      WHERE option_name
      LIKE '_transient_%%%s%%%s%%'
      ",
      $this->namespace,
      $base
    ));

    foreach($transients as $transient){
      $transient_name = str_replace("_transient_", '', $transient->option_name);
      $transient_path = str_replace($this->namespace, '', $transient_name);

      $route = site_url() . '/wp-json'. $transient_path . '&' . OVERRIDE_PARAM .'=true';

      // TODO: Do this with a mocked version of rest server instead of making actual requests

      $get = wp_remote_get($route);

      $body = json_decode($get['body']);

      if($body->id === $id){ // Delete single post transient
        delete_transient($transient_name);
      } elseif(is_array($body)) { // Delete post transient in array
        foreach($body as $result){
          if($result->id === $id){
            delete_transient($transient_name);
          }
        }
      }

    }
  }

  /**
   * Returns the route and query params for a request. For use with storing a transient name.
   * @param  WP_REST_Request $req Server request.
   * @return string               Full URL path for request.
   */
  function get_path_with_params($req){
    return $req->get_route() . '?' . http_build_query($req->get_params());
  }

}

$sh = new Sleepyhead();
