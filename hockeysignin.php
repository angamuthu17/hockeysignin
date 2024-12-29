<?php
/**
* Plugin Name: Hockey Sign-in
* Plugin URI: http://halifaxpickuphockey.com and Test URL : https://testhph.joinapj.com/
* Description: A custom sign-in and roster management system for hockey players, integrating with Participants Database.
* Version: 1.0
 * Author: Testing by Anga. Thanks to Jason Craig, ChatGPT 4o, Tabnine AI, Cursor Tab, Perplexity Pro
* Author Test URI: https://testhph.joinapj.com/
*/

// Add filter to suppress specific debug messages
add_filter('doing_it_wrong_trigger_error', function($trigger_error, $function) {
    if ($function === '_load_textdomain_just_in_time') {
        return false;
    }
    return $trigger_error;
}, 10, 2);

// Load helper functions first
require_once plugin_dir_path(__FILE__) . 'includes/helper-functions.php';

// Load roster functions next since many other files depend on it
require_once plugin_dir_path(__FILE__) . 'includes/roster-functions.php';

// Keep autoloader after helper functions are loaded
spl_autoload_register(function ($class) {
    // Quick check for our namespace prefix to avoid unnecessary logging
    $prefix = 'hockeysignin\\';
    $prefix_len = strlen($prefix);
    
    // If the class doesn't start with our prefix, return immediately
    if (strncmp($prefix, strtolower($class), $prefix_len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $prefix_len);
    
    // Convert namespace to file path
    $file = plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 
           str_replace('\\', DIRECTORY_SEPARATOR, strtolower($relative_class)) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        hockey_log("Loaded class file: " . $relative_class, 'debug');
    } else {
        hockey_log("Failed to load class file: " . $relative_class, 'error');
    }
});

// Move initialization code into plugins_loaded hook
add_action('plugins_loaded', function() {
    $include_files = [
        'admin/admin-functions.php',
        'public/public-functions.php',
        'includes/scheduled-tasks.php',
    ];

    foreach ($include_files as $file) {
        $file_path = plugin_dir_path(__FILE__) . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            hockey_log("Failed to include: {$file_path}", 'error');
        }
    }
});

// Move scheduling code into init hook
add_action('init', function() {
    if (!wp_next_scheduled('create_daily_roster_files_event')) {
        wp_schedule_event(time(), 'daily', 'create_daily_roster_files_event');
    }
    
    if (!wp_next_scheduled('move_waitlist_to_roster_event')) {
        wp_schedule_event(strtotime('18:00:00'), 'daily', 'move_waitlist_to_roster_event');
    }
});

// Keep these hooks outside since they need to be registered early
register_activation_hook(__FILE__, 'hockeysignin_activate');
register_deactivation_hook(__FILE__, 'hockeysignin_deactivation');

function hockeysignin_activate() {
    // Activation code here: For now, we don't need to do anything specific upon activation.
}

function hockeysignin_deactivation() {
    wp_clear_scheduled_hook('create_daily_roster_files_event');
    wp_clear_scheduled_hook('move_waitlist_to_roster_event');
}
