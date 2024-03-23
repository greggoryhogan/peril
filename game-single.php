<?php 
wp_head();
global $post;
echo '<div class="peril-single" id="game-content">';
    echo get_game_content($post->ID);
echo '</div>';
echo login_modal();
wp_footer();
?>