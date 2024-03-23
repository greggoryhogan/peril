<?php 
function check_peril_game_version() {
    $game_id = absint($_POST['game_id']);
    $game_version = sanitize_text_field($_POST['game_version']);
    $current_version = get_game_version($game_id);
    $needs_update = 0;
    if($current_version != $game_version) {
        $needs_update = 1;
    }
    //update_game_version($post_id)
    wp_send_json(
        array(
            'needs_update' => $needs_update,
            'game_version' => $current_version,
            'game_content' => get_game_content($game_id)
        )
    );

}
add_action("wp_ajax_check_peril_game_version", "check_peril_game_version");
add_action("wp_ajax_nopriv_check_peril_game_version", "check_peril_game_version");

function peril_login() {
    $info['user_login'] = sanitize_text_field($_POST['username']);
    $info['user_password'] = sanitize_text_field($_POST['password']);
    $info['remember'] = true;
    $game_id = absint($_POST['game_id']);
    $user_signon = wp_signon( $info, false );
    if ( !is_wp_error($user_signon) ){
        wp_set_current_user($user_signon->ID);
        wp_set_auth_cookie($user_signon->ID);
        wp_send_json( array(
            'login_success' => 1,
            'message' => 'Success! Loading your game...',
            'game_content' => get_game_content($game_id),
        ));
    } else {
        wp_send_json( array(
            'login_success' => 0,
            'message' => 'Incorrect username password combination.'
        ));
    }
}
add_action("wp_ajax_peril_login", "peril_login");
add_action("wp_ajax_nopriv_peril_login", "peril_login");

function claim_host() {
    $game_id = absint($_POST['game_id']);
    $user_id = absint($_POST['user_id']);
    $host = get_post_meta($game_id, 'game_host', true);
    if($host == '') {
        update_post_meta($game_id, 'game_host', $user_id);
        $game_version = update_game_version($game_id);
        wp_send_json(array(
            'game_content' => get_game_content($game_id),
            'game_version' => $game_version
        ));
    }
}
add_action("wp_ajax_claim_host", "claim_host");
add_action("wp_ajax_nopriv_claim_host", "claim_host");

function claim_player() {
    $game_id = absint($_POST['game_id']);
    $user_id = absint($_POST['user_id']);
    $players = get_post_meta($game_id, 'game_players');
    if(!is_array($players)) {
        $players = array();
    }
    if(!in_array($user_id, $players)) {
        add_post_meta($game_id, 'game_players', $user_id);
    }
    wp_send_json(array(
        'game_content' => get_game_content($game_id) 
    ));
}
add_action("wp_ajax_claim_player", "claim_player");
add_action("wp_ajax_nopriv_claim_player", "claim_player");


function start_game() {
    $game_id = absint($_POST['game_id']);
    update_post_meta($game_id, 'game_started', 1);
    $game_version = update_game_version($game_id);
    wp_send_json(array(
        'game_content' => get_game_content($game_id),
        'game_version' => $game_version
    ));
}
add_action("wp_ajax_start_game", "start_game");
add_action("wp_ajax_nopriv_start_game", "start_game");

function get_game() {
    $game_id = absint($_POST['game_id']);
    wp_send_json(array(
        'game_content' => get_game_content($game_id),
        'game_version' => get_game_version($game_id),
    ));
}
add_action("wp_ajax_get_game", "get_game");
add_action("wp_ajax_nopriv_get_game", "get_game");

function host_action() {
    $game_id = absint($_POST['game_id']);
    $game_action = sanitize_text_field($_POST['game_action']);
    if($game_action == 'resume_game') {
        delete_post_meta($game_id,'game_action');
    } else {
        update_post_meta($game_id,'game_action', $game_action);
    }
    $game_version = update_game_version($game_id);
    wp_send_json(array(
        'game_content' => get_game_content($game_id),
        'game_version' => $game_version
    ));
}
add_action("wp_ajax_host_action", "host_action");
add_action("wp_ajax_nopriv_host_action", "host_action");


function peril_upload_file() {
    $uploads_dir = wp_upload_dir();
    if ( isset( $_FILES['game_csv'] ) ) {
        if ( $upload = wp_upload_bits( $_FILES['game_csv']['name'], null, file_get_contents( $_FILES['game_csv']['tmp_name'] ) ) ) {
            $filename = $_FILES['game_csv']['name'];
            if (!$upload['error']) {
                $wp_filetype = wp_check_filetype($filename, null );
                $parent_post_id = 0;
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_parent' => $parent_post_id,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
                if (!is_wp_error($attachment_id)) {
                    add_post_meta($attachment_id, 'peril_csv_upload', 'true');
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
                    wp_update_attachment_metadata( $attachment_id,  $attachment_data );
                    wp_send_json( array(
                        'success' => 1,
                        'file_id' => $attachment_id,
                    )
                    );
                    wp_die();
                }
            }
        } 
    }
    wp_send_json( array(
        'success' => 0,
        'error' => 'An error occurred'
        )
    );
    wp_die();
}
add_action("wp_ajax_peril_upload_file", "peril_upload_file");
add_action("wp_ajax_nopriv_peril_upload_file", "peril_upload_file");

function create_game() {
    $game_name = sanitize_text_field($_POST['game_name']);
    $game_csv = absint($_POST['game_csv']);
    $args = array(
        'post_title' => $game_name,
        'post_type' => 'game',
        'post_status' => 'publish'
    );
    $id = wp_insert_post($args);
    if(!is_wp_error( $id )) {
        add_post_meta($id, 'game_csv', $game_csv);
        $message = 'Game created! <a href="'.get_permalink($id).'">Continue to game</a>';
        wp_send_json(array(
            'html' => $message
        ));
    }

    
}
add_action("wp_ajax_create_game", "create_game");
add_action("wp_ajax_nopriv_create_game", "create_game");