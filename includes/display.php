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
    $started = get_post_meta($post_id, 'peril_game_started', true);
    $host = get_post_meta($post_id, 'peril_game_host', true);
    $players = get_post_meta($post_id, 'peril_game_players');
    $requires_login = get_option('peril_gameplay_requires_login');
    $peril_uuid = get_peril_uuid();
    if(!is_array($players)) {
        $players = array();
    }
    $content = '';
    if($started == '') {
        include_once dirname( PERIL_PLUGIN_FILE ) . '/assets/lib/phpqrcode/qrlib.php';
        $text = get_permalink($post_id);
        QRcode::png($text, PERIL_PLUGIN_PATH .'assets/img/qrcode.jpg', 'M', '7');
        
        //echo '<div class="notice shadow">Buzzers ready, we&rsquo;re waiting for the game to start!</div>';
        $content .= '<div class="question-text awaiting-start">The game has not started</div>';
        
        if($peril_uuid > 0) {
            if(is_audience_member($post_id)) {
                $content .= '<div class="welcome-actions">';
                    $content .= '<div>';
                        $content .= '<img src="'.PERIL_PLUGIN_URL .'assets/img/qrcode.jpg" />';
                    $content .= '</div>';
                $content .= '</div>';
            } else if(in_array($peril_uuid, $players)) {
                $name = get_post_meta($post_id, "peril_player_name_$peril_uuid", true);
                $content .= '<p class="peril-white">Enter your name below<span class="player-name-field"><input type="text" id="enter-player-name" placeholder="'.$name.'" /><button class="peril-button" id="update-name">Update</button></span></p>';
                $content .= '<p class="peril-white">Using your buzzer';
                $content .= '<span>When you have the question for an answer, tap your device to buzz in. You will have 5 seconds to respond.<br>Don&rsquo;t forget your phrasing!</span></p>';
            } else {
                if($host == '') {
                    $content .= '<div class="welcome-actions">';
                        $content .= '<div>';
                            $content .= '<button class="peril-button" id="claim-host">Join as host</button>';
                            $content .= '<button class="peril-button" id="claim-player">Join as a Competitor</button>';
                            $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
                        $content .= '</div>';
                        $content .= '<div>';
                            $content .= '<img src="'.PERIL_PLUGIN_URL .'assets/img/qrcode.jpg" />';
                        $content .= '</div>';
                    $content .= '</div>';

                } else {
                    if($host == $peril_uuid) {
                        $content .= '<div class="notice shadow">When all players are ready, start the game!</div>';
                        $content .= '<button class="peril-button" id="start_game">Start the game</button>';
                    } else {
                        $content .= '<div class="welcome-actions">';
                            $content .= '<div>';
                                $content .= '<button class="peril-button" id="claim-player">Join as a Competitor</button>';
                                $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
                            $content .= '</div>';
                            $content .= '<div>';
                                $content .= '<img src="'.PERIL_PLUGIN_URL .'assets/img/qrcode.jpg" />';
                            $content .= '</div>';
                        $content .= '</div>';
                    }
                }
            }
            
            //$content .= '<p class="peril-white">If you&rsquo;re a competitor, please wait for the host start game.</p>';
        } else {
            if(!is_audience_member($post_id) && $requires_login == 1) {
                $content .= show_login_link();
            }
        }
        
        if($peril_uuid == 0 && !is_audience_member($post_id)) {
            $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
        }
    } else if($host == '') {
        $content .= '<p>The host has left the game.</p>';
        if(!in_array($peril_uuid, $players)) {
            $content .= '<button class="peril-button" id="claim-host">Join as host</button>';
        } else {
            $content .= '<p>As a contestant, you cannot host. Please have someone else join to host your game.</p>';
        }
    } else {
        $content = show_started_game($post_id);
    }
    return $content;
}

function get_peril_uuid() {
    $requires_login = get_option('peril_gameplay_requires_login');
    global $current_user;
    $uuid = 0;
    if($current_user->ID > 0 && $requires_login == '1') {
        $uuid = $current_user->ID;
    } else {
        if(isset($_COOKIE['peril_uuid'])) {
            $uuid = $_COOKIE['peril_uuid'];
        }
    }
    return $uuid;
}


function show_started_game($post_id) {
    $content = '';
    $found_player = false;
    $players = get_post_meta($post_id, 'peril_game_players');
    $current_action = get_post_meta($post_id, 'peril_game_action', true);
    if(!is_array($players)) {
        $players = array();
    }
    $peril_uuid = get_peril_uuid();
    $current_round = get_game_round($post_id);
    if($peril_uuid > 0) {
        $host = get_post_meta($post_id, 'peril_game_host', true);
        
        if($host == $peril_uuid) {
            $toggle_active = '';
            $actions_active = 'inactive';
            $show_buttons = false;
            if($current_action != '') {
                $show_buttons = true;
                $ignore_actions = array(
                    'show_clue',
                    'daily_double',
                    'show_final_jeopardy',
                    'end_final_jeopardy_guesses',
                    'show_player_final_guess',
                    'show_player_final_wager',
                    'player_final_is_correct',
                    'player_final_is_incorrect',
                    'show_winner',
                );
                if(in_array($current_action, $ignore_actions)) {
                    $show_buttons = false;
                }
            }
            if($show_buttons) {
                $toggle_active = ' is-active';
                $actions_active = '';
            }
            $found_player = true;
            $content .= get_screen_content($post_id, $current_action, 'host');
            $content .= '<div class="show-host-toggle '.$toggle_active .'">Game Options</div>';
            $content .= '<div class="host-actions '.$actions_active.'">';
                $content .= '<div class="action-list">';
                    $actions = array(
                        'show_scores' => 'Show Scores',
                        'display_game_board' => 'Show Board',
                    );
                    if($current_action != '') {
                        if(!in_array($current_action, $ignore_actions) && $current_action != 'game_intro') {
                            $content .= '<div class="host-action" data-action="resume_game">Resume Game</div>';    
                        }
                    }
                    foreach($actions as $k => $v) {
                        $content .= '<div class="host-action';
                        if($k == $current_action) {
                            $content .= ' inactive';
                        }
                        $content .= '" data-action="'.$k.'">'.$v.'</div>';
                    }
                    
                    $actions = array(
                        'goto_round_1' => 'Go to round 1',
                        'goto_round_2' => 'Go to round 2',
                        'goto_round_3' => 'Go to round 3',
                    );
                    $rounds = 0;
                    foreach($actions as $k => $v) {
                        $rounds++;
                        $content .= '<div class="host-action';
                        if($rounds == $current_round) {
                            $content .= ' inactive';
                        }
                        $content .= '" data-action="'.$k.'">'.$v.'</div>';
                    }
                    if($current_round == 3) {
                        $k = 'show_final_jeopardy_clue';
                        $content .= '<div class="host-action';
                        if($k == $current_action) {
                            $content .= ' inactive';
                        }
                        $content .= '" data-action="'.$k.'">Show Final Peril Clue</div>';
                        $k = 'show_final_jeopardy';
                        $content .= '<div class="host-action';
                        if($k == $current_action) {
                            $content .= ' inactive';
                        }
                        $content .= '" data-action="'.$k.'">Start Final Peril</div>';
                    } else {
                        if($current_round <= 2) {
                            $actions = array();
                            $actions['show_category_1'] = 'Show Category 1';
                            $actions['show_category_2'] = 'Show Category 2';
                            $actions['show_category_3'] = 'Show Category 3';
                            $actions['show_category_4'] = 'Show Category 4';
                            $actions['show_category_5'] = 'Show Category 5';
                            foreach($actions as $k => $v) {
                                $content .= '<div class="host-action';
                                if($k == $current_action) {
                                    $content .= ' inactive';
                                }
                                $content .= '" data-action="'.$k.'">'.$v.'</div>';
                            }
                        }
                    }
                $content .= '</div>';
                $content .= '<div class="score-adjustments">';
                    $currently_answering = get_post_meta($post_id, 'peril_player_answering', true);
                    if($currently_answering == '') {
                        $currently_answering = get_post_meta($post_id, 'peril_last_player', true);
                    }
                    $current_text = 'Currently player: ';
                    foreach($players as $player) {
                        $score = get_post_meta($post_id,"peril_player_{$player}_score", true);
                        if($score == '') {
                            $score = 0;
                        }
                        $username = get_post_meta($post_id, "peril_player_name_$player", true);
                        if($currently_answering == $player) {
                            $current_text .= $username;
                        }
                        $content .= '<div class="score"><div class="name">'.$username.'</div>';
                        $content .= '<input type="text" class="new-score" value="'.$score.'" />';
                        $content .= '<button class="update-score peril-button" data-player="'.$player.'">Update Score</button>';
                        $content .= '</div>';
                    }
                    $content .= $current_text;
                $content .= '</div>';
                //$content .= '<div>'. get_player_scores($players, $post_id, 'host').'</div>';
            $content .= '</div>';
            //show scores
            
        } else if(in_array($peril_uuid, $players)) {
            $found_player = true;
            $content .= get_screen_content($post_id, $current_action, 'contestant');
            //show scores
            $content .= '<div class="show-score-toggle contestant">scores</div>';
            $content .= get_player_scores($players, $post_id, 'public inactive');
        } else {
            if(!is_audience_member($post_id)) {
               // $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
            }
        }
    } 
    if(!$found_player) {
        if(is_audience_member($post_id)) {
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
    $current_round = get_game_round($game_id);
    $uuid = get_peril_uuid();
    if($current_action != '') {
        $board_array = maybe_unserialize(get_post_meta($game_id, 'peril_game_board', true));
        $answering = 'no-answer';
        $currently_answering = get_post_meta($game_id, 'peril_player_answering', true);
        if($currently_answering != '') {
            $answering = 'player-answering';
            
            if($uuid == $currently_answering) {
                $answering .= ' current-player-answering';
            }
        }
        $zoom = false;
        if($current_action == 'show_clue' || $current_action == 'daily_double') {
            $zoom = true;
            $category = get_post_meta($game_id, 'peril_current_category', true);
            $value = get_post_meta($game_id, 'peril_current_value', true);
            $concat = "$category:$value";
            //get double jeopardy question(s)
            $dbl_jeopardy = get_post_meta($game_id, "round_{$current_round}_double_jeopardy");
            if(in_array($concat, $dbl_jeopardy)) {
                $zoom = false;
                $answering = 'player-answering';
                $previously_answering = get_post_meta($game_id, 'peril_last_player', true);
                if($uuid == $previously_answering) {
                    $answering .= ' current-player-answering';
                }
            }
        }
        $zoom_class = '';
        if($zoom && $player_type == 'audience_member') {
            $zoom_class = 'zoom-in';
            $content .= display_game_board($game_id, $current_round, 'behind-clue');
        }
        
        $content .= '<div class="game-action '.$player_type.' '.$current_action.' '.$zoom_class.' '.$answering.'">';
        switch($current_action) {
            case 'starting_game':
                $content .= '<div class="question-text">The game is about to start!</div>';
                break;
            case 'game_intro':
                $content .= '<div class="peril-intro"></div>';

            case 'show_scores':
                $players = get_post_meta($game_id, 'peril_game_players');
                if(!is_array($players)) {
                    $players = array();
                }
                $content .= get_player_scores($players, $game_id, 'score-recap');
                break;
            case 'display_game_board':
                $seen = get_post_meta($game_id, "peril_player_{$uuid}_seen_round_{$current_round}_board", true);
                if($seen == '') {
                    $class = 'intro_board';
                    update_post_meta($game_id, "peril_player_{$uuid}_seen_round_{$current_round}_board", 'yep');
                    update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
                } else {
                    $class = 'has-seen';
                    update_post_meta($game_id,'peril_player_audio_for_type', 'none');
                }
                
                $content .= display_game_board($game_id, $current_round, $class);
                break;
            case 'show_category_1':
            case 'show_category_2':
            case 'show_category_3':
            case 'show_category_4':
            case 'show_category_5':
                $board_array = maybe_unserialize(get_post_meta($game_id, 'peril_game_board', true));
                $keys = array_keys($board_array[$current_round]);
                //$content .= print_r($keys, true);
                if($current_action == 'show_category_1') {
                    $content .= '<div class="question-text slide-left">'.$keys[0].'</div>';
                } else if($current_action == 'show_category_2') {
                    $content .= '<div class="question-text slide-left">'.$keys[1].'</div>';
                }  else if($current_action == 'show_category_3') {
                    $content .= '<div class="question-text  slide-left">'.$keys[2].'</div>';
                } else if($current_action == 'show_category_4') {
                    $content .= '<div class="question-text slide-left">'.$keys[3].'</div>';
                } else if($current_action == 'show_category_5') {
                    $content .= '<div class="question-text slide-left">'.$keys[4].'</div>';
                }
                break;
            case 'show_clue':
                $show_timer = true;
                if(in_array($concat, $dbl_jeopardy)) {
                    $time_delay = 5000;
                    $peril_dd_wager = get_post_meta($game_id, 'peril_dd_wager', true);
                    if($peril_dd_wager == '') {
                        $show_timer = false;
                    }
                    $spin = '';
                    if(is_audience_member($game_id)) {
                        $spin = 'spin';
                    }
                    $content .= '<div class="question-text daily-double '.$spin.'">Daily Double!</div>';
                    if($player_type == 'host') {
                        $player_name = get_post_meta($game_id, "peril_player_name_$previously_answering", true);
                        $content .= '<div>Contestant guessing: '.$player_name.'</div>';
                        $player_score = get_post_meta($game_id, "peril_player_{$previously_answering}_score", true);
                        $content .= '<div>Current score: $'.$player_score.'</div>';
                        if($current_round == 1) {
                            if($player_score < 1000) {
                                $player_score = 1000;
                            }
                        } else if ($current_round == 2) {
                            if($player_score < 2000) {
                                $player_score = 2000;
                            }
                        }
                        $content .= '<div>Available to wager: $'.$player_score.'</div>';
                        //$category = array_search($board_array[$current_round][$category]);
                        $content .= '<div>Category: '.$category.'</div>';
                        $content .= '<div class="peril-form-field row wager"><input type="text" placeholder="Wager" id="input-wager" data-max="'.$player_score.'" /><button class="peril-button" id="submit-wager">Set Wager</button>';
                    }
                } else {
                    $time_delay = 0;
                    if(isset($board_array[$current_round][$category][$value])) {
                        if($player_type == 'host') {
                            $content .= $category.': $'.$value;
                        }
                        $content .= '<div class="question-text">'.$board_array[$current_round][$category][$value]['answer'].'</div>';
                        if($player_type == 'host') {
                            $content .= '<div>Answer: '.$board_array[$current_round][$category][$value]['question'].'</div>';
                            if($currently_answering != '') {
                                $player_name = get_post_meta($game_id, "peril_player_name_$currently_answering", true);
                                $content .= 'Contestant guessing: '.$player_name;
                                $content .= '<div class="host-answer-responses">';
                                    $content .= '<button class="peril-button correct" data-value="correct">Correct</button>';
                                    $content .= '<button class="peril-button incorrect" data-value="incorrect">Incorrect</button>';
                                $content .= '</div>';
                            } else {
                                $content .= '<button class="peril-button no-answer host-action" data-action="no-response">No Response</button>';
                            }
                        }
                    }
                }
                if(is_audience_member($game_id)) {
                    if($show_timer) {
                        $content .= '<div class="question-timer is-hidden" data-delay="'.$time_delay.'"><div></div><div></div><div></div><div></div><div></div><div></div></div>';
                    } 
                }
                
               // $content .= display_game_board($game_id, $current_round);
                break;
            case 'daily_double':
                if(isset($board_array[$current_round][$category][$value])) {
                    if($player_type == 'host') {
                        $content .= $category.': $'.$value;
                    }
                    $content .= '<div class="question-text">'.$board_array[$current_round][$category][$value]['answer'].'</div>';
                    if($player_type == 'host') {
                        $content .= '<div>'.$board_array[$current_round][$category][$value]['question'].'</div>';
                        if($previously_answering != '') {
                            $player_name = get_post_meta($game_id, "peril_player_name_$previously_answering", true);
                            $content .= 'Contestant guessing: '.$player_name;
                            $content .= '<div class="host-answer-responses">';
                                $content .= '<button class="peril-button correct" data-value="correct">Correct</button>';
                                $content .= '<button class="peril-button incorrect" data-value="incorrect">Incorrect</button>';
                            $content .= '</div>';
                        }
                    }
                }
                if(is_audience_member($game_id)) {
                    $content .= '<div class="question-timer is-hidden" data-delay="5000"><div></div><div></div><div></div><div></div><div></div><div></div></div>';
                }
                break;
            case 'show_final_jeopardy_clue':
            case 'show_final_jeopardy':
            case 'end_final_jeopardy_guesses':
                $key = array_key_first($board_array[$current_round]);
                
                if($key !== false) {
                    if($player_type == 'host') {
                        $content .= $key;
                    }
                    if(isset($board_array[$current_round][$key])) {
                        if($player_type != 'host') {
                           $content .= '<div style="margin-bottom: 15px;">'.$key.'</div>';
                        }
                        $second_key = array_key_first($board_array[$current_round][$key]);
                        if($second_key !== false) {
                            if(isset($board_array[$current_round][$key][$second_key])) {
                                $answer = $board_array[$current_round][$key][$second_key]['answer'];
                                $content .= '<div class="question-text">'.$answer.'</div>';
                            }
                        } 
                    }
                    $players = get_post_meta($game_id, 'peril_game_players');
                    if($current_action == 'show_final_jeopardy') {
                        if(in_array($uuid, $players)) {
                            $content .= '<div class="final-input-container">';
                                $guess = get_post_meta($game_id,"peril_player_{$uuid}_final_guess", true);
                                $content .= '<textarea class="final-guess" rows="1">'.$guess.'</textarea>';
                                $content .= '<button class="peril-button set-my-guess">Set Response</button>';
                                $content .= '<div class="guess-response-feedback"></div>';
                            $content .= '</div>';
                        } else if($player_type == 'host') {
                            $content .= '<div class="peril-button" id="show_guesses">Show Player Guesses</button>';
                        }
                    } else {
                        //were showing guesses
                        if($player_type == 'host') {
                            $content .= show_final_player_score_actions($game_id, $players);
                            $content .= '<button id="show_winner" class="peril-button">Show Winner</button>';
                        }
                    }
                    
                } else {
                    //$content .= 'Oh no! There&rsquo;s no final clue!';
                }
                
                
                
                // $content .= display_game_board($game_id, $current_round);
                break;
            case 'show_player_final_guess':
            case 'show_player_final_wager':
            case 'player_final_is_correct':
            case 'player_final_is_incorrect':
                if($player_type == 'host') {
                    $key = array_key_first($board_array[$current_round]);
                
                    if($key !== false) {
                        if($player_type == 'host') {
                            $content .= $key;
                        }
                        if(isset($board_array[$current_round][$key])) {
                            if($player_type == 'host') {
                            // $content .= $category.': $'.$value;
                            }
                            $second_key = array_key_first($board_array[$current_round][$key]);
                            if($second_key !== false) {
                                if(isset($board_array[$current_round][$key][$second_key])) {
                                    $answer = $board_array[$current_round][$key][$second_key]['answer'];
                                    $content .= '<div class="question-text">'.$answer.'</div>';
                                }
                            } 
                        }
                        
                        
                    } 
                    $players = get_post_meta($game_id, 'peril_game_players');
                    $content .= show_final_player_score_actions($game_id, $players);
                    $content .= '<button id="show_winner" class="peril-button">Show Winner</button>';
                } else {
                    $player_guessing = get_post_meta($game_id, 'peril_final_player_displaying', true);
                    $username = get_post_meta($game_id, "peril_player_name_$player_guessing", true);
                    $content .= '<div class="script-font">'.$username.'</div>';
                    
                    $guess = get_post_meta($game_id, "peril_player_{$player_guessing}_final_guess", true);
                    $content .= '<div>'.$guess.'</div>';
                    if($current_action == 'show_player_final_wager') {
                        $wager = get_post_meta($game_id, "peril_player_{$player_guessing}_wager", true);
                        $content .= '$'.number_format($wager, 0);
                    }
                    if($current_action == 'player_final_is_correct' || $current_action == 'player_final_is_incorrect') {
                        $score = get_post_meta($game_id,"peril_player_{$player_guessing}_score", true);
                        $content .= '$'.number_format($score, 0);
                    }
                }

                break;
            case 'show_winner':
                $players = get_post_meta($game_id, 'peril_game_players');
                $max_score = 0;
                foreach($players as $player) {
                    $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
                    if($score >= $max_score) {
                        $max_score = $score;
                    }
                }
                $winners = array();
                foreach($players as $player) {
                    $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
                    if($score == $max_score) {
                        $winners[] = $player;
                    }
                }
                $content .= '<div id="winners">';
                foreach($winners as $winner) {
                    $username = get_post_meta($game_id, "peril_player_name_$winner", true);
                    $score = get_post_meta($game_id,"peril_player_{$winner}_score", true);
                    $content .= '<div><div>Congratulations '.$username.'! You won!</div><div>$'.number_format($score, 0).'</div></div>';
                }
                $content .= '</div>';
                break;
        }
        $content .= '</div>';
        return $content;
    } else {
        //show board for now
        if($current_round != 3) {
            $seen = get_post_meta($game_id, "peril_player_{$uuid}_seen_round_{$current_round}_board", true);
            if($seen == '') {
                $class = 'intro_board';
                update_post_meta($game_id, "peril_player_{$uuid}_seen_round_{$current_round}_board", '1');
                update_post_meta($game_id,'peril_player_audio_for_type', 'audience_member');
            } else {
                $class = 'has-seen';
                update_post_meta($game_id,'peril_player_audio_for_type', 'none');
            }
        }
        $content = display_game_board($game_id, $current_round, $class);
        /*switch($player_type) {
            case 'host':
                $content = '<p class="peril-white">You are the host</p>';
                break;
            case 'contestant': 
                $content = '<p class="peril-white">You are a competitor, this is competitor content</p>';
                break;
            case 'audience_member': 
                $content = '<p class="peril-white">You are an audience member, this is audience content.</p>';
                break;
        }*/
    }
    return $content;
}

function show_final_player_score_actions($game_id, $players) {
    $player_guessing = get_post_meta($game_id, 'peril_final_player_displaying', true);
    $content = '<div class="player-results">';
    foreach($players as $player) {
        $class = '';
        if($player_guessing == $player) {
            $class = 'current-player';
        }
        $score = get_post_meta($game_id,"peril_player_{$player}_score", true);
        $wager = get_post_meta($game_id, "peril_player_{$player}_wager", true);
        $guess = get_post_meta($game_id, "peril_player_{$player}_final_guess", true);
        $username = get_post_meta($game_id, "peril_player_name_$player", true);
        $content .= '<div class="player-guess '.$class.'"><div>'.$username.'</div><div>Score: '.$score.'</div><div>Wager: '.$wager.'</div><div>'.$guess.'</div>';
        //$content .= 'Complete the following in order';
        $content .= '<button class="final-show-player-guess peril-button" data-player="'.$player.'">Show Player Guess</button>';
        $content .= '<button class="final-show-player-wager peril-button" data-player="'.$player.'">Show Player Wager</button>';
        $done = get_post_meta($game_id,"peril_player_{$player}_done", true);
        if($done != 1) {
            $content .= '<button class="final-player-is-correct peril-button" data-player="'.$player.'">Player is Correct</button>';
            $content .= '<button class="final-player-is-incorrect peril-button" data-player="'.$player.'">Player is Incorrect</button>';
        }
        $content .= '</div>';
    }
    $content .= '</div>';
    return $content;

}

function get_game_round($game_id) {
    $game_round = get_post_meta($game_id, 'peril_game_round', true);
    if($game_round == '') {
        $game_round = 1;
    }
    return $game_round;
}

add_shortcode('peril_create_game', 'peril_create_game_callback');
function peril_create_game_callback() {
    global $current_user, $post;
    $peril_uuid = get_peril_uuid();
    $requires_login = get_option('peril_creation_requires_login');
    if($peril_uuid == 0 && $requires_login == '1') {
        return sprintf('<p>You must be logged in to create a game. <a href="%s" title="Log in">Log In</a></p>', wp_login_url(get_permalink($post->ID)));
    }
    $form = '<div id="peril-game-creator">';
        $form .= '<form autocomplete="off">';
        $form .= '<div class="peril-form-field">';
            $form .= '<label for="game-name">Game name</label>';
            $form .= '<input type="text" name="game-name" id="game-name" placeholder="Game name" />';
        $form .= '</div>';

        $has_imports = false;
        $args = array(
            'post_type'   => 'attachment',
            'posts_per_page' => 10, 
            'post_status' => 'inherit', 
            'meta_query'    => array(
                array(
                    'key'       => 'peril_csv_game_title',
                    'compare' => 'EXISTS',
                ),
            ),   
        );
        $query = new WP_Query($args);
        if ($query->have_posts())  {
            $has_imports = true;
            
        }
        
        if($has_imports) {
            $form .= '<div class="peril-form-field"><label>Answers source</label>';
            $form .= '<label><input type="radio" name="answers-source" value="import-peril-csv" checked="checked" /> Upload CSV</label>';
            $form .= '<label><input type="radio" name="answers-source" value="select-peril-csv" /> Use existing game</label>';
            $form .= '</div>';
            
            $form .= '<div class="peril-conditional" id="select-peril-csv">';
                $form .= '<div class="peril-form-field">';
                    $form .= '<select name="peril-csv-select" id="peril-csv-select">';
                    $form .= '<option value="0">Select a game to copy from</option>';
                    while($query->have_posts()) {
                        $query->the_post(); 
                        global $post;
                        //$data = wp_get_attachment_metadata(get_the_ID());
                        //$form .= basename( get_attached_file(get_the_ID()));
                        $name = get_post_meta(get_the_ID(),'peril_csv_game_title', true);
                        $form .= '<option value="'.get_the_ID().'">'.$name.'</option>';
                    }
                    $form .= '</select>';
                $form .= '</div>';
            $form .= '</div>';
            $form .= '<div class="peril-conditional is-active" id="import-peril-csv">';
        }
        
            $form .= '<div class="peril-form-field">';
                if(!$has_imports) {
                    $form .= '<label for="game-csv">Import answers</label>';
                }
                $form .= '<input type="file" name="game-csv" id="game-csv" accept=".csv" />';
                $form .= '<p class="description"><a href="'.PERIL_PLUGIN_URL.'/assets/imports/example.csv">Download an example csv</a></p>';
                $form .= '<input type="hidden" name="game-csv-id" id="game-csv-id" />';
            $form .= '</div>';

        if($has_imports) {
            $form .= '</div>'; //close import-peril-csv
        }
        $form .= '<div class="peril-form-field">';
            $form .= '<input type="hidden" name="answers-type" id="answers-type" value="import-peril-csv" />';
            $form .= '<input type="submit" class="btn btn-primary button button-primary" id="create-peril-game" value="Create game" />';
        $form .= '</div>';
        $form .= '</form>';
        $form .= '<div class="game-creator-feedback"></div>';
    $form .= '</div>';

    wp_reset_postdata();

    return $form;
}

function display_game_board($game_id, $game_round = 1, $extra_class = '') {
    $board_array = maybe_unserialize(get_post_meta($game_id, 'peril_game_board', true));
    $used_answers_array = maybe_unserialize(get_post_meta($game_id, 'peril_game_used_answers', true));
    if(!is_array($used_answers_array)) {
        $used_answers_array = array();
    }
    //$board_array = ''; //for testing
    $content = '';
    if($board_array == '' || (is_array($board_array) && empty($board_array))) {
        delete_post_meta($game_id, 'round_1_double_jeopardy');
        delete_post_meta($game_id, 'round_2_double_jeopardy');
        delete_post_meta($game_id, 'round_3_double_jeopardy');
        // board hasnt been created
        $game_csv = get_post_meta($game_id, 'peril_game_csv', true);
        if($game_csv == '') {
            return 'There are no answers for this game. Please create a new game.';
        }
        $csv_url = wp_get_attachment_url($game_csv);
        //remove url
        $csv_url = str_replace(get_bloginfo('url'),ABSPATH,$csv_url); 
        $f = fopen($csv_url, "r");
        $rows = 0;
        $board_array = array();
        while (($line = fgetcsv($f)) !== false) {
            if($rows > 0) {
                //skip headers
                $round = $line[0];
                $category = $line[1];
                $answer = $line[2];
                $question = $line[3];
                if(isset($line[4])) {
                    $value = $line[4];
                } else {
                    $value = 0;
                }
                if($value == '') {
                    $value = 0;
                }
                
                $board_array["$round"]["$category"]["$value"] = array(
                    'answer' => $answer,
                    'question' => $question
                );
            }
            $rows++;
        }
        fclose($f);
        update_post_meta($game_id, 'peril_game_board', maybe_serialize($board_array));
        if(isset($board_array["1"])) {
            $category = array_rand($board_array["1"]); //here yoy get random first of array(green or red or yellow)
            $value = array_search($board_array["1"][$category][array_rand($board_array["1"][$category])],$board_array["1"][$category]);
            add_post_meta( $game_id, "round_1_double_jeopardy", "$category:$value" );
        }
        if(isset($board_array["2"])) {
            $category = array_rand($board_array["2"]); //here yoy get random first of array(green or red or yellow)
            $value = array_search($board_array["2"][$category][array_rand($board_array["2"][$category])],$board_array["2"][$category]);
            $dbl_1 = "$category:$value";
            add_post_meta( $game_id, "round_2_double_jeopardy", "$dbl_1" );
            $found_unique = false;
            while(!$found_unique) {
                $category = array_rand($board_array["2"]); //here yoy get random first of array(green or red or yellow)
                $value = array_search($board_array["2"][$category][array_rand($board_array["2"][$category])],$board_array["2"][$category]);
                $dbl_2 = "$category:$value";
                if($dbl_1 != $dbl_2) {
                    $found_unique = true;
                }
            }
            add_post_meta( $game_id, "round_2_double_jeopardy", "$dbl_2" );
        }
    } 
    if(isset($board_array[$game_round])) {
        $content .= '<div class="game-board round-'.$game_round.' '.$extra_class.'">';
            if($game_round == 3) {
                //$content .= '<span class="answer"><span class="prompt">'.$v['answer'].'</span>'.$v['question'].'</span>';
                //$content .= '<span class="answer"><span class="prompt">'.$board_array[$game_round]['answer'].'</span></span>';
                /*foreach($board_array[$game_round] as $category => $array) {
                    $content .= '<div class="round-column" data-category="'.$category.'">';
                    foreach($array as $k => $v) {
                        $content .= '<div data-category="'.$category.'" data-value=""><span class="question-text">'.$v['answer'].'</span>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                }*/
                $key = array_key_first($board_array[$game_round]);
                $content .= '<div class="question-text">';
                if($key !== false) {
                    $content .= $key;
                } else {
                    $content .= 'Oh no! There&rsquo;s no final clue!';
                }
                $content .= '</div>';
                $host = get_post_meta($game_id, 'peril_game_host', true);
                $players = get_post_meta($game_id, 'peril_game_players');
                $peril_uuid = get_peril_uuid();
                if(is_audience_member($game_id)) {
                    //do nothing, we're waiting
                } else if(in_array($peril_uuid, $players)) {
                    $score = get_post_meta($game_id,"peril_player_{$peril_uuid}_score", true);
                    if($score == '') {
                        $score = 0;
                    }
                    if($score >= 0) {
                        $scores = '$'.number_format($score, 0);
                    } else {
                        $score = $score * -1;
                        $scores = '- $'.number_format($score, 0);
                    }
                    $content .= '<p>You have '.$scores.' to wager.</p>';
                    if($score == 0) {
                        $content .= '<p>Sorry, you don&rsquo;t have enough points to continue</p>';
                    } else {
                        $content .= '<div><p>Please enter your final wager</p></div>';
                        $content .= '<div class="final-input-container">';
                            $wager = get_post_meta($game_id,"peril_player_{$peril_uuid}_wager", true);
                            if($wager == '') {
                                $wager = 0;
                            }
                            $content .= '<input type="text" class="wager-value" placeholder="'.$wager.'" />';
                            $content .= '<button class="peril-button set-my-wager">Set Wager</button>';
                        $content .= '</div>';
                    }
                } else {
                    if($host == $peril_uuid) {
                        $content .= '<p>When all players have submitted their wagers, go to the game options and start Final Peril</p>';
                        $content .= '<button class="peril-button" id="check-player-wagers">Check Wagers</button>';
                        $content .= '<div id="wagers-response"></div>';
                    }
                }
                
            } else {

                foreach($board_array[$game_round] as $category => $array) {
                    $content .= '<div class="category">'.$category.'</div>';   
                }
                foreach($board_array[$game_round] as $category => $array) {
                    $content .= '<div class="round-column specifier" data-category="'.$category.'">';
                    foreach($array as $k => $v) {
                        $question_status = 'available';
                        if(isset($used_answers_array[$category])) {
                            if(in_array($k, $used_answers_array[$category])) {
                                $question_status = 'unavailable';
                            }

                        }
                        $content .= '<div class="round-question '.$question_status.'" data-category="'.$category.'" data-value="'.$k.'"><span class="value">$'.$k.'</span>';
                        if($question_status == 'unavailable') {
                                $content .= '<span class="answer"><span class="prompt">'.$v['answer'].'</span>'.$v['question'].'</span>';
                        }
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                }
            }
        
        $content .= '</div>';
    } else {
        $content .= '<div>There is no content for this round!</div>';
    }
    return $content;
}

function convertToArray(string $content): array
{
   $data = str_getcsv($content,"\n");
   array_walk($data, function(&$a) use ($data) {
       $a = str_getcsv($a);
   });

   return $data;
}