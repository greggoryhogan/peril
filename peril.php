<?php
/*
Plugin Name:  Peril
Plugin URI:	  https://fragmentlms.com
Description:  Learning management software built for developers, by developers
Version:	  1.1.0
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

include_once dirname( PERIL_PLUGIN_FILE ) . '/includes/settings.php';
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
	$content = get_the_content('', true, $post);
	if( !function_exists('get_plugin_data') ){
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$plugin_data = get_plugin_data( PERIL_PLUGIN_FILE );
	$version = $plugin_data['Version'];
	if($post_type == 'game' || has_shortcode( $content, 'peril_create_game')) {
		wp_register_style( 'peril', PERIL_PLUGIN_URL . 'assets/css/peril.css', false, $version );
		wp_enqueue_style('peril');
	}
	if($post_type == 'game') {
		add_filter ('show_admin_bar', '__return_false');
		wp_enqueue_style('adobe-fonts', 'https://use.typekit.net/tjd7smh.css', false, $version);
		wp_enqueue_script('js-cookie', 'https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js', array(), '1.0', true);
		wp_enqueue_script('peril', PERIL_PLUGIN_URL . 'assets/js/peril.js', array('jquery', 'js-cookie'), $version, true);
		global $current_user;
		$game_timer = get_option('peril_update_frequency');
		$requires_login = get_option('peril_gameplay_requires_login');
		$default_uuid = create_default_uuid();
		$game_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'game_timer' => $game_timer,
			'requires_login' => $requires_login,
			'default_uuid' => $default_uuid,
			'user_id' => get_peril_uuid(),
			'game_id' => $post->ID,
			'game_version' => get_game_version($post->ID),
			'player_type' => get_player_type($post->ID),
		);
		wp_localize_script( 'peril', 'peril', $game_data);
	}
	if(has_shortcode( $content, 'peril_create_game' )) {
		wp_enqueue_script('peril-game-creator', PERIL_PLUGIN_URL . 'assets/js/peril-game-creator.js', array('jquery',), $version, true);
		$game_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'user_id' => get_peril_uuid(),
		);
		wp_localize_script( 'peril-game-creator', 'peril_game_creator', $game_data);
	}
	
}