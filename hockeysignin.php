<?php
/**
 * Plugin Name: Hockey Sign-in
 * Plugin URI: http://halifaxpickuphockey.com
 * Description: A custom sign-in and roster management system for hockey players, integrating with Participants Database.
 * Version: 1.0
 * Author: Jason Craig, ChatGPT 4, Tabnine & Gemini AI
 * Author URI: http://halifaxpickuphockey.com
 */

register_activation_hook(__FILE__, 'hockeysignin_activate');
function hockeysignin_activate() {
    // Activation code here: For now, we don't need to do anything specific upon activation.
}
add_action('admin_menu', 'hockeysignin_admin_menu');

function hockeysignin_search_players() {
    global $wpdb; // Get global class to interact with the database
    $term = $_GET['term']; // Retrieve the search term sent from the autocomplete function

    $query = $wpdb->prepare("SELECT CONCAT(first_name, ' ', last_name) AS label FROM {$wpdb->prefix}participants_database WHERE first_name LIKE %s OR last_name LIKE %s", '%' . $wpdb->esc_like($term) . '%', '%' . $wpdb->esc_like($term) . '%');
    $results = $wpdb->get_results($query);

    wp_send_json($results);
}
add_action('wp_ajax_hockeysignin_search_players', 'hockeysignin_search_players');


function hockeysignin_admin_menu() {
    add_menu_page(
        'Hockey Sign-in', // Page title
        'Hockey Sign-in', // Menu title
        'manage_options', // Capability required to see this page
        'hockeysignin', // Menu slug
        'hockeysignin_admin_page', // Function that displays the page content
        'dashicons-admin-users', // Icon URL
        25 // Position in the menu
    );
}

function hockeysignin_enqueue_scripts($hook) {
    // Only add to the admin page of our plugin
    if ('toplevel_page_hockeysignin' !== $hook) {
        return;
    }

    // Enqueue jQuery UI for autocomplete
    wp_enqueue_script('jquery-ui-autocomplete');

    // Enqueue your custom JavaScript file
    wp_enqueue_script(
        'hockeysignin-autocomplete-script', // Handle for your script
        plugins_url('js/hockeysignin-autocomplete.js', __FILE__), // Path to your script file
        array('jquery-ui-autocomplete'), // Dependencies, jQuery UI Autocomplete
        '1.0', // Version number for your script
        true // Whether to enqueue the script in the footer
    );

    // Enqueue the CSS for jQuery UI
    wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}
add_action('admin_enqueue_scripts', 'hockeysignin_enqueue_scripts');

function hockeysignin_admin_page() {
    echo '<div class="wrap">';
    echo '<h1>Hockey Player Check-In</h1>';

    // Check if the form was submitted and the nonce is verified
    if (isset($_POST['submit']) && check_admin_referer('hockey_signin_checkin')) {
        global $wpdb;

        // Sanitize and retrieve the player name from input
        $player_name = isset($_POST['player_name']) ? sanitize_text_field($_POST['player_name']) : '';
        // Split the player name into first and last names assuming the first word is the first name and the rest is the last name
        $names = explode(' ', $player_name, 2);
        $first_name = $names[0];
        $last_name = isset($names[1]) ? $names[1] : '';

        // Query the database to find the player
        $table_name = $wpdb->prefix . 'participants_database';
        $player = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE first_name = %s AND last_name = %s",
            $first_name, $last_name
        ));

        if ($player) {
            // Player found, considered as prepaid
            echo '<div class="updated"><p>' . esc_html($player_name) . ' is checked in as prepaid.</p></div>';
        } else {
            // Player not found, considered as waitlist
            echo '<div class="error"><p>Player not found, added to the waitlist.</p></div>';
            // Here you can add logic to handle adding the player to the waitlist
        }
    }

    // Display the check-in form
    echo '<form method="post" action="">';
    wp_nonce_field('hockey_signin_checkin');
    echo '<table class="form-table">
            <tr valign="top">
                <th scope="row">Player Name:</th>
                <td><input type="text" name="player_name" id="player_name" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <td>' . submit_button('Check In') . '</td>
            </tr>
        </table>';
    echo '</form>';
    echo '</div>';
}
