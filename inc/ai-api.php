<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Main AI API handler class
 */
class AiApi {
    /**
     * Fetch AI response for blog post generation
     */
    public static function fetchResponse(array $post_data): ?array {
        try {
            $api_key = Helpers::getCachedOption('ai_blogpost_api_key');
            if (empty($api_key)) {
                throw new \Exception('API key is missing');
            }

            Logs::logApiCall('Text Generation', true, [
                'status' => 'Starting request',
                'category' => $post_data['category']
            ]);

            $prompt = self::preparePrompt($post_data);
            $response = self::sendRequest($prompt);
            
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid API response format');
            }

            Logs::logApiCall('Text Generation', true, [
                'prompt' => $prompt,
                'content' => $response['choices'][0]['message']['content'],
                'status' => 'Content generated successfully',
                'category' => $post_data['category']
            ]);

            return [
                'content' => $response['choices'][0]['message']['content'],
                'category' => $post_data['category'],
                'focus_keyword' => $post_data['focus_keyword']
            ];
        } catch (\Exception $e) {
            Logs::logApiCall('Text Generation', false, [
                'error' => $e->getMessage(),
                'category' => $post_data['category'] ?? 'unknown',
                'status' => 'Failed: ' . $e->getMessage()
            ]);
            error_log('AI Response Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepare AI prompt with system messages and user content
     */
    private static function preparePrompt(array $post_data): array {
        try {
            $language = get_option('ai_blogpost_language', 'en');
            
            $system_messages = [
                [
                    "role" => "system",
                    "content" => LanguageHandler::getInstruction($language)
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
            
            Logs::debug('Prepared messages:', $messages);
            return $messages;
            
        } catch (\Exception $e) {
            Logs::debug('Error in preparePrompt:', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send request to OpenAI API
     */
    private static function sendRequest(array $messages): array {
        try {
            if (Helpers::getCachedOption('ai_blogpost_lm_enabled', 0)) {
                return LmStudioApi::sendRequest($messages);
            }

            $args = [
                'body' => json_encode([
                    'model' => get_option('ai_blogpost_model', 'gpt-4'),
                    'messages' => $messages,
                    'temperature' => (float)get_option('ai_blogpost_temperature', 0.7),
                    'max_tokens' => min((int)get_option('ai_blogpost_max_tokens', 2048), 4096)
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . get_option('ai_blogpost_api_key')
                ],
                'timeout' => 90
            ];

            Logs::debug('Sending API request');
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
            
            if (is_wp_error($response)) {
                throw new \Exception('API request failed: ' . $response->get_error_message());
            }
            
            $result = json_decode(wp_remote_retrieve_body($response), true);
            Logs::debug('API response received:', $result);
            
            if (!isset($result['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid API response format');
            }
            
            return $result;
        } catch (\Exception $e) {
            Logs::debug('Error in sendRequest:', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch DALL·E image based on text content
     */
    public static function fetchDalleImage(array $image_data): ?int {
        $generation_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        return match($generation_type) {
            'comfyui' => ComfyUiApi::fetchImage($image_data),
            'dalle' => DalleApi::fetchImage($image_data),
            'localai' => LocalAiApi::fetchImage($image_data),
            default => null
        };
    }

    /**
     * Fetch available OpenAI models
     */
    public static function fetchModels(): bool {
        try {
            $api_key = Helpers::getCachedOption('ai_blogpost_api_key');
            if (empty($api_key)) {
                throw new \Exception('API key is missing');
            }

            $response = wp_remote_get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                throw new \Exception('Failed to fetch models: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($body['data'])) {
                throw new \Exception('Invalid API response format');
            }

            $gpt_models = [];
            $dalle_models = [];
            foreach ($body['data'] as $model) {
                if (str_contains($model['id'], 'gpt')) {
                    $gpt_models[] = $model['id'];
                } elseif (str_contains($model['id'], 'dall-e')) {
                    $dalle_models[] = $model['id'];
                }
            }

            update_option('ai_blogpost_available_gpt_models', $gpt_models);
            update_option('ai_blogpost_available_dalle_models', $dalle_models);
            return true;
        } catch (\Exception $e) {
            Logs::debug('Error fetching models:', $e->getMessage());
            return false;
        }
    }
}

/**
 * LM Studio API handler
 */
class LmStudioApi {
    public static function sendRequest(array $messages): array {
        try {
            $api_url = rtrim(Helpers::getCachedOption('ai_blogpost_lm_api_url', 'http://localhost:1234'), '/') . '/v1';

            $prompt = '';
            foreach ($messages as $message) {
                if ($message['role'] === 'system') {
                    $prompt .= "### System:\n" . $message['content'] . "\n\n";
                } else if ($message['role'] === 'user') {
                    $prompt .= "### User:\n" . $message['content'] . "\n\n";
                }
            }
            $prompt .= "### Assistant:\n";

            $args = [
                'body' => json_encode([
                    'model' => Helpers::getCachedOption('ai_blogpost_lm_model', 'model.gguf'),
                    'prompt' => $prompt,
                    'temperature' => (float)Helpers::getCachedOption('ai_blogpost_temperature', 0.7),
                    'max_tokens' => min((int)Helpers::getCachedOption('ai_blogpost_max_tokens', 2048), 4096),
                    'stream' => false
                ]),
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 300,
                'sslverify' => false
            ];

            $response = wp_remote_post($api_url . '/completions', $args);
            
            if (is_wp_error($response)) {
                throw new \Exception('LM Studio API request failed: ' . $response->get_error_message());
            }

            $result = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!isset($result['choices'][0]['text'])) {
                throw new \Exception('Invalid response format from LM Studio');
            }

            $content = $result['choices'][0]['text'];
            if (str_contains($content, '</think>')) {
                $content = substr($content, strpos($content, '</think>') + 8);
            }
            $content = trim(str_replace(['### Assistant:', '---'], '', $content));

            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => $content
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Logs::debug('Error in LmStudioApi::sendRequest:', $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Local AI API handler
 */
class LocalAiApi {
    public static function fetchImage(array $image_data): ?int {
        try {
            if (!isset($image_data['category'], $image_data['template'])) {
                throw new \Exception('Invalid image data structure');
            }

            $category = $image_data['category'];
            $api_url = rtrim(Helpers::getCachedOption('ai_blogpost_localai_api_url', 'http://localhost:8080'), '/');

            $prompt = str_replace(
                ['[category]', '[categorie]'],
                $category,
                $image_data['template']
            );

            Logs::debug('LocalAI Prompt Data:', [
                'category' => $category,
                'prompt' => $prompt
            ]);

            Logs::logApiCall('Image Generation', true, [
                'type' => 'LocalAI',
                'prompt' => $prompt,
                'category' => $category,
                'status' => 'Starting image generation'
            ]);

            $payload = [
                'prompt' => $prompt,
                'n' => 1,
                'size' => Helpers::getCachedOption('ai_blogpost_localai_size', '1024x1024')
            ];

            $response = wp_remote_post($api_url . '/v1/images/generations', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($payload),
                'timeout' => 120,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                throw new \Exception('LocalAI API request failed: ' . $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($response_code !== 200) {
                $error_message = $body['error']['message'] ?? 'Unknown API error';
                throw new \Exception('LocalAI API error (' . $response_code . '): ' . $error_message);
            }

            if (empty($body['data'][0]['url'])) {
                throw new \Exception('No image URL in LocalAI response');
            }

            return self::processImageUrl($body['data'][0]['url'], $category);
        } catch (\Exception $e) {
            Logs::logApiCall('Image Generation', false, [
                'type' => 'LocalAI',
                'error' => $e->getMessage(),
                'category' => $category ?? 'unknown',
                'status' => 'Error: ' . $e->getMessage()
            ]);
            return null;
        }
    }

    private static function processImageUrl(string $image_url, string $category): int {
        $image_response = wp_remote_get($image_url, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($image_response)) {
            throw new \Exception('Failed to download image: ' . $image_response->get_error_message());
        }

        if (wp_remote_retrieve_response_code($image_response) !== 200) {
            throw new \Exception('Failed to download image: Invalid response code');
        }

        $filename = 'localai-' . sanitize_title($category) . '-' . time() . '.png';
        $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
        
        if (!empty($upload['error'])) {
            throw new \Exception('Failed to save image: ' . $upload['error']);
        }

        $attachment = [
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attach_id)) {
            throw new \Exception('Failed to create attachment: ' . $attach_id->get_error_message());
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        Logs::logApiCall('Image Generation', true, [
            'type' => 'LocalAI',
            'prompt' => $image_url,
            'category' => $category,
            'image_id' => $attach_id,
            'status' => 'Image Generated Successfully'
        ]);

        return $attach_id;
    }

    public static function testConnection(): void {
        check_ajax_referer('ai_blogpost_nonce', 'nonce');
        
        $api_url = sanitize_text_field($_POST['url']);
        $api_url = rtrim($api_url, '/');
        
        try {
            Logs::debug('Testing LocalAI connection:', [
                'url' => $api_url
            ]);

            $response = wp_remote_get($api_url . '/v1/models', [
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                Logs::debug('LocalAI connection failed:', $response->get_error_message());
                wp_send_json_error('Connection failed: ' . $response->get_error_message());
                return;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data['data'])) {
                Logs::debug('Invalid LocalAI response:', $data);
                wp_send_json_error('Invalid response from LocalAI server');
                return;
            }

            Logs::logApiCall('LocalAI Test', true, [
                'url' => $api_url,
                'status' => 'Connection successful',
                'models' => $data['data']
            ]);

            wp_send_json_success([
                'message' => 'Connection successful',
                'models' => $data['data']
            ]);
        } catch (\Exception $e) {
            Logs::debug('LocalAI error:', $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

/**
 * ComfyUI API handler
 */
class ComfyUiApi {
    private static function getClientId(string $api_url, bool $force_refresh = false): string {
        try {
            $client_id = Helpers::getCachedOption('ai_blogpost_comfyui_client_id');
            
            if (empty($client_id) || $force_refresh || !self::validateClientId($api_url, $client_id)) {
                Logs::debug('Requesting new ComfyUI client ID');
                
                $response = wp_remote_get($api_url . '/client_id', [
                    'timeout' => 30,
                    'sslverify' => false
                ]);

                if (is_wp_error($response)) {
                    throw new \Exception('Failed to get client ID: ' . $response->get_error_message());
                }

                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (empty($data['client_id'])) {
                    throw new \Exception('Invalid client ID response from ComfyUI server');
                }

                $client_id = $data['client_id'];
                update_option('ai_blogpost_comfyui_client_id', $client_id);
                
                Logs::debug('New client ID obtained:', $client_id);
            }
            
            return $client_id;
        } catch (\Exception $e) {
            Logs::debug('Error in getClientId:', $e->getMessage());
            throw $e;
        }
    }

    private static function validateClientId(string $api_url, string $client_id): bool {
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
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function fetchImage(array $image_data): ?int {
        try {
            if (!isset($image_data['category'])) {
                throw new \Exception('Invalid image data structure');
            }

            $category = $image_data['category'];
            $api_url = rtrim(Helpers::getCachedOption('ai_blogpost_comfyui_api_url', 'http://localhost:8188'), '/');
            if (!preg_match('/^https?:\/\//', $api_url)) {
                $api_url = 'http://' . $api_url;
            }

            $status_response = wp_remote_get($api_url . '/system_stats', [
                'timeout' => 10,
                'sslverify' => false
            ]);
            if (is_wp_error($status_response)) {
                throw new \Exception('ComfyUI server not running: ' . $status_response->get_error_message());
            }

            $workflows = json_decode(Helpers::getCachedOption('ai_blogpost_comfyui_workflows', '[]'), true);
            $default_workflow = Helpers::getCachedOption('ai_blogpost_comfyui_default_workflow', '');
            if (empty($workflows)) {
                throw new \Exception('No ComfyUI workflows configured');
            }

            $workflow_config = null;
            foreach ($workflows as $workflow) {
                if ($workflow['name'] === $default_workflow) {
                    $workflow_config = $workflow;
                    break;
                }
            }
            if (!$workflow_config) {
                throw new \Exception('Selected workflow not found');
            }

            $prompt = self::prepareWorkflowPrompt($workflow_config['workflow'], $category);

            Logs::logApiCall('Image Generation', true, [
                'type' => 'ComfyUI',
                'category' => $category,
                'workflow' => $workflow_config['name'],
                'status' => 'Starting image generation',
                'prompt' => json_encode($prompt)
            ]);

            return self::executeWorkflow($api_url, $prompt, $category);
        } catch (\Exception $e) {
            Logs::logApiCall('Image Generation', false, [
                'type' => 'ComfyUI',
                'error' => $e->getMessage(),
                'category' => $category ?? 'unknown',
                'status' => 'Error: ' . $e->getMessage()
            ]);
            return null;
        }
    }

    private static function prepareWorkflowPrompt(array $workflow_data, string $category): array {
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
        return $prompt;
    }

    private static function executeWorkflow(string $api_url, array $prompt, string $category): ?int {
        $queue_response = wp_remote_post($api_url . '/prompt', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['prompt' => $prompt]),
            'timeout' => 30,
            'sslverify' => false
        ]);
        if (is_wp_error($queue_response)) {
            throw new \Exception('Failed to queue prompt: ' . $queue_response->get_error_message());
        }

        $queue_data = json_decode(wp_remote_retrieve_body($queue_response), true);
        if (empty($queue_data['prompt_id'])) {
            throw new \Exception('Invalid queue response');
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
                throw new \Exception('Failed to check history: ' . $history_response->get_error_message());
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
                return self::processWorkflowImage($api_url, $image_data, $category);
            }

            if (!empty($history_data[$prompt_id]['status']['error'])) {
                throw new \Exception('Workflow error: ' . $history_data[$prompt_id]['status']['error']);
            }

            sleep(2);
        }

        throw new \Exception('Generation timed out');
    }

    private static function processWorkflowImage(string $api_url, array $image_data, string $category): int {
        $image_response = wp_remote_get($api_url . '/view?' . http_build_query([
            'filename' => $image_data['filename'],
            'subfolder' => $image_data['subfolder'] ?? '',
            'type' => $image_data['type']
        ]), [
            'timeout' => 30,
            'sslverify' => false
        ]);
        if (is_wp_error($image_response)) {
            throw new \Exception('Failed to download image: ' . $image_response->get_error_message());
        }

        $filename = 'comfyui-' . sanitize_title($category) . '-' . time() . '.png';
        $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_response));
        if (!empty($upload['error'])) {
            throw new \Exception('Failed to save image: ' . $upload['error']);
        }

        $attachment = [
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attach_id)) {
            throw new \Exception('Failed to create attachment');
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}

// Initialize API hooks
add_action('wp_ajax_test_localai_connection', [LocalAiApi::class, 'testConnection']);
