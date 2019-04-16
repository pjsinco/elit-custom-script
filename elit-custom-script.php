<?php 
/**
 * Plugin Name: Elit Custom Script
 * Description: Allow editors to include a Javascript file with a post 
 * Version: 0.1.0
 */

if (!defined('WPINC')) {
    exit;
}

/**
 * Filter an array by its keys using a callback.
 *
 * Source: https://gist.github.com/h4cc/8e2e3d0f6a8cd9cacde8
 *
 * @param $arr      array    The array to filter
 * @param $callback function The filter callback, which will get 
 *                           the key as its first arg
 * @return array             The remaining key => value pairs from $array
 */
function elit_array_filter_key( $arr, $callback ) {
  $matched_keys = array_filter( array_keys( $arr ), $callback );
  return array_intersect_key( $arr, array_flip( $matched_keys ) );
}


function elit_get_available_dependencies( $all_fields ) {

  if ( ! $all_fields ) return false;

  // Dependencies begin with "elit_load_"
  return array_filter( array_keys( $all_fields ), function( $val ) {
    return substr( $val, 0, strlen( 'elit_load_' ) ) == 'elit_load_';
  } );
}

function elit_get_possible_dependencies( $all_fields, 
                                         $all_available_dependencies ) {

    if ( ! ( $all_fields && $all_available_dependencies ) ) return false;

    return 
      elit_array_filter_key( $all_fields, 
                        function( $value ) use ( $all_available_dependencies ) {
                          return in_array( $value, 
                                           $all_available_dependencies, 
                                           true );
                        } );
}  

function elit_get_dependencies_to_load( $possible_deps ) {
  
  if ( ! $possible_deps ) return [];

  return array_keys( array_filter( $possible_deps, function( $value ) {
      return $value == true;
  } ) );
}
 
function elit_rename_dependencies( $deps_to_load = array()) {

  // Rename jquery dependency so WP will load it
  return array_map( function( $dep ) {
    return $dep === 'elit_load_jquery' ? 'jquery' : $dep;
  }, $deps_to_load );
}

/**
 * List the JavaScript library dependencies needed based on the Advanced
 * Custom Fields fields associated with a post.
 *
 * @param array $all_fields - The result of ACF function get_fields()
 * @return array | false    - The dependencies to load
 */
function elit_get_script_dependencies_for_post( $all_fields ) {

  if ( ! $all_fields ) return false;

  $all_available_deps = elit_get_available_dependencies( $all_fields );
  $possible_deps = elit_get_possible_dependencies( $all_fields, 
                                                   $all_available_deps );
  $deps_to_load = elit_get_dependencies_to_load( $possible_deps );

  $deps = elit_rename_dependencies( $deps_to_load );

  return $deps;
}

/**
 * Load any post-specific scripts.
 * 
 * The scripts are set in the post via an Advanced Custom Fields
 * meta box.
 *
 * @return none
 * @author pjs
 */
function elit_load_scripts_for_post() {

  if ( ! is_singular() ) return;

  global $post;

  $all_fields = get_fields( $post->ID );

  if ( ! $all_fields ) return;

  if ( ! array_key_exists( 'elit_script_file', $all_fields ) ) return;

  $script = $all_fields['elit_script_file'];

  $deps = elit_get_script_dependencies_for_post( $all_fields );

  wp_enqueue_script( 
    $script['title'],
    $script['url'],
    $deps, 
    false, 
    true
  );
}
add_action( 'wp_enqueue_scripts', 'elit_load_scripts_for_post' );
