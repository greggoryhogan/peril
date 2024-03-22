<?php 
add_filter('single_template', 'my_custom_template');
function my_custom_template($single) {

    global $post;

    /* Checks for single template by post type */
    if ( $post->post_type == 'game' ) {
        if ( file_exists( PERIL_PLUGIN_PATH . '/game-single.php' ) ) {
            return PERIL_PLUGIN_PATH . '/game-single.php';
        }
    }

    return $single;

}