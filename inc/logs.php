<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Log API calls and their results
 * 
 * @param string $type The type of API call (Text Generation or Image Generation)
 * @param bool $success Whether the call was successful
 * @param array $data Additional data about the call
 */
function ai_blogpost_log_api_call($type, $success, $data) {
    $logs = get_option('ai_blogpost_api_logs', array());
    
    // Add new log entry with details
    $logs[] = array(
        'time' => time(),
        'type' => $type,
        'success' => $success,
        'data' => array_merge($data, array(
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => uniqid()
        ))
    );
    
    // Keep only last 20 entries
    if (count($logs) > 20) {
        $logs = array_slice($logs, -20);
    }
    
    ai_blogpost_debug_log('Updating logs:', $logs);
    update_option('ai_blogpost_api_logs', $logs);
}

/**
 * Display API logs in the admin dashboard
 * 
 * @param string $type The type of logs to display (Text Generation or Image Generation)
 */
function display_api_logs($type) {
    $logs = get_option('ai_blogpost_api_logs', array());
    ai_blogpost_debug_log('Displaying logs for type:', $type);
    
    $filtered_logs = array_filter($logs, function($log) use ($type) {
        return isset($log['type']) && $log['type'] === $type;
    });
    
    if (empty($filtered_logs)) {
        echo '<div class="notice notice-warning"><p>No ' . esc_html($type) . ' logs found.</p></div>';
        return;
    }

    // Show most recent logs first
    $filtered_logs = array_reverse(array_slice($filtered_logs, -5));

    // Add table styles
    echo '<style>
        .log-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
            background: #fff;
        }
        .log-table th, .log-table td { 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #e1e1e1; 
        }
        .log-table th { 
            background: #f5f5f5; 
            font-weight: bold; 
        }
        .log-status.success { 
            color: #46b450; 
            font-weight: bold; 
        }
        .log-status.error { 
            color: #dc3232; 
            font-weight: bold; 
        }
        .log-details pre {
            margin: 5px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            overflow-x: auto;
        }
        .log-time {
            white-space: nowrap;
        }
    </style>';

    echo '<table class="log-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Time</th>';
    echo '<th>Status</th>';
    echo '<th>Category</th>';
    echo '<th>Details</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($filtered_logs as $log) {
        $status_class = $log['success'] ? 'success' : 'error';
        $status_text = $log['success'] ? '✓ Success' : '✗ Failed';
        
        echo '<tr>';
        echo '<td class="log-time">' . date('Y-m-d H:i:s', $log['time']) . '</td>';
        echo '<td class="log-status ' . $status_class . '">' . $status_text . '</td>';
        echo '<td>' . (isset($log['data']['category']) ? esc_html($log['data']['category']) : '-') . '</td>';
        echo '<td class="log-details">';
        
        if (isset($log['data']['status'])) {
            echo '<div><strong>Status:</strong> ' . esc_html($log['data']['status']) . '</div>';
        }
        if (isset($log['data']['prompt'])) {
            echo '<div><strong>Prompt:</strong><pre>' . esc_html($log['data']['prompt']) . '</pre></div>';
        }
        if (isset($log['data']['error'])) {
            echo '<div><strong>Error:</strong><pre>' . esc_html($log['data']['error']) . '</pre></div>';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

/**
 * Clear API logs
 */
function ai_blogpost_clear_logs() {
    if (isset($_POST['clear_ai_logs']) && check_admin_referer('clear_ai_logs_nonce')) {
        delete_option('ai_blogpost_api_logs');
        wp_redirect(add_query_arg('logs_cleared', '1', wp_get_referer()));
        exit;
    }
}
add_action('admin_init', 'ai_blogpost_clear_logs');

/**
 * Debug logging function
 * 
 * @param string $message The message to log
 * @param mixed $data Optional data to log
 */
function ai_blogpost_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $log .= "\n" . print_r($data, true);
        }
        error_log($log);
    }
}
