<?php
if (!defined('ABSPATH')) {
    exit;
}

function ai_blogpost_initialize_settings() {
    // Groep: Schedule
    register_setting('ai_blogpost_schedule_settings', 'ai_blogpost_language');
    register_setting('ai_blogpost_schedule_settings', 'ai_blogpost_post_frequency');
    register_setting('ai_blogpost_schedule_settings', 'ai_blogpost_custom_categories');

    // Groep: Text Generation
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_temperature');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_max_tokens');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_role');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_api_key');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_model');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_prompt');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_lm_enabled');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_lm_api_url');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_lm_api_key');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_lm_model');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_openrouter_enabled');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_openrouter_api_key');
    register_setting('ai_blogpost_text_settings', 'ai_blogpost_openrouter_model');

    // Groep: Image Generation
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_image_generation_type');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_dalle_prompt_template');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_comfyui_api_url');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_comfyui_workflows');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_comfyui_default_workflow');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_localai_api_url');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_localai_prompt_template');
    register_setting('ai_blogpost_image_settings', 'ai_blogpost_localai_size');
}
add_action('admin_init', 'ai_blogpost_initialize_settings');

function ai_blogpost_admin_menu() {
    add_menu_page('AI Blogpost Settings', 'AI Blogpost', 'manage_options', 'ai_blogpost', 'ai_blogpost_admin_page');
}
add_action('admin_menu', 'ai_blogpost_admin_menu');

function ai_blogpost_admin_page() {
    // Bepaal het actieve tabblad
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'schedule';
    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1>AI Blogpost Dashboard</h1>
        <div class="tabs">
            <ul class="tab-links">
                <li<?php echo $active_tab === 'schedule' ? ' class="active"' : ''; ?>><a href="?page=ai_blogpost&tab=schedule">Schedule</a></li>
                <li<?php echo $active_tab === 'text' ? ' class="active"' : ''; ?>><a href="?page=ai_blogpost&tab=text">Text Generation</a></li>
                <li<?php echo $active_tab === 'image' ? ' class="active"' : ''; ?>><a href="?page=ai_blogpost&tab=image">Image Generation</a></li>
                <li<?php echo $active_tab === 'logs' ? ' class="active"' : ''; ?>><a href="?page=ai_blogpost&tab=logs">Logs</a></li>
            </ul>
            <div class="tab-content">
                <div id="tab-schedule" class="tab<?php echo $active_tab === 'schedule' ? ' active' : ''; ?>">
                    <h2>Schedule Settings</h2>
                    <form method="post" action="options.php">
                        <?php 
                        settings_fields('ai_blogpost_schedule_settings'); 
                        do_settings_sections('ai_blogpost_schedule_settings');
                        display_general_settings();
                        ?>
                        <input type="hidden" name="active_tab" value="schedule">
                        <?php submit_button('Save Schedule Settings', 'primary', 'submit', true, ['class' => 'submit-button']); ?>
                        <div class="test-post-section">
                        <h3>Test Generation</h3>
                        <form method="post" action="">
                            <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">
                            <?php wp_nonce_field('test_ai_blogpost_action', 'test_ai_blogpost_nonce'); ?>
                        </form>
                        <?php
                        $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
                        if ($next_post_time) {
                            echo '<p>Next scheduled post: ' . get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i') . '</p>';
                        }
                        ?>
                        </div>
                    </form>
                </div>
                <div id="tab-text" class="tab<?php echo $active_tab === 'text' ? ' active' : ''; ?>">
                    <h2>Text Generation</h2>
                    <form method="post" action="options.php">
                        <?php 
                        settings_fields('ai_blogpost_text_settings'); 
                        do_settings_sections('ai_blogpost_text_settings');
                        display_text_settings();
                        ?>
                        <input type="hidden" name="active_tab" value="text">
                        <?php submit_button('Save Text Settings', 'primary', 'submit', true, ['class' => 'submit-button']); ?>
                    </form>
                </div>
                <div id="tab-image" class="tab<?php echo $active_tab === 'image' ? ' active' : ''; ?>">
                    <h2>Image Generation</h2>
                    <form method="post" action="options.php">
                        <?php 
                        settings_fields('ai_blogpost_image_settings'); 
                        do_settings_sections('ai_blogpost_image_settings');
                        display_image_settings();
                        ?>
                        <input type="hidden" name="active_tab" value="image">
                        <?php submit_button('Save Image Settings', 'primary', 'submit', true, ['class' => 'submit-button']); ?>
                    </form>
                </div>
                <div id="tab-logs" class="tab<?php echo $active_tab === 'logs' ? ' active' : ''; ?>">
                    <h2>Generation Status</h2>
                    <div class="log-actions">
                        <form method="post" action="">
                            <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                            <input type="submit" name="clear_ai_logs" class="button" value="Clear Logs">
                            <input type="hidden" name="active_tab" value="logs">
                        </form>
                        <button type="button" class="button" onclick="window.location.reload();">Refresh</button>
                    </div>
                    <h3>Text Generation Logs</h3>
                    <?php display_api_logs('Text Generation'); ?>
                    <h3>Image Generation Logs</h3>
                    <?php display_api_logs('Image Generation'); ?>
                </div>
            </div>
        </div>
    </div>
    <style>
        .ai-blogpost-dashboard { max-width: 960px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .tabs { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tab-links { list-style: none; margin: 0; padding: 0; display: flex; border-bottom: 1px solid #ccd0d4; }
        .tab-links li { margin: 0; }
        .tab-links a { display: block; padding: 12px 20px; text-decoration: none; color: #1d2327; font-weight: 500; }
        .tab-links li.active a { background: #f0f7ff; border-bottom: 2px solid #2271b1; color: #2271b1; }
        .tab-content { padding: 20px; }
        .tab { display: none; }
        .tab.active { display: block; }
        .tab h2 { margin-top: 0; color: #1d2327; }
        .settings-section { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #e5e5e5; border-radius: 4px; }
        .form-table th { width: 200px; padding: 10px; vertical-align: top; }
        .form-table td { padding: 10px; }
        .form-table input[type="text"], .form-table input[type="url"], .form-table input[type="password"], .form-table textarea { width: 100%; max-width: 400px; }
        .form-table select { width: 200px; }
        .description { color: #666; font-size: 12px; margin-top: 5px; }
        .test-post-section { margin-top: 20px; padding: 15px; background: #f0f7ff; border: 1px solid #b3d4fc; border-radius: 4px; }
        .log-actions { margin-bottom: 15px; }
        .log-actions .button { margin-right: 10px; }
        .submit-button { margin-top: 20px; }
        .accordion { margin-bottom: 10px; }
        .accordion-header { background: #f1f1f1; padding: 10px; cursor: pointer; border: 1px solid #ddd; border-radius: 4px; }
        .accordion-content { display: none; padding: 15px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; }
        .accordion.active .accordion-content { display: block; }
    </style>
    <script>
        jQuery(document).ready(function($) {
            // Tab-navigatie via URL wordt nu via PHP gedaan
            $('.accordion-header').click(function() {
                $(this).parent().toggleClass('active');
            });
            $('input[name="ai_blogpost_image_generation_type"]').change(function() {
                $('.accordion').removeClass('active');
                $('#accordion-' + $(this).val()).addClass('active');
            });
            $('.test-lm-connection, .test-comfyui-connection, .test-localai-connection').click(function() {
                var $button = $(this);
                var $spinner = $button.next('.spinner');
                var url = $button.prev('input').val();
                var action = $button.hasClass('test-lm-connection') ? 'test_lm_studio' : $button.hasClass('test-comfyui-connection') ? 'test_comfyui_connection' : 'test_localai_connection';
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                
                $.post(ajaxurl, {
                    action: action,
                    url: url,
                    nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Connection successful!');
                    } else {
                        alert('Error: ' + response.data);
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
                <p class="description">Select the language for generated content.</p>
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
                <textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text"><?php echo esc_textarea(get_cached_option('ai_blogpost_custom_categories', 'tarot')); ?></textarea>
                <p class="description">Enter categories (one per line) for post generation.</p>
            </td>
        </tr>
    </table>
    <?php
}

function display_text_settings() {
    ?>
    <div class="settings-section">
        <h3>General Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="ai_blogpost_temperature">Temperature</label></th>
                <td>
                    <input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" value="<?php echo esc_attr(get_cached_option('ai_blogpost_temperature', '0.7')); ?>">
                    <p class="description">Controls randomness (0 = deterministic, 2 = very random).</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>
                <td>
                    <input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="<?php echo esc_attr(get_cached_option('ai_blogpost_max_tokens', '2048')); ?>" min="100" max="4096">
                    <p class="description">Maximum length of generated text (max 4096).</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_role">System Prompt</label></th>
                <td>
                    <textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="5" class="large-text"><?php echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.')); ?></textarea>
                    <p class="description">Define the AI's role and writing style. Used as system message.</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_prompt">Content Template</label></th>
                <td>
                    <textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="8" class="large-text"><?php echo esc_textarea(get_cached_option('ai_blogpost_prompt', "Write a blog post about [topic]. Structure the content as follows:\n\n||Title||: Create an engaging, SEO-friendly title\n\n||Content||: Write the main content here, using proper HTML structure:\n- Use <article> tags to wrap the content\n- Use <h1>, <h2> for headings\n- Use <p> for paragraphs\n- Include relevant subheadings\n- Add a strong conclusion\n\n||Category||: Suggest the most appropriate category for this post")); ?></textarea>
                    <p class="description">Customize the structure and instructions. Use [topic] for the category.</p>
                </td>
            </tr>
        </table>
    </div>
    <div class="accordion">
        <div class="accordion-header">OpenAI</div>
        <div class="accordion-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_api_key">API Key</label></th>
                    <td>
                        <input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_api_key')); ?>">
                        <p class="description">Your OpenAI API key.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_model">Model</label></th>
                    <td><?php display_model_dropdown('gpt'); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="accordion">
        <div class="accordion-header">OpenRouter</div>
        <div class="accordion-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_openrouter_enabled">Enable OpenRouter</label></th>
                    <td>
                        <input type="checkbox" name="ai_blogpost_openrouter_enabled" id="ai_blogpost_openrouter_enabled" value="1" <?php checked(get_cached_option('ai_blogpost_openrouter_enabled', 0), 1); ?>>
                        <p class="description">Use OpenRouter instead of OpenAI or LM Studio.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_openrouter_api_key">API Key</label></th>
                    <td>
                        <input type="password" name="ai_blogpost_openrouter_api_key" id="ai_blogpost_openrouter_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_openrouter_api_key')); ?>">
                        <p class="description">Your OpenRouter API key.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_openrouter_model">Model</label></th>
                    <td>
                        <input type="text" name="ai_blogpost_openrouter_model" id="ai_blogpost_openrouter_model" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_openrouter_model', 'openai/gpt-4')); ?>">
                        <p class="description">Enter the OpenRouter model ID (e.g., openai/gpt-4).</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="accordion">
        <div class="accordion-header">LM Studio</div>
        <div class="accordion-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>
                    <td>
                        <input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" <?php checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1); ?>>
                        <p class="description">Use local LM Studio for text generation.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_lm_api_url">API URL</label></th>
                    <td>
                        <input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')); ?>">
                        <button type="button" class="button test-lm-connection">Test Connection</button>
                        <span class="spinner" style="float: none; margin-left: 4px;"></span>
                        <p class="description">Usually http://localhost:1234/v1.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_lm_model">Model</label></th>
                    <td>
                        <input type="text" name="ai_blogpost_lm_model" id="ai_blogpost_lm_model" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_lm_model', 'model.gguf')); ?>">
                        <p class="description">Specify the LM Studio model file.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

function display_image_settings() {
    $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'none');
    ?>
    <div class="settings-section">
        <h3>Image Generation Type</h3>
        <table class="form-table">
            <tr>
                <th>Generation Type</th>
                <td>
                    <label><input type="radio" name="ai_blogpost_image_generation_type" value="none" <?php checked($generation_type, 'none'); ?>> None</label><br>
                    <label><input type="radio" name="ai_blogpost_image_generation_type" value="dalle" <?php checked($generation_type, 'dalle'); ?>> DALL·E</label><br>
                    <label><input type="radio" name="ai_blogpost_image_generation_type" value="comfyui" <?php checked($generation_type, 'comfyui'); ?>> ComfyUI</label><br>
                    <label><input type="radio" name="ai_blogpost_image_generation_type" value="localai" <?php checked($generation_type, 'localai'); ?>> LocalAI</label>
                </td>
            </tr>
        </table>
    </div>
    <div class="accordion" id="accordion-dalle"<?php echo $generation_type === 'dalle' ? ' class="active"' : ''; ?>>
        <div class="accordion-header">DALL·E Settings</div>
        <div class="accordion-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_dalle_api_key">API Key</label></th>
                    <td>
                        <input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_dalle_api_key')); ?>">
                        <p class="description">Your OpenAI API key for DALL·E.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_dalle_model">Model</label></th>
                    <td>
                        <select name="ai_blogpost_dalle_model" id="ai_blogpost_dalle_model">
                            <?php
                            $current_model = get_cached_option('ai_blogpost_dalle_model', 'dall-e-3');
                            $models = ['dall-e-3' => 'DALL·E 3', 'dall-e-2' => 'DALL·E 2'];
                            foreach ($models as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Select the DALL·E model.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_dalle_size">Size</label></th>
                    <td>
                        <select name="ai_blogpost_dalle_size" id="ai_blogpost_dalle_size">
                            <?php
                            $current_size = get_cached_option('ai_blogpost_dalle_size', '1024x1024');
                            $sizes = ['1024x1024' => '1024x1024', '1792x1024' => '1792x1024', '1024x1792' => '1024x1792'];
                            foreach ($sizes as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($current_size, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Image dimensions.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_dalle_style">Style</label></th>
                    <td>
                        <select name="ai_blogpost_dalle_style" id="ai_blogpost_dalle_style">
                            <?php
                            $current_style = get_cached_option('ai_blogpost_dalle_style', 'vivid');
                            $styles = ['vivid' => 'Vivid', 'natural' => 'Natural'];
                            foreach ($styles as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($current_style, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Image style (DALL·E 3 only).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_dalle_quality">Quality</label></th>
                    <td>
                        <select name="ai_blogpost_dalle_quality" id="ai_blogpost_dalle_quality">
                            <?php
                            $current_quality = get_cached_option('ai_blogpost_dalle_quality', 'standard');
                            $qualities = ['standard' => 'Standard', 'hd' => 'HD'];
                            foreach ($qualities as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($current_quality, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Image quality (DALL·E 3 only).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>
                    <td>
                        <textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text"><?php echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 'A professional blog header image for [category], modern style, clean design, subtle symbolism')); ?></textarea>
                        <p class="description">Use [category] as placeholder.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="accordion" id="accordion-comfyui"<?php echo $generation_type === 'comfyui' ? ' class="active"' : ''; ?>>
        <div class="accordion-header">ComfyUI Settings</div>
        <div class="accordion-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>
                    <td>
                        <input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188')); ?>">
                        <button type="button" class="button test-comfyui-connection">Test Connection</button>
                        <span class="spinner" style="float: none; margin-left: 4px;"></span>
                        <p class="description">Usually http://localhost:8188.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_comfyui_default_workflow">Workflow</label></th>
                    <td>
                        <select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow">
                            <?php
                            $workflows = json_decode(get_cached_option('ai_blogpost_comfyui_workflows', '[]'), true);
                            $default_workflow = get_cached_option('ai_blogpost_comfyui_default_workflow', '');
                            foreach ($workflows as $workflow) {
                                echo '<option value="' . esc_attr($workflow['name']) . '" ' . selected($default_workflow, $workflow['name'], false) . '>' . esc_html($workflow['name'] . ' - ' . $workflow['description']) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Select the ComfyUI workflow.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="accordion" id="accordion-localai"<?php echo $generation_type === 'localai' ? ' class="active"' : ''; ?>>
        <div class="accordion-header">LocalAI Settings</div>
        <div class="accordion-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_localai_api_url">API URL</label></th>
                    <td>
                        <input type="url" name="ai_blogpost_localai_api_url" id="ai_blogpost_localai_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_localai_api_url', 'http://localhost:8080')); ?>">
                        <button type="button" class="button test-localai-connection">Test Connection</button>
                        <span class="spinner" style="float: none; margin-left: 4px;"></span>
                        <p class="description">Usually http://localhost:8080.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_localai_size">Size</label></th>
                    <td>
                        <select name="ai_blogpost_localai_size" id="ai_blogpost_localai_size">
                            <?php
                            $current_size = get_cached_option('ai_blogpost_localai_size', '1024x1024');
                            $sizes = ['512x512' => '512x512', '1024x1024' => '1024x1024', '1280x720' => '1280x720'];
                            foreach ($sizes as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($current_size, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Image dimensions.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_localai_prompt_template">Prompt Template</label></th>
                    <td>
                        <textarea name="ai_blogpost_localai_prompt_template" id="ai_blogpost_localai_prompt_template" rows="3" class="large-text"><?php echo esc_textarea(get_cached_option('ai_blogpost_localai_prompt_template', 'A visually engaging image for a blog post about [category], modern and clean design')); ?></textarea>
                        <p class="description">Use [category] as placeholder.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

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
        echo '<p class="description">Save API key to fetch available models.</p>';
    }
}