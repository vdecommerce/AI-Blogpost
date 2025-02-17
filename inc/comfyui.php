<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get or refresh ComfyUI client ID using WebSocket
 *
 * @param string $api_url The ComfyUI API URL
 * @param bool $force_refresh Whether to force a refresh of the client ID
 * @return string Valid client ID
 * @throws Exception if unable to get valid client ID
 */
/**
 * Get or refresh ComfyUI client ID using WebSocket
 * Retries up to 3 times on failure
 *
 * @param string $api_url The ComfyUI API URL
 * @param bool $force_refresh Whether to force a refresh of the client ID
 * @return string|null Valid client ID or null on failure
 */
function get_comfyui_client_id($api_url, $force_refresh = false) {
    $max_retries = 3;
    $retry_count = 0;

    while ($retry_count < $max_retries) {
        try {
            $client_id = get_cached_option('ai_blogpost_comfyui_client_id');

            // Get new client ID if none exists, forced refresh, or validation fails
            if (empty($client_id) || $force_refresh || !validate_comfyui_client_id($api_url, $client_id)) {
                ai_blogpost_debug_log('Requesting new ComfyUI client ID');

                // Use WebSocket connection
                $ws = new WebSocket($api_url . '/ws');
                $ws->send(json_encode(['type' => 'get_client_id']));
                $response = json_decode($ws->receive(), true);
                $ws->close();

                if (!isset($response['client_id'])) {
                    throw new Exception('Invalid client ID response from ComfyUI server');
                }

                $client_id = $response['client_id'];
                update_option('ai_blogpost_comfyui_client_id', $client_id);

                ai_blogpost_debug_log('New client ID obtained:', $client_id);
            }

            return $client_id;
        } catch (Exception $e) {
            ai_blogpost_debug_log('Error in get_comfyui_client_id:', $e->getMessage());
            $retry_count++;
            sleep(2); // Wait before retry
        }
    }

    // Failed after max retries
    ai_blogpost_debug_log('Failed to get ComfyUI client ID after ' . $max_retries . ' attempts');
    return null;
}

/**
 * Validate ComfyUI client ID using WebSocket
 *
 * @param string $api_url The ComfyUI API URL
 * @param string $client_id The client ID to validate
 * @return bool Whether the client ID is valid
 */
function validate_comfyui_client_id($api_url, $client_id) {
    try {
        // Use WebSocket connection to validate
        $ws = new WebSocket($api_url . '/ws');
        $ws->send(json_encode(['type' => 'validate_client_id', 'client_id' => $client_id]));
        $response = json_decode($ws->receive(), true);
        $ws->close();

        return $response['valid'] ?? false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Fetch image using ComfyUI with WebSocket
 *
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
function fetch_comfyui_image_from_text($image_data) {
    try {
        // Validate image data
        if (!is_array($image_data) || empty($image_data['category'])) {
            throw new Exception('Invalid image data structure');
        }

        $category = $image_data['category'];
        $api_url = get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188');
        
        // Ensure URL is properly formatted
        $api_url = rtrim($api_url, '/');
        if (!preg_match('/^https?:\/\//', $api_url)) {
            $api_url = 'http://' . $api_url;
        }

        // Get valid client ID
        $client_id = get_comfyui_client_id($api_url, false);

        // Get workflow configuration
        $workflows = json_decode(get_cached_option('ai_blogpost_comfyui_workflows', '[]'), true);
        $default_workflow = get_cached_option('ai_blogpost_comfyui_default_workflow', '');
        
        if (empty($workflows)) {
            throw new Exception('No ComfyUI workflows configured');
        }

        // Find the selected workflow
        $workflow_config = null;
        foreach ($workflows as $workflow) {
            if ($workflow['name'] === $default_workflow) {
                $workflow_config = $workflow;
                break;
            }
        }

        if (!$workflow_config) {
            throw new Exception('Selected workflow not found');
        }

        // Replace prompt placeholder in workflow
        $workflow_data = $workflow_config['workflow'];
        foreach ($workflow_data['nodes'] as &$node) {
            if (isset($node['inputs']) && isset($node['inputs']['text'])) {
                $node['inputs']['text'] = str_replace(
                    ['[category]', '[categorie]'],
                    $category,
                    $node['inputs']['text']
                );
            }
        }

        // Initial log
        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'ComfyUI',
            'category' => $category,
            'workflow' => $workflow_config['name'],
            'status' => 'Starting image generation'
        ]);

        // Use WebSocket to queue prompt and monitor
        $ws = new WebSocket($api_url . '/ws');
        $ws->send(json_encode([
            'type' => 'queue_prompt',
            'client_id' => $client_id,
            'prompt' => $workflow_data
        ]));

        $response = json_decode($ws->receive(), true);
        if (empty($response['prompt_id'])) {
            throw new Exception('Failed to queue prompt');
        }

        $prompt_id = $response['prompt_id'];
        $timeout = 300; // 5 minute timeout
        $start_time = time();

        while (time() - $start_time < $timeout) {
            $ws->send(json_encode(['type' => 'get_status', 'prompt_id' => $prompt_id]));
            $status = json_decode($ws->receive(), true);

            if (!empty($status['error'])) {
                throw new Exception('Workflow error: ' . $status['error']);
            }

            if (!empty($status['outputs'])) {
                $image_data = null;
                foreach ($status['outputs'] as $output) {
                    if (!empty($output['images'])) {
                        $image_data = $output['images'][0];
                        break;
                    }
                }

                if ($image_data) {
                    $ws->send(json_encode([
                        'type' => 'get_image',
                        'image' => $image_data
                    ]));
                    $image_response = $ws->receive();
                    $ws->close();

                    // Save image 
                    $filename = 'comfyui-' . sanitize_title($category) . '-' . time() . '.png';
                    $upload = wp_upload_bits($filename, null, $image_response);
                    
                    if (!empty($upload['error'])) {
                        throw new Exception('Failed to save image: ' . $upload['error']);
                    }

                    // Create attachment
                    $attachment = [
                        'post_mime_type' => wp_check_filetype($filename)['type'],
                        'post_title' => sanitize_file_name($filename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ];

                    $attach_id = wp_insert_attachment($attachment, $upload['file']);
                    if (is_wp_error($attach_id)) {
                        throw new Exception('Failed to create attachment');
                    }

                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    // Log success
                    ai_blogpost_log_api_call('Image Generation', true, [
                        'type' => 'ComfyUI',
                        'category' => $category,
                        'workflow' => $workflow_config['name'],
                        'image_id' => $attach_id,
                        'status' => 'Image Generated Successfully'
                    ]);

                    return $attach_id;
                }
            }

            sleep(2);
        }

        throw new Exception('Generation timed out');

    } catch (Exception $e) {
        // Log error
        ai_blogpost_log_api_call('Image Generation', false, [
            'type' => 'ComfyUI',
            'error' => $e->getMessage(),
            'category' => $category ?? 'unknown',
            'status' => 'Error: ' . $e->getMessage()
        ]);
        
        return null;
    }
}
