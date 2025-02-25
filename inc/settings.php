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
        if ($screen && $screen->id === 'toplevel_page_' . self::MENU_SLUG) {
            // Ensure dashicons are loaded
            wp_enqueue_style('dashicons');
            
            // Enqueue our custom CSS with dashicons dependency
            wp_enqueue_style(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
                ['dashicons'],
                AI_BLOGPOST_VERSION . '.' . time() // Add timestamp to prevent caching during development
            );
            
            // Enqueue our custom JS
            wp_enqueue_script(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
                ['jquery'],
                AI_BLOGPOST_VERSION . '.' . time(), // Add timestamp to prevent caching during development
                true
            );
            
            // Pass data to JavaScript
            wp_localize_script('ai-blogpost-admin', 'aiBlogpostAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_blogpost_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'version' => AI_BLOGPOST_VERSION
            ]);
            
            // Add inline script to detect JS errors
            wp_add_inline_script('ai-blogpost-admin', '
                console.log("AI Blogpost Admin JS loaded, version: ' . AI_BLOGPOST_VERSION . '");
                window.addEventListener("error", function(e) {
                    console.error("AI Blogpost JS Error:", e.message, "at", e.filename, ":", e.lineno);
                    if (document.querySelector(".ai-blogpost-dashboard")) {
                        if (!document.getElementById("ai-blogpost-js-error")) {
                            var errorDiv = document.createElement("div");
                            errorDiv.id = "ai-blogpost-js-error";
                            errorDiv.className = "notice notice-error";
                            errorDiv.innerHTML = "<p><strong>JavaScript Error:</strong> " + e.message + "</p>";
                            document.querySelector(".ai-blogpost-dashboard").prepend(errorDiv);
                        }
                    }
                });
            ');
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
        if ($screen && $screen->id === 'toplevel_page_' . self::MENU_SLUG) {
            // Ensure dashicons are loaded
            wp_enqueue_style('dashicons');
            
            // Enqueue our custom CSS with dashicons dependency
            wp_enqueue_style(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
                ['dashicons'],
                AI_BLOGPOST_VERSION . '.' . time() // Add timestamp to prevent caching during development
            );
            
            // Enqueue our custom JS
            wp_enqueue_script(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
                ['jquery'],
                AI_BLOGPOST_VERSION . '.' . time(), // Add timestamp to prevent caching during development
                true
            );
            
            // Pass data to JavaScript
            wp_localize_script('ai-blogpost-admin', 'aiBlogpostAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_blogpost_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'version' => AI_BLOGPOST_VERSION
            ]);
            
            // Add inline script to detect JS errors
            wp_add_inline_script('ai-blogpost-admin', '
                console.log("AI Blogpost Admin JS loaded, version: ' . AI_BLOGPOST_VERSION . '");
                window.addEventListener("error", function(e) {
                    console.error("AI Blogpost JS Error:", e.message, "at", e.filename, ":", e.lineno);
                    if (document.querySelector(".ai-blogpost-dashboard")) {
                        if (!document.getElementById("ai-blogpost-js-error")) {
                            var errorDiv = document.createElement("div");
                            errorDiv.id = "ai-blogpost-js-error";
                            errorDiv.className = "notice notice-error";
                            errorDiv.innerHTML = "<p><strong>JavaScript Error:</strong> " + e.message + "</p>";
                            document.querySelector(".ai-blogpost-dashboard").prepend(errorDiv);
                        }
                    }
                });
            ');
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
        if ($screen && $screen->id === 'toplevel_page_' . self::MENU_SLUG) {
            // Ensure dashicons are loaded
            wp_enqueue_style('dashicons');
            
            // Enqueue our custom CSS with dashicons dependency
            wp_enqueue_style(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
                ['dashicons'],
                AI_BLOGPOST_VERSION . '.' . time() // Add timestamp to prevent caching during development
            );
            
            // Enqueue our custom JS
            wp_enqueue_script(
                'ai-blogpost-admin',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
                ['jquery'],
                AI_BLOGPOST_VERSION . '.' . time(), // Add timestamp to prevent caching during development
                true
            );
            
            // Pass data to JavaScript
            wp_localize_script('ai-blogpost-admin', 'aiBlogpostAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_blogpost_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'version' => AI_BLOGPOST_VERSION
            ]);
            
            // Add inline script to detect JS errors
            wp_add_inline_script('ai-blogpost-admin', '
                console.log("AI Blogpost Admin JS loaded, version: ' . AI_BLOGPOST_VERSION . '");
                window.addEventListener("error", function(e) {
                    console.error("AI Blogpost JS Error:", e.message, "at", e.filename, ":", e.lineno);
                    if (document.querySelector(".ai-blogpost-dashboard")) {
                        if (!document.getElementById("ai-blogpost-js-error")) {
                            var errorDiv = document.createElement("div");
                            errorDiv.id = "ai-blogpost-js-error";
                            errorDiv.className = "notice notice-error";
                            errorDiv.innerHTML = "<p><strong>JavaScript Error:</strong> " + e.message + "</p>";
                            document.querySelector(".ai-blogpost-dashboard").prepend(errorDiv);
                        }
                    }
                });
            ');
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
        wp_nonce_field('clear_ai_logs_nonce', '_wpnonce');
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
    
    /**
     * Render frequency field
     */
    private static function renderFrequencyField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">';
        $frequency = Helpers::getCachedOption('ai_blogpost_post_frequency', 'daily');
        $frequencies = [
            'daily' => 'Daily (every day)',
            'weekly' => 'Weekly (every Monday)'
        ];
        foreach ($frequencies as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($frequency, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">How often should new posts be generated</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render categories field
     */
    private static function renderCategoriesField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_custom_categories">Post Categories</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_custom_categories', '')) . '</textarea>';
        echo '<p class="description">Enter one category per line. A random category will be selected for each post.</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render API key field
     */
    private static function renderApiKeyField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>';
        echo '<td>';
        echo '<div class="api-key-field">';
        echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_api_key', '')) . '" class="regular-text">';
        echo '<button type="button" class="toggle-password" aria-label="Toggle API key visibility"><span class="dashicons dashicons-visibility"></span></button>';
        echo '</div>';
        echo '<div class="connection-test mt-10">';
        echo '<button type="button" id="test-openai-connection" class="button">Test Connection</button>';
        echo '<span class="spinner"></span>';
        echo '<span class="openai-connection-status connection-status"></span>';
        echo '</div>';
        echo '<p class="description">Your OpenAI API key from <a href="https://platform.openai.com/account/api-keys" target="_blank">platform.openai.com</a></p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render model field
     */
    private static function renderModelField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_model">AI Model</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_model" id="ai_blogpost_model">';
        
        $current_model = Helpers::getCachedOption('ai_blogpost_model', 'gpt-4');
        $available_models = get_option('ai_blogpost_available_gpt_models', [
            'gpt-4',
            'gpt-4-turbo',
            'gpt-3.5-turbo'
        ]);
        
        foreach ($available_models as $model) {
            echo '<option value="' . esc_attr($model) . '" ' . selected($current_model, $model, false) . '>' . esc_html($model) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the OpenAI model to use for text generation</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render temperature field
     */
    private static function renderTemperatureField(): void {
        $temperature = Helpers::getCachedOption('ai_blogpost_temperature', 0.7);
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
        echo '<td>';
        echo '<div class="d-flex align-center gap-10">';
        echo '<input type="range" name="ai_blogpost_temperature" id="ai_blogpost_temperature" min="0" max="1" step="0.1" value="' . esc_attr($temperature) . '" style="width: 200px;">';
        echo '<span id="temperature-value">' . esc_html($temperature) . '</span>';
        echo '</div>';
        echo '<p class="description">Controls randomness: Lower values are more focused, higher values more creative</p>';
        
        // Add JavaScript to update the displayed value
        echo '<script>
            jQuery(document).ready(function($) {
                $("#ai_blogpost_temperature").on("input", function() {
                    $("#temperature-value").text($(this).val());
                });
            });
        </script>';
        
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render max tokens field
     */
    private static function renderMaxTokensField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>';
        echo '<td>';
        echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_max_tokens', 2048)) . '" min="100" max="4096" step="1" class="small-text">';
        echo '<p class="description">Maximum length of generated content (1 token ≈ 4 characters)</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render system role field
     */
    private static function renderSystemRoleField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_role">System Role</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_role', 'You are a professional SEO content writer.')) . '</textarea>';
        echo '<p class="description">Define the AI\'s role and behavior</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render content template field
     */
    private static function renderContentTemplateField(): void {
        $default_template = "Write for a website a SEO blogpost in [language] with the [category] as keyword. Use sections:\n||Title||:\n||Content||:\n||Category||:[category]\nWrite the content of the content section within the <article></article> tags and use <p>, <h1>, and <h2>.";
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_prompt">Content Template</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="6" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_prompt', $default_template)) . '</textarea>';
        echo '<p class="description">Template for content generation. Use [category] and [language] as placeholders.</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render LM Studio section
     */
    private static function renderLmStudioSection(): void {
        $lm_enabled = Helpers::getCachedOption('ai_blogpost_lm_enabled', 0);
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
        echo '<td>';
        echo '<label class="toggle-switch">';
        echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . checked(1, $lm_enabled, false) . ' data-target=".lm-studio-fields">';
        echo '<span class="toggle-slider"></span>';
        echo '</label>';
        echo '<p class="description">Use a local LM Studio instance instead of OpenAI API</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr class="lm-studio-fields" ' . ($lm_enabled ? '' : 'style="display:none;"') . '>';
        echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
        echo '<td>';
        echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_lm_api_url', 'http://localhost:1234')) . '" class="regular-text">';
        echo '<div class="connection-test mt-10">';
        echo '<button type="button" id="test-lm-studio" class="button">Test Connection</button>';
        echo '<span class="spinner"></span>';
        echo '<span class="lm-connection-status connection-status"></span>';
        echo '</div>';
        echo '<p class="description">URL of your local LM Studio server (default: http://localhost:1234)</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr class="lm-studio-fields" ' . ($lm_enabled ? '' : 'style="display:none;"') . '>';
        echo '<th><label for="ai_blogpost_lm_model">LM Studio Model</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_lm_model" id="ai_blogpost_lm_model">';
        
        $current_model = Helpers::getCachedOption('ai_blogpost_lm_model', '');
        $available_models = get_option('ai_blogpost_available_lm_models', []);
        
        if (empty($available_models)) {
            echo '<option value="">No models found - test connection first</option>';
        } else {
            foreach ($available_models as $model) {
                $model_id = is_array($model) ? $model['id'] : $model;
                echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_model, $model_id, false) . '>' . esc_html($model_id) . '</option>';
            }
        }
        
        echo '</select>';
        echo '<p class="description">Select the model loaded in LM Studio</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render image type selector
     */
    private static function renderImageTypeSelector(): void {
        $current_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        echo '<div class="image-type-selector">';
        
        // None option
        echo '<div class="image-type-option' . ($current_type === 'none' ? ' selected' : '') . '" data-value="none">';
        echo '<h3><span class="dashicons dashicons-no-alt"></span> No Images</h3>';
        echo '<p>Don\'t generate featured images for posts</p>';
        echo '</div>';
        
        // DALL·E option
        echo '<div class="image-type-option' . ($current_type === 'dalle' ? ' selected' : '') . '" data-value="dalle">';
        echo '<h3><span class="dashicons dashicons-cloud"></span> DALL·E</h3>';
        echo '<p>Use OpenAI\'s DALL·E API for image generation</p>';
        echo '</div>';
        
        // ComfyUI option
        echo '<div class="image-type-option' . ($current_type === 'comfyui' ? ' selected' : '') . '" data-value="comfyui">';
        echo '<h3><span class="dashicons dashicons-desktop"></span> ComfyUI</h3>';
        echo '<p>Use a local ComfyUI instance for image generation</p>';
        echo '</div>';
        
        // LocalAI option
        echo '<div class="image-type-option' . ($current_type === 'localai' ? ' selected' : '') . '" data-value="localai">';
        echo '<h3><span class="dashicons dashicons-laptop"></span> LocalAI</h3>';
        echo '<p>Use a local LocalAI instance for image generation</p>';
        echo '</div>';
        
        echo '</div>';
        
        // Hidden input to store the selected value
        echo '<input type="hidden" name="ai_blogpost_image_generation_type" value="' . esc_attr($current_type) . '">';
    }
    
    /**
     * Render DALL·E settings
     */
    private static function renderDalleSettings(): void {
        $current_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        echo '<div class="settings-section image-settings image-settings-dalle" ' . ($current_type === 'dalle' ? '' : 'style="display:none;"') . '>';
        echo '<h3><span class="dashicons dashicons-cloud"></span> DALL·E Settings</h3>';
        
        echo '<table class="form-table">';
        
        // API Key
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_api_key">DALL·E API Key</label></th>';
        echo '<td>';
        echo '<div class="api-key-field">';
        echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_dalle_api_key', '')) . '" class="regular-text">';
        echo '<button type="button" class="toggle-password" aria-label="Toggle API key visibility"><span class="dashicons dashicons-visibility"></span></button>';
        echo '</div>';
        echo '<p class="description">Your OpenAI API key (can be the same as text generation key)</p>';
        echo '</td>';
        echo '</tr>';
        
        // Model
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_model">DALL·E Model</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_model" id="ai_blogpost_dalle_model">';
        
        $current_model = Helpers::getCachedOption('ai_blogpost_dalle_model', 'dall-e-3');
        $models = [
            'dall-e-3' => 'DALL·E 3',
            'dall-e-2' => 'DALL·E 2'
        ];
        
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the DALL·E model to use</p>';
        echo '</td>';
        echo '</tr>';
        
        // Size
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_size">Image Size</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_size" id="ai_blogpost_dalle_size">';
        
        $current_size = Helpers::getCachedOption('ai_blogpost_dalle_size', '1024x1024');
        $sizes = [
            '1024x1024' => 'Square (1024x1024)',
            '1792x1024' => 'Landscape (1792x1024)',
            '1024x1792' => 'Portrait (1024x1792)'
        ];
        
        foreach ($sizes as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_size, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the image dimensions</p>';
        echo '</td>';
        echo '</tr>';
        
        // Quality
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_quality">Image Quality</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_quality" id="ai_blogpost_dalle_quality">';
        
        $current_quality = Helpers::getCachedOption('ai_blogpost_dalle_quality', 'standard');
        $qualities = [
            'standard' => 'Standard',
            'hd' => 'HD (Higher quality)'
        ];
        
        foreach ($qualities as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_quality, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the image quality (HD costs more credits)</p>';
        echo '</td>';
        echo '</tr>';
        
        // Style
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_style">Image Style</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_style" id="ai_blogpost_dalle_style">';
        
        $current_style = Helpers::getCachedOption('ai_blogpost_dalle_style', 'vivid');
        $styles = [
            'vivid' => 'Vivid (Hyper-real and dramatic)',
            'natural' => 'Natural (More subtle and realistic)'
        ];
        
        foreach ($styles as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_style, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the image style</p>';
        echo '</td>';
        echo '</tr>';
        
        // Prompt Template
        $default_template = 'Create a professional blog header image about [category]. Style: Modern and professional.';
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="4" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_dalle_prompt_template', $default_template)) . '</textarea>';
        echo '<p class="description">Template for image generation. Use [category] as a placeholder.</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render ComfyUI settings
     */
    private static function renderComfyUiSettings(): void {
        $current_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        echo '<div class="settings-section image-settings image-settings-comfyui" ' . ($current_type === 'comfyui' ? '' : 'style="display:none;"') . '>';
        echo '<h3><span class="dashicons dashicons-desktop"></span> ComfyUI Settings</h3>';
        
        echo '<table class="form-table">';
        
        // API URL
        echo '<tr>';
        echo '<th><label for="ai_blogpost_comfyui_api_url">ComfyUI API URL</label></th>';
        echo '<td>';
        echo '<input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_comfyui_api_url', 'http://localhost:8188')) . '" class="regular-text">';
        echo '<div class="connection-test mt-10">';
        echo '<button type="button" id="test-comfyui-connection" class="button">Test Connection</button>';
        echo '<span class="spinner"></span>';
        echo '<span class="comfyui-connection-status connection-status"></span>';
        echo '</div>';
        echo '<p class="description">URL of your local ComfyUI server (default: http://localhost:8188)</p>';
        echo '</td>';
        echo '</tr>';
        
        // Default Workflow
        echo '<tr>';
        echo '<th><label for="ai_blogpost_comfyui_default_workflow">Default Workflow</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow">';
        
        $current_workflow = Helpers::getCachedOption('ai_blogpost_comfyui_default_workflow', '');
        $workflows = json_decode(Helpers::getCachedOption('ai_blogpost_comfyui_workflows', '[]'), true);
        
        if (empty($workflows)) {
            echo '<option value="">No workflows configured</option>';
        } else {
            foreach ($workflows as $workflow) {
                echo '<option value="' . esc_attr($workflow['name']) . '" ' . selected($current_workflow, $workflow['name'], false) . '>' . esc_html($workflow['name']) . '</option>';
            }
        }
        
        echo '</select>';
        echo '<p class="description">Select the workflow to use for image generation</p>';
        echo '</td>';
        echo '</tr>';
        
        // Workflow Management
        echo '<tr>';
        echo '<th><label>Workflow Management</label></th>';
        echo '<td>';
        echo '<p>To add or update workflows, upload JSON workflow files to the <code>workflows</code> directory.</p>';
        echo '<p>Available workflow files:</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        
        $workflow_files = glob(plugin_dir_path(dirname(__FILE__)) . 'workflows/*.json');
        if (empty($workflow_files)) {
            echo '<li>No workflow files found</li>';
        } else {
            foreach ($workflow_files as $file) {
                $filename = basename($file);
                echo '<li>' . esc_html($filename) . '</li>';
            }
        }
        
        echo '</ul>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</div>';
    }
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
