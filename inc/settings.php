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
        add_action('admin_init', [self::class, 'loadWorkflows']);
        add_action('wp_ajax_save_ai_blogpost_settings', [self::class, 'handleAjaxSave']);
    }
    
    /**
     * Load workflows from JSON files
     */
    public static function loadWorkflows(): void {
        $workflows_dir = plugin_dir_path(dirname(__FILE__)) . 'workflows';
        $workflow_files = glob($workflows_dir . '/*.json');
        
        if (empty($workflow_files)) {
            return;
        }
        
        $workflows = [];
        foreach ($workflow_files as $file) {
            $filename = basename($file);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $workflow_data = json_decode($content, true);
            if (!$workflow_data) {
                continue;
            }
            
            $workflows[] = [
                'name' => $name,
                'file' => $filename,
                'workflow' => $workflow_data
            ];
        }
        
        if (!empty($workflows)) {
            update_option('ai_blogpost_comfyui_workflows', json_encode($workflows));
            
            // Set default workflow if not already set
            $default_workflow = get_option('ai_blogpost_comfyui_default_workflow', '');
            if (empty($default_workflow) && !empty($workflows[0]['name'])) {
                update_option('ai_blogpost_comfyui_default_workflow', $workflows[0]['name']);
            }
        }
    }
    
    /**
     * Handle AJAX save request
     */
    public static function handleAjaxSave(): void {
        check_ajax_referer('ai_blogpost_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';
        
        // Remove action and nonce from the data to be saved
        $data = $_POST;
        unset($data['action'], $data['nonce'], $data['tab']);
        
        // Save each setting
        $saved = false;
        foreach ($data as $key => $value) {
            if (strpos($key, 'ai_blogpost_') === 0) {
                if (is_array($value)) {
                    $value = array_map('sanitize_text_field', $value);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_option($key, $value);
                $saved = true;
            }
        }
        
        if ($saved) {
            wp_send_json_success(['message' => 'Settings saved successfully']);
        } else {
            wp_send_json_error('No settings were saved');
        }
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
        if ($screen && $screen->id === 'toplevel_page_ai_blogpost') {
            // Definieer paden naar assets
            $css_path = AI_BLOGPOST_PLUGIN_DIR . 'assets/css/admin.css';
            $js_path = AI_BLOGPOST_PLUGIN_DIR . 'assets/js/admin.js';
    
            // Laad CSS als het bestand bestaat
            if (file_exists($css_path)) {
                wp_enqueue_style(
                    'ai-blogpost-admin',
                    AI_BLOGPOST_PLUGIN_URL . 'assets/css/admin.css',
                    ['dashicons'],
                    AI_BLOGPOST_VERSION
                );
            } else {
                error_log('AI Blogpost: CSS-bestand niet gevonden op ' . $css_path);
            }
    
            // Laad JS als het bestand bestaat
            if (file_exists($js_path)) {
                wp_enqueue_script(
                    'ai-blogpost-admin',
                    AI_BLOGPOST_PLUGIN_URL . 'assets/js/admin.js',
                    ['jquery'],
                    AI_BLOGPOST_VERSION,
                    true
                );
            } else {
                error_log('AI Blogpost: JS-bestand niet gevonden op ' . $js_path);
            }
    
            // Geef gegevens door aan JavaScript
            wp_localize_script('ai-blogpost-admin', 'aiBlogpostAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_blogpost_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'version' => AI_BLOGPOST_VERSION
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
        
        // Render tabs navigation
        self::renderTabsNavigation();
        
        // Start dashboard content
        echo '<div class="dashboard-content">';
        
        // Render tab content
        self::renderDashboardTab();
        self::renderContentTab();
        self::renderTextGenerationTab();
        self::renderImageGenerationTab();
        self::renderLogsTab();
        
        echo '</div>'; // End dashboard-content
        
        echo '</div>'; // End wrap
    }

    /**
     * Render tabs navigation
     */
    private static function renderTabsNavigation(): void {
        echo '<ul class="ai-blogpost-tabs">';
        echo '<li><a href="#tab-dashboard" class="active"><span class="dashicons dashicons-dashboard"></span> Dashboard</a></li>';
        echo '<li><a href="#tab-content"><span class="dashicons dashicons-admin-post"></span> Content Settings</a></li>';
        echo '<li><a href="#tab-text-generation"><span class="dashicons dashicons-editor-paste-text"></span> Text Generation</a></li>';
        echo '<li><a href="#tab-image-generation"><span class="dashicons dashicons-format-image"></span> Image Generation</a></li>';
        echo '<li><a href="#tab-logs"><span class="dashicons dashicons-list-view"></span> Logs & Status</a></li>';
        echo '</ul>';
    }

    /**
     * Render dashboard tab content
     */
    private static function renderDashboardTab(): void {
        echo '<div id="tab-dashboard" class="ai-blogpost-tab-content active">';
        
        // Dashboard widgets
        echo '<div class="dashboard-widgets">';
        
        // Quick actions widget
        echo '<div class="dashboard-widget">';
        echo '<div class="dashboard-widget-header">';
        echo '<h3 class="dashboard-widget-title"><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h3>';
        echo '</div>';
        echo '<div class="dashboard-widget-content">';
        echo '<form method="post" style="margin-bottom: 10px;">';
        echo '<input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">';
        echo '</form>';
        
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
        echo '</div>'; // End widget content
        echo '</div>'; // End widget
        
        // Stats widget
        echo '<div class="dashboard-widget">';
        echo '<div class="dashboard-widget-header">';
        echo '<h3 class="dashboard-widget-title"><span class="dashicons dashicons-chart-bar"></span> Statistics</h3>';
        echo '</div>';
        echo '<div class="dashboard-widget-content">';
        
        // Get post count
        $post_count = wp_count_posts();
        $published_posts = $post_count->publish ?? 0;
        
        echo '<p><strong>Published Posts:</strong> ' . $published_posts . '</p>';
        
        // Get logs count
        $logs = get_option('ai_blogpost_api_logs', []);
        $text_logs = array_filter($logs, function($log) {
            return isset($log['type']) && $log['type'] === 'Text Generation';
        });
        $image_logs = array_filter($logs, function($log) {
            return isset($log['type']) && $log['type'] === 'Image Generation';
        });
        
        echo '<p><strong>Text Generation Logs:</strong> ' . count($text_logs) . '</p>';
        echo '<p><strong>Image Generation Logs:</strong> ' . count($image_logs) . '</p>';
        
        echo '</div>'; // End widget content
        echo '</div>'; // End widget
        
        // Settings status widget
        echo '<div class="dashboard-widget">';
        echo '<div class="dashboard-widget-header">';
        echo '<h3 class="dashboard-widget-title"><span class="dashicons dashicons-admin-settings"></span> Settings Status</h3>';
        echo '</div>';
        echo '<div class="dashboard-widget-content">';
        
        // Check API key
        $api_key = Helpers::getCachedOption('ai_blogpost_api_key', '');
        $api_key_status = !empty($api_key) ? 
            '<span style="color: #46b450;">✓ Configured</span>' : 
            '<span style="color: #dc3232;">✗ Not configured</span>';
        echo '<p><strong>OpenAI API Key:</strong> ' . $api_key_status . '</p>';
        
        // Check language
        $language = Helpers::getCachedOption('ai_blogpost_language', 'en');
        $languages = [
            'en' => 'English',
            'nl' => 'Nederlands',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español'
        ];
        echo '<p><strong>Content Language:</strong> ' . ($languages[$language] ?? 'Unknown') . '</p>';
        
        // Check frequency
        $frequency = Helpers::getCachedOption('ai_blogpost_post_frequency', 'daily');
        echo '<p><strong>Post Frequency:</strong> ' . ucfirst($frequency) . '</p>';
        
        // Check image generation
        $image_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        $image_type_label = [
            'none' => 'Disabled',
            'dalle' => 'DALL·E',
            'comfyui' => 'ComfyUI',
            'localai' => 'LocalAI'
        ];
        echo '<p><strong>Image Generation:</strong> ' . ($image_type_label[$image_type] ?? 'Unknown') . '</p>';
        
        echo '</div>'; // End widget content
        echo '</div>'; // End widget
        
        echo '</div>'; // End dashboard-widgets
        
        echo '</div>'; // End tab-dashboard
    }

    /**
     * Render content settings tab
     */
    private static function renderContentTab(): void {
        echo '<div id="tab-content" class="ai-blogpost-tab-content">';
        
        echo '<form method="post" action="options.php">';
        settings_fields('ai_blogpost_settings');
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-translation"></span> Language Settings</h2>';
        echo '<table class="form-table">';
        self::renderLanguageField();
        echo '</table>';
        echo '</div>';
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-calendar-alt"></span> Schedule Settings</h2>';
        echo '<table class="form-table">';
        self::renderFrequencyField();
        echo '</table>';
        echo '</div>';
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-category"></span> Category Settings</h2>';
        echo '<table class="form-table">';
        self::renderCategoriesField();
        echo '</table>';
        echo '</div>';
        
        submit_button('Save Content Settings');
        echo '</form>';
        
        echo '</div>'; // End tab-content
    }

    /**
     * Render text generation tab
     */
    private static function renderTextGenerationTab(): void {
        echo '<div id="tab-text-generation" class="ai-blogpost-tab-content">';
        
        echo '<form method="post" action="options.php">';
        settings_fields('ai_blogpost_settings');
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-admin-network"></span> OpenAI API Settings</h2>';
        echo '<table class="form-table">';
        self::renderApiKeyField();
        self::renderModelField();
        echo '</table>';
        echo '</div>';
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-admin-generic"></span> Generation Parameters</h2>';
        echo '<table class="form-table">';
        self::renderTemperatureField();
        self::renderMaxTokensField();
        self::renderSystemRoleField();
        self::renderContentTemplateField();
        echo '</table>';
        echo '</div>';
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-update"></span> Model Management</h2>';
        echo '<table class="form-table">';
        ModelManager::renderRefreshButton();
        echo '</table>';
        echo '</div>';
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-desktop"></span> LM Studio Integration</h2>';
        echo '<p class="description">Use a local LM Studio instance instead of OpenAI API</p>';
        echo '<table class="form-table">';
        self::renderLmStudioSection();
        echo '</table>';
        echo '</div>';
        
        submit_button('Save Text Generation Settings');
        echo '</form>';
        
        echo '</div>'; // End tab-text-generation
    }

    /**
     * Render image generation tab
     */
    private static function renderImageGenerationTab(): void {
        echo '<div id="tab-image-generation" class="ai-blogpost-tab-content">';
        
        echo '<form method="post" action="options.php">';
        settings_fields('ai_blogpost_settings');
        
        echo '<div class="settings-section">';
        echo '<h2><span class="dashicons dashicons-format-image"></span> Featured Image Generation</h2>';
        
        self::renderImageTypeSelector();
        self::renderDalleSettings();
        self::renderComfyUiSettings();
        
        echo '</div>';
        
        submit_button('Save Image Generation Settings');
        echo '</form>';
        
        echo '</div>'; // End tab-image-generation
    }

    /**
     * Render logs tab
     */
    private static function renderLogsTab(): void {
        echo '<div id="tab-logs" class="ai-blogpost-tab-content">';
        
        echo '<div class="status-panel">';
        echo '<div class="status-header">';
        echo '<h2><span class="dashicons dashicons-media-text"></span> Text Generation Logs</h2>';
        echo '<div class="status-actions">';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('clear_ai_logs_nonce', '_wpnonce');
        echo '<input type="submit" name="clear_ai_logs" class="button" value="Clear Logs">';
        echo '</form>';
        echo '<button type="button" class="button" onclick="window.location.reload();">Refresh Logs</button>';
        echo '</div>';
        echo '</div>';
        
        Logs::displayApiLogs('Text Generation');
        echo '</div>';
        
        echo '<div class="status-panel">';
        echo '<div class="status-header">';
        echo '<h2><span class="dashicons dashicons-format-image"></span> Image Generation Logs</h2>';
        echo '</div>';
        
        Logs::displayApiLogs('Image Generation');
        echo '</div>';
        
        echo '</div>'; // End tab-logs
    }
}

// Include the SettingsRenderer class
require_once(plugin_dir_path(__FILE__) . 'settings-renderer.php');

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
        add_action('wp_ajax_test_openai_connection', [self::class, 'handleOpenAiTest']);
        add_action('wp_ajax_test_lm_studio', [self::class, 'handleLmStudioTest']);
        add_action('wp_ajax_test_comfyui_connection', [self::class, 'handleComfyUiTest']);
    }
    
    /**
     * Handle OpenAI connection test
     */
    public static function handleOpenAiTest(): void {
        check_ajax_referer('ai_blogpost_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        try {
            Logs::debug('Testing OpenAI connection');
            
            $response = wp_remote_get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                throw new \Exception('Invalid response code: ' . $response_code);
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data === null) {
                throw new \Exception('Invalid JSON response');
            }
            
            if (!isset($data['data']) || !is_array($data['data'])) {
                throw new \Exception('Invalid API response format');
            }
            
            // Save the API key
            update_option('ai_blogpost_api_key', $api_key);
            
            // Extract and save models
            $gpt_models = [];
            $dalle_models = [];
            foreach ($data['data'] as $model) {
                if (strpos($model['id'], 'gpt') !== false) {
                    $gpt_models[] = $model['id'];
                } elseif (strpos($model['id'], 'dall-e') !== false) {
                    $dalle_models[] = $model['id'];
                }
            }
            
            update_option('ai_blogpost_available_gpt_models', $gpt_models);
            update_option('ai_blogpost_available_dalle_models', $dalle_models);
            
            Logs::logApiCall('OpenAI Test', true, [
                'status' => 'Connection successful',
                'models_found' => count($data['data'])
            ]);
            
            wp_send_json_success([
                'message' => 'Connection successful',
                'models' => $data['data']
            ]);
            
        } catch (\Exception $e) {
            Logs::debug('OpenAI error:', $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
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
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                throw new \Exception('Invalid response code: ' . $response_code);
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data === null) {
                throw new \Exception('Invalid JSON response');
            }
            
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
            
            // First try to get system stats which is a more reliable endpoint
            $stats_response = wp_remote_get($api_url . '/system_stats', [
                'timeout' => 30,
                'sslverify' => false
            ]);
            
            if (is_wp_error($stats_response)) {
                throw new \Exception($stats_response->get_error_message());
            }
            
            $response_code = wp_remote_retrieve_response_code($stats_response);
            if ($response_code !== 200) {
                throw new \Exception('Invalid response code from system_stats: ' . $response_code);
            }
            
            $stats_data = json_decode(wp_remote_retrieve_body($stats_response), true);
            if ($stats_data === null) {
                throw new \Exception('Invalid JSON response from system_stats');
            }
            
            // Now try the queue endpoint
            $response = wp_remote_get($api_url . '/queue', [
                'timeout' => 30,
                'sslverify' => false
            ]);
            
            if (is_wp_error($response)) {
                // If queue fails but system_stats worked, we can still proceed
                Logs::debug('ComfyUI queue endpoint failed, but system_stats worked:', [
                    'error' => $response->get_error_message()
                ]);
                $queue_data = null;
            } else {
                $queue_data = json_decode(wp_remote_retrieve_body($response), true);
            }
            
            update_option('ai_blogpost_comfyui_api_url', $api_url);
            
            Logs::logApiCall('ComfyUI Test', true, [
                'url' => $api_url,
                'status' => 'Connection successful',
                'system_stats' => $stats_data,
                'queue_status' => $queue_data
            ]);
            
            wp_send_json_success([
                'message' => 'Connection successful',
                'system_stats' => $stats_data,
                'queue_status' => $queue_data
            ]);
            
        } catch (\Exception $e) {
            Logs::debug('ComfyUI error:', $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

// Initialize settings functionality
Settings::initialize();
