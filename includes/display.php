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
        //echo '<div class="notice shadow">Buzzers ready, we&rsquo;re waiting for the game to start!</div>';
        $content .= '<div class="question-text awaiting-start">The game has not started</div>';
        
        if($peril_uuid > 0) {
            if(is_audience_member($post_id)) {
                //do nothing, we're waiting
            } else if(in_array($peril_uuid, $players)) {
                $name = get_post_meta($post_id, "peril_player_name_$peril_uuid", true);
                $content .= '<p class="peril-white">Enter your name below<span class="player-name-field"><input type="text" id="enter-player-name" placeholder="'.$name.'" /><button class="peril-button" id="update-name">Update</button></span></p>';
                $content .= '<p class="peril-white">Using your buzzer';
                $content .= '<span>When you have the question for an answer, tap your device to buzz in. You will have 5 seconds to respond.<br>Don&rsquo;t forget your phrasing!</span></p>';
            } else {
                if($host == '') {
                    $content .= '<button class="peril-button" id="claim-host">Join as host</button>';
                    $content .= '<button class="peril-button" id="claim-player">Join as a Competitor</button>';
                    $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
                } else {
                    if($host == $peril_uuid) {
                        $content .= '<div class="notice shadow">When all players are ready, start the game!</div>';
                        $content .= '<button class="peril-button" id="start_game">Start the game</button>';
                    } else {
                        $content .= '<button class="peril-button" id="claim-player">Join as a Competitor</button>';
                        $content .= '<button id="audience-member" class="peril-button">Join as an audience member</button>';
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
                    'daily_double'
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
                $actions = array(
                    'show_scores' => 'Show Scores',
                    'display_game_board' => 'Show Board',
                    'goto_round_1' => 'Go to round 1',
                    'goto_round_2' => 'Go to round 2',
                    'goto_round_3' => 'Go to round 3',
                );
                if($current_action != '') {
                    if(!in_array($current_action, $ignore_actions)) {
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
    if($current_action != '') {
        $board_array = maybe_unserialize(get_post_meta($game_id, 'peril_game_board', true));
        $answering = 'no-answer';
        $currently_answering = get_post_meta($game_id, 'peril_player_answering', true);
        if($currently_answering != '') {
            $answering = 'player-answering';
            $uuid = get_peril_uuid();
            if($uuid == $currently_answering) {
                $answering .= ' current-player-answering';
            }
        }
        if($current_action == 'show_clue' || $current_action == 'daily_double') {
            $category = get_post_meta($game_id, 'peril_current_category', true);
            $value = get_post_meta($game_id, 'peril_current_value', true);
            $concat = "$category:$value";
            //get double jeopardy question(s)
            $dbl_jeopardy = get_post_meta($game_id, "round_{$current_round}_double_jeopardy");
            if(in_array($concat, $dbl_jeopardy)) {
                $answering = 'player-answering';
                $uuid = get_peril_uuid();
                $previously_answering = get_post_meta($game_id, 'peril_last_player', true);
                if($uuid == $previously_answering) {
                    $answering .= ' current-player-answering';
                }
            }
        }
        $content .= '<div class="game-action '.$player_type.' '.$current_action.' '.$answering.'">';
        switch($current_action) {
            case 'starting_game':
                $content .= '<div class="question-text">The game is about to start!</div>';
                break;
            case 'show_scores':
                $players = get_post_meta($game_id, 'peril_game_players');
                if(!is_array($players)) {
                    $players = array();
                }
                $content .= get_player_scores($players, $game_id, 'score-recap');
                break;
            case 'display_game_board':
                /*$game_round = get_post_meta($game_id, 'peril_game_round', true);
                if($game_round == '') {
                    $game_round = 1;
                }*/
                $content .= display_game_board($game_id, $current_round);
                break;
            case 'show_clue':
                if(in_array($concat, $dbl_jeopardy)) {
                    $content .= '<div class="question-text">Daily Double!</div>';
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
                    if(isset($board_array[$current_round][$category][$value])) {
                        if($player_type == 'host') {
                            $content .= $category.': $'.$value;
                        }
                        $content .= '<div class="question-text">'.$board_array[$current_round][$category][$value]['answer'].'</div>';
                        if($player_type == 'host') {
                            $content .= '<div>'.$board_array[$current_round][$category][$value]['question'].'</div>';
                            if($currently_answering != '') {
                                $player_name = get_post_meta($game_id, "peril_player_name_$currently_answering", true);
                                $content .= 'Contestant guessing: '.$player_name;
                                $content .= '<div class="host-answer-responses">';
                                    $content .= '<button class="peril-button correct" data-value="correct">Correct</button>';
                                    $content .= '<button class="peril-button incorrect" data-value="incorrect">Incorrect</button>';
                                $content .= '</div>';
                            }
                        }
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
                break;
        }
        $content .= '</div>';
        return $content;
    } else {
        //show board for now
        $content = display_game_board($game_id, $current_round);
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

function display_game_board($game_id, $game_round = 1) {
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
                $value = $line[4];
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
        $content .= '<div class="game-board round-'.$game_round.'">';
            if($game_round == 3) {
                //$content .= '<span class="answer"><span class="prompt">'.$v['answer'].'</span>'.$v['question'].'</span>';
                //$content .= '<span class="answer"><span class="prompt">'.$board_array[$game_round]['answer'].'</span></span>';
                foreach($board_array[$game_round] as $category => $array) {
                    $content .= '<div class="round-column" data-category="'.$category.'">';
                    foreach($array as $k => $v) {
                        $content .= '<div data-category="'.$category.'" data-value=""><span class="question-text">'.$v['answer'].'</span>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                }
            } else {

                foreach($board_array[$game_round] as $category => $array) {
                    $content .= '<div class="category">'.$category.'</div>';   
                }
                foreach($board_array[$game_round] as $category => $array) {
                    $content .= '<div class="round-column" data-category="'.$category.'">';
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