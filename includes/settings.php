<?php 
/*
Functions related to overall plugin functionality
*/

/*
 *
 * Plugin Options for Admin Pages
 * 
 */
add_action( 'admin_init', 'register_peril_settings' );
function register_peril_settings() {

    //Timers
    add_option( 'peril_update_frequency', 5000);
    register_setting( 'peril_settings', 'peril_update_frequency' );

    add_option( 'peril_creation_requires_login', '1');
    register_setting( 'peril_settings', 'peril_creation_requires_login' );

    add_option( 'peril_gameplay_requires_login', '0');
    register_setting( 'peril_settings', 'peril_gameplay_requires_login' );
}

/*
 *
 * Create admin page
 * 
 */
function babel_core_admin_pages() {
    add_submenu_page('edit.php?post_type=game', 'Settings', 'Settings', 'administrator', 'peril-settings', 'peril_settings_content' );

}
add_action( 'admin_menu', 'babel_core_admin_pages' );    
    
/*
 *
 * Admin page formatting
 * 
 */
function peril_settings_content() { ?> 
    <style>
        .peril-setting-section {display: flex; flex-direction: column;padding: 0px 0; gap: 8px;}
        
    </style>
    <div class="wrap">
        <h1 class="wp-heading-inline">Game of Peril Settings</h1>
        <?php settings_errors(); ?>
        <div id="poststuff">
            <form method="post" action="options.php" class="babel-settings">
                <?php settings_fields( 'peril_settings' ); ?>

                <div class="postbox">
                    <div class="postbox-header"><h2 class="post-box-heading">General settings</h2></div>
                    <div class="inside">
                        <div class="peril-setting-section">
                            <div>
                                <label>Game update frequency</label>
                                <p class="description">Frequency in milliseconds the server pings for game updates.<br>Lower numbers update games quicker but will use more server resources.</p>
                                <input type="number" min="0" max="100000" name="peril_update_frequency" value="<?php echo get_option('peril_update_frequency'); ?>" />
                            </div>

                            <div>
                                <label>Game creation requires login</label>
                                <p class="description">A user must be logged in to create a game.</p>
                                <?php $options = array(
                                    '1' => 'User must be logged in to create a game',
                                    '0' => 'Anyone can create a game'
                                );
                                $setting = get_option('peril_creation_requires_login');
                                echo '<select name="peril_creation_requires_login">';
                                foreach($options as $k => $v) {
                                    echo '<option value="'.$k.'"';
                                    if($setting == $k) echo ' selected';
                                    echo '>'.$v.'</option>';
                                }
                                echo '</select>';
                                ?>
                            </div>

                            <div>
                                <label>Gameplay requires login</label>
                                <p class="description">A user must be logged in to play a game.</p>
                                <?php $options = array(
                                    '1' => 'User must be logged in to play a game',
                                    '0' => 'Anyone can play in a game'
                                );
                                $setting = get_option('peril_gameplay_requires_login');
                                echo '<select name="peril_gameplay_requires_login">';
                                foreach($options as $k => $v) {
                                    echo '<option value="'.$k.'"';
                                    if($setting == $k) echo ' selected';
                                    echo '>'.$v.'</option>';
                                }
                                echo '</select>';
                                ?>
                            </div>
                        </div>
                        <?php submit_button(); ?>
                    </div>
                    
                </div>
                
                
               
            </form>    
            </div>
    </div>
<?php
}
?>