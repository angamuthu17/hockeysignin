<?php
function hockeysignin_add_admin_menu() {
    add_menu_page('Hockey Sign-in', 'Hockey Sign-in', 'manage_options', 'hockeysignin', 'hockeysignin_admin_page', 'dashicons-admin-users', 25);
}

function hockeysignin_admin_page() {
    echo '<div class="wrap"><h1>Hockey Player Check-In</h1>';
    // Admin page form handling and display code here...
    echo '</div>';
}

add_action('admin_menu', 'hockeysignin_add_admin_menu');

