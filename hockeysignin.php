<?php
/**
 * Plugin Name: Hockey Sign-in
 * Plugin URI: http://halifaxpickuphockey.com
 * Description: A custom sign-in and roster management system for hockey players, integrating with Participants Database.
 * Version: 1.0
 * Author: Jason Craig, ChatGPT 4, Tabnine & Gemini AI
 * Author URI: http://halifaxpickuphockey.com
 */ 

include_once 'admin/admin-functions.php';
include_once 'public/public-functions.php';
include_once 'includes/helper-functions.php';

register_activation_hook(__FILE__, 'hockeysignin_activate');
function hockeysignin_activate() {
    // Activation code here: For now, we don't need to do anything specific upon activation.
}

