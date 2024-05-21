<?php
// Functions to manage the hockey roster files

function initialize_daily_roster($day, $today) {
    // Use the absolute path to the plugin directory and then navigate to the rosters
    $base_path = plugin_dir_path(__DIR__) . "../rosters/"; // Ensure this path aligns with your plugin structure

    // General template path
    $template_path = $base_path . "roster template.txt";

    // Define the directory for today's roster based on the day
    $day_directory_map = [
        'Tuesday' => 'Tuesday 1030pm Forum',
        'Thursday' => 'Thursday 1000pm Civic',
        'Friday' => 'Friday 1030pm Forum',
        'Saturday' => 'Saturday 1030pm Forum',
    ];

    if (!isset($day_directory_map[$day])) {
        error_log("Invalid day provided for roster initialization: {$day}");
        return;
    }

    $day_directory = $day_directory_map[$day];
    $roster_directory = $base_path . "Spring 2024/{$day_directory}";

    // Ensure the directory exists
    if (!is_dir($roster_directory)) {
        mkdir($roster_directory, 0755, true);
    }

    // Adjust the date format to match "Pickup Roster - Thur May 9"
    $formatted_date = date('D M j', strtotime($today)); // "Thu May 9" format
    $roster_file_name = "Pickup Roster - {$day} {$formatted_date}.txt";
    $roster_path = "{$roster_directory}/{$roster_file_name}";

    // Create the roster file by copying the template if it doesn't exist
    if (!file_exists($roster_path) && file_exists($template_path)) {
        if (!copy($template_path, $roster_path)) {
            error_log("Failed to copy roster template to: {$roster_path}");
        }
    }
}

function is_prepaid($player_name) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'participants_database'; // Ensure the correct table name
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE name = %s", $player_name));
    return !!$player;
}

function create_daily_link() {
    $base_url = get_site_url();
    $check_in_page_slug = '/check-in';  // Ensure this is the slug of your check-in page
    $date = date('D M j');
    return $base_url . $check_in_page_slug . '?date=' . $date;
}

function update_roster($date, $player_name, $prepaid) {
    // Day calculation and day_directory_map usage
    $day = date('l', strtotime($date));
    $day_directory_map = [
        'Tuesday' => 'Tuesday 1030pm Forum',
        'Thursday' => 'Thursday 1000pm Civic',
        'Friday' => 'Friday 1030pm Forum',
        'Saturday' => 'Saturday 1030pm Forum',
    ];
    $file_path = plugin_dir_path(__DIR__) . "../rosters/Spring 2024/{$day_directory_map[$day]}/Pickup Roster - {$date}.txt";
    $roster = file_get_contents($file_path);
    
    if ($prepaid) {
        // Find the first empty position and replace it with the player's name
        $positions = ['F -', 'D -', 'Goal:'];
        foreach ($positions as $position) {
            if (strpos($roster, "{$position} \n") !== false) {
                $roster = preg_replace("/{$position} \n/", "{$position} {$player_name}\n", $roster, 1);
                break;
            }
        }
    } else {
        // Add to WL if not already on the list
        if (strpos($roster, $player_name) === false) {
            $roster .= "WL: {$player_name}\n";
        }
    }
    file_put_contents($file_path, $roster);
}

function toggle_player_status($date, $player_name) {
    $day = date('l', strtotime($date));
    $day_directory_map = [
        'Tuesday' => 'Tuesday 1030pm Forum',
        'Thursday' => 'Thursday 1000pm Civic',
        'Friday' => 'Friday 1030pm Forum',
        'Saturday' => 'Saturday 1030pm Forum',
    ];
    $file_path = plugin_dir_path(__DIR__) . "../rosters/Spring 2024/{$day_directory_map[$day]}/Pickup Roster - {$date}.txt";
    $roster = file_get_contents($file_path);

    // Determine if the player is currently on the roster or waitlist
    if (strpos($roster, $player_name) !== false) {
        // Check if they are marked as active
        if (preg_match("/\b{$player_name}\b/", $roster)) {
            // If active, comment them out to mark as checked out
            $roster = str_replace($player_name, "checked-out {$player_name}", $roster);
        } else {
            // If checked out, reactivate them
            $roster = str_replace("checked-out {$player_name}", $player_name, $roster);
        }
        file_put_contents($file_path, $roster);
    }
}

function move_waitlist_to_roster($date) {
    $day = date('l', strtotime($date));
    $day_directory_map = [
        'Tuesday' => 'Tuesday 1030pm Forum',
        'Thursday' => 'Thursday 1000pm Civic',
        'Friday' => 'Friday 1030pm Forum',
        'Saturday' => 'Saturday 1030pm Forum',
    ];
    $file_path = plugin_dir_path(__DIR__) . "../rosters/Spring 2024/{$day_directory_map[$day]}/Pickup Roster - {$date}.txt";
    $roster = file_get_contents($file_path);
    $waitlisted = [];

    // Extract waitlisted players
    preg_match_all('/WL: (.*)/', $roster, $matches);
    if (!empty($matches[1])) {
        $waitlisted = $matches[1];
    }

    // Fill open slots with waitlisted players
    foreach ($waitlisted as $index => $player) {
        foreach (['F -', 'D -', 'Goal:'] as $position) {
            if (strpos($roster, "{$position} ") !== false) {
                $roster = preg_replace("/{$position} \n/", "{$position} {$player}\n", $roster, 1);
                unset($waitlisted[$index]);
            }
        }
    }

    file_put_contents($file_path, $roster);
}

function finalize_roster_at_10pm($date) {
    $day = date('l', strtotime($date));
    $day_directory_map = [
        'Tuesday' => 'Tuesday 1030pm Forum',
        'Thursday' => 'Thursday 1000pm Civic',
        'Friday' => 'Friday 1030pm Forum',
        'Saturday' => 'Saturday 1030pm Forum',
    ];
    $file_path = plugin_dir_path(__DIR__) . "../rosters/Spring 2024/{$day_directory_map[$day]}/Pickup Roster - {$date}.txt";
    $roster = file_get_contents($file_path);
    if (!$roster) {
        // Log error or handle the error scenario
        error_log("Unable to read roster file: {$file_path}");
        return;
    }
    $roster .= "\n-- Roster Finalized --";
    if (file_put_contents($file_path, $roster) === false) {
        // Log error or handle the error scenario
        error_log("Unable to finalize roster file: {$file_path}");
    }
}
