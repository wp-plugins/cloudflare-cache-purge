<?php

/*
Plugin Name:  CloudFlare(R) Cache Purge
Description:  API Integration with CloudFlare to purge your cache
Version:      1.0.5
Author:       Bryan Shanaver @ fiftyandfifty.org
Author URI:   https://www.fiftyandfifty.org/
Contributors: shanaver

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
Neither the name of Alex Moss or pleer nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

define('CCPURGE_VERSION', '1.0.5');

define('CCPURGE_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('CCPURGE_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define('CCPURGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once( CCPURGE_PLUGIN_PATH . '/lib/ccpurge.class.php');
require_once( CCPURGE_PLUGIN_PATH . '/lib/ccpurge_posttypes.php');


/*
	Admin styles & scripts
*/

function ccpurge_admin_scripts_styles(){
	wp_register_script( 'ccpurge-scripts', CCPURGE_PLUGIN_URL . 'lib/ccpurge.js' ) ;
	wp_register_style( 'ccpurge-style', CCPURGE_PLUGIN_URL . 'lib/ccpurge.css' );

	wp_enqueue_script( 'ccpurge-scripts' );
	wp_enqueue_style( 'ccpurge-style' );
}
add_action('admin_init', 'ccpurge_admin_scripts_styles');


/*
	Menu Page
*/

function ccpurge_add_menu_page(){
	function ccpurge_menu_page(){
		$options_page_url = CCPURGE_PLUGIN_PATH . '/lib/ccpurge_options.php';
		if(file_exists($options_page_url)){
			include_once($options_page_url);
		}
	};
	add_submenu_page( 'options-general.php', 'Cloudflare Purge', 'Cloudflare Purge', 'switch_themes', 'ccpurge', 'ccpurge_menu_page' );
};
add_action( 'admin_menu', 'ccpurge_add_menu_page' );

function ccpurge_plugin_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=ccpurge">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}
add_filter("plugin_action_links_" . CCPURGE_PLUGIN_BASENAME, 'ccpurge_plugin_settings_link' );


/*
	Transaction Logging
*/

function ccpurge_transaction_logging($message='empty', $status='success') {
	global $wpdb;
	if( isset($_REQUEST['message']) ){ $message = $_REQUEST['message']; }
	if( isset($_REQUEST['status']) ){ $status = $_REQUEST['status']; }
	if($status == 'print_debug'){
		print $message;
	}
	else{
		$total = wp_count_posts( 'ccpurge_log_entries' );
		$log_entry = array(
		  'post_title' => (strtoupper($status) . ' : ' . strtolower(substr($message, 0, 150))),
		  'post_content' => $message,
		  'post_status' => 'publish',
		  'post_name' => ('ccpurge-log-' . ($total->publish + 1)),
		  'post_type' => 'ccpurge_log_entries',
		);
		wp_insert_post( $log_entry );
	}
}
add_action( 'wp_ajax_ccpurge_transaction_logging', 'ccpurge_transaction_logging' );

function ccpurge_get_table_logging($verify=false){
	$limit = "30";
	$d_page = isset($_REQUEST['d_page']) ? $_REQUEST['d_page'] : 0;

	$args = array( 'post_type' => 'ccpurge_log_entries', 'orderby' => 'ID', 'order' => 'DESC', 'paged' => $d_page, 'posts_per_page' => $limit );
	$log_entries = new WP_Query( $args );

	if( $verify && !$log_entries->have_posts() ){
		return false;
	}

	print "<h3>CloudFlare Cache Purge Logging</h3>";
	print "<table>";
	print "<tr><th>ID</th><th>Time</th><th>Message</th></tr>";
	while ( $log_entries->have_posts() ) {
		global $post;
		$log_entries->the_post();
		print "<tr class='{$post->post_title}'><td>" . str_replace ( 'ccpurge-log-' , '' , $post->post_name) . "</td><td>" . $post->post_date . "</td><td>" . $post->post_content . "</td></tr>";
	}
	print "</table>";
	print "<input id='ccpurge-prev' onclick='ccpurge.refreshLog(".($d_page - 1).");' type=button value='Previous {$limit}'/>";
	print "<input id='ccpurge-next' onclick='ccpurge.refreshLog(".($d_page + 1).");' type=button value='Next {$limit}'/>";
	die();
}
add_action( 'wp_ajax_ccpurge_get_table_logging', 'ccpurge_get_table_logging' );

function ccpurge_activate() {
	ccpurge_transaction_logging("CloudFlare Cache Purge - Activated");
}
register_activation_hook(__FILE__,'ccpurge_activate');

function ccpurge_deactivate(){
	ccpurge_transaction_logging('CloudFlare Cache Purge - *Deactivated*');
}
register_deactivation_hook(__FILE__,'ccpurge_deactivate');


/**
*
* Save post hook
* Multisite domain mapping support added by Ed Cooper
*
**/
function ccpurge_purge_after_save_post_hook( $post_id ){
  // possible options...
  // add_action('pending_to_publish', 'ccpurge_purge');
  // add_action('draft_to_publish', 'ccpurge_purge');
  // add_action('new_to_publish', 'ccpurge_purge');

  global $hook_running;

  remove_action('publish_post', 'ccpurge_purge_after_save_post_hook');

  if($hook_running)
    return;

  $hook_running = true;

  if( defined('DOING_SAVE') && DOING_SAVE || !$post_id )
    return;

  if ( !in_array(get_post_type( $post_id ), array('post', 'page', 'partners')) ) //|| wp_is_post_revision( $post_id ) )
    return;

  $ccpurge = new CCPURGE_API;
  if( $ccpurge->ccpurge_options['auto_purge'] ){
      $permalink = get_permalink( $post_id );

      if( is_multisite() ) :
        if( function_exists('domain_mapping_post_content') ) :
          global $wpdb;
          $orig_url = str_replace( "https", "http", get_original_url( 'siteurl' ) );
          $url = str_replace( "https", "http", domain_mapping_siteurl( 'NA' ) );
          if ( $url == 'NA' )
            return $permalink;
          $permalink = str_replace( $orig_url, $url, $permalink );
        endif;
      endif;

      $ccpurge->ccpurge_suppress_debug = true;
      $ccpurge->purge_url_after_post_save( $permalink );
      if( in_array(get_post_type( $post_id ), array('post')) ){

        $siteurl = site_url();
        if( is_multisite() ) :
          if( function_exists('domain_mapping_siteurl') ) :
            $siteurl = domain_mapping_siteurl( get_current_blog_id() );
          endif;
        endif;
        $ccpurge->purge_url_after_post_save( $siteurl );

      }
  }
  add_action('publish_post', 'ccpurge_purge_after_save_post_hook');
}
add_action('publish_post', 'ccpurge_purge_after_save_post_hook');


/*
	Purge AJAX Calls
*/

function ccpurge_entire_cache(){
	$ccpurge = new CCPURGE_API;
	$ccpurge->purge_entire_cache();
}
add_action( 'wp_ajax_ccpurge_entire_cache', 'ccpurge_entire_cache' );

function ccpurge_purge_url(){
	$ccpurge = new CCPURGE_API;
	$ccpurge->purge_url($_REQUEST['url']);
}
add_action( 'wp_ajax_ccpurge_purge_url', 'ccpurge_purge_url' );



