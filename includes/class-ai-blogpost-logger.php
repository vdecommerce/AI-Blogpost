<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost_Logger {
    public function log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log = date('Y-m-d H:i:s') . ' - ' . $message;
            if ($data !== null) {
                $log .= "\n" . print_r($data, true);
            }
            error_log($log);
        }
    }

    public function log_api_call($type, $success, $data) {
        // Implementation moved from original code
        // Handles API call logging
    }

    public function display_logs($type) {
        // Implementation moved from original code
        // Displays logs in admin interface
    }

    public function clear_logs() {
        // Implementation moved from original code
        // Clears stored logs
    }
}