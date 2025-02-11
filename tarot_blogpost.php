<?php
/*
Plugin Name: AI-Generated Tarot Blogpost with DALL·E
Description: Genereert een dagelijkse/wekelijkse blogpost met tarot-achtige correspondenties en een DALL·E afbeelding. DALL·E prompt komt uit de tekst output en DALL·E model is instelbaar in het dashboard. Nu met datum shortcode [datum].
Version: 1.0
Author: Your Name
*/

// ------------------ SETTINGS INIT ------------------
function ai_blogpost_initialize_settings() {
    register_setting('ai_blogpost_settings', 'ai_blogpost_temperature');
    register_setting('ai_blogpost_settings', 'ai_blogpost_max_tokens');
    register_setting('ai_blogpost_settings', 'ai_blogpost_role');
    register_setting('ai_blogpost_settings', 'ai_blogpost_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_prompt');
    register_setting('ai_blogpost_settings', 'ai_blogpost_post_frequency');
    register_setting('ai_blogpost_settings', 'ai_blogpost_custom_categories');

    // DALL·E settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');
}
add_action('admin_init', 'ai_blogpost_initialize_settings');

// ------------------ ADMIN PAGE ------------------
function ai_blogpost_admin_menu() {
    add_menu_page('AI Blogpost Settings', 'AI Blogpost', 'manage_options', 'ai_blogpost', 'ai_blogpost_admin_page');
}
add_action('admin_menu', 'ai_blogpost_admin_menu');

function ai_blogpost_admin_page() {
    echo '<div class="wrap ai-blogpost-dashboard">';
    echo '<h1>AI Blogpost Dashboard</h1>';
    
    // Settings Form
    echo '<div class="dashboard-grid">';
    
    // Left column - Settings
    echo '<div class="settings-column">';
    echo '<form method="post" action="options.php">';
    settings_fields('ai_blogpost_settings');
    do_settings_sections('ai_blogpost_settings');
    
    // General Settings
    echo '<div class="settings-group">';
    echo '<h2>Schedule Settings</h2>';
    display_general_settings();
    echo '</div>';
    
    // Text Generation Settings
    echo '<div class="settings-group">';
    echo '<h2>OpenAI Text Generation</h2>';
    display_text_settings();
    echo '</div>';
    
    // Image Generation Settings
    echo '<div class="settings-group">';
    echo '<h2>DALL·E Image Generation</h2>';
    display_image_settings();
    echo '</div>';
    
    submit_button('Save Settings');
    echo '</form>';
    echo '</div>'; // Close settings-column
    
    // Right column - Status and Test
    echo '<div class="status-column">';
    
    // Test Post Section
    echo '<div class="test-post-section">';
    echo '<div class="test-post-header">';
    echo '<h2>Test Generation</h2>';
    echo '<form method="post" style="display:inline-block;">';
    echo '<input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">';
    echo '</form>';
    echo '</div>';
    
    // Next scheduled post info
    $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
    if ($next_post_time) {
        echo '<div class="next-post-info">';
        echo '<span class="dashicons dashicons-calendar-alt"></span> ';
        echo 'Next scheduled post: ' . get_date_from_gmt(
            date('Y-m-d H:i:s', $next_post_time), 
            'F j, Y @ H:i'
        );
        echo '</div>';
    }
    echo '</div>'; // Close test-post-section
    
    // Status Panel
    echo '<div class="status-panel">';
    echo '<div class="status-header">';
    echo '<h2>Generation Status</h2>';
    echo '<div class="status-actions">';
    echo '<form method="post" style="display: inline;">';
    wp_nonce_field('clear_ai_logs_nonce');
    echo '<input type="submit" name="clear_ai_logs" class="button" value="Clear Logs">';
    echo '</form>';
    echo '<button type="button" class="button" onclick="window.location.reload();">Refresh Status</button>';
    echo '</div>';
    echo '</div>';
    
    // Text Generation Status
    echo '<h3>Text Generation</h3>';
    display_api_logs('Text Generation');
    
    // Image Generation Status
    echo '<h3 style="margin-top: 20px;">Image Generation</h3>';
    display_api_logs('Image Generation');
    
    echo '</div>'; // Close status-panel
    echo '</div>'; // Close status-column
    
    echo '</div>'; // Close dashboard-grid
    echo '</div>'; // Close wrap

    // Add dashboard styling
    echo '<style>
        .ai-blogpost-dashboard {
            margin: 20px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .settings-column,
        .status-column {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .settings-group {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .settings-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .settings-group h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .test-post-section {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .test-post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .test-post-header h2 {
            margin: 0;
        }
        .next-post-info {
            margin-top: 10px;
            color: #666;
        }
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .status-actions {
            display: flex;
            gap: 10px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .log-table th,
        .log-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .log-table tr:hover {
            background: #f8f9fa;
        }
        .description {
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
        @media screen and (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>';
}

// Add these new helper functions for tab content
function display_general_settings() {
    echo '<table class="form-table">';
    
    // Post Frequency
    echo '<tr>';
    echo '<th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">';
    $frequency = get_cached_option('ai_blogpost_post_frequency', 'daily');
    echo '<option value="daily" ' . selected($frequency, 'daily', false) . '>Daily</option>';
    echo '<option value="weekly" ' . selected($frequency, 'weekly', false) . '>Weekly</option>';
    echo '</select>';
    echo '<p class="description">How often should new posts be generated?</p>';
    echo '</td>';
    echo '</tr>';

    // Custom Categories
    echo '<tr>';
    echo '<th><label for="ai_blogpost_custom_categories">Categories</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_custom_categories', 'tarot'));
    echo '</textarea>';
    echo '<p class="description">Enter categories (one per line) that will be used for post generation</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
}

function display_text_settings() {
    echo '<table class="form-table">';
    
    // OpenAI API Key
    echo '<tr>';
    echo '<th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="' . esc_attr(get_cached_option('ai_blogpost_api_key')) . '">';
    echo '<p class="description">Your OpenAI API key for text generation</p>';
    echo '</td>';
    echo '</tr>';

    // Model Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_model">GPT Model</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_model" id="ai_blogpost_model">';
    $model = get_cached_option('ai_blogpost_model', 'gpt-4');
    $models = array('gpt-4', 'gpt-3.5-turbo');
    foreach ($models as $m) {
        echo '<option value="' . esc_attr($m) . '" ' . selected($model, $m, false) . '>' . esc_html($m) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Temperature
    echo '<tr>';
    echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
    echo '<td>';
    echo '<input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" value="' . esc_attr(get_cached_option('ai_blogpost_temperature', '0.7')) . '">';
    echo '<p class="description">Controls randomness (0 = deterministic, 2 = very random)</p>';
    echo '</td>';
    echo '</tr>';

    // Max Tokens
    echo '<tr>';
    echo '<th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>';
    echo '<td>';
    echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="' . esc_attr(get_cached_option('ai_blogpost_max_tokens', '1024')) . '">';
    echo '<p class="description">Maximum length of generated text</p>';
    echo '</td>';
    echo '</tr>';

    // System Role
    echo '<tr>';
    echo '<th><label for="ai_blogpost_role">System Role</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_role', 'Write for a tarot website a SEO blogpost with the [categorie] as keyword'));
    echo '</textarea>';
    echo '<p class="description">System role instruction for the AI</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
}

function display_image_settings() {
    echo '<table class="form-table">';
    
    // Enable DALL-E
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_enabled">Enable DALL-E</label></th>';
    echo '<td>';
    echo '<input type="checkbox" name="ai_blogpost_dalle_enabled" id="ai_blogpost_dalle_enabled" value="1" ' . checked(get_cached_option('ai_blogpost_dalle_enabled', 0), 1, false) . '>';
    echo '<p class="description">Enable DALL-E image generation for posts</p>';
    echo '</td>';
    echo '</tr>';

    // DALL-E API Key
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_api_key">DALL-E API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="' . esc_attr(get_cached_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<p class="description">Your OpenAI API key for DALL-E (can be same as text generation)</p>';
    echo '</td>';
    echo '</tr>';

    // DALL-E Model
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_model">DALL-E Model</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_dalle_model" id="ai_blogpost_dalle_model">';
    $model = get_cached_option('ai_blogpost_dalle_model', 'dall-e-3');
    $models = array('dall-e-3', 'dall-e-2');
    foreach ($models as $m) {
        echo '<option value="' . esc_attr($m) . '" ' . selected($model, $m, false) . '>' . esc_html($m) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Image Size
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_size">Image Size</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_dalle_size" id="ai_blogpost_dalle_size">';
    $size = get_cached_option('ai_blogpost_dalle_size', '1024x1024');
    $sizes = array('1024x1024', '1024x1792', '1792x1024');
    foreach ($sizes as $s) {
        echo '<option value="' . esc_attr($s) . '" ' . selected($size, $s, false) . '>' . esc_html($s) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Style
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_style">Style</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_dalle_style" id="ai_blogpost_dalle_style">';
    $style = get_cached_option('ai_blogpost_dalle_style', 'vivid');
    $styles = array('vivid' => 'Vivid', 'natural' => 'Natural');
    foreach ($styles as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($style, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Quality
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_quality">Quality</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_dalle_quality" id="ai_blogpost_dalle_quality">';
    $quality = get_cached_option('ai_blogpost_dalle_quality', 'standard');
    $qualities = array('standard' => 'Standard', 'hd' => 'HD');
    foreach ($qualities as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($quality, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Prompt Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 
        'Create a mystical and ethereal tarot-inspired digital art featuring [categorie] symbolism, with magical elements in a dreamlike atmosphere.'));
    echo '</textarea>';
    echo '<p class="description">Template for generating DALL-E prompts. Use [categorie] for category placeholder.</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
}

function display_status_panel() {
    echo '<div class="status-panel">';
    // ... (existing status panel code with improved styling)
    echo '</div>';
}

// Test post handler with static flag
function create_test_ai_blogpost() {
    static $already_running = false;
    
    if (isset($_POST['test_ai_blogpost']) && !$already_running) {
        $already_running = true;
        
        try {
            // Create post and handle both text and image generation
            $post_id = create_ai_blogpost();
            
            if ($post_id) {
                // Add success notice to transient to avoid duplicate messages
                set_transient('ai_blogpost_test_notice', 'success', 30);
            }
        } catch (Exception $e) {
            // Log error and set error notice
            error_log('Test post creation failed: ' . $e->getMessage());
            set_transient('ai_blogpost_test_notice', 'error', 30);
        }
    }
    
    // Display notice if set
    $notice_type = get_transient('ai_blogpost_test_notice');
    if ($notice_type) {
        delete_transient('ai_blogpost_test_notice');
        if ($notice_type === 'success') {
            echo '<div class="updated notice is-dismissible"><p>Test Post Created Successfully!</p></div>';
        } else {
            echo '<div class="error notice is-dismissible"><p>Test Post Creation Failed. Check error logs.</p></div>';
        }
    }
}

// Remove any existing hooks and add our new one
remove_action('admin_notices', 'create_test_ai_blogpost');
add_action('admin_notices', 'create_test_ai_blogpost', 10, 0);

// ------------------ CORRESPONDENTIES LOGICA ------------------
function ai_blogpost_get_correspondences() {
    static $data = null;
    
    if ($data === null) {
        // Get categories from settings
        $categories = array_filter(array_map('trim', explode("\n", 
            get_cached_option('ai_blogpost_custom_categories', 'tarot'))));
        
        $data = array(
            'categorie' => $categories, // Add categories to correspondences
            'planeet' => array('Zon', 'Maan', 'Mars', 'Mercurius', 'Jupiter', 'Venus', 'Saturnus'),
            'regenboogkleur' => array('Rood', 'Oranje', 'Geel', 'Groen', 'Blauw', 'Indigo', 'Violet'),
            'dag' => array('Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'),
            'hemellichaam'   => array('Zon', 'Maan', 'Mars', 'Mercurius', 'Jupiter', 'Venus', 'Saturnus'),
            'hermetisch_principe' => array('Geest', 'Overeenkomst', 'Trilling', 'Polariteit', 'Ritme', 'Oorzaak en Gevolg', 'Geslacht'),
            'mineraal'       => array('Goud', 'Zilver', 'IJzer', 'Kwik', 'Tin', 'Koper', 'Lood'),
            'geur'           => array('Wierook', 'Sandelhout', 'Muskus', 'Lavendel', 'Cederhout', 'Rozengeur', 'Patchouli'),
            'plant_kruid'    => array('Zonnebloem', 'Maanbloem', 'Brandnetel', 'Munt', 'Eik', 'Roos', 'Cipres'),
            'pantheon_grieks' => array('Helios', 'Selene', 'Ares', 'Hermes', 'Zeus', 'Aphrodite', 'Cronus')
        );
        $data = apply_filters('ai_blogpost_correspondences_data', $data);
    }

    return array_map(function($arr) {
        return $arr[array_rand($arr)];
    }, $data);
}

// ------------------ FETCH AI TEXT ------------------
function fetch_ai_response($correspondences) {
    try {
        $api_key = get_cached_option('ai_blogpost_api_key');
        if (empty($api_key)) {
            throw new Exception('API key is missing');
        }

        $prompt = prepare_ai_prompt($correspondences);
        $response = send_ai_request($prompt);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }

        return array(
            'content' => $response['choices'][0]['message']['content'],
            'category' => get_cached_option('ai_blogpost_custom_categories', 'Uncategorized'),
            'correspondences' => $correspondences
        );
    } catch (Exception $e) {
        error_log('AI Response Error: ' . $e->getMessage());
        return null;
    }
}

function prepare_ai_prompt($correspondences) {
    $base_prompt = "Schrijf een Nederlandse blog over [categorie]. Gebruik secties:
||Title||:
||Content||:
||Category||:[categorie]
Schrijf de inhoud van de content sectie binnen de <article> </article> tags en gebruik <p> en <h1> <h2>.";

    $prompt = $base_prompt;
    foreach ($correspondences as $key => $value) {
        $prompt = str_replace("[$key]", $value, $prompt);
    }
    
    return str_replace('[datum]', date_i18n(get_option('date_format')), $prompt);
}

function prepare_dalle_prompt($correspondences) {
    $template = get_cached_option('ai_blogpost_dalle_prompt_template', 
        'Create a mystical and ethereal tarot-inspired digital art featuring [categorie] symbolism, with magical elements in a dreamlike atmosphere.');
    
    $prompt = $template;
    foreach ($correspondences as $key => $value) {
        $prompt = str_replace("[$key]", $value, $prompt);
    }
    
    return $prompt;
}

function send_ai_request($prompt) {
    try {
        $args = array(
            'body' => json_encode(array(
                'model' => get_cached_option('ai_blogpost_model', 'gpt-4'),
                'messages' => array(
                    array("role" => "system", "content" => get_cached_option('ai_blogpost_role')),
                    array("role" => "user", "content" => $prompt)
                ),
                'temperature' => (float)get_cached_option('ai_blogpost_temperature', 0.7),
                'max_tokens' => (int)get_cached_option('ai_blogpost_max_tokens', 1024)
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_cached_option('ai_blogpost_api_key')
            ),
            'timeout' => 60
        );

        // Log the request (excluding API key) as 'pending' instead of 'failed'
        $log_args = $args;
        $log_args['headers']['Authorization'] = 'Bearer [HIDDEN]';
        ai_blogpost_log_api_call('Text Generation', true, array(
            'request' => $log_args,
            'prompt' => $prompt,
            'status' => 'Request Sent'
        ));

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log the successful response
        ai_blogpost_log_api_call('Text Generation', true, array(
            'response' => $result,
            'prompt' => $prompt,
            'status' => 'Response Received'
        ));
        
        return $result;
    } catch (Exception $e) {
        // Only log as failed for actual errors
        ai_blogpost_log_api_call('Text Generation', false, array(
            'error' => $e->getMessage(),
            'prompt' => $prompt,
            'status' => 'Error'
        ));
        throw $e;
    }
}

// ------------------ FETCH DALL·E IMAGE ------------------
function fetch_dalle_image_from_text($dalle_prompt) {
    $dalle_enabled = get_cached_option('ai_blogpost_dalle_enabled', 0);
    if (!$dalle_enabled) {
        return null;
    }

    if (empty($dalle_prompt)) {
        error_log('No DALL·E prompt provided');
        return null;
    }

    $api_key = get_cached_option('ai_blogpost_dalle_api_key', '');
    if (empty($api_key)) {
        error_log('DALL·E API key is missing');
        return null;
    }

    $size = get_cached_option('ai_blogpost_dalle_size', '1024x1024');
    $dalle_style = get_cached_option('ai_blogpost_dalle_style', '');
    $dalle_quality = get_cached_option('ai_blogpost_dalle_quality', '');
    $dalle_model = get_cached_option('ai_blogpost_dalle_model', 'dall-e-3');

    // Prepare the payload
    $payload = array(
        'model' => $dalle_model,
        'prompt' => $dalle_prompt,
        'n' => 1,
        'size' => $size,
        'response_format' => 'b64_json'
    );

    if (!empty($dalle_style)) {
        $payload['style'] = $dalle_style;
    }
    if (!empty($dalle_quality)) {
        $payload['quality'] = $dalle_quality;
    }

    // Debug log
    error_log('DALL·E Request Payload: ' . print_r($payload, true));

    try {
        // Log initial request as success with 'Request Sent' status
        ai_blogpost_log_api_call('Image Generation', true, array(
            'prompt' => $dalle_prompt,
            'settings' => $payload,
            'status' => 'Request Sent'
        ));
        
        $response = wp_remote_post('https://api.openai.com/v1/images/generations', array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 60
        ));

        if(is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        // Log raw response for debugging
        error_log('DALL·E Raw Response: ' . wp_remote_retrieve_body($response));
        
        $response_body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body,true);
        if(empty($decoded_response['data'][0]['b64_json'])){
            error_log('DALL·E returned no base64 image data. Response: '.$response_body);
            return null;
        }

        $image_base64 = $decoded_response['data'][0]['b64_json'];
        $image_data = base64_decode($image_base64);
        if($image_data===false){
            error_log('Failed to decode base64 image data.');
            return null;
        }

        $filename='dalle_image_'.time().'.png';
        $upload = wp_upload_bits($filename,null,$image_data);
        if($upload['error']){
            error_log('Image upload failed: '.$upload['error']);
            return null;
        }

        require_once(ABSPATH.'wp-admin/includes/image.php');
        require_once(ABSPATH.'wp-admin/includes/file.php');
        require_once(ABSPATH.'wp-admin/includes/media.php');

        $wp_filetype = wp_check_filetype($upload['file'],null);
        $attachment = array(
            'post_mime_type'=>$wp_filetype['type'],
            'post_title'=>sanitize_file_name($filename),
            'post_content'=>'',
            'post_status'=>'inherit'
        );

        $attach_id = wp_insert_attachment($attachment,$upload['file']);
        if(is_wp_error($attach_id)){
            error_log('Failed to insert attachment: '.$attach_id->get_error_message());
            return null;
        }

        $attach_data = wp_generate_attachment_metadata($attach_id,$upload['file']);
        wp_update_attachment_metadata($attach_id,$attach_data);

        // Log final success with more details
        ai_blogpost_log_api_call('Image Generation', true, array(
            'prompt' => $dalle_prompt,
            'image_id' => $attach_id,
            'status' => 'Image Generated'
        ));
        
        return $attach_id;
    } catch (Exception $e) {
        // Only log as failed for actual errors
        ai_blogpost_log_api_call('Image Generation', false, array(
            'error' => $e->getMessage(),
            'prompt' => $dalle_prompt,
            'status' => 'Error: ' . $e->getMessage()
        ));
        return null;
    }
}

// ------------------ CREATE POST ------------------
function create_ai_blogpost() {
    try {
        $correspondences = ai_blogpost_get_correspondences();
        
        // Add default category if not set
        if (!isset($correspondences['categorie'])) {
            $categories = explode("\n", get_cached_option('ai_blogpost_custom_categories', 'tarot'));
            $correspondences['categorie'] = trim($categories[array_rand($categories)]);
        }
        
        // Get DALL-E prompt template ready (if enabled)
        if (get_cached_option('ai_blogpost_dalle_enabled', 0)) {
            $dalle_prompt = prepare_dalle_prompt($correspondences);
        }
        
        // Generate text content with single API call
        $ai_result = fetch_ai_response($correspondences);
        if (!$ai_result) {
            throw new Exception('No AI result received');
        }

        $parsed_content = parse_ai_content($ai_result['content']);
        
        // Use category from correspondences if parsed category is empty
        $category = !empty($parsed_content['category']) ? 
            $parsed_content['category'] : 
            $correspondences['categorie'];
        
        // Create post first
        $post_data = array(
            'post_title' => wp_strip_all_tags($parsed_content['title']),
            'post_content' => wpautop($parsed_content['content']),
            'post_status' => 'publish',
            'post_author' => 1,
            'post_category' => array(get_cat_ID($category) ?: 1)
        );

        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }

        // Handle DALL-E image separately if enabled
        if (get_cached_option('ai_blogpost_dalle_enabled', 0)) {
            error_log('Using DALL-E prompt: ' . $dalle_prompt);
            
            $attach_id = fetch_dalle_image_from_text($dalle_prompt);
            if ($attach_id) {
                set_post_thumbnail($post_id, $attach_id);
                error_log('Featured image set for post ID: ' . $post_id);
            }
        }

        return $post_id;
    } catch (Exception $e) {
        error_log('Error in create_ai_blogpost: ' . $e->getMessage());
        return null;
    }
}

function parse_ai_content($ai_content) {
    $patterns = array(
        'title' => '/\|\|Title\|\|:\s*(.*?)(?=\|\|Content\|\|:)/s',
        'content' => '/\|\|Content\|\|:\s*(.*?)(?=\|\|Category\|\|:)/s',
        'category' => '/\|\|Category\|\|:\s*(.*?)(?=\|\|DALL_E_Prompt\|\|:|$)/s',
        'dalle_prompt' => '/\|\|DALL_E_Prompt\|\|:\s*(.*?)$/s'
    );

    $parsed = array();
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $ai_content, $matches)) {
            $parsed[$key] = trim($matches[1]);
        } else {
            error_log("Failed to parse {$key} from AI response");
            $parsed[$key] = $key === 'title' ? 'AI-Generated Post' : '';
        }
    }

    // Debug log
    error_log('Parsed Content: ' . print_r($parsed, true));

    return $parsed;
}

// ------------------ CRON SCHEDULING ------------------
function ai_blogpost_schedule_cron() {
    $frequency = get_cached_option('ai_blogpost_post_frequency','daily');
    $is_scheduled = get_cached_option('ai_blogpost_is_scheduled',false);

    if($frequency=='weekly'){
        add_filter('cron_schedules','ai_blogpost_weekly_schedule');
    }

    if(!$is_scheduled){
        if($frequency=='daily'){
            wp_schedule_event(time(),'daily','ai_blogpost_cron_hook');
        } elseif($frequency=='weekly'){
            wp_schedule_event(time(),'weekly','ai_blogpost_cron_hook');
        }
        update_option('ai_blogpost_is_scheduled',true);
    }
}
add_action('wp_loaded','ai_blogpost_schedule_cron');

function ai_blogpost_weekly_schedule($schedules){
    if(!isset($schedules['weekly'])){
        $schedules['weekly']=array(
            'interval'=>604800, // 1 week
            'display'=>__('Once Weekly')
        );
    }
    return $schedules;
}

add_action('ai_blogpost_cron_hook','create_ai_blogpost');

// Deactivatie
function ai_blogpost_deactivation(){
    wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    update_option('ai_blogpost_is_scheduled',false);
}
register_deactivation_hook(__FILE__,'ai_blogpost_deactivation');

function get_cached_option($option_name, $default = '') {
    static $cache = array();
    
    if (!isset($cache[$option_name])) {
        $cache[$option_name] = get_option($option_name, $default);
    }
    
    return $cache[$option_name];
}

// Add logging function
function ai_blogpost_log_api_call($type, $success, $data) {
    $logs = get_option('ai_blogpost_api_logs', array());
    
    // Add new log entry
    $logs[] = array(
        'time' => time(),
        'type' => $type,
        'success' => $success,
        'data' => $data
    );
    
    // Keep only last 20 entries
    if (count($logs) > 20) {
        $logs = array_slice($logs, -20);
    }
    
    update_option('ai_blogpost_api_logs', $logs);
}

// Add new helper function for displaying logs
function display_api_logs($type) {
    $logs = get_option('ai_blogpost_api_logs', array());
    $filtered_logs = array_filter($logs, function($log) use ($type) {
        return $log['type'] === $type;
    });
    $filtered_logs = array_slice($filtered_logs, -5);
    
    if (empty($filtered_logs)) {
        echo '<div style="padding: 20px; background: #f8f9fa; border-radius: 4px; text-align: center;">';
        echo "<p style='margin: 0;'>No {$type} communications logged yet.</p>";
        echo '</div>';
    } else {
        echo '<div style="border: 1px solid #e2e4e7; border-radius: 4px; overflow: hidden;">';
        echo '<table class="widefat" style="margin: 0; border: none;">
            <thead>
                <tr>
                    <th style="width: 30%;">Time</th>
                    <th style="width: 20%;">Status</th>
                    <th style="width: 50%;">Details</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($filtered_logs as $log) {
            $status_color = $log['success'] ? '#46b450' : '#dc3232';
            $log_id = 'log_' . $log['type'] . '_' . $log['time'];
            echo sprintf(
                '<tr>
                    <td>%s</td>
                    <td><span style="color: %s; font-weight: 500;">%s</span></td>
                    <td>
                        <button type="button" class="button button-small" onclick="toggleDetails(\'%s\')">Show Details</button>
                        <div id="%s" style="display:none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-family: monospace; font-size: 12px;">%s</div>
                    </td>
                </tr>',
                esc_html(date('Y-m-d H:i:s', $log['time'])),
                $status_color,
                $log['success'] ? 'Success' : 'Failed',
                esc_attr($log_id),
                esc_attr($log_id),
                esc_html(print_r($log['data'], true))
            );
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
}

// Add this function for clearing logs
function ai_blogpost_clear_logs() {
    if (isset($_POST['clear_ai_logs']) && check_admin_referer('clear_ai_logs_nonce')) {
        delete_option('ai_blogpost_api_logs');
        wp_redirect(add_query_arg('logs_cleared', '1', wp_get_referer()));
        exit;
    }
}
add_action('admin_init', 'ai_blogpost_clear_logs');
