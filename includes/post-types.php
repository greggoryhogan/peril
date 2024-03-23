<?php 
function peril_post_types() {
	$plural_name = 'Games of Peril';
	$singular_name = 'Game of Peril';
	$plural_lowercase = 'games';
	$labels = array(
		"name" => __( "$plural_name", "" ),
		"singular_name" => __( "$singular_name", "flms" ),
		'all_items' => __( "$plural_name", "flms" ),
		'edit_item' => __( "Edit $singular_name", "flms" ),
		'update_item' => __( "Update $singular_name", "flms" ),
		'add_new' => __( "Add New", "flms" ),
		'add_new_item' => __( "Add New $singular_name", "flms" ),
		'new_item_name' => __( "New $singular_name", "flms" ),
		'menu_name' => __( "$plural_name", "flms" ),
		'back_to_items' => __( "&laquo; All $plural_name", "flms" ),
		'not_found' => __( "No $plural_lowercase found.", "flms" ),
		'not_found_in_trash' => __( "No $plural_lowercase found in trash.", "flms" ),
	);
	$args = array(
		"label" => __( "$plural_name", "flms" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"has_archive" => false,
		"show_in_menu" => true,
		"exclude_from_search" => true,
		"capability_type" => "page",
		"query_var" => true,
		'hierarchical' => true,
		'show_in_rest'      => false,
		'supports' => array('title','custom-fields'),
		"rewrite" => array( "slug" => 'game', ), //"with_front" => false 
		//"taxonomies" => array( "supplier" ),
	);

	register_post_type('game', $args);

}
add_action('init', 'peril_post_types');
