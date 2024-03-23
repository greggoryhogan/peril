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
        $players = array();
    }
    $content = '';
    if($started == '') {
        //echo '<div class="notice shadow">Buzzers ready, we&rsquo;re waiting for the game to start!</div>';
        $content .= '<div class="question-text">The game has not started</div>';
        
        if($current_user->ID > 0) {
            if(in_array($current_user->ID, $players)) {
                $content .= '<p class="peril-white">You&rsquo;re in the game. Get your buzzer ready!';
                $content .= '<span>When you have the question for an answer, tap your device to buzz in. You will have 5 seconds to respond.<br>Don&rsquo;t forget your phrasing!</span></p>';
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
    $found_player = false;
    $players = get_post_meta($post_id, 'game_players');
    $current_action = get_post_meta($post_id, 'game_action', true);
    if(!is_array($players)) {
        $players = array();
    }
    
    if($current_user->ID > 0) {
        $host = get_post_meta($post_id, 'game_host', true);
        
        if($host == $current_user->ID) {
            $toggle_active = '';
            $actions_active = 'inactive';
            if($current_action != '') {
                $toggle_active = ' is-active';
                $actions_active = '';
            }
            $found_player = true;
            $content .= get_screen_content($post_id, $current_action, 'host');
            $content .= '<div class="show-host-toggle '.$toggle_active .'">Game Options</div>';
            $content .= '<div class="host-actions '.$actions_active.'">';
                $actions = array(
                    'show_scores' => 'Show Scores'
                );
                if($current_action != '') {
                    $content .= '<div class="host-action" data-action="resume_game">Resume Game</div>';    
                }
                foreach($actions as $k => $v) {
                    $content .= '<div class="host-action';
                    if($k == $current_action) {
                        $content .= ' inactive';
                    }
                    $content .= '" data-action="'.$k.'">'.$v.'</div>';
                }
                
                //$content .= '<div>'. get_player_scores($players, $post_id, 'host').'</div>';
            $content .= '</div>';
            //show scores
            
        } else if(in_array($current_user->ID, $players)) {
            $found_player = true;
            $content .= get_screen_content($post_id, $current_action, 'contestant');
            //show scores
            $content .= '<div class="show-score-toggle contestant">Show scores</div>';
            $content .= get_player_scores($players, $post_id, 'public inactive');
        } else {
            if(!is_audience_member()) {
                $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
            }
        }
    } 
    if(!$found_player) {
        if(is_audience_member()) {
            $content .= get_screen_content($post_id, $current_action, 'audience_member');
            //show scores
            $content .= get_player_scores($players, $post_id, 'public inactive');
        } else {
            $content .= '<p>A game is in progress.</p>';
            $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
        }
    }
    
    return $content;
}

function get_screen_content($game_id, $current_action, $player_type) {
    $content = '';
    if($current_action != '') {
        $content .= '<div class="game-action '.$player_type.'">';
        switch($current_action) {
            case 'show_scores':
                $players = get_post_meta($game_id, 'game_players');
                if(!is_array($players)) {
                    $players = array();
                }
                $content .= get_player_scores($players, $game_id, 'score-recap');
                break;
        }
        $content .= '</div>';
        return $content;
    } else {
        switch($player_type) {
            case 'host':
                $content = '<p class="peril-white">You are the host</p>';
                break;
            case 'contestant': 
                $content = '<p class="peril-white">You are a competitor, this is competitor content</p>';
                break;
            case 'audience_member': 
                $content = '<p class="peril-white">You are an audience member, this is audience content.</p>';
                break;
        }
    }
    return $content;
}

add_shortcode('peril_create_game', 'peril_create_game_callback');
function peril_create_game_callback() {
    global $current_user, $post;
    if($current_user->ID == 0) {
        return sprintf('You must be logged in to create a game. <a href="%s" title="Log in">Log In</a>', wp_login_url(get_permalink($post->ID)));
    }
    $form = '<div id="peril-game-creator">';
        $form .= '<form>';
        $form .= '<div class="peril-form-field">';
            $form .= '<label for="game-name">Game name</label>';
            $form .= '<input type="text" name="game-name" id="game-name" placeholder="Game name" />';
        $form .= '</div>';
        /*$args = array(
            'post_type'   => 'attachment',
            'posts_per_page' => 5, 
            'post_status' => 'inherit', 
            'meta_query'    => array(
                array(
                    'key'       => 'peril_csv_upload',
                    'value'     => 'true',
                    'compare'   => '=',
                ),
            ),   
        );
        $query = new WP_Query($args);
        if ($query->have_posts())  {
            while($query->have_posts()) {
                $query->the_post(); 
                global $post;
                $data = wp_get_attachment_metadata(get_the_ID());
                $form .= basename( get_attached_file(get_the_ID()));
                
            }

        } else {
            echo 'nothing';
        }
        wp_reset_postdata();*/
        $form .= '<div class="peril-form-field">';
            $form .= '<label for="game-csv">Import answers</label>';
            $form .= '<input type="file" name="game-csv" id="game-csv" accept=".csv" />';
        $form .= '</div>';
        $form .= '<input type="hidden" name="game-csv-id" id="game-csv-id" />';
        $form .= '<input type="submit" class="button button-primary" id="create-peril-game" value="Create game" />';
        $form .= '</form>';
        $form .= '<div class="game-creator-feedback"></div>';
    $form .= '</div>';
    return $form;
}