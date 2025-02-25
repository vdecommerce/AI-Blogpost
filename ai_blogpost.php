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
            
            // Add fallback for dashboard issues
            add_action('admin_notices', [$this, 'checkDashboardFunctionality']);
        }
    }
    
    /**
     * Check if dashboard is functioning properly and provide fallback if needed
     */
    public function checkDashboardFunctionality(): void {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_ai_blogpost') {
            // Check if assets directory exists
            if (!is_dir(plugin_dir_path(__FILE__) . 'assets')) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>AI Blogpost Error:</strong> Assets directory not found. Using fallback interface.</p>';
                echo '<p>Please ensure the plugin is installed correctly with all required files.</p>';
                echo '</div>';
                
                // Add inline styles for fallback
                echo '<style>
                    .ai-blogpost-fallback {
                        margin: 20px 0;
                        padding: 20px;
                        background: #fff;
                        border: 1px solid #ccd0d4;
                        box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    }
                    .ai-blogpost-fallback h2 {
                        margin-top: 0;
                    }
                </style>';
                
                // Add fallback JavaScript
                echo '<script>
                    jQuery(document).ready(function($) {
                        console.log("AI Blogpost: Using fallback interface");
                    });
                </script>';
            }
        }
    }
}

// Initialize the plugin
Plugin::getInstance();
