<?php
// Enqueue necessary scripts and styles for the public-facing parts
function hockeysignin_enqueue_public_scripts() {
    if (is_a_page_with_shortcode('hockeysignin')) {
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('scripts', plugins_url('public/scripts.js', __FILE__), array('jquery-ui-autocomplete'), '1.0', true);
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

    // Current server date and time
    $today = date('D M j');  // Adjusted to match your WordPress date format
    $current_time = current_time('H:i');  // Using a simpler format for easier comparison

    // Define the active time range
    $start_time = '08:00';
    $close_time = '22:00';

    // Check if we're manually initializing or if today is the active date
    $isActiveToday = (isset($_GET['date']) && $_GET['date'] === $today) || isset($_GET['manual_start']);

    if ($isActiveToday) {
        // Check-in has not started for today.
        if ($current_time < $start_time && !isset($_GET['manual_start'])) {
            echo "Check-in has not started for today.";
        } 
        // Check-in is closed if after the closing time.
        else if ($current_time > $close_time) {
            echo "Sorry, check-in for today is closed.";
        } 
        // Within the active check-in period or manual start.
        else {
            // If a check-in is submitted.
            if (isset($_POST['submit']) && check_admin_referer('hockey_signin_checkin', 'hockey_signin_nonce_field')) {
                echo handle_player_checkin(); // Process the check-in and show results.
            }

            // Show the check-in form.
            echo '<form method="post" action="">';
            wp_nonce_field('hockey_signin_checkin', 'hockey_signin_nonce_field');
            echo '<input type="text" name="player_name" id="player_name" placeholder="Enter your name" class="regular-text" />';
            echo '<input type="submit" name="submit" value="Check In" />';
            echo '</form>';

            // After 6 PM, process waitlisted players or show manual start operations.
            if ($current_time >= '18:00' && $current_time <= $close_time || isset($_GET['manual_start'])) {
                // Move waitlisted players to active spots if needed.
                move_waitlist_to_roster($today);
                echo "Roster for today has been updated.";
            }
        }
    } else {
        echo "This link is not active currently.";
    }

    return ob_get_clean();
}

function handle_player_checkin() {
    global $wpdb;
    $player_name = sanitize_text_field($_POST['player_name']);
    $names = explode(' ', $player_name, 2);
    $first_name = $names[0];
    $last_name = $names[1] ?? '';

    $table_name = $wpdb->prefix . 'participants_database';
    $player = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE first_name = %s AND last_name = %s",
        $first_name, $last_name
    ));

    if ($player) {
        return '<div class="updated"><p>' . esc_html($player_name) . ' is checked in as prepaid.</p></div>';
    }

    return '<div class="error"><p>Player not found, added to the waitlist.</p></div>';
}

function should_finalize_roster($today) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'participants_database';
    $checked_in_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE check_in_date = %s AND check_in_status = 'checked_in'",
        $today
    ));

    return $checked_in_count >= 20;
}

add_action('wp_enqueue_scripts', 'hockeysignin_enqueue_public_scripts');
add_shortcode('hockeysignin', 'hockeysignin_shortcode');

