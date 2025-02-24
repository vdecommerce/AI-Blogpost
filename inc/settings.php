<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Registreer alle instellingen
 */
function ai_blogpost_initialize_settings() {
    // Algemene instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_language');
    register_setting('ai_blogpost_settings', 'ai_blogpost_post_frequency');
    register_setting('ai_blogpost_settings', 'ai_blogpost_custom_categories');

    // Tekst generatie instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_temperature');
    register_setting('ai_blogpost_settings', 'ai_blogpost_max_tokens');
    register_setting('ai_blogpost_settings', 'ai_blogpost_prompt');
    register_setting('ai_blogpost_settings', 'ai_blogpost_role');

    // LM Studio instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_model');

    // Keuze AI-provider (default: OpenAI)
    register_setting('ai_blogpost_settings', 'ai_blogpost_ai_provider');

    // Afbeelding generatie instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_image_generation_type');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');

    // ComfyUI instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_workflows');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_default_workflow');
}
add_action('admin_init', 'ai_blogpost_initialize_settings');

/**
 * Voeg de admin-pagina toe aan het WordPress-menu
 */
function ai_blogpost_admin_menu() {
    add_menu_page('AI Blogpost Settings', 'AI Blogpost', 'manage_options', 'ai_blogpost', 'ai_blogpost_admin_page');
}
add_action('admin_menu', 'ai_blogpost_admin_menu');

/**
 * Enqueue externe CSS en JS voor de admin-UI
 */
function ai_blogpost_admin_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ai_blogpost') {
        return;
    }
    wp_enqueue_style('ai-blogpost-admin', plugin_dir_url(__FILE__) . '../assets/css/admin.css');
    wp_enqueue_script('ai-blogpost-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'ai_blogpost_admin_enqueue_scripts');

/**
 * Toon de admin-instellingenpagina:
 * - Testsectie (bovenaan)
 * - Instellingen-tabbladen (Algemeen, Tekst, Afbeelding) én een tab voor de logs
 * Alle velden staan in één formulier zodat het opslaan weer werkt.
 */
function ai_blogpost_admin_page() {
    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1>AI Blogpost Dashboard</h1>
        
        <!-- Testsectie bovenaan -->
        <div class="test-section">
            <form method="post" id="ai-blogpost-test-form">
                <?php wp_nonce_field('ai_blogpost_test_nonce'); ?>
                <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Genereer Test Bericht">
            </form>
            <?php ai_blogpost_display_test_notice(); ?>
            <?php 
            $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
            if ($next_post_time) { ?>
                <div class="next-post-info">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    Volgende geplande post: <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $next_post_time), 'F j, Y @ H:i'); ?>
                </div>
            <?php } ?>
        </div>
        
        <!-- Instellingenformulier inclusief tabbladen -->
        <form method="post" action="options.php">
            <?php settings_fields('ai_blogpost_settings'); ?>
            <?php do_settings_sections('ai_blogpost_settings'); ?>
            
            <div id="ai-blogpost-tabs">
                <ul>
                    <li><a href="#tab-general">Algemene Instellingen</a></li>
                    <li><a href="#tab-text">Tekst Generatie Instellingen</a></li>
                    <li><a href="#tab-image">Afbeelding Generatie Instellingen</a></li>
                    <li><a href="#tab-logs">Logboeken</a></li>
                </ul>
                <div id="tab-general" class="tab-content">
                    <h2>Algemene Instellingen</h2>
                    <?php display_general_settings(); ?>
                </div>
                <div id="tab-text" class="tab-content">
                    <h2>Tekst Generatie Instellingen</h2>
                    <?php display_text_settings(); ?>
                </div>
                <div id="tab-image" class="tab-content">
                    <h2>Afbeelding Generatie Instellingen</h2>
                    <?php display_image_settings(); ?>
                </div>
                <div id="tab-logs" class="tab-content">
                    <h2>Logboeken</h2>
                    <div class="log-actions">
                        <form method="post" id="ai-blogpost-clear-logs">
                            <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                            <input type="submit" name="clear_ai_logs" class="button" value="Logs Wissen">
                            <button type="button" class="button" onclick="location.reload();">Ververs Logs</button>
                        </form>
                    </div>
                    <h3>Tekst Generatie Logs</h3>
                    <?php display_api_logs('Text Generation'); ?>
                    <h3>Afbeelding Generatie Logs</h3>
                    <?php display_api_logs('Image Generation'); ?>
                </div>
            </div>
            
            <?php submit_button('Opslaan'); ?>
        </form>
    </div>
    <?php
}

/**
 * Toon testbericht-notice indien aanwezig
 */
function ai_blogpost_display_test_notice() {
    $notice_type = get_transient('ai_blogpost_test_notice');
    if ($notice_type) {
        delete_transient('ai_blogpost_test_notice');
        if ($notice_type === 'success') {
            echo '<div class="updated notice is-dismissible"><p>Test Bericht is succesvol aangemaakt!</p></div>';
        } else {
            echo '<div class="error notice is-dismissible"><p>Test Bericht aanmaken mislukt. Controleer de foutlogboeken.</p></div>';
        }
    }
}

/**
 * Algemene Instellingen: taal, post frequentie, categorieën
 */
function display_general_settings() {
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_language">Content Language</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_language" id="ai_blogpost_language">';
    $language = get_option('ai_blogpost_language', 'en');
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
    echo '<p class="description">Selecteer de taal voor de gegenereerde content</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">';
    $frequency = get_option('ai_blogpost_post_frequency', 'daily');
    echo '<option value="daily" ' . selected($frequency, 'daily', false) . '>Dagelijks</option>';
    echo '<option value="weekly" ' . selected($frequency, 'weekly', false) . '>Wekelijks</option>';
    echo '</select>';
    echo '<p class="description">Hoe vaak moeten er nieuwe posts worden gegenereerd?</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th><label for="ai_blogpost_custom_categories">Categorieën</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code">' . esc_textarea(get_option('ai_blogpost_custom_categories', '')) . '</textarea>';
    echo '<p class="description">Geef per regel een categorie op</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

/**
 * Tekst Generatie Instellingen: keuze AI-provider en bijbehorende velden
 */
function display_text_settings() {
    echo '<table class="form-table">';
    // AI Provider selectie
    echo '<tr>';
    echo '<th><label>AI Provider</label></th>';
    echo '<td>';
    $ai_provider = get_option('ai_blogpost_ai_provider', 'openai');
    echo '<label><input type="radio" name="ai_blogpost_ai_provider" value="openai" ' . checked($ai_provider, 'openai', false) . '> OpenAI</label> &nbsp;';
    echo '<label><input type="radio" name="ai_blogpost_ai_provider" value="lm_studio" ' . checked($ai_provider, 'lm_studio', false) . '> LM Studio</label>';
    echo '<p class="description">Selecteer de AI-provider voor tekstgeneratie.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    // OpenAI instellingen (zichtbaar als OpenAI geselecteerd is)
    echo '<div class="openai-settings" style="display:' . ($ai_provider === 'openai' ? 'block' : 'none') . ';">';
    echo '<h4>OpenAI Instellingen</h4>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="' . esc_attr(get_option('ai_blogpost_api_key')) . '">';
    echo '<p class="description">Voer je OpenAI API key in voor tekstgeneratie</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_model">GPT Model</label></th>';
    echo '<td>';
    display_model_dropdown('gpt');
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
    echo '<td>';
    echo '<input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" value="' . esc_attr(get_option('ai_blogpost_temperature', '0.7')) . '">';
    echo '<p class="description">Bepaalt de creativiteit van de gegenereerde tekst</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>';
    echo '<td>';
    echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="' . esc_attr(get_option('ai_blogpost_max_tokens', '2048')) . '" min="100" max="4096">';
    echo '<p class="description">Maximale lengte van de gegenereerde tekst</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_prompt">Content Template</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="4" class="large-text code">' . esc_textarea(get_option('ai_blogpost_prompt', "Schrijf een SEO-blogpost over [topic]. Gebruik de volgende structuur:\n\n||Title||: ...\n\n||Content||: ...\n\n||Category||: ...")) . '</textarea>';
    echo '<p class="description">Template voor de gegenereerde content</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_role">System Role</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">' . esc_textarea(get_option('ai_blogpost_role', "You are a professional blog writer. Write engaging, SEO-friendly content.")) . '</textarea>';
    echo '<p class="description">Bepaal de rol van de AI</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>Beschikbare Modellen</th>';
    echo '<td>';
    echo '<button type="button" class="button" id="refresh-models">Refresh Modellen</button>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">Klik om beschikbare modellen op te halen van OpenAI</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';

    // LM Studio instellingen (zichtbaar als LM Studio geselecteerd is)
    echo '<div class="lm-studio-settings" style="display:' . ($ai_provider === 'lm_studio' ? 'block' : 'none') . ';">';
    echo '<h4>LM Studio Instellingen</h4>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
    echo '<td>';
    echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . checked(get_option('ai_blogpost_lm_enabled', 0), 1, false) . '>';
    echo '<p class="description">Schakel LM Studio in voor tekstgeneratie</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
    echo '<td>';
    echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" value="' . esc_attr(get_option('ai_blogpost_lm_api_url', 'http://localhost:1234/v1')) . '">';
    echo '<button type="button" class="button test-lm-studio-connection">Test Verbinding</button>';
    echo '<span class="spinner" style="float: none; margin-left: 4px;"></span>';
    echo '<p class="description">Bijv. http://localhost:1234/v1</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_api_key">LM Studio API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_lm_api_key" id="ai_blogpost_lm_api_key" class="regular-text" value="' . esc_attr(get_option('ai_blogpost_lm_api_key')) . '">';
    echo '<p class="description">Voer je LM Studio API key in (indien van toepassing)</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_lm_model">LM Studio Model</label></th>';
    echo '<td>';
    display_model_dropdown('lm_studio');
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';
}

/**
 * Afbeelding Generatie Instellingen
 */
function display_image_settings() {
    echo '<div class="settings-section">';
    echo '<h2>Featured Image Generation</h2>';
    
    // Selectie voor afbeelding generatie type
    echo '<div class="image-generation-selector">';
    $generation_type = get_option('ai_blogpost_image_generation_type', 'dalle');
    echo '<div class="generation-type-options">';
    echo '<div class="generation-option ' . ($generation_type === 'dalle' ? 'active' : '') . '">';
    echo '<label><input type="radio" name="ai_blogpost_image_generation_type" value="dalle" ' . checked($generation_type, 'dalle', false) . '> <strong>DALL·E</strong></label>';
    echo '<p class="description">Gebruik OpenAI’s DALL·E voor AI-afbeeldingsgeneratie</p>';
    echo '</div>';
    echo '<div class="generation-option ' . ($generation_type === 'comfyui' ? 'active' : '') . '">';
    echo '<label><input type="radio" name="ai_blogpost_image_generation_type" value="comfyui" ' . checked($generation_type, 'comfyui', false) . '> <strong>ComfyUI</strong></label>';
    echo '<p class="description">Gebruik lokale ComfyUI voor geavanceerde afbeelding generatie</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // DALL·E instellingen
    echo '<div class="dalle-settings" style="display:' . ($generation_type === 'dalle' ? 'block' : 'none') . ';">';
    echo '<h3>DALL·E Instellingen</h3>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_api_key">API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" value="' . esc_attr(get_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<p class="description">Voer hier je DALL·E API key in</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_model">DALL·E Model</label></th>';
    echo '<td>';
    display_model_dropdown('dalle');
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
    echo '<td>';
    echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="3" class="large-text code">' . esc_textarea(get_option('ai_blogpost_dalle_prompt_template', 'A professional blog header image for [category], modern style, clean design, subtle symbolism')) . '</textarea>';
    echo '<p class="description">Gebruik [category] als placeholder voor de blogcategorie</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';

    // ComfyUI instellingen
    echo '<div class="comfyui-settings" style="display:' . ($generation_type === 'comfyui' ? 'block' : 'none') . ';">';
    echo '<h3>ComfyUI Instellingen</h3>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_comfyui_api_url">Server URL</label></th>';
    echo '<td>';
    echo '<input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" class="regular-text" value="' . esc_attr(get_option('ai_blogpost_comfyui_api_url', 'http://localhost:8188')) . '">';
    echo '<button type="button" class="button test-comfyui-connection">Test Verbinding</button>';
    echo '<span class="spinner"></span>';
    echo '<p class="description">Bijv. http://localhost:8188</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';

    echo '</div>';
}

/**
 * Helper: Toon model dropdown (voor OpenAI, DALL·E of LM Studio)
 */
function display_model_dropdown($type = 'gpt') {
    if ($type === 'gpt') {
        $stored_models = get_option('ai_blogpost_available_gpt_models', []);
        $current_model = get_option('ai_blogpost_model');
        $default_models = ['gpt-4', 'gpt-3.5-turbo'];
    } elseif ($type === 'dalle') {
        $stored_models = get_option('ai_blogpost_available_dalle_models', []);
        $current_model = get_option('ai_blogpost_dalle_model');
        $default_models = ['dall-e-3', 'dall-e-2'];
    } elseif ($type === 'lm_studio') {
        $stored_models = get_option('ai_blogpost_available_lm_models', []);
        $current_model = get_option('ai_blogpost_lm_model');
        $default_models = ['model.gguf'];
    } else {
        $stored_models = [];
        $current_model = '';
        $default_models = [];
    }
    $models = !empty($stored_models) ? $stored_models : $default_models;
    echo '<select name="ai_blogpost_' . ($type === 'gpt' ? 'model' : ($type === 'dalle' ? 'dalle_model' : 'lm_model')) . '">';
    foreach ($models as $model) {
        echo '<option value="' . esc_attr($model) . '" ' . selected($current_model, $model, false) . '>' . esc_html($model) . '</option>';
    }
    echo '</select>';
    if (empty($stored_models)) {
        echo '<p class="description">API key opslaan om beschikbare modellen op te halen</p>';
    }
}
?>
