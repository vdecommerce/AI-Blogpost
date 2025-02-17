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
    $dalle_enabled = get_cached_option('ai_blogpost_dalle_enabled', 0);
    if (!$dalle_enabled) {
        return null;
    }

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

        $payload = [
            'model' => get_cached_option('ai_blogpost_dalle_model', 'dall-e-3'),
            'prompt' => $dalle_prompt,
            'n' => 1,
            'size' => get_cached_option('ai_blogpost_dalle_size', '1024x1024'),
            'response_format' => 'b64_json'
        ];

        // Add optional parameters
        if ($style = get_cached_option('ai_blogpost_dalle_style')) {
            $payload['style'] = $style;
        }
        if ($quality = get_cached_option('ai_blogpost_dalle_quality')) {
            $payload['quality'] = $quality;
        }

        // Make API request
        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['data'][0]['b64_json'])) {
            throw new Exception('No image data in response');
        }

        // Save image
        $decoded_image = base64_decode($body['data'][0]['b64_json']);
        $filename = 'dalle-' . sanitize_title($category) . '-' . time() . '.png';
        
        $upload = wp_upload_bits($filename, null, $decoded_image);
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
            'prompt' => $dalle_prompt,
            'category' => $category,
            'image_id' => $attach_id,
            'status' => 'Image Generated Successfully'
        ]);

        return $attach_id;

    } catch (Exception $e) {
        // Log error
        ai_blogpost_log_api_call('Image Generation', false, [
            'error' => $e->getMessage(),
            'prompt' => $dalle_prompt ?? '',
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
