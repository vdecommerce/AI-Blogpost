<?php
if (!defined('ABSPATH')) exit;

function display_text_settings() {
    // Provider Selection
    echo '<tr>';
    echo '<th><label for="ai_blogpost_llm_provider">Text Generation Provider</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_llm_provider" id="ai_blogpost_llm_provider">';
    $provider = get_option('ai_blogpost_llm_provider', 'openai');
    echo '<option value="openai" ' . selected($provider, 'openai', false) . '>OpenAI</option>';
    echo '<option value="lmstudio" ' . selected($provider, 'lmstudio', false) . '>LM Studio (Local)</option>';
    echo '</select>';
    echo '<p class="description">Select which provider to use for text generation</p>';
    echo '</td>';
    echo '</tr>';

    // OpenAI Settings
    echo '<tr class="openai-settings provider-settings">';
    echo '<th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" class="regular-text" 
           value="' . esc_attr(get_option('ai_blogpost_api_key')) . '">';
    echo '<p class="description">Your OpenAI API key</p>';
    echo '</td>';
    echo '</tr>';

    // LM Studio Settings
    echo '<tr class="lmstudio-settings provider-settings">';
    echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
    echo '<td>';
    echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" class="regular-text" 
           value="' . esc_attr(get_option('ai_blogpost_lm_api_url', 'http://localhost:1234')) . '">';
    echo '<button type="button" class="button test-lm-connection">Test Connection</button>';
    echo '<p class="description">Usually http://localhost:1234</p>';
    echo '</td>';
    echo '</tr>';

    // Common Settings
    echo '<tr>';
    echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
    echo '<td>';
    echo '<input type="number" step="0.1" min="0" max="2" name="ai_blogpost_temperature" id="ai_blogpost_temperature" 
           value="' . esc_attr(get_option('ai_blogpost_temperature', '0.7')) . '">';
    echo '<p class="description">Controls randomness (0 = deterministic, 2 = very random)</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th><label for="ai_blogpost_language">Content Language</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_language" id="ai_blogpost_language">';
    $languages = array(
        'en' => 'English',
        'nl' => 'Nederlands',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español'
    );
    $selected_lang = get_option('ai_blogpost_language', 'en');
    foreach ($languages as $code => $name) {
        echo '<option value="' . esc_attr($code) . '" ' . selected($selected_lang, $code, false) . '>' 
             . esc_html($name) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Add JavaScript for toggling provider settings
    ?>
    <style>
        .provider-settings { display: none; }
        .provider-settings.active { display: table-row; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        function toggleProviderSettings() {
            var provider = $('#ai_blogpost_llm_provider').val();
            $('.provider-settings').removeClass('active').hide();
            $('.' + provider + '-settings').addClass('active').show();
        }
        $('#ai_blogpost_llm_provider').on('change', toggleProviderSettings);
        toggleProviderSettings();
    });
    </script>
    <?php
}

function display_image_settings() {
    // DALL-E Enable/Disable
    echo '<tr>';
    echo '<th><label for="ai_blogpost_dalle_enabled">Enable DALL-E</label></th>';
    echo '<td>';
    echo '<input type="checkbox" name="ai_blogpost_dalle_enabled" id="ai_blogpost_dalle_enabled" value="1" ' 
         . checked(get_option('ai_blogpost_dalle_enabled', 0), 1, false) . '>';
    echo '<p class="description">Enable DALL-E image generation for posts</p>';
    echo '</td>';
    echo '</tr>';

    // DALL-E API Key
    echo '<tr class="dalle-settings">';
    echo '<th><label for="ai_blogpost_dalle_api_key">DALL-E API Key</label></th>';
    echo '<td>';
    echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" class="regular-text" 
           value="' . esc_attr(get_option('ai_blogpost_dalle_api_key')) . '">';
    echo '<p class="description">Your OpenAI API key for DALL-E (can be same as text generation)</p>';
    echo '</td>';
    echo '</tr>';

    // DALL-E Size
    echo '<tr class="dalle-settings">';
    echo '<th><label for="ai_blogpost_dalle_size">Image Size</label></th>';
    echo '<td>';
    echo '<select name="ai_blogpost_dalle_size" id="ai_blogpost_dalle_size">';
    $sizes = array('1024x1024', '1024x1792', '1792x1024');
    $selected_size = get_option('ai_blogpost_dalle_size', '1024x1024');
    foreach ($sizes as $size) {
        echo '<option value="' . esc_attr($size) . '" ' . selected($selected_size, $size, false) . '>' 
             . esc_html($size) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Add JavaScript for DALL-E settings visibility
    ?>
    <script>
    jQuery(document).ready(function($) {
        function toggleDalleSettings() {
            var enabled = $('#ai_blogpost_dalle_enabled').is(':checked');
            $('.dalle-settings').toggle(enabled);
        }
        $('#ai_blogpost_dalle_enabled').on('change', toggleDalleSettings);
        toggleDalleSettings();
    });
    </script>
    <?php
}