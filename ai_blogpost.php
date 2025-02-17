<?php
/*
Plugin Name: AI-Generated Tarot Blogpost with DALL·E
Description: Generates a daily/weekly blog post with a DALL·E image. The DALL·E prompt comes from the text output, and the DALL·E model can be configured in the dashboard. 
Version: 1.0
Author: MrHerrie
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

    // Language setting
    register_setting('ai_blogpost_settings', 'ai_blogpost_language');

    // LM Studio settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_model');
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
    echo '<div class="dashboard-content">';
    
    // Test Post Section at the top
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
    
    // Settings Form
    echo '<form method="post" action="options.php">';
    settings_fields('ai_blogpost_settings');
    do_settings_sections('ai_blogpost_settings');
    
    // Settings sections in order
    echo '<div class="settings-section">';
    echo '<h2>Schedule Settings</h2>';
    display_general_settings();
    echo '</div>';
    
    echo '<div class="settings-section">';
    echo '<h2>OpenAI Text Generation</h2>';
    display_text_settings();
    echo '</div>';
    
    echo '<div class="settings-section">';
    echo '<h2>DALL·E Image Generation</h2>';
    display_image_settings();
    echo '</div>';
    
    submit_button('Save Settings');
    echo '</form>';
    
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
    
    echo '<h3>Text Generation</h3>';
    display_api_logs('Text Generation');
    
    echo '<h3 style="margin-top: 20px;">Image Generation</h3>';
    display_api_logs('Image Generation');
    echo '</div>'; // Close status-panel
    
    echo '</div>'; // Close dashboard-content
    echo '</div>'; // Close wrap

    // Updated styling for single column layout
    echo '<style>
        .ai-blogpost-dashboard {
            max-width: 1200px;
            margin: 20px auto;
        }
        .dashboard-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .settings-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
        }
        .settings-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .test-post-section {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
        }
        .test-post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .test-post-header h2 {
            margin: 0;
        }
        .next-post-info {
            margin-top: 10px;
            color: #666;
        }
        .status-panel {
            margin-top: 30px;
            padding: 20px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
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
        .form-table {
            margin-top: 0;
        }
        .form-table th {
            width: 200px;
        }
        .submit {
            margin-top: 20px;
            padding: 20px 0;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
    </style>';
}

// Add these new helper functions for tab content
function display_general_settings() {
    echo '<table class="form-table">';
    
    // Language Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_language">Content Language</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_language" id="ai_blogpost_language">';
    $language = get_cached_option('ai_blogpost_language', 'en');
    $languages = array(
        'en' => 'English',
        'nl' => 'Nederlands',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español'
    );
    foreach ($languages as $code => $name) {
        echo '<option value="' . esc_attr($code) . '" ' . selected($language, $code, false) . '>' . esc_html($name) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Select the language for generated content</p>';
    echo '</td>';
    echo '</tr>';

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
    display_model_dropdown('gpt');
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
    echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" 
        value="' . esc_attr(get_cached_option('ai_blogpost_max_tokens', '2048')) . '"
        min="100" max="4096">'; // Set reasonable limits
    echo '<p class="description">Maximum length of generated text (max 4096 for safe operation)</p>';
    echo '</td>';
    echo '</tr>';

    // System Role with better description
    echo '<tr>';
    echo '<th><label for="ai_blogpost_role">System Role</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.'));
    echo '</textarea>';
    echo '<p class="description">Define the AI\'s role and writing style</p>';
    echo '</td>';
    echo '</tr>';
    
    // Simplified Prompt Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_prompt">Content Template</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="4" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_prompt', 
        "Write a blog post about [topic]. Structure the content as follows:

||Title||: Create an engaging, SEO-friendly title

||Content||: Write the main content here, using proper HTML structure:
- Use <article> tags to wrap the content
- Use <h1>, <h2> for headings
- Use <p> for paragraphs
- Include relevant subheadings
- Add a strong conclusion

||Category||: Suggest the most appropriate category for this post"));
    echo '</textarea>';
    
    // Add template guide
    echo '<div class="template-guide" style="margin-top: 10px; padding: 15px; background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 4px;">';
    echo '<h4 style="margin-top: 0;">Content Structure Guide</h4>';
    echo '<p class="description">Use these section markers to structure the content:</p>';
    echo '<ul style="margin: 10px 0 0 20px; list-style-type: disc;">';
    echo '<li><code>||Title||:</code> - The blog post title</li>';
    echo '<li><code>||Content||:</code> - The main content (wrapped in <code>&lt;article&gt;</code> tags)</li>';
    echo '<li><code>||Category||:</code> - The suggested category</li>';
    echo '</ul>';
    echo '<p class="description" style="margin-top: 10px;"><strong>Tips:</strong></p>';
    echo '<ul style="margin: 5px 0 0 20px; list-style-type: disc;">';
    echo '<li>Use [topic] in your prompt to reference the selected category</li>';
    echo '<li>Add specific instructions about tone, style, or length in the System Role</li>';
    echo '<li>Use HTML tags for proper content structure</li>';
    echo '<li>Include SEO best practices in your instructions</li>';
    echo '</ul>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    add_refresh_models_button(); // Add the refresh models button here
    
    // LM Studio Section
    echo '<tr>';
    echo '<th colspan="2"><h3>LM Studio Integration</h3></th>';
    echo '</tr>';
    
    // Enable LM Studio
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
    echo '<td>';
    echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . 
         checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1, false) . '>';
    echo '<p class="description">Enable local LM Studio integration for text generation</p>';
    echo '</td>';
    echo '</tr>';

    // LM Studio API URL
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
    echo '<td>';
    echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')) . '">';
    echo '<button type="button" class="button test-lm-connection">Test Connection</button>';
    echo '<p class="description">Usually http://localhost:1234/v1</p>';
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
    display_model_dropdown('dalle');
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
        'Create a professional blog header image about [category]. Include visual elements that represent [category] in a modern and engaging style. Style: Clean, professional, with subtle symbolism.'));
    echo '</textarea>';
    echo '<p class="description">Template for generating DALL-E prompts. Use [category] as the placeholder for the selected category.</p>';
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
function ai_blogpost_get_post_data() {
    try {
        ai_blogpost_debug_log('Getting post data from dashboard settings');
        
        // Get categories from settings
        $categories_string = get_cached_option('ai_blogpost_custom_categories', '');
        ai_blogpost_debug_log('Retrieved categories string:', $categories_string);
        
        // Split and clean categories
        $categories = array_filter(array_map('trim', explode("\n", $categories_string)));
        
        if (empty($categories)) {
            ai_blogpost_debug_log('No categories found, using default');
            return array(
                'category' => 'SEO',
                'focus_keyword' => 'SEO optimalisatie'
            );
        }
        
        // Pick a random category
        $selected_category = $categories[array_rand($categories)];
        ai_blogpost_debug_log('Selected category:', $selected_category);
        
        $post_data = array(
            'category' => $selected_category,
            'focus_keyword' => $selected_category
        );
        
        ai_blogpost_debug_log('Final post data:', $post_data);
        return $post_data;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in get_post_data:', $e->getMessage());
        return array(
            'category' => 'SEO',
            'focus_keyword' => 'SEO optimalisatie'
        );
    }
}

// ------------------ FETCH AI TEXT ------------------
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

// Helper function to get language instruction
function get_language_instruction($language_code) {
    $instructions = [
        'en' => 'Write all content in English.',
        'nl' => 'Schrijf alle content in het Nederlands.',
        'de' => 'Schreiben Sie den gesamten Inhalt auf Deutsch.',
        'fr' => 'Écrivez tout le contenu en français.',
        'es' => 'Escribe todo el contenido en español.'
    ];
    
    return $instructions[$language_code] ?? $instructions['en'];
}

function prepare_dalle_prompt($correspondences) {
    $language = get_cached_option('ai_blogpost_language', 'en');
    $template = get_cached_option('ai_blogpost_dalle_prompt_template', 
        'Create a professional blog header image about [category]. Style: Modern en professioneel met relevante symboliek.');
    
    // Vertaal de prompt template
    if ($language !== 'en') {
        $translated_templates = [
            'nl' => 'Maak een professionele blog header afbeelding over [category]. Stijl: Modern en professioneel met relevante symboliek.',
            'de' => 'Erstellen Sie ein professionelles Blog-Header-Bild über [category]. Stil: Modern und professionell mit relevanter Symbolik.',
            'fr' => 'Créez une image d\'en-tête de blog professionnelle sur [category]. Style : Moderne et professionnel avec un symbolisme pertinent.',
            'es' => 'Crea una imagen de encabezado de blog profesional sobre [category]. Estilo: Moderno y profesional con simbolismo relevante.'
        ];
        
        $template = $translated_templates[$language] ?? $template;
    }
    
    // Replace placeholders
    $prompt = str_replace(
        ['[category]', '[categorie]', '[alle_categorieen]'],
        $correspondences['category'],
        $template
    );
    
    return $prompt;
}

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

// ------------------ FETCH DALL·E IMAGE ------------------
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

// ------------------ CREATE POST ------------------
function create_ai_blogpost() {
    try {
        ai_blogpost_debug_log('Starting blog post creation');
        
        $post_data = ai_blogpost_get_post_data();
        ai_blogpost_debug_log('Got post data:', $post_data);
        
        // Generate text content
        $ai_result = fetch_ai_response($post_data);
        if (!$ai_result) {
            throw new Exception('No AI result received');
        }

        $parsed_content = parse_ai_content($ai_result['content']);
        
        // Create post with SEO metadata
        $post_args = array(
            'post_title' => wp_strip_all_tags($parsed_content['title']),
            'post_content' => wpautop($parsed_content['content']),
            'post_status' => 'publish',
            'post_author' => 1,
            'post_category' => array(get_cat_ID($post_data['category']) ?: 1),
            'meta_input' => array(
                '_yoast_wpseo_metadesc' => $parsed_content['meta'] ?? '',
                '_yoast_wpseo_focuskw' => $post_data['focus_keyword']
            )
        );

        $post_id = wp_insert_post($post_args);
        
        // Handle featured image if enabled
        if (get_cached_option('ai_blogpost_dalle_enabled', 0)) {
            // Use the template from dashboard settings
            $template = get_cached_option('ai_blogpost_dalle_prompt_template', 
                'Create a professional blog header image about [category]. Style: Modern and professional.');
            
            // Create image data with category
            $image_data = array(
                'category' => $post_data['category'],
                'template' => $template
            );
            
            ai_blogpost_debug_log('Image generation data:', $image_data);
            
            $attach_id = fetch_dalle_image_from_text($image_data);
            if ($attach_id) {
                set_post_thumbnail($post_id, $attach_id);
            }
        }

        return $post_id;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in create_ai_blogpost:', $e->getMessage());
        throw $e;
    }
}

function parse_ai_content($ai_content) {
    ai_blogpost_debug_log('Raw AI Content:', $ai_content);

    // Remove thinking process if present
    if (strpos($ai_content, '</think>') !== false) {
        $ai_content = substr($ai_content, strpos($ai_content, '</think>') + 8);
    }

    // Initialize variables
    $title = '';
    $content = '';
    $category = '';

    // Extract title - improved pattern matching
    if (preg_match('/\|\|Title\|\|:\s*(?:"([^"]+)"|([^"\n]+))(?=\s*\|\||\s*<|\s*$)/s', $ai_content, $matches)) {
        $title = !empty($matches[1]) ? $matches[1] : $matches[2];
        $title = trim($title);
    } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $ai_content, $matches)) {
        // Backup: try to get title from H1 if ||Title|| format fails
        $title = trim(strip_tags($matches[1]));
    }

    // Extract content
    if (preg_match('/<article>(.*?)<\/article>/s', $ai_content, $matches)) {
        $content = trim($matches[1]);
    } elseif (preg_match('/\|\|Content\|\|:\s*(.+?)(?=\|\|Category\|\||\s*$)/s', $ai_content, $matches)) {
        $content = trim($matches[1]);
    }

    // Extract category
    if (preg_match('/\|\|Category\|\|:\s*([^\n]+)/s', $ai_content, $matches)) {
        $category = trim($matches[1]);
    }

    // Clean up the title
    $title = str_replace(['"', "'"], '', $title);
    $title = preg_replace('/\s+/', ' ', $title);

    // Clean up the content
    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
    $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
    $content = preg_replace('/#{3,}\s+(.+)/', '<h3>$1</h3>', $content);
    $content = preg_replace('/#{2}\s+(.+)/', '<h2>$1</h2>', $content);
    $content = preg_replace('/#{1}\s+(.+)/', '<h1>$1</h1>', $content);
    
    // Convert markdown lists to HTML
    $content = preg_replace('/^\s*[-\*]\s+(.+)$/m', '<li>$1</li>', $content);
    $content = preg_replace('/(<li>.*?<\/li>)/s', '<ul>$1</ul>', $content);

    // Add paragraph tags
    $content = wpautop($content);

    $parsed = array(
        'title' => $title ?: 'AI-Generated Post',
        'content' => $content ? "<article>$content</article>" : '<article><p>Content generation failed</p></article>',
        'category' => $category ?: 'Uncategorized'
    );

    ai_blogpost_debug_log('Parsed Content:', $parsed);
    return $parsed;
}

// ------------------ CRON SCHEDULING ------------------
function ai_blogpost_schedule_cron() {
    $frequency = get_option('ai_blogpost_post_frequency', 'daily'); // Use get_option directly
    $is_scheduled = get_option('ai_blogpost_is_scheduled', false);
    
    // Clear existing schedule
    wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    
    // Add weekly schedule if needed
    if($frequency == 'weekly') {
        add_filter('cron_schedules', 'ai_blogpost_weekly_schedule');
    }
    
    // Schedule new cron based on current frequency
    if($frequency == 'daily') {
        wp_schedule_event(strtotime('tomorrow 00:00:00'), 'daily', 'ai_blogpost_cron_hook');
    } elseif($frequency == 'weekly') {
        wp_schedule_event(strtotime('next monday 00:00:00'), 'weekly', 'ai_blogpost_cron_hook');
    }
    
    update_option('ai_blogpost_is_scheduled', true);
    
    ai_blogpost_debug_log('Cron schedule updated:', [
        'frequency' => $frequency,
        'next_run' => wp_next_scheduled('ai_blogpost_cron_hook')
    ]);
}

// Add function to handle frequency changes
function ai_blogpost_handle_frequency_change() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'ai_blogpost_settings') {
        if (isset($_POST['ai_blogpost_post_frequency'])) {
            $new_frequency = sanitize_text_field($_POST['ai_blogpost_post_frequency']);
            $old_frequency = get_option('ai_blogpost_post_frequency', 'daily');
            
            if ($old_frequency !== $new_frequency) {
                // Update the frequency option
                update_option('ai_blogpost_post_frequency', $new_frequency);
                
                // Clear existing schedule
                wp_clear_scheduled_hook('ai_blogpost_cron_hook');
                
                // Set new schedule
                $next_run = ($new_frequency === 'weekly') 
                    ? strtotime('next monday 00:00:00') 
                    : strtotime('tomorrow 00:00:00');
                
                // Add weekly schedule if needed
                if ($new_frequency === 'weekly') {
                    add_filter('cron_schedules', function($schedules) {
                        $schedules['weekly'] = array(
                            'interval' => 7 * 24 * 60 * 60,
                            'display' => __('Once Weekly')
                        );
                        return $schedules;
                    });
                    wp_schedule_event($next_run, 'weekly', 'ai_blogpost_cron_hook');
                } else {
                    wp_schedule_event($next_run, 'daily', 'ai_blogpost_cron_hook');
                }
                
                // Force page reload to show new schedule
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>Post frequency updated. Page will refresh to show new schedule.</p>';
                    echo '</div>';
                    echo '<script>setTimeout(function() { location.reload(); }, 1500);</script>';
                });
                
                ai_blogpost_debug_log('Cron schedule updated:', [
                    'old_frequency' => $old_frequency,
                    'new_frequency' => $new_frequency,
                    'next_run' => date('Y-m-d H:i:s', $next_run)
                ]);
            }
        }
    }
}

// Make sure this runs after settings are saved
remove_action('admin_init', 'ai_blogpost_handle_frequency_change');
add_action('admin_init', 'ai_blogpost_handle_frequency_change', 100);

// Update deactivation function
function ai_blogpost_deactivation() {
    wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    delete_option('ai_blogpost_is_scheduled');
    ai_blogpost_debug_log('Plugin deactivated, cron schedule cleared');
}

// Voeg deze functie toe om de cache te legen
function clear_ai_blogpost_cache() {
    global $ai_blogpost_option_cache;
    $ai_blogpost_option_cache = array(); // Reset de cache
}

// Wijzig de get_cached_option functie om een globale cache te gebruiken
function get_cached_option($option_name, $default = '') {
    global $ai_blogpost_option_cache;
    
    if (!isset($ai_blogpost_option_cache)) {
        $ai_blogpost_option_cache = array();
    }
    
    if (!isset($ai_blogpost_option_cache[$option_name])) {
        $ai_blogpost_option_cache[$option_name] = get_option($option_name, $default);
    }
    
    return $ai_blogpost_option_cache[$option_name];
}

// Voeg een action toe om de cache te legen na het opslaan van instellingen
function ai_blogpost_after_save_settings() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'ai_blogpost_settings') {
        clear_ai_blogpost_cache();
        ai_blogpost_debug_log('Cache cleared after saving settings');
    }
}
add_action('admin_init', 'ai_blogpost_after_save_settings', 99);

// Add logging function
function ai_blogpost_log_api_call($type, $success, $data) {
    $logs = get_option('ai_blogpost_api_logs', array());
    
    // Add new log entry with more details
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
    
    // Debug log the update
    ai_blogpost_debug_log('Updating logs:', $logs);
    
    update_option('ai_blogpost_api_logs', $logs);
}

// Add new helper function for displaying logs
function display_api_logs($type) {
    $logs = get_option('ai_blogpost_api_logs', array());
    ai_blogpost_debug_log('Displaying logs for type:', $type);
    
    $filtered_logs = array_filter($logs, function($log) use ($type) {
        return isset($log['type']) && $log['type'] === $type;
    });
    
    if (empty($filtered_logs)) {
        echo '<div class="notice notice-warning"><p>No ' . esc_html($type) . ' logs found.</p></div>';
        return;
    }

    // Show most recent logs first
    $filtered_logs = array_reverse(array_slice($filtered_logs, -5));

    // Add table styles
    echo '<style>
        .log-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
            background: #fff;
        }
        .log-table th, .log-table td { 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #e1e1e1; 
        }
        .log-table th { 
            background: #f5f5f5; 
            font-weight: bold; 
        }
        .log-status.success { 
            color: #46b450; 
            font-weight: bold; 
        }
        .log-status.error { 
            color: #dc3232; 
            font-weight: bold; 
        }
        .log-details pre {
            margin: 5px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            overflow-x: auto;
        }
        .log-time {
            white-space: nowrap;
        }
    </style>';

    echo '<table class="log-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Time</th>';
    echo '<th>Status</th>';
    echo '<th>Category</th>';
    echo '<th>Details</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($filtered_logs as $log) {
        $status_class = $log['success'] ? 'success' : 'error';
        $status_text = $log['success'] ? '✓ Success' : '✗ Failed';
        
        echo '<tr>';
        echo '<td class="log-time">' . date('Y-m-d H:i:s', $log['time']) . '</td>';
        echo '<td class="log-status ' . $status_class . '">' . $status_text . '</td>';
        echo '<td>' . (isset($log['data']['category']) ? esc_html($log['data']['category']) : '-') . '</td>';
        echo '<td class="log-details">';
        
        if (isset($log['data']['status'])) {
            echo '<div><strong>Status:</strong> ' . esc_html($log['data']['status']) . '</div>';
        }
        if (isset($log['data']['prompt'])) {
            echo '<div><strong>Prompt:</strong><pre>' . esc_html($log['data']['prompt']) . '</pre></div>';
        }
        if (isset($log['data']['error'])) {
            echo '<div><strong>Error:</strong><pre>' . esc_html($log['data']['error']) . '</pre></div>';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
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

function ai_blogpost_debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $log .= "\n" . print_r($data, true);
    }
    error_log($log);
}

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

// Add this to the settings save action
function ai_blogpost_save_settings() {
    if (isset($_POST['ai_blogpost_api_key'])) {
        $api_key = sanitize_text_field($_POST['ai_blogpost_api_key']);
        update_option('ai_blogpost_api_key', $api_key);
        fetch_openai_models(); // Fetch models after saving API key
    }
}
add_action('admin_init', 'ai_blogpost_save_settings');

// Modify the model dropdowns in display_text_settings() and display_image_settings()
function display_model_dropdown($type = 'gpt') {
    $stored_models = get_option($type === 'gpt' ? 'ai_blogpost_available_gpt_models' : 'ai_blogpost_available_dalle_models', []);
    $current_model = get_cached_option($type === 'gpt' ? 'ai_blogpost_model' : 'ai_blogpost_dalle_model');
    $default_models = $type === 'gpt' ? ['gpt-4', 'gpt-3.5-turbo'] : ['dall-e-3', 'dall-e-2'];
    
    $models = !empty($stored_models) ? $stored_models : $default_models;

    echo '<select name="' . ($type === 'gpt' ? 'ai_blogpost_model' : 'ai_blogpost_dalle_model') . '">';
    foreach ($models as $model) {
        echo '<option value="' . esc_attr($model) . '" ' . selected($current_model, $model, false) . '>';
        echo esc_html($model);
        echo '</option>';
    }
    echo '</select>';
    
    if (empty($stored_models)) {
        echo '<p class="description">Save API key to fetch available models</p>';
    }
}

// Add to the settings page
function add_refresh_models_button() {
    echo '<tr>';
    echo '<th>Available Models</th>';
    echo '<td>';
    echo '<button type="button" class="button" id="refresh-models">Refresh Available Models</button>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">Click to fetch available models from OpenAI</p>';
    echo '</td>';
    echo '</tr>';

    // Add JavaScript for the refresh button
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#refresh-models').click(function() {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.post(ajaxurl, {
                action: 'refresh_openai_models',
                nonce: '<?php echo wp_create_nonce('refresh_models_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to fetch models. Please check your API key.');
                }
            }).always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });
    });
    </script>
    <?php
}

// Add the AJAX handler
function handle_refresh_models() {
    check_ajax_referer('refresh_models_nonce', 'nonce');
    $success = fetch_openai_models();
    wp_send_json_success(['success' => $success]);
}
add_action('wp_ajax_refresh_openai_models', 'handle_refresh_models');

// Helper function to check cron status
function ai_blogpost_check_cron_status() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $next_run = wp_next_scheduled('ai_blogpost_cron_hook');
        $frequency = get_option('ai_blogpost_post_frequency', 'daily');
        
        error_log(sprintf(
            'AI Blogpost Cron Status - Frequency: %s, Next Run: %s',
            $frequency,
            $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled'
        ));
    }
}
add_action('admin_init', 'ai_blogpost_check_cron_status');

function handle_lm_studio_test() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    $api_url = sanitize_text_field($_POST['url']);
    // Ensure URL ends with /v1
    $api_url = rtrim($api_url, '/') . '/v1';
    
    try {
        ai_blogpost_debug_log('Testing LM Studio connection:', [
            'url' => $api_url
        ]);

        // Test basic connection to models endpoint
        $response = wp_remote_get($api_url . '/models', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            ai_blogpost_debug_log('LM Studio connection failed:', $response->get_error_message());
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        ai_blogpost_debug_log('LM Studio raw response:', $body);

        $data = json_decode($body, true);
        
        // LM Studio responses can be in different formats
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

        // Store the models in WordPress options
        update_option('ai_blogpost_available_lm_models', $models);
        update_option('ai_blogpost_lm_api_url', rtrim($api_url, '/v1')); // Store base URL
        
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

// Update display function to show LM Studio settings
function display_lm_studio_settings() {
    ?>
    <tr>
        <th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>
        <td>
            <input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" 
                   class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234')); ?>">
            <button type="button" class="button test-lm-connection">Test Connection</button>
            <span class="spinner" style="float:none;margin-left:4px;"></span>
            <p class="description">Usually http://localhost:1234 (without /v1)</p>
            <div id="lm-studio-status"></div>
            <div id="lm-studio-models"></div>
        </td>
    </tr>

    <script>
    jQuery(document).ready(function($) {
        $('.test-lm-connection').click(function() {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var $status = $('#lm-studio-status');
            var $models = $('#lm-studio-models');
            var url = $('#ai_blogpost_lm_api_url').val();
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $status.html('');
            $models.html('');
            
            $.post(ajaxurl, {
                action: 'test_lm_studio',
                url: url,
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $status.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    if (response.data.models && response.data.models.length > 0) {
                        var modelList = '<div class="notice notice-info inline"><p><strong>Available Models:</strong></p><ul>';
                        response.data.models.forEach(function(model) {
                            modelList += '<li>' + (model.id || model) + '</li>';
                        });
                        modelList += '</ul></div>';
                        $models.html(modelList);
                    }
                } else {
                    $status.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                }
            })
            .fail(function() {
                $status.html('<div class="notice notice-error inline"><p>Network error while testing connection</p></div>');
            })
            .always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });
    });
    </script>
    <?php
}