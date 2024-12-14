<?php
function hockey_log($message, $level = 'debug') {
    // Only log errors and warnings by default
    if ($level === 'error' || $level === 'warning') {
        error_log("[HockeySignin][$level] $message");
    }
    
    // Debug messages only logged if WP_DEBUG is true
    if ($level === 'debug' && defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[HockeySignin][debug] $message");
    }
}
