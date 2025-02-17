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
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="#text-generation" class="nav-tab nav-tab-active">Text Generation</a>
                <a href="#image-generation" class="nav-tab">Image Generation</a>
                <a href="#scheduling" class="nav-tab">Scheduling</a>
            </h2>

            <form action="options.php" method="post">
                <?php settings_fields('ai_blogpost_settings'); ?>

                <div id="text-generation" class="tab-content active">
                    <h2>Text Generation Settings</h2>
                    <table class="form-table">
                        <!-- Provider Selection -->
                        <tr>
                            <th scope="row">Text Generation Provider</th>
                            <td>
                                <select name="ai_blogpost_llm_provider" id="ai_blogpost_llm_provider">
                                    <option value="openai" <?php selected(get_option('ai_blogpost_llm_provider'), 'openai'); ?>>OpenAI</option>
                                    <option value="lmstudio" <?php selected(get_option('ai_blogpost_llm_provider'), 'lmstudio'); ?>>LM Studio (Local)</option>
                                </select>
                            </td>
                        </tr>

                        <!-- OpenAI Settings -->
                        <tr class="provider-settings openai-settings">
                            <th scope="row">OpenAI API Key</th>
                            <td>
                                <input type="password" name="ai_blogpost_api_key" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('ai_blogpost_api_key')); ?>">
                                <p class="description">Your OpenAI API key for text generation</p>
                            </td>
                        </tr>

                        <!-- LM Studio Settings -->
                        <tr class="provider-settings lmstudio-settings">
                            <th scope="row">LM Studio URL</th>
                            <td>
                                <input type="url" name="ai_blogpost_lm_api_url" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('ai_blogpost_lm_api_url', 'http://localhost:1234')); ?>">
                                <button type="button" class="button test-lm-connection">Test Connection</button>
                                <p class="description">The URL where LM Studio is running (usually http://localhost:1234)</p>
                            </td>
                        </tr>

                        <!-- Common Settings -->
                        <tr>
                            <th scope="row">Temperature</th>
                            <td>
                                <input type="number" name="ai_blogpost_temperature" step="0.1" min="0" max="2" 
                                       value="<?php echo esc_attr(get_option('ai_blogpost_temperature', '0.7')); ?>">
                                <p class="description">Controls randomness (0 = deterministic, 2 = very random)</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Max Tokens</th>
                            <td>
                                <input type="number" name="ai_blogpost_max_tokens" min="100" max="4096" 
                                       value="<?php echo esc_attr(get_option('ai_blogpost_max_tokens', '2048')); ?>">
                                <p class="description">Maximum length of generated text</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Language</th>
                            <td>
                                <select name="ai_blogpost_language">
                                    <?php
                                    $languages = array(
                                        'en' => 'English',
                                        'nl' => 'Nederlands',
                                        'de' => 'Deutsch',
                                        'fr' => 'Français',
                                        'es' => 'Español'
                                    );
                                    $selected = get_option('ai_blogpost_language', 'en');
                                    foreach ($languages as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '" ' . 
                                             selected($selected, $code, false) . '>' . 
                                             esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="image-generation" class="tab-content">
                    <h2>Image Generation Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable DALL-E</th>
                            <td>
                                <input type="checkbox" name="ai_blogpost_dalle_enabled" value="1" 
                                       <?php checked(get_option('ai_blogpost_dalle_enabled'), 1); ?>>
                                <p class="description">Generate images for blog posts using DALL-E</p>
                            </td>
                        </tr>

                        <tr class="dalle-setting">
                            <th scope="row">DALL-E API Key</th>
                            <td>
                                <input type="password" name="ai_blogpost_dalle_api_key" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('ai_blogpost_dalle_api_key')); ?>">
                                <p class="description">Can be the same as your OpenAI API key</p>
                            </td>
                        </tr>

                        <tr class="dalle-setting">
                            <th scope="row">Image Size</th>
                            <td>
                                <select name="ai_blogpost_dalle_size">
                                    <option value="1024x1024">1024x1024</option>
                                    <option value="1024x1792">1024x1792</option>
                                    <option value="1792x1024">1792x1024</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="scheduling" class="tab-content">
                    <h2>Post Scheduling Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Post Frequency</th>
                            <td>
                                <select name="ai_blogpost_post_frequency">
                                    <option value="daily" <?php selected(get_option('ai_blogpost_post_frequency'), 'daily'); ?>>Daily</option>
                                    <option value="weekly" <?php selected(get_option('ai_blogpost_post_frequency'), 'weekly'); ?>>Weekly</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Categories</th>
                            <td>
                                <textarea name="ai_blogpost_custom_categories" rows="5" class="large-text"><?php 
                                    echo esc_textarea(get_option('ai_blogpost_custom_categories')); 
                                ?></textarea>
                                <p class="description">Enter categories (one per line) that will be used for post generation</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>

            <div class="postbox">
                <h3>Generate Test Post</h3>
                <div class="inside">
                    <form method="post">
                        <?php wp_nonce_field('ai_blogpost_test'); ?>
                        <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">
                    </form>
                </div>
            </div>
        </div>

        <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .provider-settings { display: none; }
        .dalle-setting { display: none; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').removeClass('active').hide();
                $($(this).attr('href')).addClass('active').show();
            });

            // Provider settings toggle
            function toggleProviderSettings() {
                var provider = $('#ai_blogpost_llm_provider').val();
                $('.provider-settings').hide();
                $('.' + provider + '-settings').show();
            }
            $('#ai_blogpost_llm_provider').on('change', toggleProviderSettings);
            toggleProviderSettings();

            // DALL-E settings toggle
            $('input[name="ai_blogpost_dalle_enabled"]').on('change', function() {
                $('.dalle-setting').toggle(this.checked);
            }).trigger('change');
        });
        </script>
        <?php
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