<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Main settings handler class
 */
class Settings {
    private const OPTION_GROUP = 'ai_blogpost_settings';
    private const MENU_SLUG = 'ai_blogpost';
    
    /**
     * Initialize settings functionality
     */
    public static function initialize(): void {
        self::registerSettings();
        add_action('admin_menu', [self::class, 'initializeMenu']);
        add_action('admin_init', [ModelManager::class, 'initialize']);
        add_action('admin_init', [ConnectionTester::class, 'initialize']);
    }
    
    /**
     * Register all plugin settings
     */
    private static function registerSettings(): void {
        $settings = [
            // General settings
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
            'ai_blogpost_language',
            
            // Image Generation settings
            'ai_blogpost_image_generation_type',
            'ai_blogpost_dalle_api_key',
            'ai_blogpost_dalle_size',
            'ai_blogpost_dalle_style',
            'ai_blogpost_dalle_quality',
            'ai_blogpost_dalle_model',
            'ai_blogpost_dalle_prompt_template',
            
            // DALL·E settings
            'ai_blogpost_dalle_enabled',
            'ai_blogpost_dalle_api_key',
            'ai_blogpost_dalle_size',
            'ai_blogpost_dalle_style',
            'ai_blogpost_dalle_quality',
            'ai_blogpost_dalle_model',
            'ai_blogpost_dalle_prompt_template',
            
            // ComfyUI settings
            'ai_blogpost_comfyui_api_url',
            'ai_blogpost_comfyui_workflows',
            'ai_blogpost_comfyui_default_workflow',
            
            // LM Studio settings
            'ai_blogpost_lm_enabled',
            'ai_blogpost_lm_api_url',
            'ai_blogpost_lm_api_key',
            'ai_blogpost_lm_model'
        ];
        
        foreach ($settings as $setting) {
            register_setting(self::OPTION_GROUP, $setting);
        }
    }
    
    /**
     * Add menu page to WordPress admin
     */
    public static function initializeMenu(): void {
        add_menu_page(
            'AI Blogpost Settings',
            'AI Blogpost',
            'manage_options',
            self::MENU_SLUG,
            [SettingsRenderer::class, 'renderAdminPage']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueueAssets(): void {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_' . self::MENU_SLUG) {
            wp_enqueue_style(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
                [],
                AI_BLOGPOST_VERSION
            );
            wp_enqueue_script(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
                ['jquery'],
                AI_BLOGPOST_VERSION,
                true
            );
            wp_localize_script('ai-blogpost-admin', 'aiBlogpostAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_blogpost_nonce')
            ]);
        }
    }
}

/**
 * Settings page renderer
 */
class SettingsRenderer {
    /**
     * Render the admin settings page
     */
    public static function renderAdminPage(): void {
        echo '<div class="wrap ai-blogpost-dashboard">';
        echo '<h1>AI Blogpost Dashboard</h1>';
        
        self::renderDashboardContent();
        
        echo '</div>';
        
        self::outputStyles();
    }
    
    /**
     * Render dashboard content
     */
    private static function renderDashboardContent(): void {
        echo '<div class="dashboard-content">';
        
        self::renderTestSection();
        self::renderSettingsForm();
        self::renderStatusPanel();
        
        echo '</div>';
    }
    
    /**
     * Render test post section
     */
    private static function renderTestSection(): void {
        echo '<div class="test-post-section">';
        echo '<div class="test-post-header">';
        echo '<h2>Test Generation</h2>';
        echo '<form method="post" style="display:inline-block;">';
        echo '<input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">';
        echo '</form>';
        echo '</div>';
        
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
        echo '</div>';
    }
    
    /**
     * Render settings form
     */
    private static function renderSettingsForm(): void {
        echo '<form method="post" action="options.php">';
        settings_fields('ai_blogpost_settings');
        do_settings_sections('ai_blogpost_settings');
        
        self::renderGeneralSettings();
        self::renderTextSettings();
        self::renderImageSettings();
        
        submit_button('Save Settings');
        echo '</form>';
    }
    
    /**
     * Render general settings section
     */
    private static function renderGeneralSettings(): void {
        echo '<div class="settings-section">';
        echo '<h2>Schedule Settings</h2>';
        
        echo '<table class="form-table">';
        
        // Language Selection
        self::renderLanguageField();
        
        // Post Frequency
        self::renderFrequencyField();
        
        // Custom Categories
        self::renderCategoriesField();
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render text generation settings section
     */
    private static function renderTextSettings(): void {
        echo '<div class="settings-section">';
        echo '<h2>OpenAI Text Generation</h2>';
        
        echo '<table class="form-table">';
        
        // OpenAI API Key
        self::renderApiKeyField();
        
        // Model Selection
        self::renderModelField();
        
        // Temperature
        self::renderTemperatureField();
        
        // Max Tokens
        self::renderMaxTokensField();
        
        // System Role
        self::renderSystemRoleField();
        
        // Content Template
        self::renderContentTemplateField();
        
        // Refresh Models Button
        ModelManager::renderRefreshButton();
        
        // LM Studio Section
        self::renderLmStudioSection();
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render image generation settings section
     */
    private static function renderImageSettings(): void {
        echo '<div class="settings-section">';
        echo '<h2>Featured Image Generation</h2>';
        
        self::renderImageTypeSelector();
        self::renderDalleSettings();
        self::renderComfyUiSettings();
        
        echo '</div>';
    }
    
    /**
     * Render status panel
     */
    private static function renderStatusPanel(): void {
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
        Logs::displayApiLogs('Text Generation');
        
        echo '<h3 style="margin-top: 20px;">Image Generation</h3>';
        Logs::displayApiLogs('Image Generation');
        echo '</div>';
    }
    
    /**
     * Output page styles
     */
    private static function outputStyles(): void {
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
    
    // Individual field rendering methods...
    private static function renderLanguageField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_language">Content Language</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_language" id="ai_blogpost_language">';
        $language = Helpers::getCachedOption('ai_blogpost_language', 'en');
        $languages = [
            'en' => 'English',
            'nl' => 'Nederlands',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español'
        ];
        foreach ($languages as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($language, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select the language for generated content</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    // ... Additional field rendering methods
}

/**
 * Model management functionality
 */
class ModelManager {
    /**
     * Initialize model management
     */
    public static function initialize(): void {
        add_action('wp_ajax_refresh_openai_models', [self::class, 'handleRefresh']);
    }
    
    /**
     * Handle AJAX request to refresh OpenAI models
     */
    public static function handleRefresh(): void {
        check_ajax_referer('refresh_models_nonce', 'nonce');
        $success = AiApi::fetchModels();
        wp_send_json_success(['success' => $success]);
    }
    
    /**
     * Render refresh models button
     */
    public static function renderRefreshButton(): void {
        echo '<tr>';
        echo '<th>Available Models</th>';
        echo '<td>';
        echo '<button type="button" class="button" id="refresh-models">Refresh Available Models</button>';
        echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
        echo '<p class="description">Click to fetch available models from OpenAI</p>';
        echo '</td>';
        echo '</tr>';
        
        self::outputScript();
    }
    
    /**
     * Output refresh button JavaScript
     */
    private static function outputScript(): void {
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
}

/**
 * API connection testing functionality
 */
class ConnectionTester {
    /**
     * Initialize connection testing
     */
    public static function initialize(): void {
        add_action('wp_ajax_test_lm_studio', [self::class, 'handleLmStudioTest']);
        add_action('wp_ajax_test_comfyui_connection', [self::class, 'handleComfyUiTest']);
    }
    
    /**
     * Handle LM Studio connection test
     */
    public static function handleLmStudioTest(): void {
        check_ajax_referer('ai_blogpost_nonce', 'nonce');
        
        $api_url = sanitize_text_field($_POST['url']);
        $api_url = rtrim($api_url, '/') . '/v1';
        
        try {
            Logs::debug('Testing LM Studio connection:', [
                'url' => $api_url
            ]);
            
            $response = wp_remote_get($api_url . '/models', [
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 30,
                'sslverify' => false
            ]);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            $models = [];
            if (!empty($data['data'])) {
                $models = $data['data'];
            } elseif (is_array($data)) {
                $models = array_map(function($model) {
                    return is_array($model) ? $model : ['id' => $model];
                }, $data);
            }
            
            if (empty($models)) {
                throw new \Exception('No models found');
            }
            
            update_option('ai_blogpost_available_lm_models', $models);
            update_option('ai_blogpost_lm_api_url', rtrim($api_url, '/v1'));
            
            Logs::logApiCall('LM Studio Test', true, [
                'url' => $api_url,
                'status' => 'Connection successful',
                'models_found' => count($models)
            ]);
            
            wp_send_json_success([
                'message' => 'Connection successful',
                'models' => $models
            ]);
            
        } catch (\Exception $e) {
            Logs::debug('LM Studio error:', $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle ComfyUI connection test
     */
    public static function handleComfyUiTest(): void {
        check_ajax_referer('ai_blogpost_nonce', 'nonce');
        
        $api_url = sanitize_text_field($_POST['url']);
        $api_url = rtrim($api_url, '/');
        
        try {
            Logs::debug('Testing ComfyUI connection:', [
                'url' => $api_url
            ]);
            
            $response = wp_remote_get($api_url . '/queue', [
                'timeout' => 30,
                'sslverify' => false
            ]);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $queue_data = json_decode(wp_remote_retrieve_body($response), true);
            
            $history_response = wp_remote_get($api_url . '/history', [
                'timeout' => 30,
                'sslverify' => false
            ]);
            
            if (is_wp_error($history_response)) {
                throw new \Exception('Failed to access history endpoint');
            }
            
            $history_data = json_decode(wp_remote_retrieve_body($history_response), true);
            
            update_option('ai_blogpost_comfyui_api_url', $api_url);
            
            Logs::logApiCall('ComfyUI Test', true, [
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
            
        } catch (\Exception $e) {
            Logs::debug('ComfyUI error:', $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

// Initialize settings functionality
Settings::initialize();
