<?php
function process_waitlist_at_6pm() {
    $current_date = current_time('Y-m-d');
    $day_of_week = date('l', strtotime($current_date));
    $day_directory_map = get_day_directory_map($current_date);
    $day_directory = $day_directory_map[$day_of_week] ?? null;
    $formatted_date = date('D_M_j', strtotime($current_date));
    $season = get_current_season($current_date);
    $file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";
    
    // Define $local_time
    $local_time = current_time('H:i');

    // Add error logs for debugging
    error_log("Current season: " . $season);
    error_log("Day directory map: " . print_r($day_directory_map, true));
    error_log("process_waitlist_at_6pm called at 18:00 on {$current_date}");
    error_log("process_waitlist_at_6pm called at $local_time on $current_date");
    if (file_exists($file_path)) {
        move_waitlist_to_roster($current_date);
        error_log("Waitlist processed successfully for {$current_date}");
    } else {
        error_log("Roster file not found: {$file_path}");
    }
}

if (!wp_next_scheduled('move_waitlist_to_roster_event')) {
    $local_time = new DateTime('18:00:00', new DateTimeZone(wp_timezone_string()));
    $utc_time = $local_time->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
    wp_schedule_event($utc_time, 'daily', 'move_waitlist_to_roster_event');
}
