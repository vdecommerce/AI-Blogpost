<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initialize plugin settings
 */
function ai_blogpost_initialize_settings() {
    $settings = [
        'ai_blogpost_temperature',
        'ai_blogpost_max_tokens',
        'ai_blogpost_role',
        'ai_blogpost_api_key',
        'ai_blogpost_model',
        'ai_blogpost_prompt',
        'ai_blogpost_post_frequency',
        'ai_blogpost_custom_categories',
        'ai_blogpost_localai_api_url',
        'ai_blogpost_localai_prompt_template',
        'ai_blogpost_image_generation_type',
        'ai_blogpost_dalle_api_key',
        'ai_blogpost_dalle_size',
        'ai_blogpost_dalle_style',
        'ai_blogpost_dalle_quality',
        'ai_blogpost_dalle_model',
        'ai_blogpost_dalle_prompt_template',
        'ai_blogpost_language',
        'ai_blogpost_dalle_enabled',
        'ai_blogpost_comfyui_api_url',
        'ai_blogpost_comfyui_workflows',
        'ai_blogpost_comfyui_default_workflow',
        'ai_blogpost_lm_enabled',
        'ai_blogpost_lm_api_url',
        'ai_blogpost_lm_api_key',
        'ai_blogpost_lm_model',
        'ai_blogpost_comfyui_new_workflow'
    ];

    foreach ($settings as $setting) {
        register_setting('ai_blogpost_settings', $setting, ['sanitize_callback' => 'sanitize_text_field']);
    }

    register_setting('ai_blogpost_settings', 'ai_blogpost_role', ['sanitize_callback' => 'wp_kses_post']);
    register_setting('ai_blogpost_settings', 'ai_blogpost_prompt', ['sanitize_callback' => 'wp_kses_post']);
    register_setting('ai_blogpost_settings', 'ai_blogpost_custom_categories', ['sanitize_callback' => 'wp_kses_post']);
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template', ['sanitize_callback' => 'wp_kses_post']);
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_new_workflow', ['sanitize_callback' => 'ai_blogpost_sanitize_workflow']);
}
add_action('admin_init', 'ai_blogpost_initialize_settings');

/**
 * Custom sanitization for workflow JSON
 */
function ai_blogpost_sanitize_workflow($value) {
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['name']) && isset($decoded['workflow'])) {
        return $value;
    }
    return '';
}

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ai_blogpost_debug_log('POST data received:', print_r($_POST, true));
    }

    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1>AI Blogpost Dashboard</h1>
        <div class="dashboard-content">
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
                    echo '<div class="next-post-info"><span class="dashicons dashicons-calendar-alt"></span> ';
                    echo 'Next scheduled post: ' . get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i');
                    echo '</div>';
                }
                ?>
            </div>

            <form method="post" action="options.php">
                <?php 
                settings_fields('ai_blogpost_settings');
                do_settings_sections('ai_blogpost_settings');
                ?>
                <div class="tabs">
                    <ul class="tab-links">
                        <li class="active"><a href="#tab-general">General</a></li>
                        <li><a href="#tab-text">Text Generation</a></li>
                        <li><a href="#tab-image">Image Generation</a></li>
                    </ul>

                    <div class="tab-content">
                        <div id="tab-general" class="tab active">
                            <?php display_general_settings(); ?>
                        </div>
                        <div id="tab-text" class="tab">
                            <?php display_text_settings(); ?>
                        </div>
                        <div id="tab-image" class="tab">
                            <?php display_image_settings(); ?>
                        </div>
                    </div>
                </div>
                <?php submit_button('Save Settings'); ?>
            </form>

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
        </div>
    </div>

    <style>
        .ai-blogpost-dashboard { max-width: 960px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .dashboard-content { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .test-post-section { padding: 15px; background: #f9fafb; border-radius: 6px; margin-bottom: 20px; }
        .test-post-header { display: flex; justify-content: space-between; align-items: center; }
        .test-post-header h2 { margin: 0; font-size: 18px; }
        .next-post-info { margin-top: 10px; color: #555; font-size: 14px; }
        .tabs { margin-bottom: 20px; }
        .tab-links { list-style: none; padding: 0; margin: 0; display: flex; border-bottom: 1px solid #ddd; }
        .tab-links li { margin: 0; }
        .tab-links a { display: block; padding: 10px 20px; text-decoration: none; color: #0073aa; font-weight: 500; }
        .tab-links li.active a { background: #fff; border-bottom: 2px solid #0073aa; color: #0073aa; }
        .tab-content { padding: 20px; background: #fff; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; }
        .tab { display: none; }
        .tab.active { display: block; }
        .form-table th { width: 180px; font-weight: 600; }
        .form-table td { padding: 10px 0; }
        .form-table input[type="text"], .form-table input[type="password"], .form-table input[type="url"], .form-table textarea, .form-table select { width: 100%; max-width: 400px; }
        .status-panel { padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .status-header { display: flex; justify-content: space-between; align-items: center; }
        .status-actions { display: flex; gap: 10px; }
        .submit { margin-top: 20px; text-align: right; }
        .generation-type-options { display: flex; gap: 15px; margin-bottom: 20px; }
        .generation-option { flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 6px; transition: all 0.3s ease; cursor: pointer; }
        .generation-option:hover, .generation-option.active { background: #f0f7ff; border-color: #0073aa; }
        .generation-option label { margin-bottom: 8px; font-weight: 600; }
        .dalle-settings, .comfyui-settings { transition: opacity 0.3s ease; }
        .workflow-manager { margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 6px; }
        @media (max-width: 768px) { .tab-links { flex-direction: column; } .tab-links a { padding: 10px; } .generation-type-options { flex-direction: column; } }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('.tab-links a').on('click', function(e) {
            e.preventDefault();
            var $this = $(this), tabId = $this.attr('href');
            $('.tab-links li').removeClass('active');
            $this.parent().addClass('active');
            $('.tab').removeClass('active');
            $(tabId).addClass('active');
        });

        $('input[name="ai_blogpost_image_generation_type"]').change(function() {
            var type = $(this).val();
            $('.generation-option').removeClass('active');
            $(this).closest('.generation-option').addClass('active');
            $('.dalle-settings, .comfyui-settings').css('opacity', 0).hide();
            $('.' + type + '-settings').css('opacity', 0).show().animate({opacity: 1}, 300);
        });

        $('.generation-option').click(function() {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        });

        $('.test-comfyui-connection, .test-lm-connection').click(function() {
            var $button = $(this), $spinner = $button.next('.spinner'), apiUrl = $button.prev('input').val(),
                action = $button.hasClass('test-comfyui-connection') ? 'test_comfyui_connection' : 'test_lm_studio';
            var $notification = $('<div>').css({
                position: 'fixed', top: '20px', right: '20px', padding: '10px 20px', borderRadius: '4px',
                background: '#fff', boxShadow: '0 2px 5px rgba(0,0,0,0.2)', zIndex: 9999, display: 'none'
            }).appendTo('body');
            $button.prop('disabled', true); $spinner.addClass('is-active');
            $.post(ajaxurl, { action: action, url: apiUrl, nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>' }, function(response) {
                $notification.html(response.success ? '✅ Connection successful!' : '❌ ' + response.data)
                    .css('background', response.success ? '#e7f5ea' : '#fde8e8').fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
            }).fail(function() {
                $notification.html('❌ Connection failed').css('background', '#fde8e8').fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
            }).always(function() { $button.prop('disabled', false); $spinner.removeClass('is-active'); });
        });

        $('#ai_blogpost_comfyui_new_workflow').on('change', function() {
            try {
                var json = JSON.parse($(this).val());
                if (json.name && json.workflow) {
                    var $select = $('#ai_blogpost_comfyui_default_workflow');
                    if (!$select.find('option[value="' + json.name + '"]').length) {
                        $select.append($('<option>', { value: json.name, text: json.name + ' - ' + (json.description || 'New Workflow') }));
                    }
                    $select.val(json.name);
                }
            } catch (e) { console.log('Invalid workflow JSON'); }
        });
    });
    </script>
    <?php
}

/**
 * Display general settings section
 */
function display_general_settings() {
    ?>
    <table class="form-table">
        <tr>
            <th><label for="ai_blogpost_language">Content Language</label></th>
            <td>
                <select name="ai_blogpost_language" id="ai_blogpost_language">
                    <?php
                    $language = get_cached_option('ai_blogpost_language', 'en');
                    $languages = ['en' => 'English', 'nl' => 'Nederlands', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español'];
                    foreach ($languages as $code => $name) {
                        echo '<option value="' . esc_attr($code) . '" ' . selected($language, $code, false) . '>' . esc_html($name) . '</option>';
                    }
                    ?>
                </select>
                <p class="description">Select the language for generated content</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>
            <td>
                <select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">
                    <?php
                    $frequency = get_cached_option('ai_blogpost_post_frequency', 'daily');
                    echo '<option value="daily" ' . selected($frequency, 'daily', false) . '>Daily</option>';
                    echo '<option value="weekly" ' . selected($frequency, 'weekly', false) . '>Weekly</option>';
                    ?>
                </select>
                <p class="description">How often should new posts be generated?</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_custom_categories">Categories</label></th>
            <td>
                <textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_custom_categories', 'tarot')); ?></textarea>
                <p class="description">Enter categories (one per line) for post generation</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Display text generation settings section
 */
function display_text_settings() {
    ?>
    <table class="form-table">
        <tr>
            <th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>
            <td>
                <input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_api_key')); ?>">
                <p class="description">Your OpenAI API key for text generation</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_model">GPT Model</label></th>
            <td><?php display_model_dropdown('gpt'); ?></td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_temperature">Temperature</label></th>
            <td>
                <input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" value="<?php echo esc_attr(get_cached_option('ai_blogpost_temperature', '0.7')); ?>">
                <p class="description">Controls randomness (0 = deterministic, 2 = very random)</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>
            <td>
                <input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="<?php echo esc_attr(get_cached_option('ai_blogpost_max_tokens', '2048')); ?>" min="100" max="4096">
                <p class="description">Maximum length of generated text (max 4096)</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_role">System Role</label></th>
            <td>
                <textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.')); ?></textarea>
                <p class="description">Define the AI's role and writing style</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_prompt">Content Template</label></th>
            <td>
                <textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="4" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_prompt', "Write a blog post about [topic]. Structure the content as follows:\n\n||Title||: Create an engaging, SEO-friendly title\n\n||Content||: Write the main content here, using proper HTML structure:\n- Use <article> tags to wrap the content\n- Use <h1>, <h2> for headings\n- Use <p> for paragraphs\n- Include relevant subheadings\n- Add a strong conclusion\n\n||Category||: Suggest the most appropriate category for this post")); ?></textarea>
                <div class="template-guide" style="margin-top: 10px; padding: 15px; background: #f9fafb; border-radius: 6px;">
                    <h4 style="margin-top: 0;">Content Structure Guide</h4>
                    <p>Use: <code>||Title||:</code>, <code>||Content||:</code>, <code>||Category||:</code></p>
                    <p><strong>Tips:</strong> Use [topic] for category, include HTML tags, add SEO instructions.</p>
                </div>
            </td>
        </tr>
        <?php add_refresh_models_button(); ?>
        <tr>
            <th colspan="2"><h3>LM Studio Integration</h3></th>
        </tr>
        <tr>
            <th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>
            <td>
                <input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" <?php checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1); ?>>
                <p class="description">Enable local LM Studio integration</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>
            <td>
                <input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')); ?>">
                <button type="button" class="button test-lm-connection">Test Connection</button>
                <span class="spinner" style="float: none; margin-left: 4px;"></span>
                <p class="description">Usually http://localhost:1234/v1</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Display image generation settings section
 */
function display_image_settings() {
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'dalle');
    ?>
    <div class="image-generation-selector">
        <div class="generation-type-options">
            <div class="generation-option <?php echo $generation_type === 'dalle' ? 'active' : ''; ?>">
                <label><input type="radio" name="ai_blogpost_image_generation_type" value="dalle" <?php checked($generation_type, 'dalle'); ?>> <strong>DALL-E</strong></label>
                <p class="description">Use OpenAI's DALL-E</p>
            </div>
            <div class="generation-option <?php echo $generation_type === 'comfyui' ? 'active' : ''; ?>">
                <label><input type="radio" name="ai_blogpost_image_generation_type" value="comfyui" <?php checked($generation_type, 'comfyui'); ?>> <strong>ComfyUI</strong></label>
                <p class="description">Use local ComfyUI</p>
            </div>
        </div>
    </div>

    <div class="dalle-settings" style="display: <?php echo $generation_type === 'dalle' ? 'block' : 'none'; ?>;">
        <h3>DALL-E Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="ai_blogpost_dalle_api_key">API Key</label></th>
                <td>
                    <input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_dalle_api_key')); ?>">
                    <p class="description">Your OpenAI API key for DALL-E</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_model">Model</label></th>
                <td><?php display_model_dropdown('dalle'); ?></td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>
                <td>
                    <textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 'A professional blog header image for [category], modern style, clean design, subtle symbolism')); ?></textarea>
                    <p class="description">Use [category] as placeholder</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="comfyui-settings" style="display: <?php echo $generation_type === 'comfyui' ? 'block' : 'none'; ?>;">
        <h3>ComfyUI Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>
                <td>
                    <input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188')); ?>">
                    <button type="button" class="button test-comfyui-connection">Test Connection</button>
                    <span class="spinner" style="float: none; margin-left: 4px;"></span>
                    <p class="description">Usually http://localhost:8188</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_comfyui_default_workflow">Default Workflow</label></th>
                <td>
                    <?php
                    $workflows_raw = get_cached_option('ai_blogpost_comfyui_workflows', '[]');
                    $workflows = is_array($workflows_raw) ? $workflows_raw : json_decode($workflows_raw, true);
                    if (!is_array($workflows)) {
                        $workflows = [];
                        ai_blogpost_debug_log('Workflows invalid or empty, resetting to empty array');
                    }
                    $default_workflow = get_cached_option('ai_blogpost_comfyui_default_workflow', '');
                    ?>
                    <select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow">
                        <?php
                        if (empty($workflows)) {
                            echo '<option value="">No workflows available</option>';
                        } else {
                            foreach ($workflows as $workflow) {
                                echo '<option value="' . esc_attr($workflow['name']) . '" ' . selected($default_workflow, $workflow['name'], false) . '>' . esc_html($workflow['name'] . ' - ' . $workflow['description']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description">Select the default workflow</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_comfyui_new_workflow">Add New Workflow</label></th>
                <td>
                    <textarea name="ai_blogpost_comfyui_new_workflow" id="ai_blogpost_comfyui_new_workflow" rows="5" class="large-text code" placeholder='{"name": "new_workflow", "description": "Description", "workflow": {...}}'></textarea>
                    <p class="description">Paste a valid ComfyUI workflow JSON</p>
                </td>
            </tr>
        </table>
        <div class="workflow-manager">
            <h4>Workflow Details</h4>
            <p>Input: Category-based prompt | Processing: Advanced pipeline | Output: 512x512 image</p>
        </div>
    </div>
    <?php
}

/**
 * Display model dropdown for GPT or DALL-E
 */
function display_model_dropdown($type = 'gpt') {
    $stored_models = get_option($type === 'gpt' ? 'ai_blogpost_available_gpt_models' : 'ai_blogpost_available_dalle_models', []);
    $current_model = get_cached_option($type === 'gpt' ? 'ai_blogpost_model' : 'ai_blogpost_dalle_model');
    $default_models = $type === 'gpt' ? ['gpt-4', 'gpt-3.5-turbo'] : ['dall-e-3', 'dall-e-2'];
    $models = !empty($stored_models) ? $stored_models : $default_models;

    echo '<select name="' . ($type === 'gpt' ? 'ai_blogpost_model' : 'ai_blogpost_dalle_model') . '">';
    foreach ($models as $model) {
        echo '<option value="' . esc_attr($model) . '" ' . selected($current_model, $model, false) . '>' . esc_html($model) . '</option>';
    }
    echo '</select>';
    if (empty($stored_models)) {
        echo '<p class="description">Save API key to fetch models</p>';
    }
}

/**
 * Add refresh models button
 */
function add_refresh_models_button() {
    ?>
    <tr>
        <th>Available Models</th>
        <td>
            <button type="button" class="button" id="refresh-models">Refresh Models</button>
            <span class="spinner" style="float: none; margin-left: 4px;"></span>
            <p class="description">Fetch available models from OpenAI</p>
        </td>
    </tr>
    <script>
    jQuery(document).ready(function($) {
        $('#refresh-models').click(function() {
            var $button = $(this), $spinner = $button.next('.spinner');
            $button.prop('disabled', true); $spinner.addClass('is-active');
            $.post(ajaxurl, { action: 'refresh_openai_models', nonce: '<?php echo wp_create_nonce("refresh_models_nonce"); ?>' }, function(response) {
                if (response.success) { location.reload(); } else { alert('Failed to fetch models. Check API key.'); }
            }).always(function() { $button.prop('disabled', false); $spinner.removeClass('is-active'); });
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
        $response = wp_remote_get($api_url . '/models', ['headers' => ['Content-Type' => 'application/json'], 'timeout' => 30, 'sslverify' => false]);
        if (is_wp_error($response)) { wp_send_json_error('Connection failed: ' . $response->get_error_message()); return; }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $models = !empty($data['data']) ? $data['data'] : (is_array($data) ? array_map(function($m) { return is_array($m) ? $m : ['id' => $m]; }, $data) : []);
        if (empty($models)) { wp_send_json_error('No models found'); return; }
        update_option('ai_blogpost_available_lm_models', $models);
        update_option('ai_blogpost_lm_api_url', rtrim($api_url, '/v1'));
        ai_blogpost_log_api_call('LM Studio Test', true, ['url' => $api_url, 'status' => 'Success', 'models_found' => count($models)]);
        wp_send_json_success(['message' => 'Connection successful', 'models' => $models]);
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
        $response = wp_remote_get($api_url . '/queue', ['timeout' => 30, 'sslverify' => false]);
        if (is_wp_error($response)) { wp_send_json_error('Connection failed: ' . $response->get_error_message()); return; }
        $queue_data = json_decode(wp_remote_retrieve_body($response), true);
        $history_response = wp_remote_get($api_url . '/history', ['timeout' => 30, 'sslverify' => false]);
        if (is_wp_error($history_response)) { wp_send_json_error('Failed to access history'); return; }
        $history_data = json_decode(wp_remote_retrieve_body($history_response), true);
        update_option('ai_blogpost_comfyui_api_url', $api_url);
        ai_blogpost_log_api_call('ComfyUI Test', true, ['url' => $api_url, 'status' => 'Success', 'queue_status' => $queue_data, 'history_available' => !empty($history_data)]);
        wp_send_json_success(['message' => 'Connection successful', 'queue_status' => $queue_data, 'history_available' => !empty($history_data)]);
    } catch (Exception $e) {
        ai_blogpost_debug_log('ComfyUI error:', $e->getMessage());
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_test_comfyui_connection', 'handle_comfyui_test');

/**
 * Save new ComfyUI workflow
 */
function ai_blogpost_save_new_workflow() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['option_page']) && $_POST['option_page'] === 'ai_blogpost_settings') {
        ai_blogpost_debug_log('Saving settings, checking for new workflow...');
        
        if (isset($_POST['ai_blogpost_comfyui_new_workflow']) && !empty(trim($_POST['ai_blogpost_comfyui_new_workflow']))) {
            $new_workflow_json = wp_unslash($_POST['ai_blogpost_comfyui_new_workflow']);
            $new_workflow = json_decode($new_workflow_json, true);
            ai_blogpost_debug_log('New workflow submitted:', $new_workflow_json);

            if (json_last_error() === JSON_ERROR_NONE && isset($new_workflow['name']) && isset($new_workflow['workflow'])) {
                $workflows = json_decode(get_option('ai_blogpost_comfyui_workflows', '[]'), true);
                if (!is_array($workflows)) {
                    $workflows = [];
                    ai_blogpost_debug_log('Existing workflows invalid, resetting to empty array');
                }
                
                $workflow_exists = false;
                foreach ($workflows as $index => $existing_workflow) {
                    if ($existing_workflow['name'] === $new_workflow['name']) {
                        $workflows[$index] = $new_workflow;
                        $workflow_exists = true;
                        break;
                    }
                }
                
                if (!$workflow_exists) {
                    $workflows[] = $new_workflow;
                    update_option('ai_blogpost_comfyui_default_workflow', $new_workflow['name']);
                }
                
                update_option('ai_blogpost_comfyui_workflows', json_encode($workflows));
                clear_ai_blogpost_cache(); // Clear cache after saving
                ai_blogpost_debug_log('Workflow successfully saved:', $new_workflow);
                add_settings_error('ai_blogpost_settings', 'workflow_saved', 'New workflow "' . esc_html($new_workflow['name']) . '" saved successfully.', 'success');
            } else {
                ai_blogpost_debug_log('Invalid workflow JSON:', $new_workflow_json);
                add_settings_error('ai_blogpost_settings', 'invalid_workflow', 'Invalid workflow JSON format.', 'error');
            }
        } else {
            ai_blogpost_debug_log('No new workflow submitted or empty in POST data');
        }
    }
}
add_action('admin_init', 'ai_blogpost_save_new_workflow', 100);