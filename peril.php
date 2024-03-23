<?php
/*
Plugin Name:  Peril
Plugin URI:	  https://fragmentlms.com
Description:  Learning management software built for developers, by developers
Version:	  1.1.8
Author:		  Fragment
Author URI:   https://fragmentwebworks.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  flms
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PERIL_PLUGIN_FILE', __FILE__ );
define( 'PERIL_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'PERIL_PLUGIN_URL', plugin_dir_url( PERIL_PLUGIN_FILE ));

include_once dirname( PERIL_PLUGIN_FILE ) . '/includes/post-types.php';
include_once dirname( PERIL_PLUGIN_FILE ) . '/includes/display.php';
include_once dirname( PERIL_PLUGIN_FILE ) . '/includes/global.php';
include_once dirname( PERIL_PLUGIN_FILE ) . '/includes/ajax.php';

add_action('wp', 'peril_scripts');
function peril_scripts() {
	global $post, $wp;
	if(!isset($post)) {
		return;
	}
	$post_type = get_post_type($post);
	if($post_type == 'game') {
		if( !function_exists('get_plugin_data') ){
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugin_data = get_plugin_data( PERIL_PLUGIN_FILE );
		$version = $plugin_data['Version'];
		wp_register_style( 'peril', PERIL_PLUGIN_URL . 'assets/css/peril.css', false, $version );
		wp_enqueue_style('peril');

		wp_enqueue_style('adobe-fonts', 'https://use.typekit.net/tjd7smh.css', false, $version);
		wp_enqueue_script('js-cookie', 'https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js', array(), '1.0', true);
		wp_enqueue_script('peril', PERIL_PLUGIN_URL . 'assets/js/peril.js', array('jquery', 'js-cookie'), $version, true);
		global $current_user;
		$game_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'user_id' => $current_user->ID,
			'game_id' => $post->ID,
			'game_version' => get_game_version($post->ID),
		);
		wp_localize_script( 'peril', 'peril', $game_data);
		
	}
}