<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Handles all cron-related functionality
 */
class Cron {
    private const CRON_HOOK = 'ai_blogpost_cron_hook';
    private const SCHEDULE_OPTION = 'ai_blogpost_is_scheduled';
    private const FREQUENCY_OPTION = 'ai_blogpost_post_frequency';
    
    /**
     * Schedule the cron job based on frequency settings
     */
    public static function schedule(): void {
        $frequency = get_option(self::FREQUENCY_OPTION, 'daily');
        
        // Clear existing schedule
        wp_clear_scheduled_hook(self::CRON_HOOK);
        
        // Add weekly schedule if needed
        if ($frequency === 'weekly') {
            add_filter('cron_schedules', [self::class, 'addWeeklySchedule']);
        }
        
        // Schedule new cron based on current frequency
        $next_run = $frequency === 'weekly' 
            ? strtotime('next monday 00:00:00') 
            : strtotime('tomorrow 00:00:00');
            
        wp_schedule_event($next_run, $frequency, self::CRON_HOOK);
        
        update_option(self::SCHEDULE_OPTION, true);
        
        Logs::debug('Cron schedule updated:', [
            'frequency' => $frequency,
            'next_run' => wp_next_scheduled(self::CRON_HOOK)
        ]);
    }
    
    /**
     * Add weekly interval to WordPress cron schedules
     */
    public static function addWeeklySchedule(array $schedules): array {
        $schedules['weekly'] = [
            'interval' => 7 * 24 * 60 * 60,
            'display' => __('Once Weekly')
        ];
        return $schedules;
    }
    
    /**
     * Handle frequency changes in settings
     */
    public static function handleFrequencyChange(): void {
        if (!isset($_POST['option_page'], $_POST['ai_blogpost_post_frequency']) 
            || $_POST['option_page'] !== 'ai_blogpost_settings') {
            return;
        }
        
        $new_frequency = sanitize_text_field($_POST['ai_blogpost_post_frequency']);
        $old_frequency = get_option(self::FREQUENCY_OPTION, 'daily');
        
        if ($old_frequency === $new_frequency) {
            return;
        }
        
        // Update the frequency option
        update_option(self::FREQUENCY_OPTION, $new_frequency);
        
        // Reschedule cron
        self::schedule();
        
        // Show admin notice
        add_action('admin_notices', function(): void {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Post frequency updated. Page will refresh to show new schedule.</p>';
            echo '</div>';
            echo '<script>setTimeout(function() { location.reload(); }, 1500);</script>';
        });
        
        Logs::debug('Cron frequency changed:', [
            'old_frequency' => $old_frequency,
            'new_frequency' => $new_frequency,
            'next_run' => wp_next_scheduled(self::CRON_HOOK)
        ]);
    }
    
    /**
     * Plugin deactivation cleanup
     */
    public static function deactivate(): void {
        wp_clear_scheduled_hook(self::CRON_HOOK);
        delete_option(self::SCHEDULE_OPTION);
        Logs::debug('Plugin deactivated, cron schedule cleared');
    }
    
    /**
     * Check and log cron status if debug is enabled
     */
    public static function checkStatus(): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        $frequency = get_option(self::FREQUENCY_OPTION, 'daily');
        
        Logs::debug('AI Blogpost Cron Status:', [
            'Frequency' => $frequency,
            'Next Run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled'
        ]);
    }
    
    /**
     * Initialize cron functionality
     */
    public static function initialize(): void {
        add_action('admin_init', [self::class, 'handleFrequencyChange'], 100);
        add_action('admin_init', [self::class, 'checkStatus']);
    }
}

// Initialize cron functionality
Cron::initialize();
