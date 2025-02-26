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
 * Display the admin settings page with optimized UX/UI.
 */
function ai_blogpost_admin_page() {
    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1>AI Blogpost Dashboard</h1>
        <div class="dashboard-content">
            <!-- Test Post Section -->
            <div class="test-post-section">
                <div class="test-post-header">
                    <h2>Test Generation</h2>
                    <form method="post" style="display:inline-block;">
                        <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">
                    </form>
                </div>
                <?php 
                $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
                if ($next_post_time) {
                    echo '<div class="next-post-info"><span class="dashicons dashicons-calendar-alt"></span> Next scheduled post: ' .
                         get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i') . '</div>';
                }
                ?>
            </div>
            <!-- End Test Post Section -->

            <!-- Settings Form -->
            <form method="post" action="options.php" class="ai-blogpost-form">
                <?php
                    settings_fields('ai_blogpost_settings');
                    do_settings_sections('ai_blogpost_settings');
                ?>
                <div class="settings-section">
                    <h2>Schedule Settings</h2>
                    <?php display_general_settings(); ?>
                </div>
                <div class="settings-section">
                    <h2>OpenAI Text Generation</h2>
                    <?php display_text_settings(); ?>
                </div>
                <div class="settings-section">
                    <h2>Featured Image Generation</h2>
                    <?php display_image_settings(); ?>
                </div>
                <?php submit_button('Save Settings'); ?>
            </form>
            <!-- End Settings Form -->

            <!-- Status Panel -->
            <div class="status-panel">
                <div class="status-header">
                    <h2>Generation Status</h2>
                    <div class="status-actions">
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                            <input type="submit" name="clear_ai_logs" class="button" value="Clear Logs">
                        </form>
                        <button type="button" class="button" onclick="window.location.reload();">Refresh Status</button>
                    </div>
                </div>
                <h3>Text Generation</h3>
                <?php display_api_logs('Text Generation'); ?>
                <h3 style="margin-top: 20px;">Image Generation</h3>
                <?php display_api_logs('Image Generation'); ?>
            </div>
            <!-- End Status Panel -->
        </div>
    </div>
    <style>
        /* Algemene styling voor de AI Blogpost Dashboard */
        .ai-blogpost-dashboard {
            max-width: 1200px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
        }
        .dashboard-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 4px;
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
            border-bottom: 2px solid #2271b1;
            color: #2271b1;
        }
        .ai-blogpost-form .form-table th {
            width: 220px;
            vertical-align: top;
            padding: 10px 0;
        }
        .ai-blogpost-form .form-table td {
            padding: 10px 0;
        }
        .test-post-section {
            margin-bottom: 20px;
            padding: 20px;
            background: #f1f1f1;
            border-left: 5px solid #2271b1;
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
            font-size: 14px;
            color: #555;
        }
        .status-panel {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
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
        /* Responsiviteit */
        @media screen and (max-width: 768px) {
            .dashboard-content, .settings-section, .status-panel {
                padding: 15px;
            }
            .ai-blogpost-dashboard {
                margin: 10px;
            }
            .test-post-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .status-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <?php
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
    
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
    echo '<td>';
    echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . 
         checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1, false) . '>';
    echo '<p class="description">Enable local LM Studio integration for text generation</p>';
    echo '</td>';
    echo '</tr>';

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
function display_image_settings() {
    echo '<div class="settings-section">';
    echo '<h2>Featured Image Generation</h2>';
    
    echo '<div class="image-generation-selector" style="margin-bottom: 20px;">';
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'dalle');
    echo '<div class="generation-type-options" style="display: flex; gap: 20px;">';
    
    echo '<div class="generation-option" style="flex: 1; padding: 15px; border: 1px solid #ccc; border-radius: 5px; ' . 
         ($generation_type === 'dalle' ? 'background: #f0f7ff; border-color: #2271b1;' : '') . '">';
    echo '<label style="display: block; margin-bottom: 10px;">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" value="dalle" ' . 
         checked($generation_type, 'dalle', false) . ' style="margin-right: 8px;">';
    echo '<strong>DALL-E</strong></label>';
    echo '<p class="description" style="margin: 0;">Use OpenAI\'s DALL-E for AI image generation</p>';
    echo '</div>';
    
    echo '<div class="generation-option" style="flex: 1; padding: 15px; border: 1px solid #ccc; border-radius: 5px; ' . 
         ($generation_type === 'comfyui' ? 'background: #f0f7ff; border-color: #2271b1;' : '') . '">';
    echo '<label style="display: block; margin-bottom: 10px;">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" value="comfyui" ' . 
         checked($generation_type, 'comfyui', false) . ' style="margin-right: 8px;">';
    echo '<strong>ComfyUI</strong></label>';
    echo '<p class="description" style="margin: 0;">Use local ComfyUI for advanced image generation</p>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';

    echo '<div class="dalle-settings" style="display: ' . ($generation_type === 'dalle' ? 'block' : 'none') . ';">';
    echo '<h3 style="margin-top: 0;">DALL-E Settings</h3>';
    echo '<table class="form-table">';
    
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_api_key">API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<p class="description">Your OpenAI API key for DALL-E image generation</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_model">Model</label></th>';
    echo '<td>';
    display_model_dropdown('dalle');
    echo '</td>';
    echo '</tr>';

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
    echo '</div>';

    echo '<div class="comfyui-settings" style="display: ' . ($generation_type === 'comfyui' ? 'block' : 'none') . ';">';
    echo '<h3 style="margin-top: 0;">ComfyUI Settings</h3>';
    echo '<table class="form-table">';
    
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

    $workflows_json = file_get_contents(plugin_dir_path(__FILE__) . '../workflows/config.json');
    $workflows_data = json_decode($workflows_json, true);
    $workflows = isset($workflows_data['workflows']) ? $workflows_data['workflows'] : [];
    $default_workflow = isset($workflows_data['default_workflow']) ? $workflows_data['default_workflow'] : '';

    update_option('ai_blogpost_comfyui_workflows', json_encode($workflows));
    update_option('ai_blogpost_comfyui_default_workflow', $default_workflow);

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
    echo '</div>';
    
    echo '</div>';

    ?>
    <script>
    jQuery(document).ready(function($) {
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

        $('input[name="ai_blogpost_image_generation_type"]').change(function() {
            var type = $(this).val();
            
            $('.generation-option').css({
                'background': 'none',
                'border-color': '#ccc'
            });
            $(this).closest('.generation-option').css({
                'background': '#f0f7ff',
                'border-color': '#2271b1'
            });

            if (type === 'dalle') {
                $('.comfyui-settings').css('opacity', 0).hide();
                $('.dalle-settings').css('opacity', 0).show().animate({opacity: 1}, 300);
            } else if (type === 'comfyui') {
                $('.dalle-settings').css('opacity', 0).hide();
                $('.comfyui-settings').css('opacity', 0).show().animate({opacity: 1}, 300);
            }
        });

        $('.generation-option').click(function() {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        });

        $('.test-comfyui-connection').click(function() {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var apiUrl = $('#ai_blogpost_comfyui_api_url').val();
            
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
    $api_url = rtrim($api_url, '/') . '/v1';
    
    try {
        ai_blogpost_debug_log('Testing LM Studio connection:', [
            'url' => $api_url
        ]);

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
