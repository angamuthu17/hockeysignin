<?php

// Include the roster functions file
require_once plugin_dir_path(__FILE__) . '../includes/roster-functions.php';

// Handle player check-in and check-out
function hockeysignin_handle_form_submission() {
    if (isset($_POST['player_name']) && isset($_POST['action'])) {
        $player_name = sanitize_text_field($_POST['player_name']);
        $action = sanitize_text_field($_POST['action']);
        $date = current_time('Y-m-d');

        if ($action === 'checkin') {
            check_in_player($date, $player_name);
        } elseif ($action === 'checkout') {
            check_out_player($player_name);
        }
    }
}

function display_next_game_date() {
    $next_game_date = get_next_game_date();
    echo "The next scheduled skate date is " . date('l, F jS', strtotime($next_game_date)) . ".";
}
add_action('init', 'hockeysignin_handle_form_submission');

// Enqueue necessary scripts and styles for the public-facing parts
function hockeysignin_enqueue_public_scripts() {
    wp_enqueue_style('hockeysignin-styles', plugin_dir_url(__FILE__) . 'styles.css');
    wp_enqueue_script('hockeysignin-scripts', plugin_dir_url(__FILE__) . 'scripts.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'hockeysignin_enqueue_public_scripts');

// Shortcode for displaying the sign-in form and handling submissions
function hockeysignin_shortcode() {
    ob_start();
    ?>
    <div class="hockeysignin-container">
        <form method="post" action="">
            <label for="player_name">Player Name:</label>
            <input type="text" id="player_name" name="player_name" required>
            <button type="submit" name="action" value="checkin">Check In</button>
            <button type="submit" name="action" value="checkout">Check Out</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('hockeysignin', 'hockeysignin_shortcode');

// Shortcode for displaying the roster
add_shortcode('hockeysignin_roster', 'display_roster');
