<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost_Cron {
    private $logger;

    public function __construct() {
        $this->logger = new AI_Blogpost_Logger();
    }

    public function init() {
        add_action('ai_blogpost_cron_hook', array($this, 'execute'));
        add_action('admin_init', array($this, 'handle_frequency_change'), 100);
    }

    public static function activate() {
        $frequency = get_option('ai_blogpost_post_frequency', 'daily');
        
        if ($frequency === 'weekly') {
            add_filter('cron_schedules', array(__CLASS__, 'add_weekly_schedule'));
            $next_run = strtotime('next monday 00:00:00');
        } else {
            $next_run = strtotime('tomorrow 00:00:00');
        }
        
        wp_schedule_event($next_run, $frequency, 'ai_blogpost_cron_hook');
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    }

    public function execute() {
        try {
            $plugin = new AI_Blogpost();
            $plugin->create_post();
        } catch (Exception $e) {
            $this->logger->log('Cron execution failed:', $e->getMessage());
        }
    }

    public static function add_weekly_schedule($schedules) {
        $schedules['weekly'] = array(
            'interval' => 7 * 24 * 60 * 60,
            'display' => __('Once Weekly')
        );
        return $schedules;
    }
}