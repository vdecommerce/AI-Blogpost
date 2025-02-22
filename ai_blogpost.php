<?php
/*
Plugin Name: AI-Generated Tarot Blogpost with DALL·E
Description: Generates a daily/weekly blog post with a DALL·E image. The DALL·E prompt comes from the text output, and the DALL·E model can be configured in the dashboard.
Version: 1.0
Author: MrHerrie
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'inc/helpers.php';
require_once plugin_dir_path(__FILE__) . 'inc/settings.php';
require_once plugin_dir_path(__FILE__) . 'inc/ai-api.php';
require_once plugin_dir_path(__FILE__) . 'inc/post-creation.php';
require_once plugin_dir_path(__FILE__) . 'inc/cron.php';
require_once plugin_dir_path(__FILE__) . 'inc/logs.php';

register_deactivation_hook(__FILE__, 'ai_blogpost_deactivation');
add_action('ai_blogpost_cron_hook', 'create_ai_blogpost');
register_activation_hook(__FILE__, 'ai_blogpost_schedule_cron');

add_action('wp_ajax_test_localai_connection', 'handle_localai_test');
add_action('wp_ajax_test_comfyui_connection', 'handle_comfyui_test');
add_action('wp_ajax_test_lm_studio', 'handle_lm_studio_test');

function ai_blogpost_handle_clear_logs() {
    if (isset($_POST['clear_ai_logs']) && check_admin_referer('clear_ai_logs_nonce')) {
        delete_option('ai_blogpost_api_logs');
        $active_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : 'logs';
        wp_redirect(admin_url('admin.php?page=ai_blogpost&tab=' . $active_tab . '&logs_cleared=1'));
        exit;
    }
}
add_action('admin_init', 'ai_blogpost_handle_clear_logs');

function ai_blogpost_redirect_after_save() {
    if (isset($_POST['option_page']) && in_array($_POST['option_page'], ['ai_blogpost_schedule_settings', 'ai_blogpost_text_settings', 'ai_blogpost_image_settings']) && !isset($_POST['test_ai_blogpost'])) {
        $active_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : 'schedule';
        wp_redirect(admin_url('admin.php?page=ai_blogpost&tab=' . $active_tab));
        exit;
    }
}
add_action('admin_init', 'ai_blogpost_redirect_after_save', 11);