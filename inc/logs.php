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
        echo '<div class="ai-empty-logs">
            <span class="dashicons dashicons-info"></span>
            <p>No ' . esc_html($type) . ' logs found.</p>
        </div>';
        return;
    }

    // Show most recent logs first
    $filtered_logs = array_reverse(array_slice($filtered_logs, -5));

    echo '<div class="ai-logs-wrapper">';
    
    foreach ($filtered_logs as $log) {
        $status_class = $log['success'] ? 'success' : 'error';
        $status_icon = $log['success'] ? 'yes' : 'no-alt';
        $category = isset($log['data']['category']) ? esc_html($log['data']['category']) : '-';
        
        echo '<div class="ai-log-entry ai-log-' . $status_class . '">';
        
        // Log header
        echo '<div class="ai-log-header">';
        echo '<div class="ai-log-status"><span class="dashicons dashicons-' . $status_icon . '"></span></div>';
        echo '<div class="ai-log-meta">';
        echo '<div class="ai-log-time">' . date('Y-m-d H:i:s', $log['time']) . '</div>';
        echo '<div class="ai-log-category">' . $category . '</div>';
        echo '</div>';
        echo '<div class="ai-log-toggle"><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
        echo '</div>';
        
        // Log content
        echo '<div class="ai-log-content">';
        
        if (isset($log['data']['status'])) {
            echo '<div class="ai-log-item"><strong>Status:</strong> ' . esc_html($log['data']['status']) . '</div>';
        }
        
        if (isset($log['data']['prompt']) && !empty($log['data']['prompt'])) {
            $prompt = $log['data']['prompt'];
            // If prompt is an array, convert to JSON string
            if (is_array($prompt)) {
                $prompt = json_encode($prompt, JSON_PRETTY_PRINT);
            }
            echo '<div class="ai-log-item"><strong>Prompt:</strong>';
            echo '<div class="ai-log-code">' . esc_html($prompt) . '</div>';
            echo '</div>';
        }
        
        if (isset($log['data']['error'])) {
            echo '<div class="ai-log-item"><strong>Error:</strong>';
            echo '<div class="ai-log-code ai-log-error">' . esc_html($log['data']['error']) . '</div>';
            echo '</div>';
        }
        
        if (isset($log['data']['image_id']) && !empty($log['data']['image_id'])) {
            $image_url = wp_get_attachment_image_url($log['data']['image_id'], 'thumbnail');
            if ($image_url) {
                echo '<div class="ai-log-item"><strong>Generated Image:</strong>';
                echo '<div class="ai-log-image"><img src="' . esc_url($image_url) . '" alt="Generated image"></div>';
                echo '</div>';
            }
        }
        
        echo '</div>'; // End log content
        echo '</div>'; // End log entry
    }
    
    echo '</div>'; // End logs wrapper
    
    // Add JavaScript for toggling log details
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.ai-log-header').click(function() {
            var $entry = $(this).closest('.ai-log-entry');
            var $content = $entry.find('.ai-log-content');
            var $toggle = $(this).find('.ai-log-toggle .dashicons');
            
            $content.slideToggle(200);
            $toggle.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        });
        
        // Expand the first log entry by default
        $('.ai-log-entry:first-child .ai-log-header').click();
    });
    </script>
    <style>
    .ai-empty-logs {
        padding: 20px;
        background: #f8f9fa;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        text-align: center;
        color: #646970;
    }
    
    .ai-empty-logs .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
        margin-bottom: 10px;
    }
    
    .ai-logs-wrapper {
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .ai-log-entry {
        border-bottom: 1px solid #e2e4e7;
    }
    
    .ai-log-entry:last-child {
        border-bottom: none;
    }
    
    .ai-log-header {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background: #f8f9fa;
        cursor: pointer;
        transition: background 0.2s ease;
    }
    
    .ai-log-header:hover {
        background: #f0f0f1;
    }
    
    .ai-log-status {
        margin-right: 15px;
    }
    
    .ai-log-status .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    
    .ai-log-success .ai-log-status .dashicons {
        color: #00a32a;
    }
    
    .ai-log-error .ai-log-status .dashicons {
        color: #d63638;
    }
    
    .ai-log-meta {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .ai-log-time {
        font-size: 13px;
        color: #646970;
    }
    
    .ai-log-category {
        font-weight: 500;
    }
    
    .ai-log-toggle {
        margin-left: auto;
    }
    
    .ai-log-content {
        display: none;
        padding: 15px;
        background: white;
        border-top: 1px solid #e2e4e7;
    }
    
    .ai-log-item {
        margin-bottom: 15px;
    }
    
    .ai-log-item:last-child {
        margin-bottom: 0;
    }
    
    .ai-log-code {
        margin-top: 5px;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        font-family: monospace;
        white-space: pre-wrap;
        overflow-x: auto;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .ai-log-error {
        background: #fde8e8;
        border-color: #d63638;
    }
    
    .ai-log-image {
        margin-top: 10px;
    }
    
    .ai-log-image img {
        max-width: 150px;
        height: auto;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
    }
    </style>
    <?php
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
