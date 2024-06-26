<?php

/**
 * Get game version
 */
function get_game_version($post_id) {
    $version = get_post_meta( $post_id, 'peril_last_updated', true );
    if($version == '') {
        $version = update_game_version($post_id);
    }
    return $version;
}

/**
 * Update version of game
 */
function update_game_version($post_id) {
    $time = current_time('timestamp');
    $page_version_hash = wp_hash($time);
    $page_version = substr($page_version_hash, 0, 8);
    delete_post_meta( $post_id, 'peril_last_updated' );
    update_post_meta( $post_id, 'peril_last_updated', $page_version );
    return $page_version;
}

function get_player_scores($players, $game_id, $class = '') {
    $uuid = get_peril_uuid();
    $scores = '<div class="player-scores '.$class.'">';
    foreach($players as $player) {
        $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
        if($score == '') {
            $score = 0;
        }
        $username = get_post_meta($game_id, "peril_player_name_$player", true);
        if($username == '') {
            $username = $player;
        }
        $myself = '';
        if($player == $uuid) {
            $myself = 'current_player';
        }
        $scores .= '<div class="score '.$myself.'"><div class="name">'.$username.'</div><div class="currency">';
        if($score >= 0) {
            $scores .= '$'.number_format($score, 0);
        } else {
            $score = $score * -1;
            $scores .= '- $'.number_format($score, 0);
        }
        $scores .= '</div></div>';
    }
    $scores .= '</div>';
    return $scores;
}

function is_audience_member($game_id) {
    if(isset($_COOKIE["peril_audience_member_$game_id"])) {
        return true;
    }
    return false;
}

function get_player_type($post_id) {
    global $current_user;
    $players = get_post_meta($post_id, 'peril_game_players');
    if(!is_array($players)) {
        $players = array();
    }
    $player_type = '';
    $peril_uuid = get_peril_uuid();
    if($peril_uuid != 0) {
        $host = get_post_meta($post_id, 'peril_game_host', true);
        if($peril_uuid == $host) {
            $player_type = 'host';
        } else if(in_array($peril_uuid, $players)) {
            $player_type = 'contestant';
        } else {
            $player_type = 'audience_member';
        }
    } else if(is_audience_member($post_id)) {
        $player_type = 'audience_member';
    }
    return $player_type;
}

function create_default_uuid() {
    $time = current_time('timestamp');
    $hash = wp_hash($time);
    return $hash;
}

function create_questions_from_csv($attachment_id) {
    //$data = wp_get_attachment_metadata(get_the_ID());
    //$form .= basename( get_attached_file(get_the_ID()));
}