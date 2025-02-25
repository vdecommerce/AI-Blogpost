<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Handles all logging functionality
 */
class Logs {
    private const API_LOGS_OPTION = 'ai_blogpost_api_logs';
    private const MAX_LOG_ENTRIES = 20;
    
    /**
     * Log debug messages if WP_DEBUG is enabled
     * @param string $message
     * @param mixed $data
     * @return void
     */
    public static function debug(string $message, $data = null): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $log .= "\n" . print_r($data, true);
        }
        error_log($log);
    }
    
    /**
     * Log API calls and their results
     */
    public static function logApiCall(string $type, bool $success, array $data): void {
        $logs = get_option(self::API_LOGS_OPTION, []);
        
        // Add new log entry with details
        $logs[] = [
            'time' => time(),
            'type' => $type,
            'success' => $success,
            'data' => array_merge($data, [
                'timestamp' => date('Y-m-d H:i:s'),
                'request_id' => uniqid()
            ])
        ];
        
        // Keep only last N entries
        if (count($logs) > self::MAX_LOG_ENTRIES) {
            $logs = array_slice($logs, -self::MAX_LOG_ENTRIES);
        }
        
        self::debug('Updating logs:', $logs);
        update_option(self::API_LOGS_OPTION, $logs);
    }
    
    /**
     * Display API logs in the admin dashboard
     */
    public static function displayApiLogs(string $type): void {
        $logs = get_option(self::API_LOGS_OPTION, []);
        self::debug('Displaying logs for type:', $type);
        
        $filtered_logs = array_filter($logs, function($log) use ($type): bool {
            return isset($log['type']) && $log['type'] === $type;
        });
        
        if (empty($filtered_logs)) {
            echo '<div class="notice notice-warning"><p>No ' . esc_html($type) . ' logs found.</p></div>';
            return;
        }

        // Show most recent logs first
        $filtered_logs = array_reverse(array_slice($filtered_logs, -5));

        self::outputLogTable($filtered_logs);
        
        // Add JavaScript for toggling log details
        self::outputLogScript();
    }
    
    /**
     * Output log table with entries
     */
    private static function outputLogTable(array $logs): void {
        echo '<table class="log-table">';
        echo '<thead><tr>';
        echo '<th>Time</th>';
        echo '<th>Status</th>';
        echo '<th>Category</th>';
        echo '<th>Details</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($logs as $log) {
            $status_class = $log['success'] ? 'success' : 'error';
            $status_icon = $log['success'] ? 'dashicons-yes-alt' : 'dashicons-warning';
            $status_text = $log['success'] ? 'Success' : 'Failed';
            
            echo '<tr>';
            echo '<td class="log-time">' . date('Y-m-d H:i:s', $log['time']) . '</td>';
            echo '<td class="log-status ' . $status_class . '"><span class="dashicons ' . $status_icon . '"></span> ' . $status_text . '</td>';
            echo '<td>' . (isset($log['data']['category']) ? esc_html($log['data']['category']) : '-') . '</td>';
            echo '<td class="log-details">';
            
            // Collapsible details
            echo '<div class="log-summary">';
            if (isset($log['data']['status'])) {
                echo '<strong>' . esc_html($log['data']['status']) . '</strong>';
            }
            echo ' <button type="button" class="toggle-details button-link">Show details</button>';
            echo '</div>';
            
            echo '<div class="log-full-details" style="display:none;">';
            if (isset($log['data']['status'])) {
                echo '<div><strong>Status:</strong> ' . esc_html($log['data']['status']) . '</div>';
            }
            if (isset($log['data']['prompt'])) {
                echo '<div><strong>Prompt:</strong><pre>' . esc_html($log['data']['prompt']) . '</pre></div>';
            }
            if (isset($log['data']['error'])) {
                echo '<div><strong>Error:</strong><pre>' . esc_html($log['data']['error']) . '</pre></div>';
            }
            
            // Show all other data
            foreach ($log['data'] as $key => $value) {
                if (!in_array($key, ['status', 'prompt', 'error', 'timestamp', 'request_id', 'category'])) {
                    if (is_scalar($value)) {
                        echo '<div><strong>' . esc_html(ucfirst($key)) . ':</strong> ' . esc_html($value) . '</div>';
                    } elseif (!empty($value)) {
                        echo '<div><strong>' . esc_html(ucfirst($key)) . ':</strong><pre>' . esc_html(print_r($value, true)) . '</pre></div>';
                    }
                }
            }
            
            echo '</div>'; // End log-full-details
            
            echo '</td></tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Output JavaScript for log details toggling
     */
    private static function outputLogScript(): void {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.toggle-details').on('click', function() {
                var $details = $(this).closest('.log-summary').next('.log-full-details');
                $details.toggle();
                
                // Update button text
                if ($details.is(':visible')) {
                    $(this).text('Hide details');
                } else {
                    $(this).text('Show details');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Clear API logs
     */
    public static function clearLogs(): void {
        if (!isset($_POST['clear_ai_logs']) || !check_admin_referer('clear_ai_logs_nonce')) {
            return;
        }
        
        delete_option(self::API_LOGS_OPTION);
        wp_redirect(add_query_arg('logs_cleared', '1', wp_get_referer()));
        exit;
    }
    
    /**
     * Initialize logging functionality
     */
    public static function initialize(): void {
        add_action('admin_init', [self::class, 'clearLogs']);
    }
}

// Initialize logging functionality
Logs::initialize();
