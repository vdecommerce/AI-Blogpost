<?php declare(strict_types=1);
/*
Plugin Name: AI-Generated Blogpost with DALL·E
Description: Automates blog post creation with AI-generated content and images.
Version: 1.1
Author: MrHerrie
*/

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

// Set up constants
define('AI_BLOGPOST_VERSION', '1.1');
define('AI_BLOGPOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_BLOGPOST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes from inc/ directory
spl_autoload_register(function (string $class): void {
    // Project-specific namespace prefix
    $prefix = 'AI_Blogpost\\';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Convert class name to file path
    $file = AI_BLOGPOST_PLUGIN_DIR . 'inc/' . str_replace('\\', '/', strtolower($relative_class)) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize plugin functionality
class Plugin {
    private static ?Plugin $instance = null;
    
    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeHooks();
    }
    
    private function initializeHooks(): void {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, ['\AI_Blogpost\Cron', 'schedule']);
        register_deactivation_hook(__FILE__, ['\AI_Blogpost\Cron', 'deactivate']);
        
        // Register cron action
        add_action('ai_blogpost_cron_hook', ['\AI_Blogpost\PostCreation', 'create']);
        
        // Initialize admin functionality if in admin area
        if (is_admin()) {
            add_action('admin_menu', ['\AI_Blogpost\Settings', 'initializeMenu']);
            add_action('admin_enqueue_scripts', ['\AI_Blogpost\Settings', 'enqueueAssets']);
        }
    }
}

// Initialize the plugin
Plugin::getInstance();
