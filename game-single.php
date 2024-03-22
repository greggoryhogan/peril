<?php 
wp_head();
echo '<div class="peril-single">';
global $current_user, $post;
$started = get_post_meta($post->ID, 'game_started', true);
$host = get_post_meta($post->ID, 'game_host', true);
if($started == '') {
    //echo '<div class="notice shadow">Buzzers ready, we&rsquo;re waiting for the game to start!</div>';
    if($host == '') {
        echo '<div class="notice shadow">We have no Alex or Ken. Think you can handle the responsibility?</div>';
        echo '<button class="notice" id="claim-host">I&rsquo;m ready to host</button>';
    } else {
        echo '<div class="notice shadow">Think you&rsquo;re a champion? Join the game!</div>';
    }
    
}
echo '</div>';
wp_footer();
?>