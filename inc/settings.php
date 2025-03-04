<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initialize plugin settings
 */
function ai_blogpost_initialize_settings() {
    register_setting('ai_blogpost_settings', 'ai_blogpost_temperature');
    register_setting('ai_blogpost_settings', 'ai_blogpost_max_tokens');
    register_setting('ai_blogpost_settings', 'ai_blogpost_role');
    register_setting('ai_blogpost_settings', 'ai_blogpost_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_prompt');
    register_setting('ai_blogpost_settings', 'ai_blogpost_post_frequency');
    register_setting('ai_blogpost_settings', 'ai_blogpost_custom_categories');
    register_setting('ai_blogpost_settings', 'ai_blogpost_localai_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_localai_prompt_template');

    // Image Generation settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_image_generation_type');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');

    // Language setting
    register_setting('ai_blogpost_settings', 'ai_blogpost_language');

    // DALL·E settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');

    // ComfyUI settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_workflows');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_default_workflow');

    // LM Studio settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_model');
}
add_action('admin_init', 'ai_blogpost_initialize_settings');

/**
 * Add menu page to WordPress admin
 */
function ai_blogpost_admin_menu() {
    add_menu_page('AI Blogpost Settings', 'AI Blogpost', 'manage_options', 'ai_blogpost', 'ai_blogpost_admin_page');
}
add_action('admin_menu', 'ai_blogpost_admin_menu');

/**
 * Display the admin settings page
 */
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

/**
 * Display general settings section
 */
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

/**
 * Display text generation settings section
 */
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
        min="100" max="4096">';
    echo '<p class="description">Maximum length of generated text (max 4096 for safe operation)</p>';
    echo '</td>';
    echo '</tr>';

    // System Role
    echo '<tr>';
    echo '<th><label for="ai_blogpost_role">System Role</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.'));
    echo '</textarea>';
    echo '<p class="description">Define the AI\'s role and writing style</p>';
    echo '</td>';
    echo '</tr>';
    
    // Content Template
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
    
    add_refresh_models_button();
    
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
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">Usually http://localhost:1234/v1</p>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
}

/**
 * Display image generation settings section
 */
// In display_image_settings() function
function display_image_settings() {
    echo '<div class="settings-section">';
    echo '<h2>Featured Image Generation</h2>';
    
    // Image Generation Type with improved styling
    echo '<div class="image-generation-selector" style="margin-bottom: 20px;">';
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'dalle');
    echo '<div class="generation-type-options" style="display: flex; gap: 20px;">';
    
    // DALL-E Option
    echo '<div class="generation-option" style="flex: 1; padding: 15px; border: 1px solid #ccc; border-radius: 5px; ' . 
         ($generation_type === 'dalle' ? 'background: #f0f7ff; border-color: #2271b1;' : '') . '">';
    echo '<label style="display: block; margin-bottom: 10px;">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" value="dalle" ' . 
         checked($generation_type, 'dalle', false) . ' style="margin-right: 8px;">';
    echo '<strong>DALL-E</strong></label>';
    echo '<p class="description" style="margin: 0;">Use OpenAI\'s DALL-E for AI image generation</p>';
    echo '</div>';
    
    // ComfyUI Option
    echo '<div class="generation-option" style="flex: 1; padding: 15px; border: 1px solid #ccc; border-radius: 5px; ' . 
         ($generation_type === 'comfyui' ? 'background: #f0f7ff; border-color: #2271b1;' : '') . '">';
    echo '<label style="display: block; margin-bottom: 10px;">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" value="comfyui" ' . 
         checked($generation_type, 'comfyui', false) . ' style="margin-right: 8px;">';
    echo '<strong>ComfyUI</strong></label>';
    echo '<p class="description" style="margin: 0;">Use local ComfyUI for advanced image generation</p>';
    echo '</div>';
    
    echo '</div>'; // Close generation-type-options
    echo '</div>'; // Close image-generation-selector

    // DALL-E Settings
    echo '<div class="dalle-settings" style="display: ' . ($generation_type === 'dalle' ? 'block' : 'none') . ';">';
    echo '<h3 style="margin-top: 0;">DALL-E Settings</h3>';
    echo '<table class="form-table">';
    
    // DALL-E API Key
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_api_key">API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<p class="description">Your OpenAI API key for DALL-E image generation</p>';
    echo '</td>';
    echo '</tr>';

    // DALL-E Model
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_model">Model</label></th>';
    echo '<td>';
    display_model_dropdown('dalle');
    echo '</td>';
    echo '</tr>';

    // DALL-E Prompt Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 
        'A professional blog header image for [category], modern style, clean design, subtle symbolism'));
    echo '</textarea>';
    echo '<p class="description">Use [category] as placeholder for the blog category</p>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>'; // Close dalle-settings

    // ComfyUI Settings
    echo '<div class="comfyui-settings" style="display: ' . ($generation_type === 'comfyui' ? 'block' : 'none') . ';">';
    echo '<h3 style="margin-top: 0;">ComfyUI Settings</h3>';
    
    // Add workflow management section
    echo '<div class="workflow-management" style="margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">';
    echo '<h3 style="margin-top: 0;">Workflow Management</h3>';
    
    // Add upload workflow button
    echo '<div class="upload-workflow" style="margin-bottom: 15px;">';
    echo '<input type="file" id="workflow-upload" accept=".json" style="display: none;">';
    echo '<button type="button" class="button" id="upload-workflow-btn">Upload New Workflow</button>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '</div>';
    
    // Existing workflows section
    echo '<div class="existing-workflows">';
    echo '<h4 style="margin-top: 0;">Existing Workflows</h4>';
    echo '<div class="workflow-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 4px;">';
    
    // Load and display existing workflows
    $workflows_json = file_get_contents(plugin_dir_path(__FILE__) . '../workflows/config.json');
    $workflows_data = json_decode($workflows_json, true);
    $workflows = isset($workflows_data['workflows']) ? $workflows_data['workflows'] : [];
    
    foreach ($workflows as $workflow) {
        echo '<div class="workflow-item" style="margin-bottom: 15px; padding: 15px; background: #fff; border: 1px solid #e2e4e7; border-radius: 3px;">';
        
        // Workflow header
        echo '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">';
        echo '<div class="workflow-info">';
        echo '<h4 style="margin: 0;">' . esc_html($workflow['name']) . '</h4>';
        echo '<p class="description" style="margin: 5px 0 0;">' . esc_html($workflow['description']) . '</p>';
        echo '</div>';
        echo '<div class="workflow-actions">';
        echo '<button type="button" class="button view-workflow" data-workflow="' . esc_attr($workflow['name']) . '">View JSON</button> ';
        echo '<button type="button" class="button button-link-delete delete-workflow" data-workflow="' . esc_attr($workflow['name']) . '">Delete</button>';
        echo '</div>';
        echo '</div>';

        // Prompt settings
        echo '<div class="workflow-prompts" style="background: #f8f9fa; padding: 15px; border: 1px solid #e2e4e7; border-radius: 3px;">';
        echo '<h4 style="margin-top: 0;">Prompt Templates</h4>';

        // Positive prompt
        echo '<div class="prompt-field" style="margin-bottom: 10px;">';
        echo '<label style="display: block; margin-bottom: 5px;">Positive Prompt Template:</label>';
        echo '<textarea class="large-text code positive-prompt" data-workflow="' . esc_attr($workflow['name']) . '" rows="2" style="margin-bottom: 5px;">' . 
             esc_textarea(isset($workflow['prompts']['positive']) ? $workflow['prompts']['positive'] : 'beautiful scenery [category], artistic style') . '</textarea>';
        echo '</div>';

        // Negative prompt
        echo '<div class="prompt-field">';
        echo '<label style="display: block; margin-bottom: 5px;">Negative Prompt Template:</label>';
        echo '<textarea class="large-text code negative-prompt" data-workflow="' . esc_attr($workflow['name']) . '" rows="2" style="margin-bottom: 5px;">' . 
             esc_textarea(isset($workflow['prompts']['negative']) ? $workflow['prompts']['negative'] : 'text, watermark, blur') . '</textarea>';
        echo '</div>';

        // Save prompts button
        echo '<div style="margin-top: 10px;">';
        echo '<button type="button" class="button save-prompts" data-workflow="' . esc_attr($workflow['name']) . '">Save Prompts</button>';
        echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
        echo '</div>';

        echo '</div>'; // Close workflow-prompts
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>'; // Close workflow-list
    echo '</div>'; // Close existing-workflows
    
    // Workflow preview modal
    echo '<div id="workflow-preview-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">';
    echo '<div style="background-color: #fff; margin: 10% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: 4px;">';
    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
    echo '<h3 style="margin: 0;">Workflow Preview</h3>';
    echo '<button type="button" class="button close-modal" style="padding: 0 10px;">×</button>';
    echo '</div>';
    echo '<pre id="workflow-content" style="max-height: 400px; overflow: auto; background: #f8f9fa; padding: 15px; border: 1px solid #e2e4e7; border-radius: 4px;"></pre>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // Close workflow-management

    echo '<table class="form-table">';
    
    // ComfyUI API URL
    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>';
    echo '<td>';
    $comfyui_url = get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188');
    echo '<input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="' . 
         esc_attr($comfyui_url) . '">';
    echo '<button type="button" class="button test-comfyui-connection">Test Connection</button>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">Usually http://localhost:8188</p>';
    echo '</td>';
    echo '</tr>';

    // Load workflow configuration
    $workflows_json = file_get_contents(plugin_dir_path(__FILE__) . '../workflows/config.json');
    $workflows_data = json_decode($workflows_json, true);
    $workflows = isset($workflows_data['workflows']) ? $workflows_data['workflows'] : [];
    $default_workflow = isset($workflows_data['default_workflow']) ? $workflows_data['default_workflow'] : '';

    // Update WordPress options
    update_option('ai_blogpost_comfyui_workflows', json_encode($workflows));
    update_option('ai_blogpost_comfyui_default_workflow', $default_workflow);

    // Display available workflows
    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_default_workflow">Workflow</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow" style="margin-bottom: 10px; width: 100%;">';
    foreach ($workflows as $workflow) {
        echo '<option value="' . esc_attr($workflow['name']) . '" ' . 
             selected($default_workflow, $workflow['name'], false) . '>' . 
             esc_html($workflow['name'] . ' - ' . $workflow['description']) . '</option>';
    }
    echo '</select>';
    
    // Add workflow preview section
    echo '<div class="workflow-preview" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 4px;">';
    echo '<h4 style="margin-top: 0;">Workflow Details</h4>';
    echo '<ul style="margin: 10px 0 0 20px; list-style-type: disc;">';
    echo '<li>Input: Category-based prompt with customizable style</li>';
    echo '<li>Processing: Advanced image generation pipeline</li>';
    echo '<li>Output: High-quality 512x512 featured image</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>'; // Close comfyui-settings
    
    echo '</div>'; // Close settings-section

    // Updated JavaScript for toggles and workflow management
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Workflow Management
        $('#upload-workflow-btn').click(function() {
            $('#workflow-upload').click();
        });

        $('#workflow-upload').change(function() {
            var file = this.files[0];
            if (!file) return;

            var reader = new FileReader();
            var $spinner = $(this).closest('.upload-workflow').find('.spinner');
            
            reader.onload = function(e) {
                try {
                    // Basic JSON validation
                    var workflow = JSON.parse(e.target.result);
                    
                    // Check if it's a valid ComfyUI workflow (should have at least one node)
                    if (!workflow || typeof workflow !== 'object' || Object.keys(workflow).length === 0) {
                        throw new Error('Invalid ComfyUI workflow structure. The file should contain at least one node.');
                    }

                    // Create form data
                    var formData = new FormData();
                    formData.append('action', 'upload_comfyui_workflow');
                    formData.append('nonce', '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>');
                    formData.append('workflow', e.target.result);
                    
                    // Show loading
                    $spinner.addClass('is-active');
                    
                    // Upload workflow
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                location.reload(); // Refresh to show new workflow
                            } else {
                                alert('Error uploading workflow: ' + response.data);
                            }
                        },
                        error: function() {
                            alert('Upload failed. Please try again.');
                        },
                        complete: function() {
                            $spinner.removeClass('is-active');
                            $('#workflow-upload').val('');
                        }
                    });
                } catch (error) {
                    alert('Invalid workflow file: ' + error.message);
                    $('#workflow-upload').val('');
                }
            };
            
            reader.readAsText(file);
        });

        // View workflow
        $('.view-workflow').click(function() {
            var workflow = $(this).data('workflow');
            var $modal = $('#workflow-preview-modal');
            var $content = $('#workflow-content');
            
            $.post(ajaxurl, {
                action: 'get_comfyui_workflow',
                workflow: workflow,
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    $content.text(JSON.stringify(response.data, null, 2));
                    $modal.show();
                } else {
                    alert('Error loading workflow: ' + response.data);
                }
            });
        });

        // Close modal
        $('.close-modal, #workflow-preview-modal').click(function(e) {
            if (e.target === this) {
                $('#workflow-preview-modal').hide();
            }
        });

        // Delete workflow
        $('.delete-workflow').click(function() {
            if (!confirm('Are you sure you want to delete this workflow?')) return;
            
            var workflow = $(this).data('workflow');
            var $item = $(this).closest('.workflow-item');
            
            $.post(ajaxurl, {
                action: 'delete_comfyui_workflow',
                workflow: workflow,
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    $item.slideUp(function() {
                        $item.remove();
                        // Reload if we deleted the last workflow
                        if ($('.workflow-item').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Error deleting workflow: ' + response.data);
                }
            });
        });

        // Add transition styles
        $('<style>')
            .text(`
                .generation-option {
                    transition: all 0.3s ease;
                    cursor: pointer;
                }
                .generation-option:hover {
                    background: #f0f7ff;
                    border-color: #2271b1;
                }
                .dalle-settings, .comfyui-settings {
                    transition: opacity 0.3s ease;
                }
            `)
            .appendTo('head');

        // Handle generation type selection
        $('input[name="ai_blogpost_image_generation_type"]').change(function() {
            var type = $(this).val();
            
            // Update option styling
            $('.generation-option').css({
                'background': 'none',
                'border-color': '#ccc'
            });
            $(this).closest('.generation-option').css({
                'background': '#f0f7ff',
                'border-color': '#2271b1'
            });

            // Smooth transition between settings panels
            if (type === 'dalle') {
                $('.comfyui-settings').css('opacity', 0).hide();
                $('.dalle-settings').css('opacity', 0).show().animate({opacity: 1}, 300);
            } else if (type === 'comfyui') {
                $('.dalle-settings').css('opacity', 0).hide();
                $('.comfyui-settings').css('opacity', 0).show().animate({opacity: 1}, 300);
            }
        });

        // Make entire option box clickable
        $('.generation-option').click(function() {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        });


        // Enhanced connection test handling
        // Save workflow prompts
        $('.save-prompts').click(function() {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var workflow = $(this).data('workflow');
            var $container = $(this).closest('.workflow-prompts');
            
            var positivePrompt = $container.find('.positive-prompt').val();
            var negativePrompt = $container.find('.negative-prompt').val();
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.post(ajaxurl, {
                action: 'save_workflow_prompts',
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>',
                workflow: workflow,
                prompts: {
                    positive: positivePrompt,
                    negative: negativePrompt
                }
            }, function(response) {
                if (response.success) {
                    $notification = createNotification('✅ Prompts saved successfully!', '#e7f5ea');
                } else {
                    $notification = createNotification('❌ ' + response.data, '#fde8e8');
                }
                $notification.fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
            }).fail(function() {
                var $notification = createNotification('❌ Failed to save prompts', '#fde8e8');
                $notification.fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
            }).always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });

        // Helper function to create notifications
        function createNotification(message, bgColor) {
            return $('<div>')
                .css({
                    'position': 'fixed',
                    'top': '20px',
                    'right': '20px',
                    'padding': '10px 20px',
                    'border-radius': '4px',
                    'background': bgColor,
                    'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                    'z-index': 9999,
                    'display': 'none'
                })
                .html(message)
                .appendTo('body');
        }

        $('.test-comfyui-connection').click(function() {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var apiUrl = $('#ai_blogpost_comfyui_api_url').val();
            
            // Create notification element
            var $notification = $('<div>')
                .css({
                    'position': 'fixed',
                    'top': '20px',
                    'right': '20px',
                    'padding': '10px 20px',
                    'border-radius': '4px',
                    'background': '#fff',
                    'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                    'z-index': 9999,
                    'display': 'none'
                })
                .appendTo('body');
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.post(ajaxurl, {
                action: 'test_comfyui_connection',
                url: apiUrl,
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    $notification
                        .html('✅ ComfyUI connected successfully!')
                        .css('background', '#e7f5ea')
                        .fadeIn()
                        .delay(3000)
                        .fadeOut(function() { $(this).remove(); });
                } else {
                    $notification
                        .html('❌ ' + response.data)
                        .css('background', '#fde8e8')
                        .fadeIn()
                        .delay(3000)
                        .fadeOut(function() { $(this).remove(); });
                }
            }).fail(function() {
                $notification
                    .html('❌ Connection failed. Please check the server URL.')
                    .css('background', '#fde8e8')
                    .fadeIn()
                    .delay(3000)
                    .fadeOut(function() { $(this).remove(); });
            }).always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });
    });
    </script>
    <?php
}

/**
 * Display model dropdown for GPT or DALL-E
 * 
 * @param string $type Either 'gpt' or 'dalle'
 */
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

/**
 * Add refresh models button to settings
 */
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
                nonce: '<?php echo wp_create_nonce("refresh_models_nonce"); ?>'
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

/**
 * Handle AJAX request to refresh OpenAI models
 */
function handle_refresh_models() {
    check_ajax_referer('refresh_models_nonce', 'nonce');
    $success = fetch_openai_models();
    wp_send_json_success(['success' => $success]);
}
add_action('wp_ajax_refresh_openai_models', 'handle_refresh_models');

/**
 * Handle LM Studio connection test
 */
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

        // Test connection to queue endpoint
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
        
        // Test connection to history endpoint
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
        
        // If we can access both endpoints, the server is running properly
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
 * Handle workflow file upload
 */
function handle_workflow_upload() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    try {
        // Get uploaded workflow data
        $uploaded_data = json_decode(stripslashes($_POST['workflow']), true);
        if (!$uploaded_data) {
            throw new Exception('Invalid JSON format');
        }

        // Check if the workflow is already in the correct format or needs wrapping
        $workflow_data = [];
        if (isset($uploaded_data['name']) && isset($uploaded_data['description']) && isset($uploaded_data['workflow'])) {
            // Already in correct format
            $workflow_data = $uploaded_data;
        } else {
            // This is a raw workflow - wrap it in the correct structure
            $workflow_data = [
                'name' => 'Workflow_' . time(),
                'description' => 'Imported ComfyUI workflow',
                'workflow' => $uploaded_data
            ];
        }

        // Validate the workflow structure
        if (!is_array($workflow_data['workflow'])) {
            throw new Exception('Invalid workflow structure');
        }

        // Load existing config
        $config_file = plugin_dir_path(__FILE__) . '../workflows/config.json';
        $config_data = json_decode(file_get_contents($config_file), true);
        if (!$config_data) {
            $config_data = ['workflows' => [], 'default_workflow' => ''];
        }

        // Check if workflow name already exists
        foreach ($config_data['workflows'] as $existing) {
            if ($existing['name'] === $workflow_data['name']) {
                throw new Exception('Workflow with this name already exists');
            }
        }

        // Add new workflow
        $config_data['workflows'][] = $workflow_data;
        
        // If this is the first workflow, set it as default
        if (empty($config_data['default_workflow'])) {
            $config_data['default_workflow'] = $workflow_data['name'];
        }

        // Save updated config
        if (!file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT))) {
            throw new Exception('Failed to save workflow configuration');
        }

        // Create individual workflow file
        $workflow_file = plugin_dir_path(__FILE__) . '../workflows/' . sanitize_file_name($workflow_data['name']) . '.json';
        if (!file_put_contents($workflow_file, json_encode($workflow_data['workflow'], JSON_PRETTY_PRINT))) {
            throw new Exception('Failed to save workflow file');
        }

        wp_send_json_success('Workflow uploaded successfully');

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_upload_comfyui_workflow', 'handle_workflow_upload');

/**
 * Handle workflow preview request
 */
function handle_get_workflow() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    try {
        $workflow_name = sanitize_text_field($_POST['workflow']);
        $workflow_file = plugin_dir_path(__FILE__) . '../workflows/' . sanitize_file_name($workflow_name) . '.json';
        
        if (!file_exists($workflow_file)) {
            throw new Exception('Workflow file not found');
        }
        
        $workflow_data = json_decode(file_get_contents($workflow_file), true);
        if (!$workflow_data) {
            throw new Exception('Invalid workflow file');
        }
        
        wp_send_json_success($workflow_data);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_get_comfyui_workflow', 'handle_get_workflow');

/**
 * Handle workflow deletion
 */
function handle_delete_workflow() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    try {
        $workflow_name = sanitize_text_field($_POST['workflow']);
        
        // Load config
        $config_file = plugin_dir_path(__FILE__) . '../workflows/config.json';
        $config_data = json_decode(file_get_contents($config_file), true);
        if (!$config_data) {
            throw new Exception('Failed to load workflow configuration');
        }
        
        // Find and remove workflow from config
        $workflow_index = -1;
        foreach ($config_data['workflows'] as $index => $workflow) {
            if ($workflow['name'] === $workflow_name) {
                $workflow_index = $index;
                break;
            }
        }
        
        if ($workflow_index === -1) {
            throw new Exception('Workflow not found in configuration');
        }
        
        // Remove workflow from array
        array_splice($config_data['workflows'], $workflow_index, 1);
        
        // If this was the default workflow, update default
        if ($config_data['default_workflow'] === $workflow_name) {
            $config_data['default_workflow'] = !empty($config_data['workflows']) 
                ? $config_data['workflows'][0]['name'] 
                : '';
        }
        
        // Save updated config
        if (!file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT))) {
            throw new Exception('Failed to update workflow configuration');
        }
        
        // Delete workflow file
        $workflow_file = plugin_dir_path(__FILE__) . '../workflows/' . sanitize_file_name($workflow_name) . '.json';
        if (file_exists($workflow_file) && !unlink($workflow_file)) {
            throw new Exception('Failed to delete workflow file');
        }
        
        wp_send_json_success('Workflow deleted successfully');

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_delete_comfyui_workflow', 'handle_delete_workflow');

/**
 * Handle saving workflow prompts
 */
function handle_save_workflow_prompts() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    try {
        if (!isset($_POST['workflow']) || !isset($_POST['prompts']) || 
            !isset($_POST['prompts']['positive']) || !isset($_POST['prompts']['negative'])) {
            throw new Exception('Missing required prompt data');
        }

        $workflow_name = sanitize_text_field($_POST['workflow']);
        $prompts = [
            'positive' => sanitize_text_field($_POST['prompts']['positive']),
            'negative' => sanitize_text_field($_POST['prompts']['negative'])
        ];

        ai_blogpost_debug_log('Saving workflow prompts:', [
            'workflow' => $workflow_name,
            'prompts' => $prompts
        ]);
        
        // Load config file
        $config_file = plugin_dir_path(__FILE__) . '../workflows/config.json';
        $config_data = json_decode(file_get_contents($config_file), true);
        if (!$config_data) {
            throw new Exception('Failed to load workflow configuration');
        }
        
        // Find workflow and update prompts
        foreach ($config_data['workflows'] as &$workflow) {
            if ($workflow['name'] === $workflow_name) {
                $workflow['prompts'] = [
                    'positive' => sanitize_text_field($prompts['positive']),
                    'negative' => sanitize_text_field($prompts['negative'])
                ];
                
                // Also update the workflow nodes with new prompts
                $workflow_file = plugin_dir_path(__FILE__) . '../workflows/' . sanitize_file_name($workflow_name) . '.json';
                $workflow_data = json_decode(file_get_contents($workflow_file), true);
                
                if ($workflow_data) {
                    // Find and update nodes with CLIPTextEncode class_type
                    foreach ($workflow_data as &$node) {
                        if (isset($node['class_type']) && $node['class_type'] === 'CLIPTextEncode') {
                            // Identify positive/negative prompt nodes by name or connection
                            if (isset($node['_meta']['title']) && strpos(strtolower($node['_meta']['title']), 'negative') !== false) {
                                $node['inputs']['text'] = $prompts['negative'];
                            } else {
                                $node['inputs']['text'] = $prompts['positive'];
                            }
                        }
                    }
                    
                    // Save updated workflow file
                    if (!file_put_contents($workflow_file, json_encode($workflow_data, JSON_PRETTY_PRINT))) {
                        throw new Exception('Failed to save workflow file');
                    }
                }
                break;
            }
        }
        
        // Save updated config
        if (!file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT))) {
            throw new Exception('Failed to save workflow configuration');
        }
        
        wp_send_json_success('Prompts saved successfully');

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_save_workflow_prompts', 'handle_save_workflow_prompts');
