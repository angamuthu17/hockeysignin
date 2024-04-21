<?php
// Enqueue necessary scripts and styles for the public-facing parts
function hockeysignin_enqueue_public_scripts() {
    if (is_a_page_with_shortcode('hockeysignin')) {
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('scripts', plugins_url('scripts.js', __DIR__), array('jquery-ui-autocomplete'), '1.0', true)  ;
        wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }
}

function is_a_page_with_shortcode($shortcode = '') {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, $shortcode)) {
        return true;
    }
    return false;
}

// Shortcode for displaying the sign-in form and handling submissions
function hockeysignin_shortcode() {
    ob_start();

    if (isset($_POST['submit']) && check_admin_referer('hockey_signin_checkin', 'hockey_signin_nonce_field')) {
        $response = handle_player_checkin(); // Handle player check-in
        echo $response;
    }

    // Display the check-in form
    echo '<form method="post" action="">';
    wp_nonce_field('hockey_signin_checkin', 'hockey_signin_nonce_field');
    echo '<input type="text" name="player_name" id="player_name" placeholder="Enter your name" class="regular-text" />';
    echo '<input type="submit" name="submit" value="Check In" />';
    echo '</form>';

    return ob_get_clean();
}

function handle_player_checkin() {
    global $wpdb;
    $player_name = sanitize_text_field($_POST['player_name']);
    $names = explode(' ', $player_name, 2);
    $first_name = $names[0];
    $last_name = $names[1] ?? '';

    $table_name = $wpdb->prefix . 'participants_database';
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE first_name = %s AND last_name = %s", $first_name, $last_name));

    if ($player) {
        return '<div class="updated"><p>' . esc_html($player_name) . ' is checked in as prepaid.</p></div>';
    } else {
        return '<div class="error"><p>Player not found, added to the waitlist.</p></div>';
    }
}

add_action('wp_enqueue_scripts', 'hockeysignin_enqueue_public_scripts');
add_shortcode('hockeysignin', 'hockeysignin_shortcode');

