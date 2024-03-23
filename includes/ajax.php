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
    add_post_meta($game_id, 'game_players', $user_id);
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