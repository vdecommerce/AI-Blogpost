<?php
/*
Plugin Name: AI-Generated Tarot Blogpost with DALL·E
Description: Generates a daily/weekly blog post with a DALL·E image. The DALL·E prompt comes from the text output, and the DALL·E model can be configured in the dashboard.
Version: 1.0
Author: MrHerrie
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Include plugin files from the inc/ directory
require_once plugin_dir_path(__FILE__) . 'inc/helpers.php';
require_once plugin_dir_path(__FILE__) . 'inc/settings.php';
require_once plugin_dir_path(__FILE__) . 'inc/ai-api.php';
require_once plugin_dir_path(__FILE__) . 'inc/post-creation.php';
require_once plugin_dir_path(__FILE__) . 'inc/cron.php';
require_once plugin_dir_path(__FILE__) . 'inc/logs.php';

// Register deactivation hook
register_deactivation_hook(__FILE__, 'ai_blogpost_deactivation');

// Add cron hook for post creation
add_action('ai_blogpost_cron_hook', 'create_ai_blogpost');

// Schedule cron on plugin activation
register_activation_hook(__FILE__, 'ai_blogpost_schedule_cron');
