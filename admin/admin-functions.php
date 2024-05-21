<?php
function hockeysignin_add_admin_menu() {
    add_menu_page('Hockey Sign-in', 'Hockey Sign-in', 'manage_options', 'hockeysignin', 'hockeysignin_admin_page', 'dashicons-admin-users', 25);
}

function hockeysignin_admin_page() {
    echo '<div class="wrap"><h1>Hockey Player Check-In</h1>';

    // Check if manual start was triggered
    if (isset($_POST['manual_start'])) {
        // Optionally perform any initialization logic here
        // For demonstration, just confirming the action
        echo '<div class="updated"><p>Manual start initialized for today\'s game.</p></div>';
        // Simulate the start of the check-in process
        do_action('simulate_game_day_start');
    }

    // Display the button to manually start the game day process
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="manual_start" value="1">';
    echo '<input type="submit" class="button-primary" value="Start Today\'s Game">';
    echo '</form>';

    echo '</div>';
}

add_action('admin_menu', 'hockeysignin_add_admin_menu');
