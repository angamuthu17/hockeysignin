<?php
namespace hockeysignin\Core;

class Config {
    private static $instance = null;
    private $config = [];
    private $seasons = [];

    private function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->seasons = require __DIR__ . '/../config/seasons.php';
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function getSeason($date) {
        $month_day = date('m-d', strtotime($date));
        
        foreach ($this->seasons as $season => $details) {
            $start = $details['start'];
            $end = $details['end'];
            
            if ($this->isDateInRange($month_day, $start, $end)) {
                return $details;
            }
        }
        
        return null;
    }

    private function isDateInRange($date, $start, $end) {
        if ($start <= $end) {
            return $date >= $start && $date <= $end;
        }
        // Handles wrapping around year end (e.g., Oct-Mar season)
        return $date >= $start || $date <= $end;
    }

    public function getSeasonFolder($date) {
        $year = date('Y', strtotime($date));
        $season_details = $this->getSeason($date);
        
        if (!$season_details) {
            return null;
        }
        
        $folder_format = $season_details['folder_format'];
        return str_replace('{year}', $year, $folder_format);
    }
}
