<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost_Cron {
    public function init() {
        add_action('ai_blogpost_cron_hook', array($this, 'execute'));
    }

    public static function activate() {
        // Implementation moved from original code
        // Sets up cron schedule
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('ai_blogpost_cron_hook');
        delete_option('ai_blogpost_is_scheduled');
    }

    public function execute() {
        $plugin = new AI_Blogpost();
        $plugin->create_post();
    }
}