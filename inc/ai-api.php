<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Fetch AI response for blog post generation
 * 
 * @param array $post_data Post data including category and focus keyword
 * @return array|null Generated content or null on failure
 */
function fetch_ai_response($post_data) {
    try {
        $api_key = get_cached_option('ai_blogpost_api_key');
        if (empty($api_key) && !get_cached_option('ai_blogpost_openrouter_enabled', 0) && !get_cached_option('ai_blogpost_lm_enabled', 0)) {
            throw new Exception('API key is missing');
        }

        ai_blogpost_log_api_call('Text Generation', true, array(
            'status' => 'Starting request',
            'category' => $post_data['category']
        ));

        $prompt = prepare_ai_prompt($post_data);
        $response = send_ai_request($prompt);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }

        ai_blogpost_log_api_call('Text Generation', true, array(
            'prompt' => $prompt,
            'content' => $response['choices'][0]['message']['content'],
            'status' => 'Content generated successfully',
            'category' => $post_data['category']
        ));

        return array(
            'content' => $response['choices'][0]['message']['content'],
            'category' => $post_data['category'],
            'focus_keyword' => $post_data['focus_keyword']
        );
    } catch (Exception $e) {
        ai_blogpost_log_api_call('Text Generation', false, array(
            'error' => $e->getMessage(),
            'category' => $post_data['category'] ?? 'unknown',
            'status' => 'Failed: ' . $e->getMessage()
        ));
        error_log('AI Response Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Prepare AI prompt with system messages and user content
 * 
 * @param array $post_data Post data for prompt generation
 * @return array Array of message objects for the API
 */
function prepare_ai_prompt($post_data) {
    try {
        $language = get_option('ai_blogpost_language', 'en');
        $system_role = get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.');
        $content_template = get_cached_option('ai_blogpost_prompt', "Write a blog post about [topic]. Structure the content as follows:\n\n||Title||: Create an engaging, SEO-friendly title\n\n||Content||: Write the main content here, using proper HTML structure:\n- Use <article> tags to wrap the content\n- Use <h1>, <h2> for headings\n- Use <p> for paragraphs\n- Include relevant subheadings\n- Add a strong conclusion\n\n||Category||: Suggest the most appropriate category for this post");

        $system_messages = [
            ["role" => "system", "content" => get_language_instruction($language)],
            ["role" => "system", "content" => $system_role]
        ];

        $user_prompt = str_replace('[topic]', $post_data['category'], $content_template);

        $messages = array_merge(
            $system_messages,
            [["role" => "user", "content" => $user_prompt]]
        );
        
        ai_blogpost_debug_log('Prepared messages:', $messages);
        return $messages;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in prepare_ai_prompt:', $e->getMessage());
        throw $e;
    }
}

/**
 * Send request to OpenAI, OpenRouter, or LM Studio API
 * 
 * @param array $messages Array of message objects
 * @return array API response
 */
function send_ai_request($messages) {
    try {
        $temperature = (float)get_cached_option('ai_blogpost_temperature', 0.7);
        $max_tokens = min((int)get_cached_option('ai_blogpost_max_tokens', 2048), 4096);

        if (get_cached_option('ai_blogpost_openrouter_enabled', 0)) {
            $args = array(
                'body' => json_encode(array(
                    'model' => get_cached_option('ai_blogpost_openrouter_model', 'openai/gpt-4'),
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $max_tokens
                )),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . get_cached_option('ai_blogpost_openrouter_api_key')
                ),
                'timeout' => 90
            );
            $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', $args);
        } elseif (get_cached_option('ai_blogpost_lm_enabled', 0)) {
            return send_lm_studio_request($messages);
        } else {
            $args = array(
                'body' => json_encode(array(
                    'model' => get_option('ai_blogpost_model', 'gpt-4'),
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $max_tokens
                )),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . get_option('ai_blogpost_api_key')
                ),
                'timeout' => 90
            );
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
        }

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        ai_blogpost_debug_log('API response received:', $result);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $result;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in send_ai_request:', $e->getMessage());
        throw $e;
    }
}

/**
 * Send request to LM Studio API
 * 
 * @param array $messages Array of message objects
 * @return array API response
 */
function send_lm_studio_request($messages) {
    try {
        $api_url = rtrim(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234'), '/') . '/v1';

        $prompt = '';
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $prompt .= "### System:\n" . $message['content'] . "\n\n";
            } else if ($message['role'] === 'user') {
                $prompt .= "### User:\n" . $message['content'] . "\n\n";
            }
        }
        $prompt .= "### Assistant:\n";

        $args = array(
            'body' => json_encode(array(
                'model' => get_cached_option('ai_blogpost_lm_model', 'model.gguf'),
                'prompt' => $prompt,
                'temperature' => (float)get_cached_option('ai_blogpost_temperature', 0.7),
                'max_tokens' => min((int)get_cached_option('ai_blogpost_max_tokens', 2048), 4096),
                'stream' => false
            )),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 300,
            'sslverify' => false
        );

        $response = wp_remote_post($api_url . '/completions', $args);
        
        if (is_wp_error($response)) {
            throw new Exception('LM Studio API request failed: ' . $response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($result['choices'][0]['text'])) {
            throw new Exception('Invalid response format from LM Studio');
        }

        $content = $result['choices'][0]['text'];
        if (strpos($content, '</think>') !== false) {
            $content = substr($content, strpos($content, '</think>') + 8);
        }
        $content = trim(str_replace(['### Assistant:', '---'], '', $content));

        return array(
            'choices' => array(
                array(
                    'message' => array(
                        'content' => $content
                    )
                )
            )
        );
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in send_lm_studio_request:', $e->getMessage());
        throw $e;
    }
}

/**
 * Fetch image based on text content
 * 
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
function fetch_dalle_image_from_text($image_data) {
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'none');
    
    if ($generation_type === 'comfyui') {
        return fetch_comfyui_image_from_text($image_data);
    } elseif ($generation_type === 'dalle') {
        return fetch_dalle_image($image_data);
    } elseif ($generation_type === 'localai') {
        return fetch_localai_image($image_data);
    }
    
    return null;
}

/**
 * Fetch image using DALL·E
 * 
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
function fetch_dalle_image($image_data) {
    try {
        if (!is_array($image_data) || empty($image_data['category']) || empty($image_data['template'])) {
            throw new Exception('Invalid image data structure');
        }

        $category = $image_data['category'];
        $api_key = get_cached_option('ai_blogpost_dalle_api_key');
        if (empty($api_key)) {
            throw new Exception('DALL·E API key is missing');
        }

        $prompt = str_replace(['[category]', '[categorie]'], $category, $image_data['template']);

        ai_blogpost_debug_log('DALL·E Prompt Data:', [
            'category' => $category,
            'prompt' => $prompt
        ]);

        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'DALL·E',
            'prompt' => $prompt,
            'category' => $category,
            'status' => 'Starting image generation'
        ]);

        $payload = [
            'model' => get_cached_option('ai_blogpost_dalle_model', 'dall-e-3'),
            'prompt' => $prompt,
            'n' => 1,
            'size' => get_cached_option('ai_blogpost_dalle_size', '1024x1024'),
            'style' => get_cached_option('ai_blogpost_dalle_style', 'vivid'),
            'quality' => get_cached_option('ai_blogpost_dalle_quality', 'standard')
        ];

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode($payload),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            throw new Exception('DALL·E API request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = $body['error']['message'] ?? 'Unknown API error';
            throw new Exception('DALL·E API error (' . $response_code . '): ' . $error_message);
        }

        if (empty($body['data'][0]['url'])) {
            throw new Exception('No image URL in DALL·E response');
        }

        $image_url = $body['data'][0]['url'];
        $image_response = wp_remote_get($image_url, ['timeout' => 30]);

        if (is_wp_error($image_response)) {
            throw new Exception('Failed to download image: ' . $image_response->get_error_message());
        }

        if (wp_remote_retrieve_response_code($image_response) !== 200) {
            throw new Exception('Failed to download image: Invalid response code');
        }

        $filename = 'dalle-' . sanitize_title($category) . '-' . time() . '.png';
        $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
        
        if (!empty($upload['error'])) {
            throw new Exception('Failed to save image: ' . $upload['error']);
        }

        $attachment = [
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attach_id)) {
            throw new Exception('Failed to create attachment: ' . $attach_id->get_error_message());
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'DALL·E',
            'prompt' => $prompt,
            'category' => $category,
            'image_id' => $attach_id,
            'status' => 'Image Generated Successfully'
        ]);

        return $attach_id;
    } catch (Exception $e) {
        ai_blogpost_log_api_call('Image Generation', false, [
            'type' => 'DALL·E',
            'error' => $e->getMessage(),
            'category' => $category ?? 'unknown',
            'status' => 'Error: ' . $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Fetch image using LocalAI
 * 
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
function fetch_localai_image($image_data) {
    try {
        if (!is_array($image_data) || empty($image_data['category']) || empty($image_data['template'])) {
            throw new Exception('Invalid image data structure');
        }

        $category = $image_data['category'];
        $api_url = rtrim(get_cached_option('ai_blogpost_localai_api_url', 'http://localhost:8080'), '/');
        $prompt = str_replace(['[category]', '[categorie]'], $category, $image_data['template']);
        $size = get_cached_option('ai_blogpost_localai_size', '1024x1024');

        ai_blogpost_debug_log('LocalAI Prompt Data:', [
            'category' => $category,
            'prompt' => $prompt,
            'size' => $size
        ]);

        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'LocalAI',
            'prompt' => $prompt,
            'category' => $category,
            'status' => 'Starting image generation'
        ]);

        $payload = [
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size
        ];

        $response = wp_remote_post($api_url . '/v1/images/generations', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 120,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            throw new Exception('LocalAI API request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = $body['error']['message'] ?? 'Unknown API error';
            throw new Exception('LocalAI API error (' . $response_code . '): ' . $error_message);
        }

        if (empty($body['data'][0]['url'])) {
            throw new Exception('No image URL in LocalAI response');
        }

        $image_url = $body['data'][0]['url'];
        $image_response = wp_remote_get($image_url, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($image_response)) {
            throw new Exception('Failed to download image: ' . $image_response->get_error_message());
        }

        if (wp_remote_retrieve_response_code($image_response) !== 200) {
            throw new Exception('Failed to download image: Invalid response code');
        }

        $filename = 'localai-' . sanitize_title($category) . '-' . time() . '.png';
        $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
        
        if (!empty($upload['error'])) {
            throw new Exception('Failed to save image: ' . $upload['error']);
        }

        $attachment = [
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attach_id)) {
            throw new Exception('Failed to create attachment: ' . $attach_id->get_error_message());
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'LocalAI',
            'prompt' => $prompt,
            'category' => $category,
            'image_id' => $attach_id,
            'status' => 'Image Generated Successfully'
        ]);

        return $attach_id;
    } catch (Exception $e) {
        ai_blogpost_log_api_call('Image Generation', false, [
            'type' => 'LocalAI',
            'error' => $e->getMessage(),
            'category' => $category ?? 'unknown',
            'status' => 'Error: ' . $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Handle LocalAI connection test
 */
function handle_localai_test() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    $api_url = sanitize_text_field($_POST['url']);
    $api_url = rtrim($api_url, '/');
    
    try {
        ai_blogpost_debug_log('Testing LocalAI connection:', ['url' => $api_url]);

        $response = wp_remote_get($api_url . '/v1/models', [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            ai_blogpost_debug_log('LocalAI connection failed:', $response->get_error_message());
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['data'])) {
            ai_blogpost_debug_log('Invalid LocalAI response:', $data);
            wp_send_json_error('Invalid response from LocalAI server');
            return;
        }

        ai_blogpost_log_api_call('LocalAI Test', true, [
            'url' => $api_url,
            'status' => 'Connection successful',
            'models' => $data['data']
        ]);

        wp_send_json_success([
            'message' => 'Connection successful',
            'models' => $data['data']
        ]);
    } catch (Exception $e) {
        ai_blogpost_debug_log('LocalAI error:', $e->getMessage());
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_test_localai_connection', 'handle_localai_test');

/**
 * Get or refresh ComfyUI client ID
 * 
 * @param string $api_url The ComfyUI API URL
 * @param bool $force_refresh Whether to force a refresh of the client ID
 * @return string Valid client ID
 * @throws Exception if unable to get valid client ID
 */
function get_comfyui_client_id($api_url, $force_refresh = false) {
    try {
        $client_id = get_cached_option('ai_blogpost_comfyui_client_id');
        
        if (empty($client_id) || $force_refresh || !validate_comfyui_client_id($api_url, $client_id)) {
            ai_blogpost_debug_log('Requesting new ComfyUI client ID');
            
            $response = wp_remote_get($api_url . '/client_id', [
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                throw new Exception('Failed to get client ID: ' . $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (empty($data['client_id'])) {
                throw new Exception('Invalid client ID response from ComfyUI server');
            }

            $client_id = $data['client_id'];
            update_option('ai_blogpost_comfyui_client_id', $client_id);
            
            ai_blogpost_debug_log('New client ID obtained:', $client_id);
        }
        
        return $client_id;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in get_comfyui_client_id:', $e->getMessage());
        throw $e;
    }
}

/**
 * Validate ComfyUI client ID
 * 
 * @param string $api_url The ComfyUI API URL
 * @param string $client_id The client ID to validate
 * @return bool Whether the client ID is valid
 */
function validate_comfyui_client_id($api_url, $client_id) {
    try {
        $response = wp_remote_get($api_url . '/system_stats', [
            'headers' => ['client_id' => $client_id],
            'timeout' => 10,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return !empty($data) && is_array($data);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Fetch image using ComfyUI
 * 
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
function fetch_comfyui_image_from_text($image_data) {
    try {
        if (!is_array($image_data) || empty($image_data['category'])) {
            throw new Exception('Invalid image data structure');
        }

        $category = $image_data['category'];
        $api_url = rtrim(get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188'), '/');
        if (!preg_match('/^https?:\/\//', $api_url)) {
            $api_url = 'http://' . $api_url;
        }

        $status_response = wp_remote_get($api_url . '/system_stats', [
            'timeout' => 10,
            'sslverify' => false
        ]);
        if (is_wp_error($status_response)) {
            throw new Exception('ComfyUI server not running: ' . $status_response->get_error_message());
        }

        $workflows = json_decode(get_cached_option('ai_blogpost_comfyui_workflows', '[]'), true);
        $default_workflow = get_cached_option('ai_blogpost_comfyui_default_workflow', '');
        if (empty($workflows)) {
            throw new Exception('No ComfyUI workflows configured');
        }

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

        $workflow_data = $workflow_config['workflow'];
        $prompt = [];
        foreach ($workflow_data['nodes'] as $node) {
            $node_id = strval($node['id']);
            $prompt[$node_id] = [
                'class_type' => $node['class_type'],
                'inputs' => []
            ];

            if (isset($node['widgets_values']) && is_array($node['widgets_values'])) {
                $widget_names = [
                    'CheckpointLoaderSimple' => ['ckpt_name'],
                    'EmptyLatentImage' => ['width', 'height', 'batch_size'],
                    'CLIPTextEncode' => ['text'],
                    'KSampler' => ['seed', 'control_after_generate', 'steps', 'cfg', 'sampler_name', 'scheduler', 'denoise'],
                    'SaveImage' => ['filename_prefix']
                ];
                $input_names = $widget_names[$node['class_type']] ?? [];
                foreach ($input_names as $index => $name) {
                    if (isset($node['widgets_values'][$index])) {
                        $value = $node['widgets_values'][$index];
                        if (is_string($value)) {
                            $value = str_replace(['[category]', '[categorie]'], $category, $value);
                        }
                        $prompt[$node_id]['inputs'][$name] = $value;
                    }
                }
            }

            foreach ($node['inputs'] as $input) {
                if (isset($input['link'])) {
                    $link_id = $input['link'];
                    foreach ($workflow_data['links'] as $link) {
                        if ($link[0] === $link_id) {
                            $source_node_id = strval($link[1]);
                            $source_slot = $link[2];
                            $prompt[$node_id]['inputs'][$input['name']] = [$source_node_id, $source_slot];
                            break;
                        }
                    }
                }
            }
        }

        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'ComfyUI',
            'category' => $category,
            'workflow' => $workflow_config['name'],
            'status' => 'Starting image generation',
            'prompt' => json_encode($prompt)
        ]);

        $queue_response = wp_remote_post($api_url . '/prompt', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['prompt' => $prompt]),
            'timeout' => 30,
            'sslverify' => false
        ]);
        if (is_wp_error($queue_response)) {
            throw new Exception('Failed to queue prompt: ' . $queue_response->get_error_message());
        }

        $queue_data = json_decode(wp_remote_retrieve_body($queue_response), true);
        if (empty($queue_data['prompt_id'])) {
            throw new Exception('Invalid queue response');
        }

        $prompt_id = $queue_data['prompt_id'];
        $start_time = time();
        $timeout = 300;

        while (time() - $start_time < $timeout) {
            $history_response = wp_remote_get($api_url . '/history/' . $prompt_id, [
                'timeout' => 30,
                'sslverify' => false
            ]);
            if (is_wp_error($history_response)) {
                throw new Exception('Failed to check history: ' . $history_response->get_error_message());
            }

            $history_data = json_decode(wp_remote_retrieve_body($history_response), true);
            if (!isset($history_data[$prompt_id])) {
                sleep(2);
                continue;
            }

            $outputs = $history_data[$prompt_id]['outputs'];
            $image_data = null;
            foreach ($outputs as $node_id => $output) {
                if (!empty($output['images'])) {
                    $image_data = $output['images'][0];
                    break;
                }
            }

            if ($image_data) {
                $image_response = wp_remote_get($api_url . '/view?' . http_build_query([
                    'filename' => $image_data['filename'],
                    'subfolder' => $image_data['subfolder'] ?? '',
                    'type' => $image_data['type']
                ]), [
                    'timeout' => 30,
                    'sslverify' => false
                ]);
                if (is_wp_error($image_response)) {
                    throw new Exception('Failed to download image: ' . $image_response->get_error_message());
                }

                $filename = 'comfyui-' . sanitize_title($category) . '-' . time() . '.png';
                $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
                if (!empty($upload['error'])) {
                    throw new Exception('Failed to save image: ' . $upload['error']);
                }

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

                ai_blogpost_log_api_call('Image Generation', true, [
                    'type' => 'ComfyUI',
                    'category' => $category,
                    'workflow' => $workflow_config['name'],
                    'image_id' => $attach_id,
                    'status' => 'Image Generated Successfully'
                ]);

                return $attach_id;
            }

            if (!empty($history_data[$prompt_id]['status']['error'])) {
                throw new Exception('Workflow error: ' . $history_data[$prompt_id]['status']['error']);
            }

            sleep(2);
        }

        throw new Exception('Generation timed out');
    } catch (Exception $e) {
        ai_blogpost_log_api_call('Image Generation', false, [
            'type' => 'ComfyUI',
            'error' => $e->getMessage(),
            'category' => $category ?? 'unknown',
            'status' => 'Error: ' . $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Fetch available OpenAI models
 * 
 * @return bool Success status
 */
function fetch_openai_models() {
    try {
        $api_key = get_cached_option('ai_blogpost_api_key');
        if (empty($api_key)) {
            throw new Exception('API key is missing');
        }

        $response = wp_remote_get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch models: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['data'])) {
            throw new Exception('Invalid API response format');
        }

        $gpt_models = [];
        $dalle_models = [];
        foreach ($body['data'] as $model) {
            if (strpos($model['id'], 'gpt') !== false) {
                $gpt_models[] = $model['id'];
            } elseif (strpos($model['id'], 'dall-e') !== false) {
                $dalle_models[] = $model['id'];
            }
        }

        update_option('ai_blogpost_available_gpt_models', $gpt_models);
        update_option('ai_blogpost_available_dalle_models', $dalle_models);
        return true;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error fetching models:', $e->getMessage());
        return false;
    }
}

/**
 * Handle ComfyUI connection test
 */
function handle_comfyui_test() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    $api_url = sanitize_text_field($_POST['url']);
    $api_url = rtrim($api_url, '/');
    
    try {
        ai_blogpost_debug_log('Testing ComfyUI connection:', ['url' => $api_url]);

        $response = wp_remote_get($api_url . '/queue', [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            ai_blogpost_debug_log('ComfyUI connection failed:', $response->get_error_message());
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $queue_data = json_decode(wp_remote_retrieve_body($response), true);
        
        $history_response = wp_remote_get($api_url . '/history', [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($history_response)) {
            ai_blogpost_debug_log('ComfyUI history endpoint failed:', $history_response->get_error_message());
            wp_send_json_error('Failed to access history endpoint');
            return;
        }

        $history_data = json_decode(wp_remote_retrieve_body($history_response), true);
        
        update_option('ai_blogpost_comfyui_api_url', $api_url);
        
        ai_blogpost_log_api_call('ComfyUI Test', true, [
            'url' => $api_url,
            'status' => 'Connection successful',
            'queue_status' => $queue_data,
            'history_available' => !empty($history_data)
        ]);

        wp_send_json_success([
            'message' => 'Connection successful',
            'queue_status' => $queue_data,
            'history_available' => !empty($history_data)
        ]);
    } catch (Exception $e) {
        ai_blogpost_debug_log('ComfyUI error:', $e->getMessage());
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_test_comfyui_connection', 'handle_comfyui_test');

/**
 * Handle LM Studio connection test
 */
function handle_lm_studio_test() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    $api_url = sanitize_text_field($_POST['url']);
    $api_url = rtrim($api_url, '/') . '/v1';
    
    try {
        ai_blogpost_debug_log('Testing LM Studio connection:', ['url' => $api_url]);

        $response = wp_remote_get($api_url . '/models', [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            ai_blogpost_debug_log('LM Studio connection failed:', $response->get_error_message());
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $models = [];
        if (!empty($data['data'])) {
            $models = $data['data'];
        } elseif (is_array($data)) {
            $models = array_map(function($model) {
                return is_array($model) ? $model : ['id' => $model];
            }, $data);
        }

        if (empty($models)) {
            ai_blogpost_debug_log('No models found in LM Studio response:', $data);
            wp_send_json_error('No models found in LM Studio');
            return;
        }

        update_option('ai_blogpost_available_lm_models', $models);
        update_option('ai_blogpost_lm_api_url', rtrim($api_url, '/v1'));
        
        ai_blogpost_log_api_call('LM Studio Test', true, [
            'url' => $api_url,
            'status' => 'Connection successful',
            'models_found' => count($models),
            'models' => array_map(function($model) {
                return isset($model['id']) ? $model['id'] : $model;
            }, $models)
        ]);

        wp_send_json_success([
            'message' => 'Connection successful',
            'models' => $models
        ]);
    } catch (Exception $e) {
        ai_blogpost_debug_log('LM Studio error:', $e->getMessage());
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_test_lm_studio', 'handle_lm_studio_test');