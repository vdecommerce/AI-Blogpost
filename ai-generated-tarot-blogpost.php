<?php
/*
Plugin Name: AI-Generated Tarot Blogpost with DALL·E
Description: Generates a daily/weekly blog post with a DALL·E image. The DALL·E prompt comes from the text output, and the DALL·E model can be configured in the dashboard. 
Version: 1.0
Author: MrHerrie
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_BLOGPOST_VERSION', '1.0.0');
define('AI_BLOGPOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_BLOGPOST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require core files
require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/class-ai-blogpost.php';
require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/class-ai-blogpost-settings.php';
require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/class-ai-blogpost-cron.php';
require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/class-ai-blogpost-openai.php';
require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/class-ai-blogpost-lm-studio.php';
require_once AI_BLOGPOST_PLUGIN_DIR . 'includes/class-ai-blogpost-logger.php';

// Initialize the plugin
function ai_blogpost_init() {
    $plugin = new AI_Blogpost();
    $plugin->init();
}
add_action('plugins_loaded', 'ai_blogpost_init');

// Activation hook
register_activation_hook(__FILE__, array('AI_Blogpost_Cron', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('AI_Blogpost_Cron', 'deactivate'));