<?php 
get_header();
global $post;
echo '<div class="peril-single" id="game-content">';
    echo get_game_content($post->ID);
echo '</div>';
echo '<button id="play-peril-music" class="show-play">PLAY AUDIO</button>';
echo '<audio id="peril-music" preload="auto" src="'.PERIL_PLUGIN_URL.'/assets/music/ding.mp3"></audio>';
echo login_modal();
get_footer();
?>