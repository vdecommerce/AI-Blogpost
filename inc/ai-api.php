<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Fetch AI response for blog post generation
 */
function fetch_ai_response($post_data) {
    try {
        $api_key = get_cached_option('ai_blogpost_api_key');
        if (empty($api_key)) {
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
 */
function prepare_ai_prompt($post_data) {
    try {
        $language = get_option('ai_blogpost_language', 'en');

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

        $user_prompt = "Write a professional blog post about [topic].

Requirements:
1. Create an SEO-optimized title that includes '[topic]'
2. Write well-structured content with proper HTML tags
3. Use h1 for main title, h2 for sections
4. Include relevant keywords naturally
5. Write engaging, informative paragraphs
6. Add a strong conclusion
7. Follow the exact structure shown above";

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
 */
function send_ai_request($messages) {
    try {
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
 * Fetch DALL·E image based on text content
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
 * Fetch image using ComfyUI
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

        // Controleer of de ComfyUI-server draait
        $status_response = wp_remote_get($api_url . '/system_stats', [
            'timeout' => 10,
            'sslverify' => false
        ]);
        if (is_wp_error($status_response)) {
            throw new Exception('ComfyUI server not running: ' . $status_response->get_error_message());
        }

        // Laad workflows
        $workflows_json = get_option('ai_blogpost_comfyui_workflows', '{}');
        $workflows = json_decode($workflows_json, true) ?: [];
        $default_workflow = get_cached_option('ai_blogpost_comfyui_default_workflow', '');
        
        ai_blogpost_debug_log('Available workflows:', $workflows);
        ai_blogpost_debug_log('Selected default workflow:', $default_workflow);

        // Als de geselecteerde workflow niet bestaat, gebruik de eerste beschikbare
        if (empty($workflows)) {
            throw new Exception('No workflows uploaded');
        }
        if (empty($default_workflow) || !isset($workflows[$default_workflow])) {
            $default_workflow = array_key_first($workflows);
            if (!$default_workflow) {
                throw new Exception('No valid workflow available');
            }
            ai_blogpost_debug_log('Falling back to first available workflow:', $default_workflow);
            update_option('ai_blogpost_comfyui_default_workflow', $default_workflow); // Update de default
        }

        $workflow_data = $workflows[$default_workflow];

        $prompt = [];
        foreach ($workflow_data['nodes'] as $node) {
            $node_id = strval($node['id']);
            $prompt[$node_id] = [
                'class_type' => $node['type'] ?? $node['class_type'],
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
                        if (is_string($value) && $node['class_type'] === 'CLIPTextEncode') {
                            // Use the custom prompt template for CLIPTextEncode nodes
                            $template = get_cached_option('ai_blogpost_comfyui_prompt_template', 'beautiful scenery nature [category] landscape, , purple galaxy [category],');
                            $value = str_replace(['[category]', '[categorie]'], $category, $template);
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
                            $prompt[$node_id]['inputs'][$input['name']] = [strval($link[1]), $link[2]];
                            break;
                        }
                    }
                }
            }
        }

        ai_blogpost_log_api_call('Image Generation', true, [
            'type' => 'ComfyUI',
            'category' => $category,
            'workflow' => $default_workflow,
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
                    'workflow' => $default_workflow,
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
 * Fetch image using DALL-E
 */
function fetch_dalle_image($image_data) {
    try {
        if (!is_array($image_data) || empty($image_data['category']) || empty($image_data['template'])) {
            throw new Exception('Invalid image data structure');
        }

        $api_key = get_cached_option('ai_blogpost_dalle_api_key');
        if (empty($api_key)) {
            throw new Exception('DALL-E API key is missing');
        }

        $category = $image_data['category'];
        $prompt = str_replace(['[category]', '[categorie]'], $category, $image_data['template']);

        ai_blogpost_debug_log('DALL-E Prompt Data:', [
            'category' => $category,
            'prompt' => $prompt
        ]);

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => get_cached_option('ai_blogpost_dalle_model', 'dall-e-3'),
                'prompt' => $prompt,
                'n' => 1,
                'size' => get_cached_option('ai_blogpost_dalle_size', '1024x1024'),
                'quality' => get_cached_option('ai_blogpost_dalle_quality', 'standard'),
                'style' => get_cached_option('ai_blogpost_dalle_style', 'vivid')
            ]),
            'timeout' => 60
        ];

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', $args);
        if (is_wp_error($response)) {
            throw new Exception('DALL-E API request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['data'][0]['url'])) {
            throw new Exception('No image URL in DALL-E response');
        }

        $image_url = $body['data'][0]['url'];
        $image_response = wp_remote_get($image_url);
        if (is_wp_error($image_response)) {
            throw new Exception('Failed to download image: ' . $image_response->get_error_message());
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
            throw new Exception('Failed to create attachment');
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in fetch_dalle_image:', $e->getMessage());
        return null;
    }
}
