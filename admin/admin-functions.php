<?php
function hockeysignin_add_admin_menu() {
    add_menu_page('Hockey Sign-in', 'Hockey Sign-in', 'manage_options', 'hockeysignin', 'hockeysignin_admin_page', 'dashicons-admin-users', 25);
    add_submenu_page('hockeysignin', 'Settings', 'Settings', 'manage_options', 'hockeysignin_settings', 'hockeysignin_settings_page');
}

function hockeysignin_settings_page() {
    ?>
    <div class="wrap">
        <h1>Hockey Sign-in Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('hockeysignin_settings_group');
            do_settings_sections('hockeysignin_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function hockeysignin_admin_page() {
    echo '<div class="wrap"><h1>Hockey Player Check-In</h1>';

    // Global variable to store the manually started next game date
    global $manually_started_next_game_date;

    $current_date = current_time('Y-m-d');
    $current_time = current_time('H:i');

    if (isset($_POST['manual_start'])) {
        if (!isset($_POST['hockeysignin_nonce']) || !wp_verify_nonce($_POST['hockeysignin_nonce'], 'hockeysignin_action')) {
            die('Security check failed');
        }

        $manually_started_next_game_date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : get_next_game_date();
        create_next_game_roster_files($manually_started_next_game_date);
        echo '<div class="updated"><p>Roster file created for ' . esc_html($manually_started_next_game_date) . '.</p></div>';
    }

    // Check if it's past 6 PM
    if ($current_time >= '18:00') {
        // Move waitlisted players to active spots if needed
        move_waitlist_to_roster($current_date);
        echo '<div class="updated"><p>Roster has been automatically updated at 6 PM.</p></div>';
    }

    if (isset($_POST['check_in_player'])) {
        if (!isset($_POST['hockeysignin_nonce']) || !wp_verify_nonce($_POST['hockeysignin_nonce'], 'hockeysignin_action')) {
            die('Security check failed');
        }

        $player_name = sanitize_text_field($_POST['player_name']);
        $date = isset($manually_started_next_game_date) ? $manually_started_next_game_date : (isset($_POST['date']) ? sanitize_text_field($_POST['date']) : null);
        $response = check_in_player($date, $player_name);
        echo esc_html($response);
    }

    if (isset($_POST['confirm_checkout'])) {
        if (!isset($_POST['hockeysignin_nonce']) || !wp_verify_nonce($_POST['hockeysignin_nonce'], 'hockeysignin_action')) {
            die('Security check failed');
        }

        $player_name = sanitize_text_field($_POST['player_name']);
        check_out_player($player_name);
        echo '<div class="updated"><p>' . esc_html($player_name) . ' has been checked out.</p></div>';
    }

    // Create a container for the forms and the roster
    echo '<div class="hockeysignin-container" style="display: flex;">';

    // Create a container for the forms
    echo '<div class="hockeysignin-forms" style="flex: 1; margin-right: 20px;">';
    echo '<form method="post" action="" onsubmit="return confirm(\'Would you like to create the next scheduled game day roster?\');">';
    wp_nonce_field('hockeysignin_action', 'hockeysignin_nonce');
    echo '<input type="hidden" name="manual_start" value="1">';
    echo '<input type="submit" class="button-primary" value="Start Next Game">';
    echo '</form>';

    echo '<h2>Manual Player Check-In</h2>';
    echo '<form method="post" action="">';
    wp_nonce_field('hockeysignin_action', 'hockeysignin_nonce');
    echo '<label for="player_name">Player Name:</label>';
    echo '<input type="text" name="player_name" required>';
    echo '<label for="date">Date (optional):</label>';
    echo '<input type="date" name="date">';
    echo '<input type="hidden" name="check_in_player" value="1">';
    echo '<input type="submit" class="button-primary" value="Check In Player">';
    echo '</form>';
    echo '</div>'; // Close the forms container

    // Create a container for the roster
    echo '<div class="hockeysignin-roster" style="flex: 1;">';
    echo '<h2>Current Roster</h2>';
    echo display_roster(isset($manually_started_next_game_date) ? $manually_started_next_game_date : null);
    echo '</div>'; // Close the roster container

    echo '</div>'; // Close the main container

    echo '</div>';
}

// Function to get the next game date
function get_next_game_date() {
    $today = current_time('Y-m-d');
    $day_of_week = date('N', strtotime($today)); // 1 (for Monday) through 7 (for Sunday)
    $current_time = current_time('H:i');

    // Define the game days (2 for Tuesday, 4 for Thursday, 5 for Friday, 6 for Saturday)
    $game_days = [2, 4, 5, 6];

    // Special case: From Saturday 11pm until Tuesday 7:59am, report next game as Tuesday
    if (($day_of_week == 6 && $current_time >= '23:00') || $day_of_week == 7 || $day_of_week == 1 || ($day_of_week == 2 && $current_time < '08:00')) {
        return date('Y-m-d', strtotime('next Tuesday', strtotime($today)));
    }

    // Special case: From Tuesday 11pm until Thursday 7:59am, report next game as Thursday
    if (($day_of_week == 2 && $current_time >= '23:00') || $day_of_week == 3 || ($day_of_week == 4 && $current_time < '08:00')) {
        return date('Y-m-d', strtotime('next Thursday', strtotime($today)));
    }

    // Special case: From Thursday 11pm until Friday 7:59am, report next game as Friday
    if (($day_of_week == 4 && $current_time >= '23:00') || ($day_of_week == 5 && $current_time < '08:00')) {
        return date('Y-m-d', strtotime('next Friday', strtotime($today)));
    }

    // Special case: From Friday 11pm until Saturday 7:59am, report next game as Saturday
    if (($day_of_week == 5 && $current_time >= '23:00') || ($day_of_week == 6 && $current_time < '08:00')) {
        return date('Y-m-d', strtotime('next Saturday', strtotime($today)));
    }

    // Default case: Determine the next game day based on the current day and time
    $next_game_day = null;
    foreach ($game_days as $game_day) {
        if ($game_day > $day_of_week) {
            $next_game_day = $game_day;
            break;
        }
    }

    if ($next_game_day === null) {
        $next_game_day = $game_days[0]; // Loop back to the first game day
    }

    return date('Y-m-d', strtotime('next ' . date('l', strtotime('Sunday +' . ($next_game_day - 1) . ' days')), strtotime($today)));
}

// Include the roster functions file
require_once plugin_dir_path(__FILE__) . '../includes/roster-functions.php';

add_action('admin_menu', 'hockeysignin_add_admin_menu');

function hockeysignin_register_settings() {
    register_setting('hockeysignin_settings_group', 'hockeysignin_off_state');
    register_setting('hockeysignin_settings_group', 'hockeysignin_custom_text');

    add_settings_section('hockeysignin_main_section', 'Main Settings', null, 'hockeysignin_settings');

    add_settings_field('hockeysignin_off_state', 'Turn Off Sign-in', 'hockeysignin_off_state_callback', 'hockeysignin_settings', 'hockeysignin_main_section');
    add_settings_field('hockeysignin_custom_text', 'Custom Text', 'hockeysignin_custom_text_callback', 'hockeysignin_settings', 'hockeysignin_main_section');
}

function hockeysignin_off_state_callback() {
    $checked = get_option('hockeysignin_off_state') ? 'checked' : '';
    echo '<input type="checkbox" name="hockeysignin_off_state" value="1" ' . $checked . '> Disable Sign-in';
}

function hockeysignin_custom_text_callback() {
    $text = get_option('hockeysignin_custom_text', 'Sign-in is currently disabled.');
    echo '<textarea name="hockeysignin_custom_text" rows="5" cols="50">' . esc_textarea($text) . '</textarea>';
}

add_action('admin_init', 'hockeysignin_register_settings');