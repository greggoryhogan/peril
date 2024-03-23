<?php

/**
 * Get game version
 */
function get_game_version($post_id) {
    $version = get_post_meta( $post_id, 'force_refresh_current_page_version', true );
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
    delete_post_meta( $post_id, 'force_refresh_current_page_version' );
    update_post_meta( $post_id, 'force_refresh_current_page_version', $page_version );
    return $page_version;
}

/**
 * Display login link
 */
function show_login_link() {
    return '<button class="peril-button" id="peril-login">Log in to join</button>';
}

function login_modal() {
    $link = '<div class="peril-modal" id="login-modal">';
        $link .= '<div class="content">';
            $link .= '<h2 class="peril-heading">Peril Login</h2>';
            $link .= '<div class="peril-form-field">';
                $link .= '<label>Email</label>';
                $link .= '<input type="email" id="login-email" placeholder="Email" />';
            $link .= '</div>';
            $link .= '<div class="peril-form-field">';
                $link .= '<label>Password</label>';
                $link .= '<input type="password" id="login-password" placeholder="Password" />';
            $link .= '</div>';
            $link .= '<div class="peril-form-field">';
                $link .= '<button class="peril-button" id="peril-game-login">Log In</button>';
            $link .= '</div>';
            $link .= '<div id="login-response">Success!</div>';
        $link .= '</div>';
    $link .= '</div>';
    return $link;
}

/**
 * Display game content
 */
function get_game_content($post_id) {
    global $current_user;
    $started = get_post_meta($post_id, 'game_started', true);
    $host = get_post_meta($post_id, 'game_host', true);
    $players = get_post_meta($post_id, 'game_players');
    if(!is_array($players)) {
        $player = array();
    }
    $content = '';
    if($started == '') {
        //echo '<div class="notice shadow">Buzzers ready, we&rsquo;re waiting for the game to start!</div>';
        $content .= '<div class="question-text">The game has not started</div>';
        
        if($current_user->ID > 0) {
            if(in_array($current_user->ID, $players)) {
                $content .= '<p class="peril-white">Buzzers ready!</p>';
            } else {
                if($host == '') {
                    $content .= '<button class="peril-button" id="claim-host">Join as host</button>';
                    $content .= '<button class="peril-button" id="claim-player">Join as a Competitor</button>';
                } else {
                    if($host == $current_user->ID) {
                        $content .= '<div class="notice shadow">When all players are ready, start the game!</div>';
                        $content .= '<button class="peril-button" id="start_game">Start the game</button>';
                    } else {
                        $content .= '<button class="peril-button" id="claim-player">Join as a Competitor</button>';
                    }
                }
            }
            
            //$content .= '<p class="peril-white">If you&rsquo;re a competitor, please wait for the host start game.</p>';
        } else {
            if(!is_audience_member()) {
                $content .= show_login_link();
            }
        }
        
        if($current_user->ID == 0 && !is_audience_member()) {
            $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
        }
    } else {
        $content = show_started_game($post_id);
    }
    return $content;
}

function show_started_game($post_id) {
    global $current_user;
    $content = '';
    if($current_user->ID > 0) {
        $host = get_post_meta($post_id, 'game_host', true);
        $players = get_post_meta($post_id, 'game_players');
        if($host == $current_user->ID) {
            $content .= '<p class="peril-white">You are the host, this is host content</p>';
        } else if(in_array($current_user->ID, $players)) {
            $content .= '<p class="peril-white">You are a competitor, this is competitor content</p>';
        } else {
            if(!is_audience_member()) {
                $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
            }
        }
    } 
    if(is_audience_member()) {
        $content .= '<p class="peril-white">You are an audience member, this is audience content.</p>';
    }
    
    return $content;
}

function is_audience_member() {
    if(isset($_COOKIE['peril_audience_member'])) {
        return true;
    }
    return false;
}