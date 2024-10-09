<?php
function process_waitlist_at_6pm() {
    // Get current date and time
    $current_date = current_time('Y-m-d');
    $current_time = current_time('H:i', true); // Get the current time in GMT

    // Convert current time to the local time zone
    $local_time = date('H:i', strtotime(current_time('mysql')));

    // Log the current time for debugging
    error_log("process_waitlist_at_6pm called at $local_time on $current_date");

    // Check if it's between 6:00pm and 6:10pm
    if ($local_time >= '18:00' && $local_time <= '18:10') {
        // Get the day of the week
        $day_of_week = date('l', strtotime($current_date));

        // Check if it's a game day (Tuesday, Thursday, Friday, or Saturday)
        $game_days = array('Tuesday', 'Thursday', 'Friday', 'Saturday');
        if (in_array($day_of_week, $game_days)) {
            try {
                // Attempt to move waitlisted players to the roster
                move_waitlist_to_roster($current_date);
                error_log("Waitlist processed successfully for $current_date");
            } catch (Exception $e) {
                // Log any errors that occur
                error_log("Error processing waitlist for $current_date: " . $e->getMessage());
            }
        } else {
            // Log if it's not a game day
            error_log("Not a game day. Waitlist not processed for $current_date");
        }
    } else {
        // Log if it's not within the time range
        error_log("Current time is $local_time. Waitlist processing not triggered.");
    }
}

if (!wp_next_scheduled('move_waitlist_to_roster_event')) {
    $local_time = new DateTime('18:00:00', new DateTimeZone(wp_timezone_string()));
    $utc_time = $local_time->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
    wp_schedule_event($utc_time, 'daily', 'move_waitlist_to_roster_event');
}