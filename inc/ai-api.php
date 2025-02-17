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
        if (empty($api_key)) {
            throw new Exception('API key is missing');
        }

        // Log the start of the request
        ai_blogpost_log_api_call('Text Generation', true, array(
            'status' => 'Starting request',
            'category' => $post_data['category']
        ));

        $prompt = prepare_ai_prompt($post_data);
        $response = send_ai_request($prompt);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }

        // Log successful response
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
        // Log error
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
        
        // System messages for better structure
        $system_messages = [
            [
                "role" => "system",
                "content" => get_language_instruction($language)
            ],
            [
                "role" => "system",
                "content" => "You are a professional SEO content writer. 
                Structure your response exactly as follows:

                ||Title||: Write an SEO-optimized title here

                ||Content||: 
                <article>
                    <h1>Main title (same as above)</h1>
                    <p>Introduction paragraph</p>

                    <h2>First Section</h2>
                    <p>Content for first section</p>

                    <h2>Second Section</h2>
                    <p>Content for second section</p>

                    <!-- Add more sections as needed -->

                    <h2>Conclusion</h2>
                    <p>Concluding thoughts</p>
                </article>

                ||Category||: Category name"
            ]
        ];

        // Create specific user prompt
        $user_prompt = "Write a professional blog post about [topic].

Requirements:
1. Create an SEO-optimized title that includes '[topic]'
2. Write well-structured content with proper HTML tags
3. Use h1 for main title, h2 for sections
4. Include relevant keywords naturally
5. Write engaging, informative paragraphs
6. Add a strong conclusion
7. Follow the exact structure shown above";

        // Combine messages
        $messages = array_merge(
            $system_messages,
            [
                [
                    "role" => "user",
                    "content" => str_replace('[topic]', $post_data['category'], $user_prompt)
                ]
            ]
        );
        
        ai_blogpost_debug_log('Prepared messages:', $messages);
        return $messages;
        
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in prepare_ai_prompt:', $e->getMessage());
        throw $e;
    }
}

/**
 * Send request to OpenAI API
 * 
 * @param array $messages Array of message objects
 * @return array API response
 */
function send_ai_request($messages) {
    try {
        // Check if LM Studio is enabled and should be used
        if (get_cached_option('ai_blogpost_lm_enabled', 0)) {
            return send_lm_studio_request($messages);
        }

        $args = array(
            'body' => json_encode(array(
                'model' => get_option('ai_blogpost_model', 'gpt-4'),
                'messages' => $messages,
                'temperature' => (float)get_option('ai_blogpost_temperature', 0.7),
                'max_tokens' => min((int)get_option('ai_blogpost_max_tokens', 2048), 4096)
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('ai_blogpost_api_key')
            ),
            'timeout' => 90
        );

        ai_blogpost_debug_log('Sending API request');
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
        
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

        // Prepare prompt from messages
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
                'max_tokens' => 2048,
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

        // Extract actual content from response
        $content = $result['choices'][0]['text'];
        
        // Remove thinking process if present
        if (strpos($content, '</think>') !== false) {
            $content = substr($content, strpos($content, '</think>') + 8);
        }

        // Clean up the response
        $content = trim(str_replace(['### Assistant:', '---'], '', $content));

        // Format response to match OpenAI format
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
 * Fetch DALL·E image based on text content
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
    }
    
    return null;
}

/**
 * Fetch image using DALL-E
 * 
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
function fetch_dalle_image($image_data) {
    try {
        // Validate image data
        if (!is_array($image_data) || empty($image_data['category']) || empty($image_data['template'])) {
            throw new Exception('Invalid image data structure');
        }

        $category = $image_data['category'];

        // Prepare DALL-E prompt
        $dalle_prompt = str_replace(
            ['[category]', '[categorie]'],
            $category,
            $image_data['template']
        );

        ai_blogpost_debug_log('DALL-E Prompt Data:', [
            'category' => $category,
            'prompt' => $dalle_prompt
        ]);

        // Initial log
        ai_blogpost_log_api_call('Image Generation', true, [
            'prompt' => $dalle_prompt,
            'category' => $category,
            'status' => 'Starting image generation'
        ]);

        // API request setup
        $api_key = get_cached_option('ai_blogpost_dalle_api_key');
        if (empty($api_key)) {
            throw new Exception('DALL-E API key missing');
        }

        // Validate model and size compatibility
        $model = get_cached_option('ai_blogpost_dalle_model', 'dall-e-3');
        $size = get_cached_option('ai_blogpost_dalle_size', '1024x1024');
        
        // DALL-E 3 size validation
        if ($model === 'dall-e-3' && !in_array($size, ['1024x1024', '1792x1024', '1024x1792'])) {
            $size = '1024x1024'; // Default to supported size
            ai_blogpost_debug_log('Adjusted size for DALL-E 3 compatibility:', $size);
        }

        $payload = [
            'model' => $model,
            'prompt' => $dalle_prompt,
            'n' => 1,
            'size' => $size
        ];

        // DALL-E 3 specific options
        if ($model === 'dall-e-3') {
            $payload['quality'] = get_cached_option('ai_blogpost_dalle_quality', 'standard');
            $payload['style'] = get_cached_option('ai_blogpost_dalle_style', 'vivid');
        }

        // Make API request
        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'dall-e-3'
            ],
            'body' => json_encode($payload),
            'timeout' => 120 // Increased timeout for larger images
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown API error';
            throw new Exception('API error (' . $response_code . '): ' . $error_message);
        }

        if (empty($body['data'][0]['url'])) {
            throw new Exception('No image URL in response');
        }

        // Download image from URL
        $image_url = $body['data'][0]['url'];
        $image_response = wp_remote_get($image_url);
        
        if (is_wp_error($image_response)) {
            throw new Exception('Failed to download image: ' . $image_response->get_error_message());
        }

        if (wp_remote_retrieve_response_code($image_response) !== 200) {
            throw new Exception('Failed to download image: Invalid response code');
        }

        // Save image
        $filename = 'dalle-' . sanitize_title($category) . '-' . time() . '.png';
        $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
        
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
            throw new Exception('Failed to create attachment: ' . $attach_id->get_error_message());
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Log success
        ai_blogpost_log_api_call('Image Generation', true, [
            'prompt' => $dalle_prompt,
            'category' => $category,
            'image_id' => $attach_id,
            'model' => $model,
            'size' => $size,
            'status' => 'Image Generated Successfully'
        ]);

        return $attach_id;

    } catch (Exception $e) {
        // Log error with detailed information
        $error_data = [
            'error' => $e->getMessage(),
            'prompt' => $dalle_prompt ?? '',
            'category' => $category ?? 'unknown',
            'model' => $model ?? 'unknown',
            'size' => $size ?? 'unknown',
            'status' => 'Error: ' . $e->getMessage()
        ];
        
        ai_blogpost_debug_log('DALL-E Error:', $error_data);
        ai_blogpost_log_api_call('Image Generation', false, $error_data);
        
        return null;
    }
}

/**
 * Fetch image using ComfyUI
 * 
 * @param array $image_data Image generation data
 * @return int|null Attachment ID or null on failure
 */
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
        // Validate API URL format
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid ComfyUI API URL format');
        }

        // Ensure URL is properly formatted
        $api_url = rtrim($api_url, '/');
        if (!preg_match('/^https?:\/\//', $api_url)) {
            $api_url = 'http://' . $api_url;
        }

        ai_blogpost_debug_log('ComfyUI API URL:', $api_url);

        // Check if we have a cached client ID
        $client_id = get_cached_option('ai_blogpost_comfyui_client_id');
        
        // Get new client ID if none exists, forced refresh, or validation fails
        if (empty($client_id) || $force_refresh || !validate_comfyui_client_id($api_url, $client_id)) {
            ai_blogpost_debug_log('Requesting new ComfyUI client ID - Force refresh:', $force_refresh ? 'true' : 'false');
            
            // Implement retry logic
            $max_retries = 3;
            $retry_delay = 2; // seconds
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
                        throw new Exception('Failed to get client ID: ' . $response->get_error_message());
                    }

                    $response_code = wp_remote_retrieve_response_code($response);
                    if ($response_code !== 200) {
                        throw new Exception('Invalid response code: ' . $response_code);
                    }

                    $body = wp_remote_retrieve_body($response);
                    ai_blogpost_debug_log('ComfyUI Response:', $body);
                    
                    $data = json_decode($body, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid JSON response: ' . json_last_error_msg());
                    }
                    
                    if (empty($data['client_id'])) {
                        throw new Exception('Invalid client ID response from ComfyUI server');
                    }

                    $client_id = $data['client_id'];
                    update_option('ai_blogpost_comfyui_client_id', $client_id);
                    
                    ai_blogpost_debug_log('New client ID obtained:', $client_id);
                    
                    // Validate the new client ID immediately
                    if (!validate_comfyui_client_id($api_url, $client_id)) {
                        throw new Exception('Failed to validate new client ID');
                    }

                    return $client_id;

                } catch (Exception $e) {
                    $last_error = $e;
                    $attempt++;
                    
                    ai_blogpost_debug_log(sprintf(
                        'ComfyUI client ID attempt %d/%d failed: %s',
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
            throw new Exception(
                sprintf('Failed to get valid client ID after %d attempts. Last error: %s',
                    $max_retries,
                    $last_error ? $last_error->getMessage() : 'Unknown error'
                )
            );
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
        ai_blogpost_debug_log('Starting ComfyUI client ID validation:', [
            'api_url' => $api_url,
            'client_id' => $client_id
        ]);

        if (empty($api_url) || empty($client_id)) {
            throw new Exception('API URL or client ID is empty');
        }

        // Validate URL format
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid API URL format');
        }

        // Array of endpoints to try in order
        $endpoints = [
            '/system_stats' => function($data) {
                return !empty($data) && is_array($data);
            },
            '/history' => function($data) {
                return is_array($data);
            },
            '/queue' => function($data) {
                return isset($data['queue_running']) || isset($data['items']);
            }
        ];

        foreach ($endpoints as $endpoint => $validator) {
            ai_blogpost_debug_log('Trying endpoint:', $endpoint);
            
            $response = wp_remote_get($api_url . $endpoint, [
                'headers' => [
                    'client_id' => $client_id,
                    'Accept' => 'application/json'
                ],
                'timeout' => 15,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                ai_blogpost_debug_log("Endpoint $endpoint failed:", $response->get_error_message());
                continue;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if (empty($body)) {
                ai_blogpost_debug_log("Empty response from $endpoint");
                continue;
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                ai_blogpost_debug_log("Invalid JSON from $endpoint:", json_last_error_msg());
                continue;
            }

            ai_blogpost_debug_log("Response from $endpoint:", [
                'code' => $response_code,
                'body' => $body,
                'parsed_data' => $data
            ]);

            if ($response_code === 200 && $validator($data)) {
                ai_blogpost_debug_log("Validation successful using $endpoint");
                return true;
            }
        }

        throw new Exception('All validation endpoints failed');

    } catch (Exception $e) {
        ai_blogpost_debug_log('ComfyUI validation error:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

function fetch_comfyui_image_from_text($image_data) {
    try {
        ai_blogpost_debug_log('Starting ComfyUI image generation:', $image_data);

        // Validate image data
        if (!is_array($image_data)) {
            throw new Exception('Image data must be an array');
        }
        if (empty($image_data['category'])) {
            throw new Exception('Category is required in image data');
        }

        $category = $image_data['category'];
        $api_url = get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188');
        
        // Validate and format API URL
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            $api_url = 'http://' . ltrim($api_url, '/');
        }
        $api_url = rtrim($api_url, '/');
        
        ai_blogpost_debug_log('ComfyUI API URL:', $api_url);

        // Get valid client ID with retry logic
        $max_retries = 3;
        $retry_count = 0;
        $client_id = null;
        
        while ($retry_count < $max_retries) {
            try {
                $client_id = get_comfyui_client_id($api_url, $retry_count > 0);
                break;
            } catch (Exception $e) {
                $retry_count++;
                if ($retry_count >= $max_retries) {
                    throw new Exception('Failed to obtain valid client ID after ' . $max_retries . ' attempts');
                }
                sleep(1); // Wait before retry
            }
        }

        if (empty($client_id)) {
            throw new Exception('Unable to obtain valid ComfyUI client ID');
        }

        // Get workflow configuration with validation
        $workflows_json = get_cached_option('ai_blogpost_comfyui_workflows', '[]');
        $workflows = json_decode($workflows_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid workflows JSON: ' . json_last_error_msg());
        }
        
        if (empty($workflows) || !is_array($workflows)) {
            throw new Exception('No valid ComfyUI workflows configured');
        }

        $default_workflow = get_cached_option('ai_blogpost_comfyui_default_workflow', '');
        if (empty($default_workflow)) {
            throw new Exception('No default workflow selected');
        }
        
        ai_blogpost_debug_log('ComfyUI Workflow Configuration:', [
            'default_workflow' => $default_workflow,
            'available_workflows' => array_column($workflows, 'name')
        ]);

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

        // Queue prompt with enhanced error handling
        $queue_request = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'prompt' => $workflow_data,
                'client_id' => $client_id
            ]),
            'timeout' => 30,
            'sslverify' => false
        ];

        ai_blogpost_debug_log('Queueing ComfyUI prompt:', [
            'url' => $api_url . '/prompt',
            'client_id' => $client_id,
            'workflow_name' => $workflow_config['name']
        ]);

        $queue_response = wp_remote_post($api_url . '/prompt', $queue_request);

        if (is_wp_error($queue_response)) {
            throw new Exception('Failed to queue prompt: ' . $queue_response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($queue_response);
        if ($response_code !== 200) {
            throw new Exception('Invalid response code from queue endpoint: ' . $response_code);
        }

        $queue_body = wp_remote_retrieve_body($queue_response);
        $queue_data = json_decode($queue_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from queue endpoint: ' . json_last_error_msg());
        }

        if (empty($queue_data['prompt_id'])) {
            throw new Exception('No prompt ID in queue response: ' . $queue_body);
        }

        ai_blogpost_debug_log('ComfyUI prompt queued:', [
            'prompt_id' => $queue_data['prompt_id']
        ]);

        $prompt_id = $queue_data['prompt_id'];
        $start_time = time();
        $timeout = 300; // 5 minutes timeout

        // Poll for completion with improved error handling
        $poll_interval = 2; // seconds between polls
        $polls = 0;
        $max_polls = ceil($timeout / $poll_interval);

        while (time() - $start_time < $timeout) {
            $polls++;
            ai_blogpost_debug_log(sprintf(
                'Polling ComfyUI history (attempt %d/%d):',
                $polls,
                $max_polls
            ), ['prompt_id' => $prompt_id]);

            $history_response = wp_remote_get($api_url . '/history/' . $prompt_id, [
                'timeout' => 30,
                'sslverify' => false,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if (is_wp_error($history_response)) {
                throw new Exception('Failed to check history: ' . $history_response->get_error_message());
            }

            $history_body = wp_remote_retrieve_body($history_response);
            $history_data = json_decode($history_body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in history response: ' . json_last_error_msg());
            }

            if (!empty($history_data['error'])) {
                throw new Exception('Workflow error: ' . $history_data['error']);
            }
            
            if (!empty($history_data['outputs'])) {
                // Find the image output node
                $image_data = null;
                foreach ($history_data['outputs'] as $node_id => $output) {
                    if (!empty($output['images'])) {
                        $image_data = $output['images'][0];
                        break;
                    }
                }

                if ($image_data) {
                    ai_blogpost_debug_log('ComfyUI image ready for download:', [
                        'filename' => $image_data['filename'],
                        'type' => $image_data['type']
                    ]);

                    // Prepare image download URL
                    $image_url = $api_url . '/view?' . http_build_query([
                        'filename' => $image_data['filename'],
                        'subfolder' => $image_data['subfolder'] ?? '',
                        'type' => $image_data['type']
                    ]);

                    // Download with retry logic
                    $download_attempts = 3;
                    $download_success = false;
                    $last_download_error = null;

                    for ($i = 1; $i <= $download_attempts; $i++) {
                        try {
                            $image_response = wp_remote_get($image_url, [
                                'timeout' => 30,
                                'sslverify' => false
                            ]);

                            if (is_wp_error($image_response)) {
                                throw new Exception('Download failed: ' . $image_response->get_error_message());
                            }

                            $response_code = wp_remote_retrieve_response_code($image_response);
                            if ($response_code !== 200) {
                                throw new Exception('Invalid response code: ' . $response_code);
                            }

                            $image_data = wp_remote_retrieve_body($image_response);
                            if (empty($image_data)) {
                                throw new Exception('Empty image data received');
                            }

                            $download_success = true;
                            break;

                        } catch (Exception $e) {
                            $last_download_error = $e;
                            ai_blogpost_debug_log(sprintf(
                                'Image download attempt %d/%d failed: %s',
                                $i,
                                $download_attempts,
                                $e->getMessage()
                            ));

                            if ($i < $download_attempts) {
                                sleep(2);
                            }
                        }
                    }

                    if (!$download_success) {
                        throw new Exception('Failed to download image after ' . $download_attempts . ' attempts: ' . 
                            ($last_download_error ? $last_download_error->getMessage() : 'Unknown error'));
                    }

                    // Save image
                    $filename = 'comfyui-' . sanitize_title($category) . '-' . time() . '.png';
                    $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
                    
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

            if (!empty($history_data['error'])) {
                throw new Exception('Workflow error: ' . $history_data['error']);
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

        // Filter for GPT and DALL-E models
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
