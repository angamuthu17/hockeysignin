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

$include_files = [
    'admin/admin-functions.php',
    'public/public-functions.php',
    'includes/helper-functions.php',
    'includes/roster-functions.php'
];

foreach ($include_files as $file) {
    $file_path = plugin_dir_path(__FILE__) . $file;
    if (file_exists($file_path)) {
        include_once $file_path;
    } else {
        error_log("Failed to include: {$file_path}");
    }
}
