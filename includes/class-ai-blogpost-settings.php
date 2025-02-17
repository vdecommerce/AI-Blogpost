<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost_Settings {
    private $logger;

    public function __construct() {
        $this->logger = new AI_Blogpost_Logger();
    }

    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'handle_settings_save'), 99);
    }

    public function register_settings() {
        // Text generation settings
        register_setting('ai_blogpost_settings', 'ai_blogpost_llm_provider');
        register_setting('ai_blogpost_settings', 'ai_blogpost_temperature');
        register_setting('ai_blogpost_settings', 'ai_blogpost_max_tokens');
        register_setting('ai_blogpost_settings', 'ai_blogpost_role');
        register_setting('ai_blogpost_settings', 'ai_blogpost_prompt');
        register_setting('ai_blogpost_settings', 'ai_blogpost_post_frequency');
        register_setting('ai_blogpost_settings', 'ai_blogpost_custom_categories');
        register_setting('ai_blogpost_settings', 'ai_blogpost_language');

        // OpenAI settings
        register_setting('ai_blogpost_settings', 'ai_blogpost_api_key');
        register_setting('ai_blogpost_settings', 'ai_blogpost_model');

        // LM Studio settings
        register_setting('ai_blogpost_settings', 'ai_blogpost_lm_enabled');
        register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_url');
        register_setting('ai_blogpost_settings', 'ai_blogpost_lm_model');

        // DALL·E settings
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_enabled');
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
        register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');
    }

    public function add_menu_page() {
        add_menu_page(
            'AI Blogpost Settings', // page title
            'AI Blogpost',          // menu title
            'manage_options',       // capability
            'ai-blogpost',          // menu slug
            array($this, 'render_settings_page'), // callback function
            'dashicons-admin-generic', // icon
            30 // position
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/admin/settings-page.php';
    }

    private function save_settings() {
        if (isset($_POST['ai_blogpost_llm_provider'])) {
            update_option('ai_blogpost_llm_provider', sanitize_text_field($_POST['ai_blogpost_llm_provider']));
        }
        if (isset($_POST['ai_blogpost_api_key'])) {
            update_option('ai_blogpost_api_key', sanitize_text_field($_POST['ai_blogpost_api_key']));
        }
        if (isset($_POST['ai_blogpost_lm_api_url'])) {
            update_option('ai_blogpost_lm_api_url', esc_url_raw($_POST['ai_blogpost_lm_api_url']));
        }
        if (isset($_POST['ai_blogpost_temperature'])) {
            update_option('ai_blogpost_temperature', floatval($_POST['ai_blogpost_temperature']));
        }
        if (isset($_POST['ai_blogpost_language'])) {
            update_option('ai_blogpost_language', sanitize_text_field($_POST['ai_blogpost_language']));
        }
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Handle test post generation
        if (isset($_POST['test_ai_blogpost']) && check_admin_referer('ai_blogpost_test')) {
            try {
                $plugin = new AI_Blogpost();
                $post_id = $plugin->create_post();
                if ($post_id) {
                    add_settings_error('ai_blogpost', 'post_created', 'Test post created successfully!', 'updated');
                }
            } catch (Exception $e) {
                add_settings_error('ai_blogpost', 'post_failed', 'Error creating test post: ' . $e->getMessage(), 'error');
            }
        }

        // Display settings page
        ?>
        <div class="wrap">
            <h1>AI Blogpost Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_blogpost_settings');
                do_settings_sections('ai_blogpost_settings');
                ?>
                
                <h2>Text Generation Settings</h2>
                <table class="form-table">
                    <?php $this->render_text_generation_settings(); ?>
                </table>

                <h2>Image Generation Settings</h2>
                <table class="form-table">
                    <?php $this->render_image_generation_settings(); ?>
                </table>

                <?php submit_button(); ?>
            </form>

            <div class="test-post-section">
                <h2>Test Post Generation</h2>
                <form method="post">
                    <?php wp_nonce_field('ai_blogpost_test'); ?>
                    <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">
                </form>
            </div>
        </div>
        <?php
    }

    private function render_text_generation_settings() {
        // Add rendering code for text generation settings
        include(AI_BLOGPOST_PLUGIN_DIR . 'includes/admin/display-functions.php');
        display_text_settings();
    }

    private function render_image_generation_settings() {
        // Add rendering code for image generation settings
        include(AI_BLOGPOST_PLUGIN_DIR . 'includes/admin/display-functions.php');
        display_image_settings();
    }
}