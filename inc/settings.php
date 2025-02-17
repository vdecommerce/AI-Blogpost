<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handle ComfyUI connection test
 */
function handle_comfyui_test() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    $api_url = sanitize_text_field($_POST['url']);
    $api_url = rtrim($api_url, '/');
    
    try {
        ai_blogpost_debug_log('Testing ComfyUI connection:', [
            'url' => $api_url
        ]);

        // Validate URL format
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid API URL format. URL must start with http:// or https://');
        }

        // Test connection to client_id endpoint with retry logic
        $max_retries = 3;
        $retry_delay = 2;
        $attempt = 0;
        $last_error = null;

        while ($attempt < $max_retries) {
            try {
                $response = wp_remote_get($api_url . '/client_id', [
                    'timeout' => 30,
                    'sslverify' => false,
                    'headers' => [
                        'Accept' => 'application/json'
                    ]
                ]);

                if (is_wp_error($response)) {
                    throw new Exception('Connection failed: ' . $response->get_error_message());
                }

                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code !== 200) {
                    throw new Exception('Invalid response code: ' . $response_code);
                }

                $body = wp_remote_retrieve_body($response);
                if (empty($body)) {
                    throw new Exception('Empty response from server');
                }

                ai_blogpost_debug_log('ComfyUI raw response:', $body);
                
                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON response: ' . json_last_error_msg());
                }
                
                if (empty($data['client_id'])) {
                    throw new Exception('No client_id in response: ' . print_r($data, true));
                }

                // Store the client ID and validate it
                $client_id = $data['client_id'];
                update_option('ai_blogpost_comfyui_client_id', $client_id);
                update_option('ai_blogpost_comfyui_api_url', $api_url);

                // Test the client ID with system_stats
                $validation_response = wp_remote_get($api_url . '/system_stats', [
                    'headers' => [
                        'client_id' => $client_id,
                        'Accept' => 'application/json'
                    ],
                    'timeout' => 15,
                    'sslverify' => false
                ]);

                if (is_wp_error($validation_response)) {
                    throw new Exception('Client ID validation failed: ' . $validation_response->get_error_message());
                }

                $validation_code = wp_remote_retrieve_response_code($validation_response);
                if ($validation_code !== 200) {
                    throw new Exception('Client ID validation failed with code: ' . $validation_code);
                }

                // Log success
                ai_blogpost_log_api_call('ComfyUI Test', true, [
                    'url' => $api_url,
                    'status' => 'Connection successful',
                    'client_id' => $client_id,
                    'validation' => 'Passed'
                ]);

                wp_send_json_success([
                    'message' => 'Connection successful',
                    'client_id' => $client_id
                ]);
                return;

            } catch (Exception $e) {
                $last_error = $e;
                $attempt++;
                
                ai_blogpost_debug_log(sprintf(
                    'ComfyUI connection attempt %d/%d failed: %s',
                    $attempt,
                    $max_retries,
                    $e->getMessage()
                ));

                if ($attempt < $max_retries) {
                    sleep($retry_delay);
                }
            }
        }

        // If we get here, all retries failed
        $error_message = sprintf(
            'Connection failed after %d attempts. Last error: %s',
            $max_retries,
            $last_error ? $last_error->getMessage() : 'Unknown error'
        );

        ai_blogpost_log_api_call('ComfyUI Test', false, [
            'url' => $api_url,
            'status' => 'Failed',
            'error' => $error_message
        ]);

        wp_send_json_error($error_message);

    } catch (Exception $e) {
        $error_message = 'ComfyUI connection error: ' . $e->getMessage();
        ai_blogpost_debug_log($error_message);
        ai_blogpost_log_api_call('ComfyUI Test', false, [
            'url' => $api_url,
            'status' => 'Error',
            'error' => $error_message
        ]);
        wp_send_json_error($error_message);
    }
}
add_action('wp_ajax_test_comfyui_connection', 'handle_comfyui_test');
