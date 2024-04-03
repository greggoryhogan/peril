<?php 
function check_peril_game_version() {
    $game_id = absint($_POST['game_id']);
    $game_version = sanitize_text_field($_POST['game_version']);
    $current_version = get_game_version($game_id);
    $needs_update = 0;
    if($current_version != $game_version) {
        $needs_update = 1;
        $player_audio_for_type = get_post_meta($game_id,'peril_player_audio_for_type', true);
        $player_audio_for_player = get_post_meta($game_id, 'peril_player_audio_for_player', true);
        $audio_file = get_post_meta($game_id, 'peril_current_audio', true);
        wp_send_json(
            array(
                'needs_update' => $needs_update,
                'game_version' => $current_version,
                'game_content' => get_game_content($game_id),
                'player_audio_for_type' => $player_audio_for_type,
                'player_audio_for_player' => $player_audio_for_player,
                'audio_file' => $audio_file
            )
        );
    } else {
        wp_send_json(
            array(
                'needs_update' => $needs_update,
                'game_version' => $current_version,
                'game_content' => get_game_content($game_id)
            )
        );
    }
    //update_game_version($post_id)

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
    $user_id = sanitize_text_field($_POST['user_id']);
    $host = get_post_meta($game_id, 'peril_game_host', true);
    if($host == '') {
        update_post_meta($game_id, 'peril_game_host', $user_id);
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
    $user_id = sanitize_text_field($_POST['user_id']);
    $players = get_post_meta($game_id, 'peril_game_players');
    if(!is_array($players)) {
        $players = array();
    }
    if(!in_array($user_id, $players)) {
        add_post_meta($game_id, 'peril_game_players', $user_id);
        $count = count($players) + 1;
        add_post_meta($game_id, "peril_player_name_$user_id", 'Player '.$count);
    }
    wp_send_json(array(
        'game_content' => get_game_content($game_id) 
    ));
}
add_action("wp_ajax_claim_player", "claim_player");
add_action("wp_ajax_nopriv_claim_player", "claim_player");

function update_player_name() {
    $game_id = absint($_POST['game_id']);
    $user_id = sanitize_text_field($_POST['user_id']);
    $name = sanitize_text_field($_POST['name']);
    update_post_meta($game_id, "peril_player_name_$user_id", $name);
    wp_send_json(array(
        'game_content' => get_game_content($game_id) 
    ));
}
add_action("wp_ajax_update_player_name", "update_player_name");
add_action("wp_ajax_nopriv_update_player_name", "update_player_name");

function start_game() {
    $game_id = absint($_POST['game_id']);
    update_post_meta($game_id, 'peril_game_started', 1);
    update_post_meta($game_id, 'peril_game_round', 1);
    $players = get_post_meta($game_id, 'peril_game_players');
    update_post_meta($game_id, 'peril_last_player', $players[0]);
    $game_version = update_game_version($game_id);
    //update_post_meta($game_id,'peril_game_action', 'starting_game');
    //sleep(5);
    //delete_post_meta($game_id,'peril_game_action');
    update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
    update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
    update_post_meta($game_id, 'peril_current_audio', 'peril-intro.mp3');
    update_post_meta($game_id,'peril_game_action', 'game_intro');
    sleep(15);
    update_post_meta($game_id,'peril_player_audio_for_type', 'none');
    //delete_post_meta($game_id,'peril_game_action');
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
        $previous_action = get_post_meta($game_id, 'peril_previous_action', true);
        $ignore_actions = array(
            'show_scores',
            'show_category_1',
            'show_category_2',
            'show_category_3',
            'show_category_4',
            'show_category_5'
        );
        if($previous_action != '' && $previous_action != 'show_scores') {
            update_post_meta($game_id,'peril_game_action', $previous_action);
            delete_post_meta($game_id,'peril_previous_action');
        } else {
            delete_post_meta($game_id,'peril_game_action');
        }
    } else {
        $previous_action = get_post_meta($game_id, 'peril_game_action', true);
        update_post_meta($game_id,'peril_previous_action', $previous_action);
        update_post_meta($game_id,'peril_game_action', $game_action);
        if($game_action == 'show_clue') {
            $category = sanitize_text_field( $_POST['category'] );
            $value = absint( $_POST['value'] );
            update_post_meta($game_id, 'peril_current_category', $category);
            update_post_meta($game_id, 'peril_current_value', $value);
            $concat = "$category:$value";
            //get double jeopardy question(s)
            $current_round = get_game_round($game_id);
            $dbl_jeopardy = get_post_meta($game_id, "round_{$current_round}_double_jeopardy");
            if(in_array($concat, $dbl_jeopardy)) {
                update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
                update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
                update_post_meta($game_id, 'peril_current_audio', 'daily-double.mp3');
            }
        } else if($game_action == 'show_scores') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');  
        } else if($game_action == 'display_game_board') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', 'jeopardy-board.mp3');
        } else if($game_action == 'no-response') {
            $peril_current_value = get_post_meta($game_id, 'peril_current_value', true);
            $peril_current_category = get_post_meta($game_id, 'peril_current_category', true);
            delete_post_meta( $game_id, 'peril_current_value');
            delete_post_meta( $game_id, 'peril_current_category');
            delete_post_meta( $game_id, 'peril_game_action');
            delete_post_meta($game_id, 'peril_players_buzzed');
            $used_answers = get_post_meta($game_id, 'peril_game_used_answers', true);
            if($used_answers == '') {
                $used_answers = array();
            }
            $used_answers = maybe_unserialize($used_answers);
            $used_answers[$peril_current_category][] = $peril_current_value;
            update_post_meta($game_id,  'peril_game_used_answers', $used_answers);
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
        } else if($game_action == 'daily_double') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', '');
        } else if($game_action == 'show_category_1') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
        } else if($game_action == 'show_category_2') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
        } else if($game_action == 'show_category_3') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
        } else if($game_action == 'show_category_4') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
        } else if($game_action == 'show_category_5') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
        } else if($game_action == 'goto_round_1') {
            update_post_meta($game_id, 'peril_game_round', '1');
            delete_post_meta($game_id,'peril_game_action');
            update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', 'jeopardy-board.mp3');
        } else if($game_action == 'goto_round_2') {
            //set lowest score to start round
            $players = get_post_meta($game_id, 'peril_game_players');
            $min_score = 9999999;
            foreach($players as $player) {
                $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
                if($score <= $min_score) {
                    $min_score = $score;
                }
            }
            $winners = array();
            foreach($players as $player) {
                $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
                if($score <= $min_score) {
                    $winners[] = $player;
                }
            }
            update_post_meta($game_id, 'peril_last_player', $winners[0]);
            update_post_meta($game_id, 'peril_game_round', '2');
            delete_post_meta($game_id,'peril_game_action');
            update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', 'jeopardy-board.mp3');
        } else if($game_action == 'goto_round_3') {
            delete_post_meta($game_id,'peril_game_action');
            update_post_meta($game_id, 'peril_game_round', '3');
            delete_post_meta($game_id,'peril_game_action');
            //make ding
            update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', 'ding.mp3');

        } else if($game_action == 'show_final_jeopardy_clue') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', 'ding.mp3');
        } else if($game_action == 'show_final_jeopardy') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
            update_post_meta($game_id, 'peril_current_audio', 'thinking-music.mp3');
        }
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
        add_post_meta($id, 'peril_game_csv', $game_csv);
        $csv_title = get_post_meta($game_csv, 'peril_csv_game_title', true);
        if($csv_title == '') {
            add_post_meta($game_csv, 'peril_csv_game_title', $game_name);
        }
        $message = 'Game created! <a href="'.get_permalink($id).'">Continue to game</a>';
        wp_send_json(array(
            'html' => $message
        ));
    }

    
}
add_action("wp_ajax_create_game", "create_game");
add_action("wp_ajax_nopriv_create_game", "create_game");

function player_buzz() {
    $game_id = absint($_POST['game_id']);
    $user_id = sanitize_text_field($_POST['user_id']);
    $peril_current_category = get_post_meta($game_id, 'peril_current_category', true);
    if($peril_current_category != '') {
        $currently_answering = get_post_meta($game_id, 'peril_player_answering', true);
        $already_buzzed = get_post_meta($game_id, 'peril_players_buzzed', true);
        if(!is_array($already_buzzed)) {
            $already_buzzed = array();
        }
        if(in_array($user_id, $already_buzzed)) {
            //nothing, they got a question wrong
        } else if($currently_answering == '') {
            update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            update_post_meta($game_id, 'peril_player_audio_for_player', $user_id);
            update_post_meta($game_id, 'peril_current_audio', 'ding.mp3');
            update_post_meta( $game_id, 'peril_player_answering', $user_id);
            $already_buzzed[] = $user_id;
            update_post_meta($game_id, 'peril_players_buzzed', $already_buzzed);
            $game_version = update_game_version($game_id);
        } 
    } else {
        $game_version = get_game_version($game_id);
    }
    wp_send_json(
        array(
            'game_version' => $game_version,
            'game_content' => get_game_content($game_id),
        )
    );
}
add_action("wp_ajax_player_buzz", "player_buzz");
add_action("wp_ajax_nopriv_player_buzz", "player_buzz");

function player_response() {
    $game_id = absint($_POST['game_id']);
    $player_response = sanitize_text_field($_POST['player_response']);

    $action = get_post_meta($game_id, 'peril_game_action', true);
    $peril_current_value = get_post_meta($game_id, 'peril_current_value', true);
    $peril_current_category = get_post_meta($game_id, 'peril_current_category', true);
    if($action == 'daily_double') {
        //daily double
        $currently_answering = get_post_meta($game_id, 'peril_last_player', true);
        $wager = absint(get_post_meta($game_id, 'peril_dd_wager', true));
        $score = get_post_meta($game_id,"peril_player_{$currently_answering}_score", true);
        if($score == '') {
            $score = 0;
        }
        if($player_response == 'correct') {
            //score them and on to the next question
            $score = intval($score) + $wager;
        } else {
            //subtract points
            $score = intval($score) - $wager;
        }

        //update their score
        update_post_meta($game_id,"peril_player_{$currently_answering}_score", $score);
        delete_post_meta($game_id, 'peril_dd_wager');
        delete_post_meta( $game_id, 'peril_current_value');
        delete_post_meta( $game_id, 'peril_current_category');
        delete_post_meta( $game_id, 'peril_game_action');
        delete_post_meta($game_id, 'peril_players_buzzed');
        $used_answers = get_post_meta($game_id, 'peril_game_used_answers', true);
        if($used_answers == '') {
            $used_answers = array();
        }
        $used_answers = maybe_unserialize($used_answers);
        $used_answers[$peril_current_category][] = $peril_current_value;
        update_post_meta($game_id,  'peril_game_used_answers', $used_answers);

       
        
        delete_post_meta( $game_id, 'peril_player_answering');

    } else {
        $currently_answering = get_post_meta($game_id, 'peril_player_answering', true);
        //normal clue
        $score = get_post_meta($game_id,"peril_player_{$currently_answering}_score", true);
        if($score == '') {
            $score = 0;
        }
        if($player_response == 'correct') {
            //score them and on to the next question
            $score = intval($score) + absint($peril_current_value);
            $used_answers = get_post_meta($game_id, 'peril_game_used_answers', true);
            if($used_answers == '') {
                $used_answers = array();
            }
            $used_answers = maybe_unserialize($used_answers);
            $used_answers[$peril_current_category][] = $peril_current_value;
            update_post_meta($game_id,  'peril_game_used_answers', $used_answers);
            delete_post_meta( $game_id, 'peril_current_value');
            delete_post_meta( $game_id, 'peril_current_category');
            delete_post_meta( $game_id, 'peril_game_action');
            delete_post_meta($game_id, 'peril_players_buzzed');
            update_post_meta($game_id, 'peril_last_player', $currently_answering);
        } else {
            //subtract points
            $score = intval($score) - absint($peril_current_value);
        }
        //update their score
        update_post_meta($game_id,"peril_player_{$currently_answering}_score", $score);
        //remove player answering and log last player for dbl jeopardy
        delete_post_meta( $game_id, 'peril_player_answering');
    }

    update_post_meta($game_id,'peril_player_audio_for_type', 'none');
    update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
    update_post_meta($game_id, 'peril_current_audio', '');
    
    $game_version = update_game_version($game_id);
    wp_send_json(
        array(
            'game_version' => $game_version,
            'game_content' => get_game_content($game_id),
        )
    );

}
add_action("wp_ajax_player_response", "player_response");
add_action("wp_ajax_nopriv_player_response", "player_response");

function set_daily_double() {
    $game_id = absint($_POST['game_id']);
    $wager = absint($_POST['wager']);
    update_post_meta($game_id,'peril_game_action', 'daily_double');
    update_post_meta($game_id, 'peril_dd_wager', $wager);
    update_post_meta($game_id,'peril_player_audio_for_type', 'none');
    update_post_meta($game_id, 'peril_player_audio_for_player', 'none');
    $game_version = update_game_version($game_id);
    wp_send_json(
        array(
            'game_version' => $game_version,
            'game_content' => get_game_content($game_id),
        )
    );

}
add_action("wp_ajax_set_daily_double", "set_daily_double");
add_action("wp_ajax_nopriv_set_daily_double", "set_daily_double");

function update_player_score() {
    $game_id = absint($_POST['game_id']);
    $player = sanitize_text_field($_POST['player']);
    $score = absint($_POST['score']);
    update_post_meta($game_id, "peril_player_{$player}_score", $score);
    $game_version = update_game_version($game_id);
    wp_send_json(
        array(
            'game_version' => $game_version,
            'game_content' => get_game_content($game_id),
        )
    );
}
add_action("wp_ajax_update_player_score", "update_player_score");
add_action("wp_ajax_nopriv_update_player_score", "update_player_score");

function check_player_wagers() {
    $game_id = absint($_POST['game_id']);
    $players = get_post_meta($game_id, 'peril_game_players');
    $wagers = '';
    foreach($players as $player) {
        $wager = get_post_meta($game_id,"peril_player_{$player}_wager", true);
        if ($wager == '') {
            $wager = 'Not set';
        }
        $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
        if($score == '') {
            $score = 0;
        }
        $username = get_post_meta($game_id, "peril_player_name_$player", true);
        $wagers .= '<div><div>'.$username.'</div><div>Score: '.$score.'</div><div>Wager: '.$wager.'</div></div>';
    }
    wp_send_json(
        array(
            'wagers' => $wagers
        )
    );
}
add_action("wp_ajax_check_player_wagers", "check_player_wagers");
add_action("wp_ajax_nopriv_check_player_wagers", "check_player_wagers");

function set_final_wager() {
    $game_id = absint($_POST['game_id']);
    $player = sanitize_text_field($_POST['player']);
    $wager = absint($_POST['wager']);
    $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
    if($wager > $score) {
        $wager = $score;
    }
    update_post_meta($game_id, "peril_player_{$player}_wager", $wager);
    $current_version = get_game_version($game_id);
    wp_send_json(
        array(
            'game_version' => $current_version,
            'game_content' => get_game_content($game_id)
        )
    );
}
add_action("wp_ajax_set_final_wager", "set_final_wager");
add_action("wp_ajax_nopriv_set_final_wager", "set_final_wager");

function set_final_guess() {
    $game_id = absint($_POST['game_id']);
    $player = sanitize_text_field($_POST['player']);
    $guess = sanitize_text_field($_POST['guess']);
    update_post_meta($game_id, "peril_player_{$player}_final_guess", $guess);
    $current_version = get_game_version($game_id);
    wp_send_json(
        array(
            'game_version' => $current_version,
        )
    );
}
add_action("wp_ajax_set_final_guess", "set_final_guess");
add_action("wp_ajax_nopriv_set_final_guess", "set_final_guess");

function final_action() {
    $game_id = absint($_POST['game_id']);
    $player = sanitize_text_field($_POST['player']);
    $action = sanitize_text_field($_POST['final_action']);
    update_post_meta($game_id,'peril_game_action', $action);
    update_post_meta($game_id,'peril_final_player_displaying',$player);
    if($action == 'player_final_is_correct') {
        $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
        $wager = get_post_meta($game_id, "peril_player_{$player}_wager", true);
        $final_score = absint($score) + absint($wager);
        update_post_meta($game_id,"peril_player_{$player}_score", $final_score);
        update_post_meta($game_id,"peril_player_{$player}_done", 1);
    } else if($action == 'player_final_is_incorrect') {
        $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
        $wager = get_post_meta($game_id, "peril_player_{$player}_wager", true);
        $final_score = absint($score) - absint($wager);
        update_post_meta($game_id,"peril_player_{$player}_score", $final_score);
        update_post_meta($game_id,"peril_player_{$player}_done", 1);
    }
    $game_version = update_game_version($game_id);
    wp_send_json(
        array(
            'game_version' => $game_version,
            'game_content' => get_game_content($game_id)
        )
    );
}
add_action("wp_ajax_final_action", "final_action");
add_action("wp_ajax_nopriv_final_action", "final_action");
