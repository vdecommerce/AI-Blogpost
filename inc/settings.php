<?php
if (!defined('ABSPATH')) {
    exit;
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
    register_setting('ai_blogpost_settings', 'ai_blogpost_image_generation_type');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');
    register_setting('ai_blogpost_settings', 'ai_blogpost_language');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_workflows');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_default_workflow');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_workflow_json');
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
    add_menu_page('AI Blogpost Settings', 'AI Blogpost', 'manage_options', 'ai_blogpost', 'ai_blogpost_admin_page', 'dashicons-admin-generic');
}
add_action('admin_menu', 'ai_blogpost_admin_menu');

/**
 * Display the admin settings page
 */
function ai_blogpost_admin_page() {
    // Handle workflow upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ai_blogpost_debug_log('Form submitted with POST data:', $_POST);
        ai_blogpost_debug_log('Files uploaded:', $_FILES);
    }
    if (isset($_POST['ai_blogpost_comfyui_workflow_json']) && !empty($_POST['ai_blogpost_comfyui_workflow_json'])) {
        $json_input = $_POST['ai_blogpost_comfyui_workflow_json'];
        $workflow_data = json_decode($json_input, true);
        
        if ($workflow_data && isset($workflow_data['nodes']) && isset($workflow_data['links'])) {
            $workflows = get_option('ai_blogpost_comfyui_workflows', []);
            $workflow_name = 'default'; // Vast naam, kan later dynamisch worden gemaakt
            $workflows[$workflow_name] = $workflow_data;
            update_option('ai_blogpost_comfyui_workflows', $workflows);
            update_option('ai_blogpost_comfyui_default_workflow', $workflow_name);
            clear_ai_blogpost_cache();
            ai_blogpost_debug_log('Workflow JSON saved:', $workflow_name);
            echo '<div class="notice notice-success is-dismissible"><p>Workflow JSON succesvol opgeslagen als "default"!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Ongeldige JSON. Zorg ervoor dat het een geldige ComfyUI-workflow is met "nodes" en "links".</p></div>';
        }
    }

    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1>AI Blogpost Dashboard</h1>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-button active" data-tab="general">Algemene Instellingen</button>
            <button class="tab-button" data-tab="text">Tekstgeneratie</button>
            <button class="tab-button" data-tab="image">Afbeeldingsgeneratie</button>
            <button class="tab-button" data-tab="status">Status</button>
        </div>

        <!-- Test Post Section -->
        <div class="test-post-section">
            <div class="test-post-header">
                <h2>Test Generatie</h2>
                <form method="post" style="display:inline-block;">
                    <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Genereer Test Post">
                </form>
            </div>
            <?php
            $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
            if ($next_post_time) {
                echo '<div class="next-post-info">';
                echo '<span class="dashicons dashicons-calendar-alt"></span> ';
                echo 'Volgende geplande post: ' . get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i');
                echo '</div>';
            }
            ?>
        </div>

        <!-- Settings Form -->
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php 
            settings_fields('ai_blogpost_settings'); 
            do_settings_sections('ai_blogpost_settings');
            ?>

            <!-- General Settings Tab -->
            <div class="tab-content active" id="general">
                <h2>Algemene Instellingen</h2>
                <?php display_general_settings(); ?>
            </div>

            <!-- Text Generation Tab -->
            <div class="tab-content" id="text">
                <h2>Tekstgeneratie</h2>
                <?php display_text_settings(); ?>
            </div>

            <!-- Image Generation Tab -->
            <div class="tab-content" id="image">
                <h2>Afbeeldingsgeneratie</h2>
                <?php display_image_settings(); ?>
            </div>

            <!-- Submit Button -->
            <p class="submit">
                <?php submit_button('Instellingen Opslaan', 'primary', 'submit', false); ?>
            </p>
        </form>

        <!-- Status Tab -->
        <div class="tab-content" id="status">
            <h2>Generatiestatus</h2>
            <div class="status-actions">
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                    <input type="submit" name="clear_ai_logs" class="button" value="Logs Wissen">
                </form>
                <button type="button" class="button" onclick="location.reload();">Vernieuwen</button>
            </div>
            <h3>Tekstgeneratie Logs</h3>
            <?php display_api_logs('Text Generation'); ?>
            <h3>Afbeeldingsgeneratie Logs</h3>
            <?php display_api_logs('Image Generation'); ?>
        </div>

        <!-- Styling -->
        <style>
            .ai-blogpost-dashboard { max-width: 1000px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .tabs { display: flex; gap: 5px; margin-bottom: 20px; }
            .tab-button { padding: 10px 20px; background: #f1f1f1; border: none; border-radius: 5px 5px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s; }
            .tab-button.active { background: #0073aa; color: white; }
            .tab-content { display: none; padding: 20px; background: white; border: 1px solid #ccd0d4; border-radius: 0 5px 5px 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .tab-content.active { display: block; }
            .form-table th { width: 200px; padding: 15px 0; vertical-align: top; }
            .form-table td { padding: 15px 0; }
            .regular-text, textarea { width: 100%; max-width: 400px; padding: 8px; border-radius: 4px; border: 1px solid #ccd0d4; }
            .description { color: #666; font-size: 12px; margin-top: 5px; }
            .submit { margin-top: 20px; text-align: right; }
            .button-primary { background: #0073aa; border-color: #006799; }
            .button-primary:hover { background: #006799; }
            .test-post-section { margin-bottom: 20px; padding: 20px; background: #f8f9fa; border: 1px solid #e5e5e5; border-radius: 4px; }
            .test-post-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
            .next-post-info { margin-top: 10px; color: #666; }
            .status-actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .generation-option { flex: 1; padding: 15px; border: 1px solid #ccc; border-radius: 5px; transition: all 0.3s ease; cursor: pointer; }
            .generation-option:hover { background: #f0f7ff; border-color: #2271b1; }
            .template-guide { margin-top: 10px; padding: 15px; background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 4px; }
        </style>

        <!-- JavaScript -->
        <script>
            jQuery(document).ready(function($) {
                // Tab switching
                $('.tab-button').click(function() {
                    $('.tab-button').removeClass('active');
                    $('.tab-content').removeClass('active');
                    $(this).addClass('active');
                    $('#' + $(this).data('tab')).addClass('active');
                });

                // Image generation type toggle
                $('input[name="ai_blogpost_image_generation_type"]').change(function() {
                    var type = $(this).val();
                    $('.generation-option').css({'background': 'none', 'border-color': '#ccc'});
                    $(this).closest('.generation-option').css({'background': '#f0f7ff', 'border-color': '#2271b1'});
                    $('.dalle-settings, .comfyui-settings').hide();
                    if (type === 'dalle') $('.dalle-settings').show();
                    if (type === 'comfyui') $('.comfyui-settings').show();
                });

                $('.generation-option').click(function() {
                    $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
                });

                // Connection tests
                function handleConnectionTest(buttonClass, action, urlFieldId) {
                    $(buttonClass).click(function() {
                        const $button = $(this);
                        const $spinner = $button.next('.spinner');
                        const url = $(urlFieldId).val();

                        $button.prop('disabled', true);
                        $spinner.addClass('is-active');

                        $.post(ajaxurl, {
                            action: action,
                            url: url,
                            nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
                        }, function(response) {
                            if (response.success) {
                                alert('Verbinding succesvol!');
                            } else {
                                alert('Verbinding mislukt: ' + (response.data || 'Onbekende fout'));
                            }
                        }).fail(function() {
                            alert('Netwerkfout bij het testen van de verbinding.');
                        }).always(function() {
                            $button.prop('disabled', false);
                            $spinner.removeClass('is-active');
                        });
                    });
                }

                handleConnectionTest('.test-comfyui-connection', 'test_comfyui_connection', '#ai_blogpost_comfyui_api_url');
                handleConnectionTest('.test-lm-connection', 'test_lm_studio', '#ai_blogpost_lm_api_url');

                // Refresh models
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
                            alert('Mislukt om modellen op te halen. Controleer je API-sleutel.');
                        }
                    }).always(function() {
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    });
                });
            });
        </script>
    </div>
    <?php
}

/**
 * Display general settings section
 */
function display_general_settings() {
    ?>
    <table class="form-table">
        <tr>
            <th><label for="ai_blogpost_language">Content Taal</label></th>
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
                <p class="description">Kies de taal voor gegenereerde content.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_post_frequency">Postfrequentie</label></th>
            <td>
                <select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">
                    <?php $frequency = get_cached_option('ai_blogpost_post_frequency', 'daily'); ?>
                    <option value="daily" <?php selected($frequency, 'daily'); ?>>Dagelijks</option>
                    <option value="weekly" <?php selected($frequency, 'weekly'); ?>>Wekelijks</option>
                </select>
                <p class="description">Hoe vaak moeten nieuwe posts worden gegenereerd?</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_custom_categories">Categorieën</label></th>
            <td>
                <textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_custom_categories', 'tarot')); ?></textarea>
                <p class="description">Voer categorieën in (één per regel) voor postgeneratie.</p>
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
            <th><label for="ai_blogpost_api_key">OpenAI API Sleutel</label></th>
            <td>
                <input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_api_key')); ?>">
                <p class="description">Jouw OpenAI API-sleutel voor tekstgeneratie.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_model">GPT Model</label></th>
            <td>
                <?php display_model_dropdown('gpt'); ?>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_temperature">Temperatuur</label></th>
            <td>
                <input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai<|control467|>

_blogpost_temperature" value="<?php echo esc_attr(get_cached_option('ai_blogpost_temperature', '0.7')); ?>">
                <p class="description">Controleert willekeur (0 = deterministisch, 2 = zeer willekeurig).</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>
            <td>
                <input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="<?php echo esc_attr(get_cached_option('ai_blogpost_max_tokens', '2048')); ?>" min="100" max="4096">
                <p class="description">Maximale lengte van gegenereerde tekst (max 4096).</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_role">Systeemrol</label></th>
            <td>
                <textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_role', 'You are a professional blog writer. Write engaging, SEO-friendly content about the given topic.')); ?></textarea>
                <p class="description">Definieer de rol en schrijfstijl van de AI.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_prompt">Content Sjabloon</label></th>
            <td>
                <textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="4" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_prompt', "Write a blog post about [topic]. Structure the content as follows:\n\n||Title||: Create an engaging, SEO-friendly title\n\n||Content||: Write the main content here, using proper HTML structure:\n- Use <article> tags to wrap the content\n- Use <h1>, <h2> for headings\n- Use <p> for paragraphs\n- Include relevant subheadings\n- Add a strong conclusion\n\n||Category||: Suggest the most appropriate category for this post")); ?></textarea>
                <div class="template-guide">
                    <h4>Content Structuur Gids</h4>
                    <p class="description">Gebruik deze sectiemarkeringen om de content te structureren:</p>
                    <ul>
                        <li><code>||Title||:</code> - De titel van het blogbericht</li>
                        <li><code>||Content||:</code> - De hoofdinhoud (in <code><article></code> tags)</li>
                        <li><code>||Category||:</code> - De voorgestelde categorie</li>
                    </ul>
                </div>
            </td>
        </tr>
        <?php add_refresh_models_button(); ?>
        <tr>
            <th colspan="2"><h3>LM Studio Integratie</h3></th>
        </tr>
        <tr>
            <th><label for="ai_blogpost_lm_enabled">LM Studio Inschakelen</label></th>
            <td>
                <input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" <?php checked(get_cached_option('ai_blogpost_lm_enabled', 0), 1); ?>>
                <p class="description">Schakel lokale LM Studio integratie in.</p>
            </td>
        </tr>
        <tr>
            <th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>
            <td>
                <input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')); ?>">
                <button type="button" class="button test-lm-connection">Test Verbinding</button>
                <span class="spinner"></span>
                <p class="description">Meestal http://localhost:1234/v1</p>
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
        <div class="generation-type-options" style="display: flex; gap: 20px;">
            <div class="generation-option" style="<?php echo $generation_type === 'dalle' ? 'background: #f0f7ff; border-color: #2271b1;' : ''; ?>">
                <label>
                    <input type="radio" name="ai_blogpost_image_generation_type" value="dalle" <?php checked($generation_type, 'dalle'); ?>>
                    <strong>DALL-E</strong>
                </label>
                <p class="description">Gebruik OpenAI's DALL-E voor afbeeldingsgeneratie.</p>
            </div>
            <div class="generation-option" style="<?php echo $generation_type === 'comfyui' ? 'background: #f0f7ff; border-color: #2271b1;' : ''; ?>">
                <label>
                    <input type="radio" name="ai_blogpost_image_generation_type" value="comfyui" <?php checked($generation_type, 'comfyui'); ?>>
                    <strong>ComfyUI</strong>
                </label>
                <p class="description">Gebruik lokale ComfyUI voor afbeeldingsgeneratie.</p>
            </div>
        </div>
    </div>

    <!-- DALL-E Settings -->
    <div class="dalle-settings" style="display: <?php echo $generation_type === 'dalle' ? 'block' : 'none'; ?>;">
        <h3>DALL-E Instellingen</h3>
        <table class="form-table">
            <tr>
                <th><label for="ai_blogpost_dalle_api_key">API Sleutel</label></th>
                <td>
                    <input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_dalle_api_key')); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_model">Model</label></th>
                <td>
                    <?php display_model_dropdown('dalle'); ?>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_size">Grootte</label></th>
                <td>
                    <select name="ai_blogpost_dalle_size" id="ai_blogpost_dalle_size">
                        <?php
                        $size = get_cached_option('ai_blogpost_dalle_size', '1024x1024');
                        $sizes = ['1024x1024', '1792x1024', '1024x1792'];
                        foreach ($sizes as $s) {
                            echo '<option value="' . esc_attr($s) . '" ' . selected($size, $s, false) . '>' . esc_html($s) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_style">Stijl</label></th>
                <td>
                    <select name="ai_blogpost_dalle_style" id="ai_blogpost_dalle_style">
                        <?php
                        $style = get_cached_option('ai_blogpost_dalle_style', 'vivid');
                        $styles = ['vivid' => 'Levendig', 'natural' => 'Natuurlijk'];
                        foreach ($styles as $value => $label) {
                            echo '<option value="' . esc_attr($value) . '" ' . selected($style, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_quality">Kwaliteit</label></th>
                <td>
                    <select name="ai_blogpost_dalle_quality" id="ai_blogpost_dalle_quality">
                        <?php
                        $quality = get_cached_option('ai_blogpost_dalle_quality', 'standard');
                        $qualities = ['standard' => 'Standaard', 'hd' => 'HD'];
                        foreach ($qualities as $value => $label) {
                            echo '<option value="' . esc_attr($value) . '" ' . selected($quality, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_dalle_prompt_template">Prompt Sjabloon</label></th>
                <td>
                    <textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code"><?php echo esc_textarea(get_cached_option('ai_blogpost_dalle_prompt_template', 'A professional blog header image for [category], modern style, clean design, subtle symbolism')); ?></textarea>
                    <p class="description">Gebruik [category] als placeholder.</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- ComfyUI Settings -->
    ?>
    <div class="comfyui-settings" style="display: <?php echo $generation_type === 'comfyui' ? 'block' : 'none'; ?>;">
        <h3>ComfyUI Instellingen</h3>
        <table class="form-table">
            <tr>
                <th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>
                <td>
                    <input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="<?php echo esc_attr(get_cached_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188')); ?>">
                    <button type="button" class="button test-comfyui-connection">Test Verbinding</button>
                    <span class="spinner"></span>
                    <p class="description">Meestal http://localhost:8188</p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_blogpost_comfyui_workflow_json">Workflow JSON</label></th>
                <td>
                    <?php
                    $default_workflow_json = get_option('ai_blogpost_comfyui_workflow_json', json_encode(json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../workflows/default.json'), true), JSON_PRETTY_PRINT));
                    ?>
                    <textarea name="ai_blogpost_comfyui_workflow_json" id="ai_blogpost_comfyui_workflow_json" rows="10" class="large-text code"><?php echo esc_textarea($default_workflow_json); ?></textarea>
                    <p class="description">Plak hier je ComfyUI workflow JSON. Standaard is 'default.json' ingevuld.</p>
                </td>
            </tr>
            <?php
            $workflows = get_option('ai_blogpost_comfyui_workflows', []);
            if (!empty($workflows)) {
                // Bestaande workflow dropdown code hier...
            } else {
                echo '<tr><td colspan="2"><p class="description">Geen workflows geüpload. Gebruik het JSON-veld hierboven om een workflow toe te voegen.</p></td></tr>';
            }
            ?>
        </table>
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
        echo '<p class="description">Sla de API-sleutel op om beschikbare modellen op te halen.</p>';
    }
}

/**
 * Add refresh models button to settings
 */
function add_refresh_models_button() {
    ?>
    <tr>
        <th>Beschikbare Modellen</th>
        <td>
            <button type="button" class="button" id="refresh-models">Vernieuw Beschikbare Modellen</button>
            <span class="spinner"></span>
            <p class="description">Klik om beschikbare modellen van OpenAI op te halen.</p>
        </td>
    </tr>
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
        $response = wp_remote_get($api_url . '/models', [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['data']) && !is_array($data)) {
            wp_send_json_error('Geen modellen gevonden in LM Studio.');
            return;
        }

        update_option('ai_blogpost_available_lm_models', $data['data'] ?? $data);
        update_option('ai_blogpost_lm_api_url', rtrim($api_url, '/v1'));
        wp_send_json_success(['message' => 'Connection successful']);
    } catch (Exception $e) {
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
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
            return;
        }

        $history_response = wp_remote_get($api_url . '/history', ['timeout' => 30, 'sslverify' => false]);
        if (is_wp_error($history_response)) {
            wp_send_json_error('Failed to access history endpoint');
            return;
        }

        update_option('ai_blogpost_comfyui_api_url', $api_url);
        wp_send_json_success(['message' => 'Connection successful']);
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_test_comfyui_connection', 'handle_comfyui_test');