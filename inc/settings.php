<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle saving settings and processing pasted workflow JSON
 */
function ai_blogpost_save_settings() {
    ai_blogpost_debug_log('Entering ai_blogpost_save_settings');

    // Controleer nonce en rechten
    if (!isset($_POST['ai_blogpost_nonce']) || !wp_verify_nonce($_POST['ai_blogpost_nonce'], 'ai_blogpost_save_settings_nonce') || !current_user_can('manage_options')) {
        ai_blogpost_debug_log('Nonce verification failed or insufficient permissions');
        wp_die('Ongeldige aanvraag.');
    }

    ai_blogpost_debug_log('POST data:', $_POST);

    try {
        // Verwerk standaardinstellingen
        $settings = [
            'ai_blogpost_temperature', 'ai_blogpost_max_tokens', 'ai_blogpost_role', 'ai_blogpost_api_key',
            'ai_blogpost_model', 'ai_blogpost_prompt', 'ai_blogpost_post_frequency', 'ai_blogpost_custom_categories',
            'ai_blogpost_localai_api_url', 'ai_blogpost_localai_prompt_template', 'ai_blogpost_image_generation_type',
            'ai_blogpost_dalle_api_key', 'ai_blogpost_dalle_size', 'ai_blogpost_dalle_style', 'ai_blogpost_dalle_quality',
            'ai_blogpost_dalle_model', 'ai_blogpost_dalle_prompt_template', 'ai_blogpost_language',
            'ai_blogpost_dalle_enabled', 'ai_blogpost_comfyui_api_url', 'ai_blogpost_comfyui_default_workflow', 
            'ai_blogpost_comfyui_prompt_template', 'ai_blogpost_lm_enabled', 'ai_blogpost_lm_api_url', 
            'ai_blogpost_lm_api_key', 'ai_blogpost_lm_model'
        ];

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }

        // Verwerk geplakte workflow JSON
        if (isset($_POST['ai_blogpost_comfyui_workflow_paste']) && !empty(trim($_POST['ai_blogpost_comfyui_workflow_paste']))) {
            $json_content = trim(stripslashes($_POST['ai_blogpost_comfyui_workflow_paste']));
            ai_blogpost_debug_log('Pasted JSON received:', $json_content);

            $workflow_data = json_decode($json_content, true);
            ai_blogpost_debug_log('Decoded workflow data:', $workflow_data);

            if (is_null($workflow_data)) {
                throw new Exception('Ongeldige JSON: Kon de geplakte JSON niet decoderen. Controleer de syntax.');
            }

            if (!isset($workflow_data['nodes']) || !isset($workflow_data['links'])) {
                throw new Exception('Ongeldige JSON: Workflow moet "nodes" en "links" bevatten.');
            }

            $workflow_name = sanitize_text_field($_POST['ai_blogpost_comfyui_workflow_name'] ?? 'PastedWorkflow_' . time());
            if (empty($workflow_name)) {
                throw new Exception('Voer een geldige naam in voor de workflow.');
            }

            // Get existing workflows as JSON string
            $existing_workflows_json = get_option('ai_blogpost_comfyui_workflows', '{}');
            $workflows = json_decode($existing_workflows_json, true) ?: [];
            
            // Add new workflow
            $workflows[$workflow_name] = $workflow_data;
            
            // Save workflows back as JSON string
            $updated = update_option('ai_blogpost_comfyui_workflows', wp_json_encode($workflows));
            ai_blogpost_debug_log('Workflows updated:', [$updated, $workflows]);

            if ($updated) {
                clear_ai_blogpost_cache();
                if (empty(get_option('ai_blogpost_comfyui_default_workflow'))) {
                    update_option('ai_blogpost_comfyui_default_workflow', $workflow_name);
                    ai_blogpost_debug_log('Set default workflow:', $workflow_name);
                }
                set_transient('ai_blogpost_settings_notice', 'Workflow succesvol opgeslagen als "' . esc_html($workflow_name) . '"!', 30);
            } else {
                throw new Exception('Kon de workflow niet opslaan in de database.');
            }

            // Reset het paste-veld
            update_option('ai_blogpost_comfyui_workflow_paste', '');
            ai_blogpost_debug_log('Paste field reset');
        }

        // Redirect terug naar de instellingenpagina met melding
        wp_safe_redirect(admin_url('admin.php?page=ai_blogpost'));
        exit;

    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in save settings:', $e->getMessage());
        set_transient('ai_blogpost_settings_error', $e->getMessage(), 30);
        wp_safe_redirect(admin_url('admin.php?page=ai_blogpost'));
        exit;
    }
}
add_action('admin_post_ai_blogpost_save_settings', 'ai_blogpost_save_settings');

/**
 * Display settings notices
 */
function ai_blogpost_admin_notices() {
    if ($notice = get_transient('ai_blogpost_settings_notice')) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
        delete_transient('ai_blogpost_settings_notice');
    }
    if ($error = get_transient('ai_blogpost_settings_error')) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        delete_transient('ai_blogpost_settings_error');
    }
}
add_action('admin_notices', 'ai_blogpost_admin_notices');
