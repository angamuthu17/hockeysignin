<?php
/**
* Plugin Name: Hockey Sign-in
* Plugin URI: http://halifaxpickuphockey.com
* Description: A custom sign-in and roster management system for hockey players, integrating with Participants Database.
* Version: 1.0
 * Author: Jason Craig, ChatGPT 4o, Tabnine AI, Cursor Tab, Perplexity Pro
* Author URI: http://halifaxpickuphockey.com
*/

// Add autoloader at the very beginning
spl_autoload_register(function ($class) {
    // Quick check for our namespace prefix to avoid unnecessary logging
    $prefix = 'hockeysignin\\';
    $prefix_len = strlen($prefix);
    
    // If the class doesn't start with our prefix, return immediately
    if (strncmp($prefix, strtolower($class), $prefix_len) !== 0) {
        return;
    }
    
    // At this point, we know it's our namespace, so we can log
    error_log("Loading hockeysignin class: " . $class);
    
    // Get the relative class name
    $relative_class = substr($class, $prefix_len);
    
    // Convert namespace to file path
    $file = plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 
           str_replace('\\', DIRECTORY_SEPARATOR, strtolower($relative_class)) . '.php';
    
    error_log("Looking for file: " . $file);
    
    if (file_exists($file)) {
        require_once $file;
        error_log("Successfully loaded: " . $file);
    } else {
        error_log("File not found: " . $file);
    }
});

register_activation_hook(__FILE__, 'hockeysignin_activate');
function hockeysignin_activate() {
// Activation code here: For now, we don't need to do anything specific upon activation.
}

$include_files = [
'admin/admin-functions.php',
'public/public-functions.php',
'includes/helper-functions.php',
    'includes/roster-functions.php',
    'includes/scheduled-tasks.php',
]
;

foreach ($include_files as $file) {
$file_path = plugin_dir_path(__FILE__) . $file;
if (file_exists($file_path)) {
        include_once $file_path;
        require_once $file_path;
} else {
error_log("Failed to include: {$file_path}");
}
}

// Schedule the daily roster creation event if it's not already scheduled
if (!wp_next_scheduled('create_daily_roster_files_event')) {
    wp_schedule_event(time(), 'daily', 'create_daily_roster_files_event');
}

// Hook the function to the scheduled event
add_action('create_daily_roster_files_event', function() {
    $current_date = current_time('Y-m-d');
    create_next_game_roster_files($current_date);
}, 10, 1);

// Schedule the 6pm waitlist movement event if it's not already scheduled
if (!wp_next_scheduled('move_waitlist_to_roster_event')) {
    wp_schedule_event(strtotime('18:00:00'), 'daily', 'move_waitlist_to_roster_event');
}
// Hook the function to the 6pm scheduled event
add_action('move_waitlist_to_roster_event', 'process_waitlist_at_6pm', 10, 0);

// Ensure the events are cleared upon deactivation
register_deactivation_hook(__FILE__, 'hockeysignin_deactivation');
function hockeysignin_deactivation() {
    wp_clear_scheduled_hook('create_daily_roster_files_event');
    wp_clear_scheduled_hook('move_waitlist_to_roster_event');
}