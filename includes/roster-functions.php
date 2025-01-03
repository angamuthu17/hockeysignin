<?php
// Functions to manage the hockey roster files

// Define the game schedule
$game_schedule = ['Tuesday', 'Thursday', 'Friday', 'Saturday'];

function get_current_season($date = null) {
    if ($date === null) {
        $date = current_time('Y-m-d');
    }
    
    try {
        if (!class_exists('hockeysignin\Core\Config')) {
            throw new Exception("Config class not found");
        }
        
        $config = hockeysignin\Core\Config::getInstance();
        
        $season_folder = $config->getSeasonFolder($date);
        
        if (!$season_folder) {
            throw new Exception("No season folder returned from config");
        }
        
        return $season_folder;
        
    } catch (Exception $e) {
        $year = date('Y', strtotime($date));
        $month_day = date('m-d', strtotime($date));
        
        if ($month_day >= '10-01' || $month_day < '04-01') {
            return "RegularSeason{$year}-" . ($year + 1);
        } elseif ($month_day >= '04-01' && $month_day < '06-01') {
            return "Spring{$year}";
        } else {
            return "Summer{$year}";
        }
    }
}

function get_day_directory_map($date) {
$year = date('Y', strtotime($date));
$month_day = date('m-d', strtotime($date));

if ($month_day >= '10-01' || $month_day < '04-01') {
// Regular Season
return [
'Tuesday' => 'Tues1030Forum',
'Thursday' => 'Thur1030Civic',
'Friday' => 'Fri1030Forum',
'Saturday' => 'Sat1030Forum',
];
} elseif ($month_day >= '04-01' && $month_day < '06-01') {
// Spring Season
return [
'Tuesday' => 'Tues1030Civic',
'Thursday' => 'Thur1030Civic',
'Friday' => 'Fri1030Civic',
'Saturday' => 'Sat1030Civic',
];
} else {
// Summer Season
return [
'Tuesday' => 'Tues1030Civic',
'Thursday' => 'Thur1030Civic',
'Friday' => 'Fri1030Civic',
'Saturday' => 'Sat1030Civic',
];
}
}

function calculate_next_game_day() {
// Get the current day of the week (1 for Monday, ..., 7 for Sunday)
$current_day_of_week = date('N');
$current_time = current_time('H:i');

// Define the game days (2 for Tuesday, 4 for Thursday, 5 for Friday, 6 for Saturday)
$game_days = [2, 4, 5, 6];

// Check if today is a game day and the current time is before 8 AM
if (in_array($current_day_of_week, $game_days) && $current_time < '08:00') {
return date('Y-m-d');
}

// Special case: From Saturday 11pm until Tuesday 7:59am, report next game as Tuesday
if (($current_day_of_week == 6 && $current_time >= '23:00') || ($current_day_of_week == 7) || ($current_day_of_week == 1) || ($current_day_of_week == 2 && $current_time < '08:00')) {
return date('Y-m-d', strtotime('next Tuesday'));
}

// Find the next game day
foreach ($game_days as $day) {
if ($day > $current_day_of_week) {
// Calculate the number of days until the next game day
$days_until_game_day = $day - $current_day_of_week;
// Return the next game day
return date('Y-m-d', strtotime("+$days_until_game_day days"));
}
}

// If no game day was found in the current week, return the first game day of the next week
$days_until_next_week = 7 - $current_day_of_week + $game_days[0];
return date('Y-m-d', strtotime("+$days_until_next_week days"));
}

// Check if the current time is within the game day window
function is_game_day() {
$current_time = current_time('H:i');
$start_time = '08:00';
$end_time = '23:59';

return ($current_time >= $start_time && $current_time <= $end_time);
}

function create_next_game_roster_files($date) {
$day_directory_map = get_day_directory_map($date);
$day_of_week = date('l', strtotime($date));
$day_directory = $day_directory_map[$day_of_week] ?? null;

if (!$day_directory) {
hockey_log("No directory mapping found for date: {$date}", 'error');
return;
}

$formatted_date = date('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (!file_exists($file_path)) {
$template_path = realpath(__DIR__ . "/../rosters/roster_template.txt");
if (file_exists($template_path)) {
copy($template_path, $file_path);
chmod($file_path, 0664); // Set file permissions to 664
hockey_log("Roster file created: {$file_path}", 'debug');
} else {
hockey_log("Roster template not found: {$template_path}", 'error');
}
}
}

function check_in_player($date = null, $player_name = null) {
global $wpdb, $game_schedule;

if (!$date) {
$date = current_time('Y-m-d');
$day_of_week = date('l', strtotime($date));
if (!in_array($day_of_week, $game_schedule)) {
$date = calculate_next_game_day();
}
}

if (!$player_name) {
hockey_log("No player name provided for check-in.", 'error');
return;
}

    // Check for profanity
    if (contains_profanity($player_name)) {
        hockey_log("Profanity detected in player name: {$player_name}", 'error');
        echo '<div class="error"><p>Inappropriate language detected. Please use a different name.</p></div>';
        return;
    }

$day_directory_map = get_day_directory_map($date);
$day_of_week = date('l', strtotime($date));
$day_directory = $day_directory_map[$day_of_week] ?? null;

if (!$day_directory) {
hockey_log("No directory mapping found for date: {$date}", 'error');
return;
}

$formatted_date = date('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (file_exists($file_path)) {
$roster = file_get_contents($file_path);
if (strpos($roster, $player_name) !== false) {
// Player is already checked in, offer check-out option
echo '<div class="error"><p>' . esc_html($player_name) . ' is already checked in. Do want to check them out?</p>';
echo '<form method="post" action="">';
echo '<input type="hidden" name="player_name" value="' . esc_attr($player_name) . '">';
echo '<input type="hidden" name="confirm_checkout" value="1">';
echo '<input type="submit" class="button-primary" value="Check Out Player">';
echo '</form></div>';
return;
}
} else {
// Prompt the user to create the file
echo '<div class="error"><p>Roster file not found for ' . $date . '. Do you want to create it?</p>';
echo '<form method="post" action="">';
echo '<input type="hidden" name="manual_start" value="1">';
echo '<input type="hidden" name="date" value="' . $date . '">';
echo '<input type="submit" class="button-primary" value="Create Roster File">';
echo '</form></div>';
return;
}

$player = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_participants_database WHERE CONCAT(`first_name`, ' ', `last_name`) = %s", $player_name));

if ($player) {
$prepaid = true;
$position = $player->position;
// Check if the player is eligible for the current night
$active_nights = maybe_unserialize($player->active_nights);
if (!is_array($active_nights) || !in_array($day_of_week, $active_nights)) {
echo '<div class="error"><p>' . esc_html($player_name) . ' is not eligible to check in on ' . $day_of_week . '.</p></div>';
return;
}
} else {
$prepaid = false;
$position = null;
}

$confirmation_message = update_roster($date, $player_name, $prepaid, $position);
echo '<div class="updated"><p>' . esc_html($confirmation_message) . '</p></div>';
}

function check_out_player($player_name) {
$date = current_time('Y-m-d');
$day_directory_map = get_day_directory_map($date);
$day_of_week = date('l', strtotime($date));
$day_directory = $day_directory_map[$day_of_week] ?? null;
$formatted_date = date('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (file_exists($file_path)) {
$roster = file_get_contents($file_path);
$roster_lines = explode("\n", $roster);
$updated_roster = [];

foreach ($roster_lines as $line) {
// Check if the line contains the player's name
if (strpos($line, $player_name) !== false) {
// Remove only the player's name, keep the position marker
$line = preg_replace('/\b' . preg_quote($player_name, '/') . '\b/', '', $line);
$line = trim($line); // Remove any extra spaces
}
$updated_roster[] = $line;
}

file_put_contents($file_path, implode("\n", $updated_roster));
hockey_log("Player checked out: {$player_name}", 'debug');
} else {
hockey_log("Roster file not found: {$file_path}", 'error');
}
}

function update_roster($date, $player_name, $prepaid, $position = null) {
global $wpdb;

if (!$date) {
$date = current_time('Y-m-d');
}

$day_directory_map = get_day_directory_map($date);
$day_of_week = date('l', strtotime($date));
$day_directory = $day_directory_map[$day_of_week] ?? null;

if (!$day_directory) {
hockey_log("No directory mapping found for date: {$date}", 'error');
return "No directory mapping found for date: {$date}";
}

$formatted_date = date('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (!file_exists($file_path)) {
hockey_log("Roster file not found: {$file_path}", 'error');
return "Roster file not found: {$file_path}";
}
$roster = file_get_contents($file_path);

// Check if the player is already on the roster
if (strpos($roster, $player_name) !== false) {
hockey_log("Player already on the roster: {$player_name}", 'error');
return "{$player_name} is already checked in.";
}

$player = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_participants_database WHERE CONCAT(`first_name`, ' ', `last_name`) = %s", $player_name));

if ($prepaid && $player) {
hockey_log("Player found in database: {$player_name}, Position: {$player->position}", 'debug');

$positions = [];
if ($player->position == 'Goalie') {
$positions = ['Goal:'];
} elseif ($player->position == 'Forward') {
$positions = ['F-'];
} elseif ($player->position == 'Defence') {
$positions = ['D-'];
}

$open_spots = [];
foreach ($positions as $position) {
if (preg_match_all("/^{$position}\s*\n/m", $roster, $matches, PREG_OFFSET_CAPTURE)) {
foreach ($matches[0] as $match) {
$open_spots[] = $match[1];
}
}
}

if (!empty($open_spots)) {
$random_spot = $open_spots[array_rand($open_spots)];
$roster = substr_replace($roster, "{$positions[0]} {$player_name}\n", $random_spot, strlen("{$positions[0]}\n"));
} else {
hockey_log("No empty position found, adding to waitlist: {$player_name}", 'debug');
// Add prepaid player to the top of the waitlist
$roster = preg_replace('/(WL: .*\n)/', "WL: {$player_name}\n$1", $roster, 1);
       }
   } else {
       if (strpos($roster, $player_name) === false) {
           hockey_log("Player not prepaid or not found in database, adding to waitlist: {$player_name}", 'debug');
           $roster .= "WL: {$player_name}\n";
       }
   }
   file_put_contents($file_path, $roster);
   return "{$player_name} has been successfully checked in.";
}

function move_waitlist_to_roster($date) {
   $day_directory_map = get_day_directory_map($date);
   $day_of_week = date_i18n('l', strtotime($date));
   $day_directory = $day_directory_map[$day_of_week] ?? null;

   // Add error logs for debugging
   hockey_log("Current season: " . get_current_season($date), 'debug');
hockey_log("Day directory map: " . print_r($day_directory_map, true), 'debug');

if (!$day_directory) {
hockey_log("No directory mapping found for date: {$date}", 'error');
return;
}

$formatted_date = date_i18n('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (!file_exists($file_path)) {
hockey_log("Roster file not found: {$file_path}", 'error');
return;
}
$roster = file_get_contents($file_path);

// Check if there are any open slots
$positions = ['F -', 'D -', 'Goal:'];
$open_slots = false;
foreach ($positions as $position) {
if (strpos($roster, "{$position} \n") !== false || strpos($roster, "{$position}\n") !== false) {
$open_slots = true;
break;
}
}

if (!$open_slots) {
hockey_log("No open slots available on the roster for date: {$date}", 'error');
return;
}

$waitlisted = [];

// Extract waitlisted players
preg_match_all('/WL: (.*)/', $roster, $matches);
if (!empty($matches[1])) {
$waitlisted = $matches[1];
}

// Fill open slots with waitlisted players
foreach ($positions as $position) {
foreach ($waitlisted as $index => $player) {
if (strpos($roster, "{$position} \n") !== false) {
$roster = preg_replace("/{$position} \n/", "{$position} {$player}\n", $roster, 1);
unset($waitlisted[$index]);
}
}
}

// Remove waitlisted players who have been moved to open slots
$roster = preg_replace('/WL: .*\n/', '', $roster);

// Add remaining waitlisted players back to the end of the roster
foreach ($waitlisted as $player) {
$roster .= "WL: {$player}\n";
}

file_put_contents($file_path, $roster);
}

function finalize_roster_at_930pm($date) {
$day_directory_map = get_day_directory_map($date);
$day_of_week = date_i18n('l', strtotime($date));
$day_directory = $day_directory_map[$day_of_week] ?? null;

if (!$day_directory) {
hockey_log("No directory mapping found for date: {$date}", 'error');
return;
}

$formatted_date = date_i18n('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (!file_exists($file_path)) {
hockey_log("Roster file not found: {$file_path}", 'error');
return;
}
$roster = file_get_contents($file_path);
$roster .= "\n-- Roster Finalized at 9:30 PM --";

if (file_put_contents($file_path, $roster) === false) {
hockey_log("Unable to finalize roster file: {$file_path}", 'error');
}
}

// Fetch the current roster from the appropriate file
function get_current_roster() {
global $day_directory_map;
$date = current_time('Y-m-d');
$day_of_week = date('l', strtotime($date));
$day_directory = $day_directory_map[$day_of_week] ?? null;
$formatted_date = date('D_M_j', strtotime($date));
$file_path = realpath(__DIR__ . "/../rosters/") . "/Summer2024/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";
if (file_exists($file_path)) {
return file_get_contents($file_path);
} else {
$next_game_day = calculate_next_game_day();
$next_game_day_formatted = date_i18n('l, F jS', strtotime($next_game_day));
return "Our skates are Tuesday 10:30pm Forum, Thursday 10:30pm Civic, and Friday & Saturday 10:30pm Forum.<br><br>Check in begins at 8:00am for each skate";
}
}

// Display the roster or the next scheduled skate date
function display_roster() {
hockey_log("display_roster function called", 'debug'); // Debugging output
if (is_game_day()) {
$date = current_time('Y-m-d');
$day_of_week = date('l', strtotime($date));
$day_directory_map = get_day_directory_map($date);
$day_directory = $day_directory_map[$day_of_week] ?? null;
$formatted_date = date('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (file_exists($file_path)) {
$roster = file_get_contents($file_path);
hockey_log("Current roster: " . $roster, 'debug'); // Debugging output
return nl2br($roster);
} else {
hockey_log("Roster file not found: {$file_path}", 'error'); // Debugging output
return "Roster file not found for today. Our skates are Tuesday 10:30pm Forum, Thursday 10:30pm Civic, and Friday & Saturday 10:30pm Forum.<br><br>Check in begins at 8:00am for each skate.";
}
} else {
$next_game_day = calculate_next_game_day();
$next_game_day_formatted = date_i18n('l, F jS', strtotime($next_game_day));
hockey_log("Next scheduled skate date: " . $next_game_day, 'debug'); // Debugging output
return "Our skates are Tuesday 10:30pm Forum, Thursday 10:30pm Civic, and Friday & Saturday 10:30pm Forum.<br><br>Check in begins at 8:00am for each skate.<br><br> The next scheduled skate date is " . $next_game_day_formatted . ".";
}
}

function check_in_player_after_6pm($date, $player_name) {
global $wpdb;

$day_of_week = date('l', strtotime($date));
$day_directory_map = get_day_directory_map($date);
$day_directory = $day_directory_map[$day_of_week] ?? null;
$formatted_date = date('D_M_j', strtotime($date));
$season = get_current_season($date);
$file_path = realpath(__DIR__ . "/../rosters/") . "/{$season}/{$day_directory}/Pickup_Roster-{$formatted_date}.txt";

if (file_exists($file_path)) {
$roster = file_get_contents($file_path);

// Check for vacant spots
$positions = ['F -', 'D -', 'Goal:'];
$vacant_spot_found = false;
foreach ($positions as $position) {
if (strpos($roster, "{$position} \n") !== false) {
$roster = preg_replace("/{$position} \n/", "{$position} {$player_name}\n", $roster, 1);
$vacant_spot_found = true;
break;
}
}

if ($vacant_spot_found) {
file_put_contents($file_path, $roster);
return "You have been added to the roster. Please contact HPH admin on FB / Messenger, at payforhockey@hotmail.com or at 902-488-5590 to confirm your spot.";
} else {
return "No vacant spots available. You have been added to the waitlist.";
}
} else {
hockey_log("Roster file not found: {$file_path}", 'error');
return "Roster file not found for today.<br><br>Our skates are Tuesday 10:30pm Forum, Thursday 10:30pm Civic, and Friday & Saturday 10:30pm Forum.<br><br>Check in begins at 8:00am for each skate.";
}
}

function contains_profanity($text) {
    $banned_patterns = [
        '/\bfuck\b/i', '/fuck\w*/i',
        '/\bshit\b/i', '/shit\w*/i',
        '/\bbitch\b/i', '/bitch\w*/i',
        '/\basshole\b/i', '/ass\w*/i',
        '/\bdick\b/i', '/dick\w*/i',
        '/\bcunt\b/i', '/cunt\w*/i',
        '/\bslut\b/i', '/slut\w*/i',
        '/\bwhore\b/i', '/whore\w*/i',
        '/\bfag\b/i', '/fag\w*/i',
        '/\bnigger\b/i', '/nigg\w*/i',
        '/\bchink\b/i', '/chink\w*/i',
        '/\bspic\b/i', '/spic\w*/i',
        '/\bkike\b/i', '/kike\w*/i',
        '/\bgook\b/i', '/gook\w*/i',
        '/\bwop\b/i', '/wop\w*/i',
        '/\bdago\b/i', '/dago\w*/i',
        '/\bwetback\b/i', '/wetback\w*/i',
        // Add more patterns as needed
    ];

    foreach ($banned_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    return false;
}