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
 * Enqueue admin scripts and styles
 */
function ai_blogpost_admin_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ai_blogpost') {
        return;
    }
    
    // Enqueue WordPress core styles and scripts
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('dashicons');
    
    // Register and enqueue our custom CSS
    wp_enqueue_style(
        'ai-blogpost-admin-css',
        plugins_url('../assets/css/admin.css', __FILE__),
        array(),
        '1.0.0'
    );
    
    // Register and enqueue our custom JS
    wp_enqueue_script(
        'ai-blogpost-admin-js', 
        plugins_url('../assets/js/admin.js', __FILE__), 
        array('jquery', 'wp-color-picker', 'jquery-ui-tabs', 'jquery-ui-tooltip'), 
        '1.0.0', 
        true
    );
    
    wp_localize_script('ai-blogpost-admin-js', 'aiSettings', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_blogpost_nonce'),
        'refreshNonce' => wp_create_nonce('refresh_models_nonce'),
        'strings' => array(
            'testSuccess' => __('Test connection successful!', 'ai-blogpost'),
            'testFailed' => __('Connection failed. Please check your settings.', 'ai-blogpost'),
            'saving' => __('Saving...', 'ai-blogpost'),
            'saved' => __('Settings saved!', 'ai-blogpost'),
            'error' => __('Error saving settings.', 'ai-blogpost'),
        )
    ));
}
add_action('admin_enqueue_scripts', 'ai_blogpost_admin_enqueue_scripts');

/**
 * Display the admin settings page with optimized UX/UI.
 */
function ai_blogpost_admin_page() {
    // Check for test post generation
    $test_notice = get_transient('ai_blogpost_test_notice');
    if ($test_notice) {
        delete_transient('ai_blogpost_test_notice');
        if ($test_notice === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Test Post Created Successfully!', 'ai-blogpost') . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Test Post Creation Failed. Check error logs.', 'ai-blogpost') . '</p></div>';
        }
    }
    
    // Get next scheduled post time
    $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1><span class="dashicons dashicons-edit-page"></span> <?php _e('AI Blogpost Dashboard', 'ai-blogpost'); ?></h1>
        
        <!-- Dashboard Header Card -->
        <div class="ai-card ai-header-card">
            <div class="ai-card-content">
                <div class="ai-header-info">
                    <div class="ai-header-title">
                        <h2><?php _e('AI-Generated Tarot Blogpost', 'ai-blogpost'); ?></h2>
                        <p class="ai-header-description"><?php _e('Automatically generate blog posts with AI-powered text and images', 'ai-blogpost'); ?></p>
                    </div>
                    <div class="ai-header-actions">
                        <form method="post" class="ai-test-form">
                            <button type="submit" name="test_ai_blogpost" class="button button-primary ai-test-button">
                                <span class="dashicons dashicons-welcome-write-blog"></span>
                                <?php _e('Generate Test Post', 'ai-blogpost'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php if ($next_post_time): ?>
                <div class="ai-next-post">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Next scheduled post:', 'ai-blogpost'); ?> 
                    <strong><?php echo get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i'); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content Area with Tabs -->
        <div class="ai-tabs-container">
            <div id="ai-tabs" class="ai-tabs">
                <ul class="ai-tabs-nav">
                    <li><a href="#tab-general"><span class="dashicons dashicons-admin-settings"></span> <?php _e('General', 'ai-blogpost'); ?></a></li>
                    <li><a href="#tab-text"><span class="dashicons dashicons-text"></span> <?php _e('Text Generation', 'ai-blogpost'); ?></a></li>
                    <li><a href="#tab-image"><span class="dashicons dashicons-format-image"></span> <?php _e('Image Generation', 'ai-blogpost'); ?></a></li>
                    <li><a href="#tab-logs"><span class="dashicons dashicons-list-view"></span> <?php _e('Logs', 'ai-blogpost'); ?></a></li>
                </ul>
                
                <form method="post" action="options.php" class="ai-blogpost-form" id="ai-settings-form">
                    <?php
                        settings_fields('ai_blogpost_settings');
                        do_settings_sections('ai_blogpost_settings');
                    ?>
                    
                    <!-- General Settings Tab -->
                    <div id="tab-general" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Schedule Settings', 'ai-blogpost'); ?></h2>
                            </div>
                            <div class="ai-card-content">
                                <?php display_general_settings(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Text Generation Tab -->
                    <div id="tab-text" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-text"></span> <?php _e('OpenAI Text Generation', 'ai-blogpost'); ?></h2>
                            </div>
                            <div class="ai-card-content">
                                <?php display_text_settings(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Generation Tab -->
                    <div id="tab-image" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-format-image"></span> <?php _e('Featured Image Generation', 'ai-blogpost'); ?></h2>
                            </div>
                            <div class="ai-card-content">
                                <?php display_image_settings(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logs Tab -->
                    <div id="tab-logs" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-list-view"></span> <?php _e('Generation Logs', 'ai-blogpost'); ?></h2>
                                <div class="ai-card-actions">
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                                        <button type="submit" name="clear_ai_logs" class="button ai-icon-button" title="<?php _e('Clear Logs', 'ai-blogpost'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                    <button type="button" class="button ai-icon-button ai-refresh-logs" title="<?php _e('Refresh Logs', 'ai-blogpost'); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="ai-card-content">
                                <div class="ai-logs-container">
                                    <div class="ai-log-section">
                                        <h3><span class="dashicons dashicons-text"></span> <?php _e('Text Generation', 'ai-blogpost'); ?></h3>
                                        <div class="ai-log-content" id="text-generation-logs">
                                            <?php display_api_logs('Text Generation'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="ai-log-section">
                                        <h3><span class="dashicons dashicons-format-image"></span> <?php _e('Image Generation', 'ai-blogpost'); ?></h3>
                                        <div class="ai-log-content" id="image-generation-logs">
                                            <?php display_api_logs('Image Generation'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ai-form-actions">
                        <div class="ai-save-indicator">
                            <span class="spinner"></span>
                            <span class="ai-save-message"></span>
                        </div>
                        <?php submit_button(__('Save Settings', 'ai-blogpost'), 'primary', 'submit', false, ['id' => 'ai-save-settings']); ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
    echo '<div class="ai-field-wrapper">';
    echo '<select name="ai_blogpost_language" id="ai_blogpost_language" class="ai-select-field">';
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
    echo '<p class="description">Select the language for all generated content. This affects both text and image prompts.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Post Frequency
    echo '<tr>';
    echo '<th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-radio-group">';
    $frequency = get_cached_option('ai_blogpost_post_frequency', 'daily');
    
    echo '<label class="ai-radio-label' . ($frequency === 'daily' ? ' ai-radio-selected' : '') . '">';
    echo '<input type="radio" name="ai_blogpost_post_frequency" value="daily" ' . checked($frequency, 'daily', false) . '>';
    echo '<span class="ai-radio-text"><span class="dashicons dashicons-calendar-alt"></span> Daily</span>';
    echo '</label>';
    
    echo '<label class="ai-radio-label' . ($frequency === 'weekly' ? ' ai-radio-selected' : '') . '">';
    echo '<input type="radio" name="ai_blogpost_post_frequency" value="weekly" ' . checked($frequency, 'weekly', false) . '>';
    echo '<span class="ai-radio-text"><span class="dashicons dashicons-calendar"></span> Weekly</span>';
    echo '</label>';
    
    echo '</div>';
    echo '<p class="description">How often should new posts be automatically generated?</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Custom Categories
    echo '<tr>';
    echo '<th><label for="ai_blogpost_custom_categories">Categories</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_custom_categories', 'tarot'));
    echo '</textarea>';
    echo '<div class="ai-field-info">';
    echo '<p class="description">Enter categories (one per line) that will be used for post generation. The system will randomly select one category for each post.</p>';
    echo '<div class="ai-field-example">';
    echo '<strong>Example:</strong><br>';
    echo 'tarot<br>astrology<br>meditation<br>crystals';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Add JavaScript for radio buttons
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Handle radio button selection
        $('.ai-radio-label').click(function() {
            $(this).closest('.ai-radio-group').find('.ai-radio-label').removeClass('ai-radio-selected');
            $(this).addClass('ai-radio-selected');
        });
    });
    </script>
    <style>
    .ai-field-wrapper {
        position: relative;
    }
    
    .ai-select-field {
        min-width: 200px;
    }
    
    .ai-radio-group {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    .ai-radio-label {
        display: flex;
        align-items: center;
        padding: 8px 15px;
        border: 1px solid var(--ai-border);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .ai-radio-label:hover {
        background: var(--ai-primary-light);
        border-color: var(--ai-primary);
    }
    
    .ai-radio-selected {
        background: var(--ai-primary-light);
        border-color: var(--ai-primary);
    }
    
    .ai-radio-label input[type="radio"] {
        display: none;
    }
    
    .ai-radio-text {
        display: flex;
        align-items: center;
    }
    
    .ai-radio-text .dashicons {
        margin-right: 8px;
        color: var(--ai-primary);
    }
    
    .ai-field-info {
        margin-top: 10px;
    }
    
    .ai-field-example {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        font-size: 13px;
    }
    </style>
    <?php
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
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-api-key-field">';
    echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="' . esc_attr(get_cached_option('ai_blogpost_api_key')) . '">';
    echo '<button type="button" class="button ai-toggle-password" title="Show/Hide API Key"><span class="dashicons dashicons-visibility"></span></button>';
    echo '</div>';
    echo '<p class="description">Your OpenAI API key for text generation. <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key here</a>.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Model Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_model">GPT Model</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    display_model_dropdown('gpt');
    echo '<p class="description">Select the GPT model to use for text generation. More advanced models (like GPT-4) provide better quality but cost more.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Temperature with slider
    echo '<tr>';
    echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    $temperature = get_cached_option('ai_blogpost_temperature', '0.7');
    echo '<div class="ai-slider-container">';
    echo '<input type="range" class="ai-slider" min="0" max="2" step="0.1" value="' . esc_attr($temperature) . '">';
    echo '<input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" class="ai-slider-value" value="' . esc_attr($temperature) . '">';
    echo '</div>';
    echo '<div class="ai-slider-labels">';
    echo '<span>Predictable</span>';
    echo '<span>Balanced</span>';
    echo '<span>Creative</span>';
    echo '</div>';
    echo '<p class="description">Controls randomness in text generation. Lower values produce more predictable text, higher values more creative.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Max Tokens with slider
    echo '<tr>';
    echo '<th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    $max_tokens = get_cached_option('ai_blogpost_max_tokens', '2048');
    echo '<div class="ai-slider-container">';
    echo '<input type="range" class="ai-slider" min="500" max="4096" step="100" value="' . esc_attr($max_tokens) . '">';
    echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" class="ai-slider-value" value="' . esc_attr($max_tokens) . '" min="500" max="4096" step="100">';
    echo '</div>';
    echo '<div class="ai-slider-labels">';
    echo '<span>Shorter</span>';
    echo '<span>Medium</span>';
    echo '<span>Longer</span>';
    echo '</div>';
    echo '<p class="description">Maximum length of generated text. Higher values allow for longer blog posts.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // System Role
    echo '<tr>';
    echo '<th><label for="ai_blogpost_role">System Role</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.'));
    echo '</textarea>';
    echo '<p class="description">Define the AI\'s role and writing style. This sets the tone and approach for all generated content.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    // Content Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_prompt">Content Template</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="6" class="large-text code">';
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
    
    echo '<div class="template-guide">';
    echo '<div class="template-guide-header">';
    echo '<h4>Content Structure Guide</h4>';
    echo '<span class="dashicons dashicons-editor-help" title="Click for more information"></span>';
    echo '</div>';
    echo '<div class="template-guide-content">';
    echo '<p class="description">Use these section markers to structure the content:</p>';
    echo '<ul>';
    echo '<li><code>||Title||:</code> - The blog post title</li>';
    echo '<li><code>||Content||:</code> - The main content (wrapped in <code>&lt;article&gt;</code> tags)</li>';
    echo '<li><code>||Category||:</code> - The suggested category</li>';
    echo '</ul>';
    echo '<p class="description"><strong>Tips:</strong></p>';
    echo '<ul>';
    echo '<li>Use [topic] in your prompt to reference the selected category</li>';
    echo '<li>Add specific instructions about tone, style, or length in the System Role</li>';
    echo '<li>Use HTML tags for proper content structure</li>';
    echo '<li>Include SEO best practices in your instructions</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    add_refresh_models_button();
    
    // LM Studio Section
    echo '<tr>';
    echo '<th colspan="2"><h3 class="ai-section-header"><span class="dashicons dashicons-desktop"></span> LM Studio Integration</h3></th>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<label class="ai-toggle-switch">';
    echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . 
         checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1, false) . '>';
    echo '<span class="ai-toggle-slider"></span>';
    echo '</label>';
    echo '<p class="description">Enable local LM Studio integration for text generation. This allows you to use your own local models instead of OpenAI\'s API.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="lm-studio-setting" style="' . (get_cached_option('ai_blogpost_lm_enabled', 0) ? '' : 'display: none;') . '">';
    echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-input-with-button">';
    echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')) . '">';
    echo '<button type="button" class="button test-lm-connection">Test Connection</button>';
    echo '</div>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">The URL to your local LM Studio API. Usually http://localhost:1234/v1</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    
    // Add JavaScript for sliders and toggle
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Handle sliders
        $('.ai-slider').on('input', function() {
            $(this).next('.ai-slider-value').val($(this).val());
        });
        
        $('.ai-slider-value').on('input', function() {
            $(this).prev('.ai-slider').val($(this).val());
        });
        
        // Toggle password visibility
        $('.ai-toggle-password').click(function() {
            var $input = $(this).prev('input');
            var $icon = $(this).find('.dashicons');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Toggle LM Studio settings
        $('#ai_blogpost_lm_enabled').change(function() {
            if ($(this).is(':checked')) {
                $('.lm-studio-setting').slideDown(200);
            } else {
                $('.lm-studio-setting').slideUp(200);
            }
        });
        
        // Template guide toggle
        $('.template-guide-header').click(function() {
            $(this).next('.template-guide-content').slideToggle(200);
            $(this).find('.dashicons').toggleClass('dashicons-editor-help dashicons-no-alt');
        });
    });
    </script>
    <style>
    /* API Key Field */
    .ai-api-key-field {
        display: flex;
        max-width: 400px;
    }
    
    .ai-api-key-field input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .ai-api-key-field button {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: none;
    }
    
    /* Slider Styling */
    .ai-slider-container {
        display: flex;
        align-items: center;
        gap: 15px;
        max-width: 400px;
    }
    
    .ai-slider {
        flex: 1;
        margin: 0;
    }
    
    .ai-slider-value {
        width: 70px !important;
    }
    
    .ai-slider-labels {
        display: flex;
        justify-content: space-between;
        max-width: 400px;
        margin-top: 5px;
        font-size: 12px;
        color: var(--ai-text-light);
    }
    
    /* Toggle Switch */
    .ai-toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .ai-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .ai-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .ai-toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .ai-toggle-slider {
        background-color: var(--ai-primary);
    }
    
    input:focus + .ai-toggle-slider {
        box-shadow: 0 0 1px var(--ai-primary);
    }
    
    input:checked + .ai-toggle-slider:before {
        transform: translateX(26px);
    }
    
    /* Section Header */
    .ai-section-header {
        display: flex;
        align-items: center;
        margin: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--ai-border);
        color: var(--ai-primary);
    }
    
    .ai-section-header .dashicons {
        margin-right: 8px;
    }
    
    /* Input with Button */
    .ai-input-with-button {
        display: flex;
        max-width: 400px;
    }
    
    .ai-input-with-button input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .ai-input-with-button button {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    
    /* Template Guide */
    .template-guide {
        margin-top: 15px;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .template-guide-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #e2e4e7;
        cursor: pointer;
    }
    
    .template-guide-header h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
    }
    
    .template-guide-content {
        padding: 15px;
        display: none;
    }
    
    .template-guide-content ul {
        margin: 10px 0 15px 20px;
        list-style-type: disc;
    }
    </style>
    <?php
}

/**
 * Display image generation settings section
 */
function display_image_settings() {
    // Get current image generation type
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'dalle');
    
    // Image Generation Type Selection Cards
    echo '<div class="ai-image-generation-selector">';
    echo '<h3 class="ai-section-title"><span class="dashicons dashicons-format-image"></span> Select Image Generation Method</h3>';
    echo '<div class="ai-generation-cards">';
    
    // DALL-E Card
    echo '<div class="ai-generation-card' . ($generation_type === 'dalle' ? ' ai-card-selected' : '') . '" data-type="dalle">';
    echo '<div class="ai-card-header">';
    echo '<div class="ai-card-radio">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" id="ai_type_dalle" value="dalle" ' . 
         checked($generation_type, 'dalle', false) . '>';
    echo '<span class="ai-radio-indicator"></span>';
    echo '</div>';
    echo '<label for="ai_type_dalle">DALL-E</label>';
    echo '</div>';
    echo '<div class="ai-card-body">';
    echo '<div class="ai-card-icon"><span class="dashicons dashicons-cloud"></span></div>';
    echo '<div class="ai-card-description">';
    echo '<p>Use OpenAI\'s DALL-E for AI image generation</p>';
    echo '<ul>';
    echo '<li>High-quality images</li>';
    echo '<li>Cloud-based solution</li>';
    echo '<li>Requires API key</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // ComfyUI Card
    echo '<div class="ai-generation-card' . ($generation_type === 'comfyui' ? ' ai-card-selected' : '') . '" data-type="comfyui">';
    echo '<div class="ai-card-header">';
    echo '<div class="ai-card-radio">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" id="ai_type_comfyui" value="comfyui" ' . 
         checked($generation_type, 'comfyui', false) . '>';
    echo '<span class="ai-radio-indicator"></span>';
    echo '</div>';
    echo '<label for="ai_type_comfyui">ComfyUI</label>';
    echo '</div>';
    echo '<div class="ai-card-body">';
    echo '<div class="ai-card-icon"><span class="dashicons dashicons-desktop"></span></div>';
    echo '<div class="ai-card-description">';
    echo '<p>Use local ComfyUI for advanced image generation</p>';
    echo '<ul>';
    echo '<li>Customizable workflows</li>';
    echo '<li>Local processing</li>';
    echo '<li>No API costs</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End ai-generation-cards
    echo '</div>'; // End ai-image-generation-selector
    
    // DALL-E Settings Panel
    echo '<div class="ai-settings-panel dalle-settings" id="dalle-settings-panel" style="display: ' . ($generation_type === 'dalle' ? 'block' : 'none') . ';">';
    echo '<div class="ai-panel-header">';
    echo '<h3><span class="dashicons dashicons-cloud"></span> DALL-E Settings</h3>';
    echo '</div>';
    echo '<div class="ai-panel-content">';
    echo '<table class="form-table">';
    
    // API Key
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_api_key">API Key</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-api-key-field">';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<button type="button" class="button ai-toggle-password" title="Show/Hide API Key"><span class="dashicons dashicons-visibility"></span></button>';
    echo '</div>';
    echo '<p class="description">Your OpenAI API key for DALL-E image generation. <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key here</a>.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Model Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_model">Model</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    display_model_dropdown('dalle');
    echo '<p class="description">Select the DALL-E model to use. DALL-E 3 provides higher quality images but costs more.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Prompt Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 
        'A professional blog header image for [category], modern style, clean design, subtle symbolism'));
    echo '</textarea>';
    echo '<div class="ai-field-info">';
    echo '<p class="description">Use <code>[category]</code> as placeholder for the blog category. Be specific about style, colors, and composition.</p>';
    echo '<div class="ai-field-example">';
    echo '<strong>Example:</strong><br>';
    echo 'A professional blog header image for [category], with a minimalist design, soft blue and white color palette, subtle symbolism, and clean typography. Top-down view, high-quality, photorealistic.';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>'; // End panel content
    echo '</div>'; // End DALL-E settings panel

    // ComfyUI Settings Panel
    echo '<div class="ai-settings-panel comfyui-settings" id="comfyui-settings-panel" style="display: ' . ($generation_type === 'comfyui' ? 'block' : 'none') . ';">';
    echo '<div class="ai-panel-header">';
    echo '<h3><span class="dashicons dashicons-desktop"></span> ComfyUI Settings</h3>';
    echo '</div>';
    echo '<div class="ai-panel-content">';
    echo '<table class="form-table">';
    
    // Server URL
    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-input-with-button">';
    $comfyui_url = get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188');
    echo '<input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="' . 
         esc_attr($comfyui_url) . '">';
    echo '<button type="button" class="button test-comfyui-connection">Test Connection</button>';
    echo '</div>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">The URL to your local ComfyUI server. Usually <code>http://localhost:8188</code></p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Workflow Selection
    $workflows_json = file_get_contents(plugin_dir_path(__FILE__) . '../workflows/config.json');
    $workflows_data = json_decode($workflows_json, true);
    $workflows = isset($workflows_data['workflows']) ? $workflows_data['workflows'] : [];
    $default_workflow = isset($workflows_data['default_workflow']) ? $workflows_data['default_workflow'] : '';

    update_option('ai_blogpost_comfyui_workflows', json_encode($workflows));
    update_option('ai_blogpost_comfyui_default_workflow', $default_workflow);

    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_default_workflow">Workflow</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow" class="ai-select-field">';
    foreach ($workflows as $workflow) {
        echo '<option value="' . esc_attr($workflow['name']) . '" ' . 
             selected($default_workflow, $workflow['name'], false) . '>' . 
             esc_html($workflow['name'] . ' - ' . $workflow['description']) . '</option>';
    }
    echo '</select>';
    
    echo '<div class="ai-workflow-preview">';
    echo '<div class="ai-workflow-header">';
    echo '<h4>Workflow Details</h4>';
    echo '<span class="dashicons dashicons-info" title="Click for more information"></span>';
    echo '</div>';
    echo '<div class="ai-workflow-content">';
    echo '<ul>';
    echo '<li><strong>Input:</strong> Category-based prompt with customizable style</li>';
    echo '<li><strong>Processing:</strong> Advanced image generation pipeline</li>';
    echo '<li><strong>Output:</strong> High-quality 512x512 featured image</li>';
    echo '</ul>';
    echo '<p class="description">ComfyUI workflows are defined in the <code>workflows/config.json</code> file. You can add custom workflows by editing this file.</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>'; // End panel content
    echo '</div>'; // End ComfyUI settings panel
    
    // Add JavaScript for image generation settings
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Handle generation type selection
        $('.ai-generation-card').click(function() {
            var type = $(this).data('type');
            
            // Update radio button
            $(this).find('input[type="radio"]').prop('checked', true);
            
            // Update card styling
            $('.ai-generation-card').removeClass('ai-card-selected');
            $(this).addClass('ai-card-selected');
            
            // Show/hide appropriate settings panel
            if (type === 'dalle') {
                $('#comfyui-settings-panel').fadeOut(200, function() {
                    $('#dalle-settings-panel').fadeIn(200);
                });
            } else if (type === 'comfyui') {
                $('#dalle-settings-panel').fadeOut(200, function() {
                    $('#comfyui-settings-panel').fadeIn(200);
                });
            }
        });
        
        // Toggle password visibility
        $('.ai-toggle-password').click(function() {
            var $input = $(this).prev('input');
            var $icon = $(this).find('.dashicons');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Workflow details toggle
        $('.ai-workflow-header').click(function() {
            $(this).next('.ai-workflow-content').slideToggle(200);
            $(this).find('.dashicons').toggleClass('dashicons-info dashicons-no-alt');
        });
        
        // ComfyUI connection test
        $('.test-comfyui-connection').click(function() {
            var $button = $(this);
            var $spinner = $button.closest('td').find('.spinner');
            var apiUrl = $('#ai_blogpost_comfyui_api_url').val();
            
            if (!apiUrl) {
                showNotification('Please enter a server URL first', 'error');
                return;
            }
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.post(ajaxurl, {
                action: 'test_comfyui_connection',
                url: apiUrl,
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    showNotification('✅ ComfyUI connected successfully!', 'success');
                } else {
                    showNotification('❌ ' + (response.data || 'Connection failed'), 'error');
                }
            }).fail(function() {
                showNotification('❌ Connection failed. Please check the server URL.', 'error');
            }).always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });
        
        // Helper function to show notifications
        function showNotification(message, type) {
            var bgColor = type === 'success' ? '#e7f5ea' : '#fde8e8';
            
            var $notification = $('<div>')
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
                .appendTo('body')
                .fadeIn()
                .delay(3000)
                .fadeOut(function() { $(this).remove(); });
        }
    });
    </script>
    <style>
    /* Image Generation Settings Styling */
    .ai-image-generation-selector {
        margin-bottom: 25px;
    }
    
    .ai-section-title {
        display: flex;
        align-items: center;
        margin: 0 0 15px 0;
        font-size: 16px;
        font-weight: 500;
        color: var(--ai-primary);
    }
    
    .ai-section-title .dashicons {
        margin-right: 8px;
    }
    
    .ai-generation-cards {
        display: flex;
        gap: 20px;
    }
    
    .ai-generation-card {
        flex: 1;
        border: 1px solid var(--ai-border);
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .ai-generation-card:hover {
        border-color: var(--ai-primary);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .ai-card-selected {
        border-color: var(--ai-primary);
        background: var(--ai-primary-light);
    }
    
    .ai-card-header {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid var(--ai-border);
    }
    
    .ai-card-selected .ai-card-header {
        background: var(--ai-primary);
        color: white;
    }
    
    .ai-card-radio {
        position: relative;
        width: 18px;
        height: 18px;
        margin-right: 10px;
    }
    
    .ai-card-radio input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .ai-radio-indicator {
        position: absolute;
        top: 0;
        left: 0;
        width: 18px;
        height: 18px;
        border: 2px solid #ccc;
        border-radius: 50%;
        background: white;
    }
    
    .ai-card-selected .ai-radio-indicator {
        border-color: white;
    }
    
    input:checked + .ai-radio-indicator:after {
        content: "";
        position: absolute;
        top: 4px;
        left: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--ai-primary);
    }
    
    .ai-card-selected input:checked + .ai-radio-indicator:after {
        background: white;
    }
    
    .ai-card-header label {
        font-weight: 600;
        cursor: pointer;
    }
    
    .ai-card-body {
        display: flex;
        padding: 15px;
    }
    
    .ai-card-icon {
        margin-right: 15px;
    }
    
    .ai-card-icon .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
        color: var(--ai-primary);
    }
    
    .ai-card-selected .ai-card-icon .dashicons {
        color: var(--ai-primary-dark);
    }
    
    .ai-card-description {
        flex: 1;
    }
    
    .ai-card-description p {
        margin: 0 0 10px 0;
    }
    
    .ai-card-description ul {
        margin: 0 0 0 20px;
        padding: 0;
        list-style-type: disc;
        font-size: 13px;
        color: var(--ai-text-light);
    }
    
    /* Settings Panels */
    .ai-settings-panel {
        margin-bottom: 20px;
        border: 1px solid var(--ai-border);
        border-radius: 6px;
        overflow: hidden;
    }
    
    .ai-panel-header {
        padding: 12px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid var(--ai-border);
    }
    
    .ai-panel-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    
    .ai-panel-header h3 .dashicons {
        margin-right: 8px;
        color: var(--ai-primary);
    }
    
    .ai-panel-content {
        padding: 20px;
    }
    
    /* Workflow Preview */
    .ai-workflow-preview {
        margin-top: 15px;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .ai-workflow-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #e2e4e7;
        cursor: pointer;
    }
    
    .ai-workflow-
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
 * Enqueue admin scripts and styles
 */
function ai_blogpost_admin_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ai_blogpost') {
        return;
    }
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('dashicons');
    
    // Register and enqueue our custom JS and CSS
    wp_enqueue_script(
        'ai-blogpost-admin-js', 
        plugins_url('../assets/js/admin.js', __FILE__), 
        array('jquery', 'wp-color-picker', 'jquery-ui-tabs', 'jquery-ui-tooltip'), 
        '1.0.0', 
        true
    );
    
    wp_localize_script('ai-blogpost-admin-js', 'aiSettings', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_blogpost_nonce'),
        'refreshNonce' => wp_create_nonce('refresh_models_nonce'),
        'strings' => array(
            'testSuccess' => __('Test connection successful!', 'ai-blogpost'),
            'testFailed' => __('Connection failed. Please check your settings.', 'ai-blogpost'),
            'saving' => __('Saving...', 'ai-blogpost'),
            'saved' => __('Settings saved!', 'ai-blogpost'),
            'error' => __('Error saving settings.', 'ai-blogpost'),
        )
    ));
}
add_action('admin_enqueue_scripts', 'ai_blogpost_admin_enqueue_scripts');

/**
 * Display the admin settings page with optimized UX/UI.
 */
function ai_blogpost_admin_page() {
    // Check for test post generation
    $test_notice = get_transient('ai_blogpost_test_notice');
    if ($test_notice) {
        delete_transient('ai_blogpost_test_notice');
        if ($test_notice === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Test Post Created Successfully!', 'ai-blogpost') . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Test Post Creation Failed. Check error logs.', 'ai-blogpost') . '</p></div>';
        }
    }
    
    // Get next scheduled post time
    $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1><span class="dashicons dashicons-edit-page"></span> <?php _e('AI Blogpost Dashboard', 'ai-blogpost'); ?></h1>
        
        <!-- Dashboard Header Card -->
        <div class="ai-card ai-header-card">
            <div class="ai-card-content">
                <div class="ai-header-info">
                    <div class="ai-header-title">
                        <h2><?php _e('AI-Generated Tarot Blogpost', 'ai-blogpost'); ?></h2>
                        <p class="ai-header-description"><?php _e('Automatically generate blog posts with AI-powered text and images', 'ai-blogpost'); ?></p>
                    </div>
                    <div class="ai-header-actions">
                        <form method="post" class="ai-test-form">
                            <button type="submit" name="test_ai_blogpost" class="button button-primary ai-test-button">
                                <span class="dashicons dashicons-welcome-write-blog"></span>
                                <?php _e('Generate Test Post', 'ai-blogpost'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php if ($next_post_time): ?>
                <div class="ai-next-post">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Next scheduled post:', 'ai-blogpost'); ?> 
                    <strong><?php echo get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i'); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content Area with Tabs -->
        <div class="ai-tabs-container">
            <div id="ai-tabs" class="ai-tabs">
                <ul class="ai-tabs-nav">
                    <li><a href="#tab-general"><span class="dashicons dashicons-admin-settings"></span> <?php _e('General', 'ai-blogpost'); ?></a></li>
                    <li><a href="#tab-text"><span class="dashicons dashicons-text"></span> <?php _e('Text Generation', 'ai-blogpost'); ?></a></li>
                    <li><a href="#tab-image"><span class="dashicons dashicons-format-image"></span> <?php _e('Image Generation', 'ai-blogpost'); ?></a></li>
                    <li><a href="#tab-logs"><span class="dashicons dashicons-list-view"></span> <?php _e('Logs', 'ai-blogpost'); ?></a></li>
                </ul>
                
                <form method="post" action="options.php" class="ai-blogpost-form" id="ai-settings-form">
                    <?php
                        settings_fields('ai_blogpost_settings');
                        do_settings_sections('ai_blogpost_settings');
                    ?>
                    
                    <!-- General Settings Tab -->
                    <div id="tab-general" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Schedule Settings', 'ai-blogpost'); ?></h2>
                            </div>
                            <div class="ai-card-content">
                                <?php display_general_settings(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Text Generation Tab -->
                    <div id="tab-text" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-text"></span> <?php _e('OpenAI Text Generation', 'ai-blogpost'); ?></h2>
                            </div>
                            <div class="ai-card-content">
                                <?php display_text_settings(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Generation Tab -->
                    <div id="tab-image" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-format-image"></span> <?php _e('Featured Image Generation', 'ai-blogpost'); ?></h2>
                            </div>
                            <div class="ai-card-content">
                                <?php display_image_settings(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logs Tab -->
                    <div id="tab-logs" class="ai-tab-content">
                        <div class="ai-card">
                            <div class="ai-card-header">
                                <h2><span class="dashicons dashicons-list-view"></span> <?php _e('Generation Logs', 'ai-blogpost'); ?></h2>
                                <div class="ai-card-actions">
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                                        <button type="submit" name="clear_ai_logs" class="button ai-icon-button" title="<?php _e('Clear Logs', 'ai-blogpost'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                    <button type="button" class="button ai-icon-button ai-refresh-logs" title="<?php _e('Refresh Logs', 'ai-blogpost'); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="ai-card-content">
                                <div class="ai-logs-container">
                                    <div class="ai-log-section">
                                        <h3><span class="dashicons dashicons-text"></span> <?php _e('Text Generation', 'ai-blogpost'); ?></h3>
                                        <div class="ai-log-content" id="text-generation-logs">
                                            <?php display_api_logs('Text Generation'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="ai-log-section">
                                        <h3><span class="dashicons dashicons-format-image"></span> <?php _e('Image Generation', 'ai-blogpost'); ?></h3>
                                        <div class="ai-log-content" id="image-generation-logs">
                                            <?php display_api_logs('Image Generation'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ai-form-actions">
                        <div class="ai-save-indicator">
                            <span class="spinner"></span>
                            <span class="ai-save-message"></span>
                        </div>
                        <?php submit_button(__('Save Settings', 'ai-blogpost'), 'primary', 'submit', false, ['id' => 'ai-save-settings']); ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        /* AI Blogpost Dashboard Styling */
        :root {
            --ai-primary: #2271b1;
            --ai-primary-dark: #135e96;
            --ai-primary-light: #f0f7ff;
            --ai-secondary: #f0f0f1;
            --ai-border: #c3c4c7;
            --ai-text: #1d2327;
            --ai-text-light: #646970;
            --ai-success: #00a32a;
            --ai-error: #d63638;
            --ai-warning: #dba617;
            --ai-card-shadow: 0 1px 3px rgba(0,0,0,0.1);
            --ai-transition: all 0.3s ease;
        }
        
        /* Main Container */
        .ai-blogpost-dashboard {
            max-width: 1200px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
        }
        
        /* Card Styling */
        .ai-card {
            background: #fff;
            border: 1px solid var(--ai-border);
            border-radius: 4px;
            box-shadow: var(--ai-card-shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .ai-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--ai-border);
        }
        
        .ai-card-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .ai-card-header h2 .dashicons {
            margin-right: 8px;
            color: var(--ai-primary);
        }
        
        .ai-card-content {
            padding: 20px;
        }
        
        .ai-card-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Header Card */
        .ai-header-card {
            background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
            color: white;
            margin-bottom: 20px;
        }
        
        .ai-header-card .ai-card-content {
            padding: 20px;
        }
        
        .ai-header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .ai-header-title h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: 400;
        }
        
        .ai-header-description {
            margin: 0;
            opacity: 0.9;
        }
        
        .ai-next-post {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .ai-next-post .dashicons {
            margin-right: 8px;
        }
        
        /* Tabs Styling */
        .ai-tabs-container {
            margin-top: 20px;
        }
        
        .ai-tabs-nav {
            display: flex;
            margin: 0;
            padding: 0;
            list-style: none;
            border-bottom: 1px solid var(--ai-border);
            background: #f0f0f1;
        }
        
        .ai-tabs-nav li {
            margin: 0;
        }
        
        .ai-tabs-nav a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--ai-text);
            font-weight: 500;
            border-right: 1px solid var(--ai-border);
            transition: var(--ai-transition);
        }
        
        .ai-tabs-nav a:focus {
            box-shadow: none;
            outline: none;
        }
        
        .ai-tabs-nav a .dashicons {
            margin-right: 8px;
            color: var(--ai-primary);
        }
        
        .ai-tabs-nav .ui-tabs-active a {
            background: white;
            border-top: 3px solid var(--ai-primary);
            margin-top: -3px;
            color: var(--ai-primary);
        }
        
        .ai-tab-content {
            background: white;
            padding: 0;
        }
        
        /* Form Elements */
        .ai-blogpost-form .form-table {
            margin-top: 0;
        }
        
        .ai-blogpost-form .form-table th {
            width: 220px;
            vertical-align: top;
            padding: 15px 10px 15px 0;
        }
        
        .ai-blogpost-form .form-table td {
            padding: 15px 0;
            vertical-align: top;
        }
        
        .ai-blogpost-form input[type="text"],
        .ai-blogpost-form input[type="url"],
        .ai-blogpost-form input[type="password"],
        .ai-blogpost-form input[type="number"],
        .ai-blogpost-form select,
        .ai-blogpost-form textarea {
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--ai-border);
            border-radius: 4px;
            padding: 8px 12px;
            transition: var(--ai-transition);
        }
        
        .ai-blogpost-form input[type="text"]:focus,
        .ai-blogpost-form input[type="url"]:focus,
        .ai-blogpost-form input[type="password"]:focus,
        .ai-blogpost-form input[type="number"]:focus,
        .ai-blogpost-form select:focus,
        .ai-blogpost-form textarea:focus {
            border-color: var(--ai-primary);
            box-shadow: 0 0 0 1px var(--ai-primary);
            outline: none;
        }
        
        .ai-blogpost-form textarea.large-text {
            max-width: 100%;
        }
        
        .ai-form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid var(--ai-border);
            margin-top: 20px;
        }
        
        .ai-save-indicator {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .ai-save-indicator .spinner {
            margin-top: 0;
            margin-right: 8px;
        }
        
        .ai-save-message {
            font-size: 14px;
        }
        
        .ai-save-message.success {
            color: var(--ai-success);
        }
        
        .ai-save-message.error {
            color: var(--ai-error);
        }
        
        /* Test Button */
        .ai-test-button {
            display: flex !important;
            align-items: center;
            padding: 6px 14px !important;
        }
        
        .ai-test-button .dashicons {
            margin-right: 8px;
        }
        
        /* Icon Buttons */
        .ai-icon-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            width: 36px;
            height: 36px;
        }
        
        .ai-icon-button .dashicons {
            margin: 0;
        }
        
        /* Logs Styling */
        .ai-logs-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .ai-log-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .ai-log-section h3 .dashicons {
            margin-right: 8px;
            color: var(--ai-primary);
        }
        
        /* Image Generation Options */
        .generation-type-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .generation-option {
            flex: 1;
            padding: 15px;
            border: 1px solid var(--ai-border);
            border-radius: 4px;
            transition: var(--ai-transition);
            cursor: pointer;
        }
        
        .generation-option:hover {
            background: var(--ai-primary-light);
            border-color: var(--ai-primary);
        }
        
        .generation-option.active {
            background: var(--ai-primary-light);
            border-color: var(--ai-primary);
        }
        
        /* Help Tooltips */
        .ai-help-tip {
            display: inline-block;
            margin-left: 5px;
            color: var(--ai-text-light);
            cursor: help;
        }
        
        /* Template Guide */
        .template-guide {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            border-radius: 4px;
        }
        
        .template-guide h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Responsive Styles */
        @media screen and (max-width: 782px) {
            .ai-header-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .ai-tabs-nav {
                flex-wrap: wrap;
            }
            
            .ai-tabs-nav a {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .ai-blogpost-form .form-table th {
                width: 100%;
                display: block;
                padding-bottom: 0;
            }
            
            .ai-blogpost-form .form-table td {
                width: 100%;
                display: block;
                padding-top: 8px;
            }
            
            .generation-type-options {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        @media screen and (max-width: 600px) {
            .ai-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .ai-card-actions {
                width: 100%;
                justify-content: flex-end;
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
    echo '<div class="ai-field-wrapper">';
    echo '<select name="ai_blogpost_language" id="ai_blogpost_language" class="ai-select-field">';
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
    echo '<p class="description">Select the language for all generated content. This affects both text and image prompts.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Post Frequency
    echo '<tr>';
    echo '<th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-radio-group">';
    $frequency = get_cached_option('ai_blogpost_post_frequency', 'daily');
    
    echo '<label class="ai-radio-label' . ($frequency === 'daily' ? ' ai-radio-selected' : '') . '">';
    echo '<input type="radio" name="ai_blogpost_post_frequency" value="daily" ' . checked($frequency, 'daily', false) . '>';
    echo '<span class="ai-radio-text"><span class="dashicons dashicons-calendar-alt"></span> Daily</span>';
    echo '</label>';
    
    echo '<label class="ai-radio-label' . ($frequency === 'weekly' ? ' ai-radio-selected' : '') . '">';
    echo '<input type="radio" name="ai_blogpost_post_frequency" value="weekly" ' . checked($frequency, 'weekly', false) . '>';
    echo '<span class="ai-radio-text"><span class="dashicons dashicons-calendar"></span> Weekly</span>';
    echo '</label>';
    
    echo '</div>';
    echo '<p class="description">How often should new posts be automatically generated?</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Custom Categories
    echo '<tr>';
    echo '<th><label for="ai_blogpost_custom_categories">Categories</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_custom_categories', 'tarot'));
    echo '</textarea>';
    echo '<div class="ai-field-info">';
    echo '<p class="description">Enter categories (one per line) that will be used for post generation. The system will randomly select one category for each post.</p>';
    echo '<div class="ai-field-example">';
    echo '<strong>Example:</strong><br>';
    echo 'tarot<br>astrology<br>meditation<br>crystals';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Add JavaScript for radio buttons
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Handle radio button selection
        $('.ai-radio-label').click(function() {
            $(this).closest('.ai-radio-group').find('.ai-radio-label').removeClass('ai-radio-selected');
            $(this).addClass('ai-radio-selected');
        });
    });
    </script>
    <style>
    .ai-field-wrapper {
        position: relative;
    }
    
    .ai-select-field {
        min-width: 200px;
    }
    
    .ai-radio-group {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    .ai-radio-label {
        display: flex;
        align-items: center;
        padding: 8px 15px;
        border: 1px solid var(--ai-border);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .ai-radio-label:hover {
        background: var(--ai-primary-light);
        border-color: var(--ai-primary);
    }
    
    .ai-radio-selected {
        background: var(--ai-primary-light);
        border-color: var(--ai-primary);
    }
    
    .ai-radio-label input[type="radio"] {
        display: none;
    }
    
    .ai-radio-text {
        display: flex;
        align-items: center;
    }
    
    .ai-radio-text .dashicons {
        margin-right: 8px;
        color: var(--ai-primary);
    }
    
    .ai-field-info {
        margin-top: 10px;
    }
    
    .ai-field-example {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        font-size: 13px;
    }
    </style>
    <?php
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
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-api-key-field">';
    echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="' . esc_attr(get_cached_option('ai_blogpost_api_key')) . '">';
    echo '<button type="button" class="button ai-toggle-password" title="Show/Hide API Key"><span class="dashicons dashicons-visibility"></span></button>';
    echo '</div>';
    echo '<p class="description">Your OpenAI API key for text generation. <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key here</a>.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Model Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_model">GPT Model</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    display_model_dropdown('gpt');
    echo '<p class="description">Select the GPT model to use for text generation. More advanced models (like GPT-4) provide better quality but cost more.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Temperature with slider
    echo '<tr>';
    echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    $temperature = get_cached_option('ai_blogpost_temperature', '0.7');
    echo '<div class="ai-slider-container">';
    echo '<input type="range" class="ai-slider" min="0" max="2" step="0.1" value="' . esc_attr($temperature) . '">';
    echo '<input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" class="ai-slider-value" value="' . esc_attr($temperature) . '">';
    echo '</div>';
    echo '<div class="ai-slider-labels">';
    echo '<span>Predictable</span>';
    echo '<span>Balanced</span>';
    echo '<span>Creative</span>';
    echo '</div>';
    echo '<p class="description">Controls randomness in text generation. Lower values produce more predictable text, higher values more creative.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Max Tokens with slider
    echo '<tr>';
    echo '<th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    $max_tokens = get_cached_option('ai_blogpost_max_tokens', '2048');
    echo '<div class="ai-slider-container">';
    echo '<input type="range" class="ai-slider" min="500" max="4096" step="100" value="' . esc_attr($max_tokens) . '">';
    echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" class="ai-slider-value" value="' . esc_attr($max_tokens) . '" min="500" max="4096" step="100">';
    echo '</div>';
    echo '<div class="ai-slider-labels">';
    echo '<span>Shorter</span>';
    echo '<span>Medium</span>';
    echo '<span>Longer</span>';
    echo '</div>';
    echo '<p class="description">Maximum length of generated text. Higher values allow for longer blog posts.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // System Role
    echo '<tr>';
    echo '<th><label for="ai_blogpost_role">System Role</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.'));
    echo '</textarea>';
    echo '<p class="description">Define the AI\'s role and writing style. This sets the tone and approach for all generated content.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    // Content Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_prompt">Content Template</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="6" class="large-text code">';
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
    
    echo '<div class="template-guide">';
    echo '<div class="template-guide-header">';
    echo '<h4>Content Structure Guide</h4>';
    echo '<span class="dashicons dashicons-editor-help" title="Click for more information"></span>';
    echo '</div>';
    echo '<div class="template-guide-content">';
    echo '<p class="description">Use these section markers to structure the content:</p>';
    echo '<ul>';
    echo '<li><code>||Title||:</code> - The blog post title</li>';
    echo '<li><code>||Content||:</code> - The main content (wrapped in <code>&lt;article&gt;</code> tags)</li>';
    echo '<li><code>||Category||:</code> - The suggested category</li>';
    echo '</ul>';
    echo '<p class="description"><strong>Tips:</strong></p>';
    echo '<ul>';
    echo '<li>Use [topic] in your prompt to reference the selected category</li>';
    echo '<li>Add specific instructions about tone, style, or length in the System Role</li>';
    echo '<li>Use HTML tags for proper content structure</li>';
    echo '<li>Include SEO best practices in your instructions</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
    
    add_refresh_models_button();
    
    // LM Studio Section
    echo '<tr>';
    echo '<th colspan="2"><h3 class="ai-section-header"><span class="dashicons dashicons-desktop"></span> LM Studio Integration</h3></th>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<label class="ai-toggle-switch">';
    echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . 
         checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1, false) . '>';
    echo '<span class="ai-toggle-slider"></span>';
    echo '</label>';
    echo '<p class="description">Enable local LM Studio integration for text generation. This allows you to use your own local models instead of OpenAI\'s API.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="lm-studio-setting" style="' . (get_cached_option('ai_blogpost_lm_enabled', 0) ? '' : 'display: none;') . '">';
    echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-input-with-button">';
    echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')) . '">';
    echo '<button type="button" class="button test-lm-connection">Test Connection</button>';
    echo '</div>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">The URL to your local LM Studio API. Usually http://localhost:1234/v1</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    
    // Add JavaScript for sliders and toggle
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Handle sliders
        $('.ai-slider').on('input', function() {
            $(this).next('.ai-slider-value').val($(this).val());
        });
        
        $('.ai-slider-value').on('input', function() {
            $(this).prev('.ai-slider').val($(this).val());
        });
        
        // Toggle password visibility
        $('.ai-toggle-password').click(function() {
            var $input = $(this).prev('input');
            var $icon = $(this).find('.dashicons');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Toggle LM Studio settings
        $('#ai_blogpost_lm_enabled').change(function() {
            if ($(this).is(':checked')) {
                $('.lm-studio-setting').slideDown(200);
            } else {
                $('.lm-studio-setting').slideUp(200);
            }
        });
        
        // Template guide toggle
        $('.template-guide-header').click(function() {
            $(this).next('.template-guide-content').slideToggle(200);
            $(this).find('.dashicons').toggleClass('dashicons-editor-help dashicons-no-alt');
        });
    });
    </script>
    <style>
    /* API Key Field */
    .ai-api-key-field {
        display: flex;
        max-width: 400px;
    }
    
    .ai-api-key-field input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .ai-api-key-field button {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: none;
    }
    
    /* Slider Styling */
    .ai-slider-container {
        display: flex;
        align-items: center;
        gap: 15px;
        max-width: 400px;
    }
    
    .ai-slider {
        flex: 1;
        margin: 0;
    }
    
    .ai-slider-value {
        width: 70px !important;
    }
    
    .ai-slider-labels {
        display: flex;
        justify-content: space-between;
        max-width: 400px;
        margin-top: 5px;
        font-size: 12px;
        color: var(--ai-text-light);
    }
    
    /* Toggle Switch */
    .ai-toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .ai-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .ai-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .ai-toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .ai-toggle-slider {
        background-color: var(--ai-primary);
    }
    
    input:focus + .ai-toggle-slider {
        box-shadow: 0 0 1px var(--ai-primary);
    }
    
    input:checked + .ai-toggle-slider:before {
        transform: translateX(26px);
    }
    
    /* Section Header */
    .ai-section-header {
        display: flex;
        align-items: center;
        margin: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--ai-border);
        color: var(--ai-primary);
    }
    
    .ai-section-header .dashicons {
        margin-right: 8px;
    }
    
    /* Input with Button */
    .ai-input-with-button {
        display: flex;
        max-width: 400px;
    }
    
    .ai-input-with-button input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .ai-input-with-button button {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    
    /* Template Guide */
    .template-guide {
        margin-top: 15px;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .template-guide-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #e2e4e7;
        cursor: pointer;
    }
    
    .template-guide-header h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
    }
    
    .template-guide-content {
        padding: 15px;
        display: none;
    }
    
    .template-guide-content ul {
        margin: 10px 0 15px 20px;
        list-style-type: disc;
    }
    </style>
    <?php
}

/**
 * Display image generation settings section
 */
function display_image_settings() {
    // Get current image generation type
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'dalle');
    
    // Image Generation Type Selection Cards
    echo '<div class="ai-image-generation-selector">';
    echo '<h3 class="ai-section-title"><span class="dashicons dashicons-format-image"></span> Select Image Generation Method</h3>';
    echo '<div class="ai-generation-cards">';
    
    // DALL-E Card
    echo '<div class="ai-generation-card' . ($generation_type === 'dalle' ? ' ai-card-selected' : '') . '" data-type="dalle">';
    echo '<div class="ai-card-header">';
    echo '<div class="ai-card-radio">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" id="ai_type_dalle" value="dalle" ' . 
         checked($generation_type, 'dalle', false) . '>';
    echo '<span class="ai-radio-indicator"></span>';
    echo '</div>';
    echo '<label for="ai_type_dalle">DALL-E</label>';
    echo '</div>';
    echo '<div class="ai-card-body">';
    echo '<div class="ai-card-icon"><span class="dashicons dashicons-cloud"></span></div>';
    echo '<div class="ai-card-description">';
    echo '<p>Use OpenAI\'s DALL-E for AI image generation</p>';
    echo '<ul>';
    echo '<li>High-quality images</li>';
    echo '<li>Cloud-based solution</li>';
    echo '<li>Requires API key</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // ComfyUI Card
    echo '<div class="ai-generation-card' . ($generation_type === 'comfyui' ? ' ai-card-selected' : '') . '" data-type="comfyui">';
    echo '<div class="ai-card-header">';
    echo '<div class="ai-card-radio">';
    echo '<input type="radio" name="ai_blogpost_image_generation_type" id="ai_type_comfyui" value="comfyui" ' . 
         checked($generation_type, 'comfyui', false) . '>';
    echo '<span class="ai-radio-indicator"></span>';
    echo '</div>';
    echo '<label for="ai_type_comfyui">ComfyUI</label>';
    echo '</div>';
    echo '<div class="ai-card-body">';
    echo '<div class="ai-card-icon"><span class="dashicons dashicons-desktop"></span></div>';
    echo '<div class="ai-card-description">';
    echo '<p>Use local ComfyUI for advanced image generation</p>';
    echo '<ul>';
    echo '<li>Customizable workflows</li>';
    echo '<li>Local processing</li>';
    echo '<li>No API costs</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End ai-generation-cards
    echo '</div>'; // End ai-image-generation-selector
    
    // DALL-E Settings Panel
    echo '<div class="ai-settings-panel dalle-settings" id="dalle-settings-panel" style="display: ' . ($generation_type === 'dalle' ? 'block' : 'none') . ';">';
    echo '<div class="ai-panel-header">';
    echo '<h3><span class="dashicons dashicons-cloud"></span> DALL-E Settings</h3>';
    echo '</div>';
    echo '<div class="ai-panel-content">';
    echo '<table class="form-table">';
    
    // API Key
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_api_key">API Key</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-api-key-field">';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="' . 
         esc_attr(get_cached_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<button type="button" class="button ai-toggle-password" title="Show/Hide API Key"><span class="dashicons dashicons-visibility"></span></button>';
    echo '</div>';
    echo '<p class="description">Your OpenAI API key for DALL-E image generation. <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key here</a>.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Model Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_model">Model</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    display_model_dropdown('dalle');
    echo '<p class="description">Select the DALL-E model to use. DALL-E 3 provides higher quality images but costs more.</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Prompt Template
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code">';
    echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 
        'A professional blog header image for [category], modern style, clean design, subtle symbolism'));
    echo '</textarea>';
    echo '<div class="ai-field-info">';
    echo '<p class="description">Use <code>[category]</code> as placeholder for the blog category. Be specific about style, colors, and composition.</p>';
    echo '<div class="ai-field-example">';
    echo '<strong>Example:</strong><br>';
    echo 'A professional blog header image for [category], with a minimalist design, soft blue and white color palette, subtle symbolism, and clean typography. Top-down view, high-quality, photorealistic.';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>'; // End panel content
    echo '</div>'; // End DALL-E settings panel

    // ComfyUI Settings Panel
    echo '<div class="ai-settings-panel comfyui-settings" id="comfyui-settings-panel" style="display: ' . ($generation_type === 'comfyui' ? 'block' : 'none') . ';">';
    echo '<div class="ai-panel-header">';
    echo '<h3><span class="dashicons dashicons-desktop"></span> ComfyUI Settings</h3>';
    echo '</div>';
    echo '<div class="ai-panel-content">';
    echo '<table class="form-table">';
    
    // Server URL
    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<div class="ai-input-with-button">';
    $comfyui_url = get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188');
    echo '<input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="' . 
         esc_attr($comfyui_url) . '">';
    echo '<button type="button" class="button test-comfyui-connection">Test Connection</button>';
    echo '</div>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">The URL to your local ComfyUI server. Usually <code>http://localhost:8188</code></p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    // Workflow Selection
    $workflows_json = file_get_contents(plugin_dir_path(__FILE__) . '../workflows/config.json');
    $workflows_data = json_decode($workflows_json, true);
    $workflows = isset($workflows_data['workflows']) ? $workflows_data['workflows'] : [];
    $default_workflow = isset($workflows_data['default_workflow']) ? $workflows_data['default_workflow'] : '';

    update_option('ai_blogpost_comfyui_workflows', json_encode($workflows));
    update_option('ai_blogpost_comfyui_default_workflow', $default_workflow);

    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_default_workflow">Workflow</label></th>';
    echo '<td>';
    echo '<div class="ai-field-wrapper">';
    echo '<select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow" class="ai-select-field">';
    foreach ($workflows as $workflow) {
        echo '<option value="' . esc_attr($workflow['name']) . '" ' . 
             selected($default_workflow, $workflow['name'], false) . '>' . 
             esc_html($workflow['name'] . ' - ' . $workflow['description']) . '</option>';
    }
    echo '</select>';
    
    echo '<div class="ai-workflow-preview">';
    echo '<div class="ai-workflow-header">';
    echo '<h4>Workflow Details</h4>';
    echo '<span class="dashicons dashicons-info" title="Click for more information"></span>';
    echo '</div>';
    echo '<div class="ai-workflow-content">';
    echo '<ul>';
    echo '<li><strong>Input:</strong> Category-based prompt with customizable style</li>';
    echo '<li><strong>Processing:</strong> Advanced image generation pipeline</li>';
    echo '<li><strong>Output:</strong> High-quality 512x512 featured image</li>';
    echo '</ul>';
    echo '<p class="description">ComfyUI workflows are defined in the <code>workflows/config.json</code> file. You can add custom workflows by editing this file.</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>'; // End panel content
    echo '</div>'; // End ComfyUI settings panel
    
    // Add JavaScript for image generation settings
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Handle generation type selection
        $('.ai-generation-card').click(function() {
            var type = $(this).data('type');
            
            // Update radio button
            $(this).find('input[type="radio"]').prop('checked', true);
            
            // Update card styling
            $('.ai-generation-card').removeClass('ai-card-selected');
            $(this).addClass('ai-card-selected');
            
            // Show/hide appropriate settings panel
            if (type === 'dalle') {
                $('#comfyui-settings-panel').fadeOut(200, function() {
                    $('#dalle-settings-panel').fadeIn(200);
                });
            } else if (type === 'comfyui') {
                $('#dalle-settings-panel').fadeOut(200, function() {
                    $('#comfyui-settings-panel').fadeIn(200);
                });
            }
        });
        
        // Toggle password visibility
        $('.ai-toggle-password').click(function() {
            var $input = $(this).prev('input');
            var $icon = $(this).find('.dashicons');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Workflow details toggle
        $('.ai-workflow-header').click(function() {
            $(this).next('.ai-workflow-content').slideToggle(200);
            $(this).find('.dashicons').toggleClass('dashicons-info dashicons-no-alt');
        });
        
        // ComfyUI connection test
        $('.test-comfyui-connection').click(function() {
            var $button = $(this);
            var $spinner = $button.closest('td').find('.spinner');
            var apiUrl = $('#ai_blogpost_comfyui_api_url').val();
            
            if (!apiUrl) {
                showNotification('Please enter a server URL first', 'error');
                return;
            }
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.post(ajaxurl, {
                action: 'test_comfyui_connection',
                url: apiUrl,
                nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    showNotification('✅ ComfyUI connected successfully!', 'success');
                } else {
                    showNotification('❌ ' + (response.data || 'Connection failed'), 'error');
                }
            }).fail(function() {
                showNotification('❌ Connection failed. Please check the server URL.', 'error');
            }).always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });
        
        // Helper function to show notifications
        function showNotification(message, type) {
            var bgColor = type === 'success' ? '#e7f5ea' : '#fde8e8';
            
            var $notification = $('<div>')
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
                .appendTo('body')
                .fadeIn()
                .delay(3000)
                .fadeOut(function() { $(this).remove(); });
        }
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

/**
 * Handle AJAX request to refresh logs
 */
function handle_refresh_logs() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    ob_start();
    display_api_logs('Text Generation');
    $text_logs = ob_get_clean();
    
    ob_start();
    display_api_logs('Image Generation');
    $image_logs = ob_get_clean();
    
    wp_send_json_success([
        'text_logs' => $text_logs,
        'image_logs' => $image_logs
    ]);
}
add_action('wp_ajax_refresh_ai_logs', 'handle_refresh_logs');

/**
 * Handle AJAX form submission
 */
function handle_save_settings() {
    check_ajax_referer('ai_blogpost_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    // Parse form data
    parse_str($_POST['data'], $form_data);
    
    // Check if frequency setting is being changed
    $old_frequency = get_option('ai_blogpost_post_frequency', 'daily');
    $new_frequency = isset($form_data['ai_blogpost_post_frequency']) ? 
                    sanitize_text_field($form_data['ai_blogpost_post_frequency']) : 
                    $old_frequency;
    
    $reload_needed = ($old_frequency !== $new_frequency);
    
    // Save each setting
    foreach ($form_data as $key => $value) {
        if (strpos($key, 'ai_blogpost_') === 0) {
            // Sanitize based on field type
            if (is_array($value)) {
                $sanitized_value = array_map('sanitize_text_field', $value);
            } elseif ($key === 'ai_blogpost_role' || $key === 'ai_blogpost_prompt' || $key === 'ai_blogpost_custom_categories') {
                $sanitized_value = wp_kses_post($value);
            } else {
                $sanitized_value = sanitize_text_field($value);
            }
            
            update_option($key, $sanitized_value);
        }
    }
    
    // Clear cache
    clear_ai_blogpost_cache();
    
    // If frequency changed, update cron schedule
    if ($reload_needed) {
        ai_blogpost_schedule_cron();
    }
    
    wp_send_json_success([
        'reload' => $reload_needed
    ]);
}
add_action('wp_ajax_save_ai_blogpost_settings', 'handle_save_settings');
