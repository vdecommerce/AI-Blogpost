<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register plugin-instellingen
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

    // Afbeelding generatie instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_image_generation_type');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');

    // Taal instelling
    register_setting('ai_blogpost_settings', 'ai_blogpost_language');

    // DALL·E instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_enabled');

    // ComfyUI instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_workflows');
    register_setting('ai_blogpost_settings', 'ai_blogpost_comfyui_default_workflow');

    // LM Studio instellingen
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_url');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_lm_model');
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
    // Zorg dat je de bestanden plaatst in de map assets/css en assets/js
    wp_enqueue_style('ai-blogpost-admin', plugin_dir_url(__FILE__) . '../assets/css/admin.css');
    wp_enqueue_script('ai-blogpost-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'ai_blogpost_admin_enqueue_scripts');

/**
 * Toon de admin-instellingenpagina met tabbladen
 */
function ai_blogpost_admin_page() {
    ?>
    <div class="wrap ai-blogpost-dashboard">
        <h1>AI Blogpost Dashboard</h1>
        <div id="ai-blogpost-tabs">
            <ul>
                <li><a href="#tab-test">Test Generatie</a></li>
                <li><a href="#tab-settings">Instellingen</a></li>
                <li><a href="#tab-status">Status</a></li>
            </ul>
            <div id="tab-test" class="tab-content">
                <h2>Test Generatie</h2>
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
            <div id="tab-settings" class="tab-content">
                <h2>Instellingen</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('ai_blogpost_settings'); ?>
                    <?php do_settings_sections('ai_blogpost_settings'); ?>
                    <div class="settings-sections">
                        <h3>Algemene Instellingen</h3>
                        <?php display_general_settings(); ?>
                        <h3>Tekst Generatie</h3>
                        <?php display_text_settings(); ?>
                        <h3>Afbeelding Generatie</h3>
                        <?php display_image_settings(); ?>
                    </div>
                    <?php submit_button('Opslaan'); ?>
                </form>
            </div>
            <div id="tab-status" class="tab-content">
                <h2>Generatie Status</h2>
                <form method="post" id="ai-blogpost-clear-logs">
                    <?php wp_nonce_field('clear_ai_logs_nonce'); ?>
                    <input type="submit" name="clear_ai_logs" class="button" value="Logs Wissen">
                    <button type="button" class="button" onclick="location.reload();">Ververs Status</button>
                </form>
                <h3>Tekst Generatie Logs</h3>
                <?php display_api_logs('Text Generation'); ?>
                <h3>Afbeelding Generatie Logs</h3>
                <?php display_api_logs('Image Generation'); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Helper-functie: toon testbericht-notice indien aanwezig
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
 * De volgende functies worden aangeroepen in de settingspagina.
 * Zorg dat je de originele implementaties behoudt of hieraan aanpast.
 */
function display_general_settings() {
    // Voorbeeld van een veld voor taalselectie en postfrequentie
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

    // Extra velden, zoals custom categories, kunnen hier worden toegevoegd.
    echo '</table>';
}

function display_text_settings() {
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" value="' . esc_attr(get_option('ai_blogpost_api_key')) . '">';
    echo '<p class="description">Voer je OpenAI API key in voor tekstgeneratie</p>';
    echo '</td>';
    echo '</tr>';
    // Overige velden (model selectie, temperature, max tokens, etc.) volgen hier...
    echo '</table>';

    // Voeg hier ook de refresh-knop toe voor het ophalen van modellen.
}

function display_image_settings() {
    echo '<div class="settings-section">';
    echo '<h2>Featured Image Generation</h2>';
    
    // Selectie voor het type afbeelding generatie
    echo '<div class="image-generation-selector">';
    $generation_type = get_option('ai_blogpost_image_generation_type', 'dalle');
    echo '<div class="generation-type-options">';
    
    // DALL·E optie
    echo '<div class="generation-option ' . ($generation_type === 'dalle' ? 'active' : '') . '">';
    echo '<label><input type="radio" name="ai_blogpost_image_generation_type" value="dalle" ' . checked($generation_type, 'dalle', false) . '> <strong>DALL·E</strong></label>';
    echo '<p class="description">Gebruik OpenAI’s DALL·E voor AI-afbeeldingsgeneratie</p>';
    echo '</div>';
    
    // ComfyUI optie
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
    // Overige DALL·E instellingen (model, prompt template, etc.) volgen hier...
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
    // Extra ComfyUI velden kunnen hier volgen...
    echo '</table>';
    echo '</div>';

    echo '</div>';
}

/**
 * AJAX handlers voor refresh-modellen en verbindingstests blijven ongewijzigd.
 */
