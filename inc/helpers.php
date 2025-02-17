<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Global cache variable
global $ai_blogpost_option_cache;
if (!isset($ai_blogpost_option_cache)) {
    $ai_blogpost_option_cache = array();
}

/**
 * Get an option value from cache or database
 * 
 * @param string $option_name The option name
 * @param mixed $default Default value if option doesn't exist
 * @return mixed The option value
 */
function get_cached_option($option_name, $default = '') {
    global $ai_blogpost_option_cache;
    
    if (!isset($ai_blogpost_option_cache[$option_name])) {
        $ai_blogpost_option_cache[$option_name] = get_option($option_name, $default);
    }
    
    return $ai_blogpost_option_cache[$option_name];
}

/**
 * Clear the options cache
 */
function clear_ai_blogpost_cache() {
    global $ai_blogpost_option_cache;
    $ai_blogpost_option_cache = array();
}

/**
 * Clear cache after saving settings
 */
function ai_blogpost_after_save_settings() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'ai_blogpost_settings') {
        clear_ai_blogpost_cache();
    }
}
add_action('admin_init', 'ai_blogpost_after_save_settings', 99);

/**
 * Get language-specific instruction for AI prompts
 * 
 * @param string $language_code The language code
 * @return string The instruction in the specified language
 */
function get_language_instruction($language_code) {
    $instructions = [
        'en' => 'Write all content in English.',
        'nl' => 'Schrijf alle content in het Nederlands.',
        'de' => 'Schreiben Sie den gesamten Inhalt auf Deutsch.',
        'fr' => 'Écrivez tout le contenu en français.',
        'es' => 'Escribe todo el contenido en español.'
    ];
    
    return $instructions[$language_code] ?? $instructions['en'];
}
