<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Helper functions and utilities
 */
class Helpers {
    private static array $optionCache = [];
    
    /**
     * Get an option value from cache or database
     * @param string $option_name
     * @param mixed $default
     * @return mixed
     */
    public static function getCachedOption(string $option_name, $default = '') {
        if (!isset(self::$optionCache[$option_name])) {
            self::$optionCache[$option_name] = get_option($option_name, $default);
        }
        
        return self::$optionCache[$option_name];
    }
    
    /**
     * Clear the options cache
     */
    public static function clearCache(): void {
        self::$optionCache = [];
    }
    
    /**
     * Clear cache after saving settings
     */
    public static function afterSaveSettings(): void {
        if (isset($_POST['option_page']) && $_POST['option_page'] === 'ai_blogpost_settings') {
            self::clearCache();
        }
    }
    
    /**
     * Initialize helper functionality
     */
    public static function initialize(): void {
        add_action('admin_init', [self::class, 'afterSaveSettings'], 99);
    }
}

/**
 * Language handling functionality
 */
class LanguageHandler {
    private const INSTRUCTIONS = [
        'en' => 'Write all content in English.',
        'nl' => 'Schrijf alle content in het Nederlands.',
        'de' => 'Schreiben Sie den gesamten Inhalt auf Deutsch.',
        'fr' => 'Écrivez tout le contenu en français.',
        'es' => 'Escribe todo el contenido en español.'
    ];
    
    /**
     * Get language-specific instruction for AI prompts
     */
    public static function getInstruction(string $language_code): string {
        return self::INSTRUCTIONS[$language_code] ?? self::INSTRUCTIONS['en'];
    }
    
    /**
     * Get available languages
     */
    public static function getAvailableLanguages(): array {
        return array_keys(self::INSTRUCTIONS);
    }
}

// Initialize helper functionality
Helpers::initialize();
