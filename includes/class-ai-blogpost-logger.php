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
        $logs = get_option('ai_blogpost_api_logs', array());
        
        $logs[] = array(
            'time' => time(),
            'type' => $type,
            'success' => $success,
            'data' => array_merge($data, array(
                'timestamp' => date('Y-m-d H:i:s'),
                'request_id' => uniqid()
            ))
        );
        
        if (count($logs) > 20) {
            $logs = array_slice($logs, -20);
        }
        
        $this->log('API Call:', array(
            'type' => $type,
            'success' => $success,
            'data' => $data
        ));
        
        update_option('ai_blogpost_api_logs', $logs);
    }

    public function display_logs($type) {
        // Implementation moved from original code
        // Displays logs in admin interface
    }

    public function clear_logs() {
        delete_option('ai_blogpost_api_logs');
    }
}