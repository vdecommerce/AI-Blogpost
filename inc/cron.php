<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Schedule the cron job based on frequency settings
 */
function ai_blogpost_schedule_cron() {
    $frequency = get_option('ai_blogpost_post_frequency', 'daily');
    $is_scheduled = get_option('ai_blogpost_is_scheduled', false);
    
    // Clear existing schedule
    wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    
    // Add weekly schedule if needed
    if ($frequency == 'weekly') {
        add_filter('cron_schedules', 'ai_blogpost_weekly_schedule');
    }
    
    // Schedule new cron based on current frequency
    if ($frequency == 'daily') {
        wp_schedule_event(strtotime('tomorrow 00:00:00'), 'daily', 'ai_blogpost_cron_hook');
    } elseif ($frequency == 'weekly') {
        wp_schedule_event(strtotime('next monday 00:00:00'), 'weekly', 'ai_blogpost_cron_hook');
    }
    
    update_option('ai_blogpost_is_scheduled', true);
    
    ai_blogpost_debug_log('Cron schedule updated:', [
        'frequency' => $frequency,
        'next_run' => wp_next_scheduled('ai_blogpost_cron_hook')
    ]);
}

/**
 * Add weekly interval to WordPress cron schedules
 * 
 * @param array $schedules Existing cron schedules
 * @return array Modified cron schedules
 */
function ai_blogpost_weekly_schedule($schedules) {
    $schedules['weekly'] = array(
        'interval' => 7 * 24 * 60 * 60,
        'display' => __('Once Weekly')
    );
    return $schedules;
}

/**
 * Handle frequency changes in settings
 */
function ai_blogpost_handle_frequency_change() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'ai_blogpost_settings') {
        if (isset($_POST['ai_blogpost_post_frequency'])) {
            $new_frequency = sanitize_text_field($_POST['ai_blogpost_post_frequency']);
            $old_frequency = get_option('ai_blogpost_post_frequency', 'daily');
            
            if ($old_frequency !== $new_frequency) {
                // Update the frequency option
                update_option('ai_blogpost_post_frequency', $new_frequency);
                
                // Clear existing schedule
                wp_clear_scheduled_hook('ai_blogpost_cron_hook');
                
                // Set new schedule
                $next_run = ($new_frequency === 'weekly') 
                    ? strtotime('next monday 00:00:00') 
                    : strtotime('tomorrow 00:00:00');
                
                // Add weekly schedule if needed
                if ($new_frequency === 'weekly') {
                    add_filter('cron_schedules', 'ai_blogpost_weekly_schedule');
                    wp_schedule_event($next_run, 'weekly', 'ai_blogpost_cron_hook');
                } else {
                    wp_schedule_event($next_run, 'daily', 'ai_blogpost_cron_hook');
                }
                
                // Force page reload to show new schedule
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>Post frequency updated. Page will refresh to show new schedule.</p>';
                    echo '</div>';
                    echo '<script>setTimeout(function() { location.reload(); }, 1500);</script>';
                });
                
                ai_blogpost_debug_log('Cron schedule updated:', [
                    'old_frequency' => $old_frequency,
                    'new_frequency' => $new_frequency,
                    'next_run' => date('Y-m-d H:i:s', $next_run)
                ]);
            }
        }
    }
}

// Make sure this runs after settings are saved
add_action('admin_init', 'ai_blogpost_handle_frequency_change', 100);

/**
 * Plugin deactivation cleanup
 */
function ai_blogpost_deactivation() {
    wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    delete_option('ai_blogpost_is_scheduled');
    ai_blogpost_debug_log('Plugin deactivated, cron schedule cleared');
}
// Note: Deactivation hook is registered in the main plugin file

/**
 * Check cron status and log it if debug is enabled
 */
function ai_blogpost_check_cron_status() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $next_run = wp_next_scheduled('ai_blogpost_cron_hook');
        $frequency = get_option('ai_blogpost_post_frequency', 'daily');
        
        ai_blogpost_debug_log('AI Blogpost Cron Status:', [
            'Frequency' => $frequency,
            'Next Run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled'
        ]);
    }
}
add_action('admin_init', 'ai_blogpost_check_cron_status');
