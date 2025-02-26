<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Additional methods for the SettingsRenderer class
 */
class SettingsRenderer {
    /**
     * Render language field
     */
    public static function renderLanguageField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_language">Content Language</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_language" id="ai_blogpost_language">';
        $language = Helpers::getCachedOption('ai_blogpost_language', 'en');
        $languages = [
            'en' => 'English',
            'nl' => 'Nederlands',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español'
        ];
        foreach ($languages as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($language, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select the language for generated content</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render model field
     */
    public static function renderModelField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_model">AI Model</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_model" id="ai_blogpost_model">';
        
        $current_model = Helpers::getCachedOption('ai_blogpost_model', 'gpt-4');
        $available_models = get_option('ai_blogpost_available_gpt_models', [
            'gpt-4',
            'gpt-4-turbo',
            'gpt-3.5-turbo'
        ]);
        
        foreach ($available_models as $model) {
            echo '<option value="' . esc_attr($model) . '" ' . selected($current_model, $model, false) . '>' . esc_html($model) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the OpenAI model to use for text generation</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render temperature field
     */
    public static function renderTemperatureField(): void {
        $temperature = Helpers::getCachedOption('ai_blogpost_temperature', 0.7);
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_temperature">Temperature</label></th>';
        echo '<td>';
        echo '<div class="d-flex align-center gap-10">';
        echo '<input type="range" name="ai_blogpost_temperature" id="ai_blogpost_temperature" min="0" max="1" step="0.1" value="' . esc_attr($temperature) . '" style="width: 200px;">';
        echo '<span id="temperature-value">' . esc_html($temperature) . '</span>';
        echo '</div>';
        echo '<p class="description">Controls randomness: Lower values are more focused, higher values more creative</p>';
        
        // Add JavaScript to update the displayed value
        echo '<script>
            jQuery(document).ready(function($) {
                $("#ai_blogpost_temperature").on("input", function() {
                    $("#temperature-value").text($(this).val());
                });
            });
        </script>';
        
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render max tokens field
     */
    public static function renderMaxTokensField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_max_tokens">Max Tokens</label></th>';
        echo '<td>';
        echo '<input type="number" name="ai_blogpost_max_tokens" id="ai_blogpost_max_tokens" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_max_tokens', 2048)) . '" min="100" max="4096" step="1" class="small-text">';
        echo '<p class="description">Maximum length of generated content (1 token ≈ 4 characters)</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render system role field
     */
    public static function renderSystemRoleField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_role">System Role</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_role" id="ai_blogpost_role" rows="3" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_role', 'You are a professional SEO content writer.')) . '</textarea>';
        echo '<p class="description">Define the AI\'s role and behavior</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render content template field
     */
    public static function renderContentTemplateField(): void {
        $default_template = "Write for a website a SEO blogpost in [language] with the [category] as keyword. Use sections:\n||Title||:\n||Content||:\n||Category||:[category]\nWrite the content of the content section within the <article></article> tags and use <p>, <h1>, and <h2>.";
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_prompt">Content Template</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_prompt" id="ai_blogpost_prompt" rows="6" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_prompt', $default_template)) . '</textarea>';
        echo '<p class="description">Template for content generation. Use [category] and [language] as placeholders.</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render LM Studio section
     */
    public static function renderLmStudioSection(): void {
        $lm_enabled = Helpers::getCachedOption('ai_blogpost_lm_enabled', 0);
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_lm_enabled">Enable LM Studio</label></th>';
        echo '<td>';
        echo '<label class="toggle-switch">';
        echo '<input type="checkbox" name="ai_blogpost_lm_enabled" id="ai_blogpost_lm_enabled" value="1" ' . checked(1, $lm_enabled, false) . ' data-target=".lm-studio-fields">';
        echo '<span class="toggle-slider"></span>';
        echo '</label>';
        echo '<p class="description">Use a local LM Studio instance instead of OpenAI API</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr class="lm-studio-fields" ' . ($lm_enabled ? '' : 'style="display:none;"') . '>';
        echo '<th><label for="ai_blogpost_lm_api_url">LM Studio API URL</label></th>';
        echo '<td>';
        echo '<input type="url" name="ai_blogpost_lm_api_url" id="ai_blogpost_lm_api_url" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_lm_api_url', 'http://localhost:1234')) . '" class="regular-text">';
        echo '<div class="connection-test mt-10">';
        echo '<button type="button" id="test-lm-studio" class="button">Test Connection</button>';
        echo '<span class="spinner"></span>';
        echo '<span class="lm-connection-status connection-status"></span>';
        echo '</div>';
        echo '<p class="description">URL of your local LM Studio server (default: http://localhost:1234)</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr class="lm-studio-fields" ' . ($lm_enabled ? '' : 'style="display:none;"') . '>';
        echo '<th><label for="ai_blogpost_lm_model">LM Studio Model</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_lm_model" id="ai_blogpost_lm_model">';
        
        $current_model = Helpers::getCachedOption('ai_blogpost_lm_model', '');
        $available_models = get_option('ai_blogpost_available_lm_models', []);
        
        if (empty($available_models)) {
            echo '<option value="">No models found - test connection first</option>';
        } else {
            foreach ($available_models as $model) {
                $model_id = is_array($model) ? $model['id'] : $model;
                echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_model, $model_id, false) . '>' . esc_html($model_id) . '</option>';
            }
        }
        
        echo '</select>';
        echo '<p class="description">Select the model loaded in LM Studio</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render image type selector
     */
    public static function renderImageTypeSelector(): void {
        $current_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        echo '<div class="image-type-selector">';
        
        // None option
        echo '<div class="image-type-option' . ($current_type === 'none' ? ' selected' : '') . '" data-value="none">';
        echo '<h3><span class="dashicons dashicons-no-alt"></span> No Images</h3>';
        echo '<p>Don\'t generate featured images for posts</p>';
        echo '</div>';
        
        // DALL·E option
        echo '<div class="image-type-option' . ($current_type === 'dalle' ? ' selected' : '') . '" data-value="dalle">';
        echo '<h3><span class="dashicons dashicons-cloud"></span> DALL·E</h3>';
        echo '<p>Use OpenAI\'s DALL·E API for image generation</p>';
        echo '</div>';
        
        // ComfyUI option
        echo '<div class="image-type-option' . ($current_type === 'comfyui' ? ' selected' : '') . '" data-value="comfyui">';
        echo '<h3><span class="dashicons dashicons-desktop"></span> ComfyUI</h3>';
        echo '<p>Use a local ComfyUI instance for image generation</p>';
        echo '</div>';
        
        // LocalAI option
        echo '<div class="image-type-option' . ($current_type === 'localai' ? ' selected' : '') . '" data-value="localai">';
        echo '<h3><span class="dashicons dashicons-laptop"></span> LocalAI</h3>';
        echo '<p>Use a local LocalAI instance for image generation</p>';
        echo '</div>';
        
        echo '</div>';
        
        // Hidden input to store the selected value
        echo '<input type="hidden" name="ai_blogpost_image_generation_type" value="' . esc_attr($current_type) . '">';
    }
    
    /**
     * Render DALL·E settings
     */
    public static function renderDalleSettings(): void {
        $current_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        echo '<div class="settings-section image-settings image-settings-dalle" ' . ($current_type === 'dalle' ? '' : 'style="display:none;"') . '>';
        echo '<h3><span class="dashicons dashicons-cloud"></span> DALL·E Settings</h3>';
        
        echo '<table class="form-table">';
        
        // API Key
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_api_key">DALL·E API Key</label></th>';
        echo '<td>';
        echo '<div class="api-key-field">';
        echo '<input type="password" name="ai_blogpost_dalle_api_key" id="ai_blogpost_dalle_api_key" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_dalle_api_key', '')) . '" class="regular-text">';
        echo '<button type="button" class="toggle-password" aria-label="Toggle API key visibility"><span class="dashicons dashicons-visibility"></span></button>';
        echo '</div>';
        echo '<p class="description">Your OpenAI API key (can be the same as text generation key)</p>';
        echo '</td>';
        echo '</tr>';
        
        // Model
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_model">DALL·E Model</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_model" id="ai_blogpost_dalle_model">';
        
        $current_model = Helpers::getCachedOption('ai_blogpost_dalle_model', 'dall-e-3');
        $models = [
            'dall-e-3' => 'DALL·E 3',
            'dall-e-2' => 'DALL·E 2'
        ];
        
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the DALL·E model to use</p>';
        echo '</td>';
        echo '</tr>';
        
        // Size
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_size">Image Size</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_size" id="ai_blogpost_dalle_size">';
        
        $current_size = Helpers::getCachedOption('ai_blogpost_dalle_size', '1024x1024');
        $sizes = [
            '1024x1024' => 'Square (1024x1024)',
            '1792x1024' => 'Landscape (1792x1024)',
            '1024x1792' => 'Portrait (1024x1792)'
        ];
        
        foreach ($sizes as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_size, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the image dimensions</p>';
        echo '</td>';
        echo '</tr>';
        
        // Quality
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_quality">Image Quality</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_quality" id="ai_blogpost_dalle_quality">';
        
        $current_quality = Helpers::getCachedOption('ai_blogpost_dalle_quality', 'standard');
        $qualities = [
            'standard' => 'Standard',
            'hd' => 'HD (Higher quality)'
        ];
        
        foreach ($qualities as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_quality, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the image quality (HD costs more credits)</p>';
        echo '</td>';
        echo '</tr>';
        
        // Style
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_style">Image Style</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_dalle_style" id="ai_blogpost_dalle_style">';
        
        $current_style = Helpers::getCachedOption('ai_blogpost_dalle_style', 'vivid');
        $styles = [
            'vivid' => 'Vivid (Hyper-real and dramatic)',
            'natural' => 'Natural (More subtle and realistic)'
        ];
        
        foreach ($styles as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_style, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">Select the image style</p>';
        echo '</td>';
        echo '</tr>';
        
        // Prompt Template
        $default_template = 'Create a professional blog header image about [category]. Style: Modern and professional.';
        
        echo '<tr>';
        echo '<th><label for="ai_blogpost_dalle_prompt_template">Prompt Template</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_dalle_prompt_template" id="ai_blogpost_dalle_prompt_template" rows="4" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_dalle_prompt_template', $default_template)) . '</textarea>';
        echo '<p class="description">Template for image generation. Use [category] as a placeholder.</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render ComfyUI settings
     */
    public static function renderComfyUiSettings(): void {
        $current_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
        
        echo '<div class="settings-section image-settings image-settings-comfyui" ' . ($current_type === 'comfyui' ? '' : 'style="display:none;"') . '>';
        echo '<h3><span class="dashicons dashicons-desktop"></span> ComfyUI Settings</h3>';
        
        echo '<table class="form-table">';
        
        // API URL
        echo '<tr>';
        echo '<th><label for="ai_blogpost_comfyui_api_url">ComfyUI API URL</label></th>';
        echo '<td>';
        echo '<input type="url" name="ai_blogpost_comfyui_api_url" id="ai_blogpost_comfyui_api_url" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_comfyui_api_url', 'http://localhost:8188')) . '" class="regular-text">';
        echo '<div class="connection-test mt-10">';
        echo '<button type="button" id="test-comfyui-connection" class="button">Test Connection</button>';
        echo '<span class="spinner"></span>';
        echo '<span class="comfyui-connection-status connection-status"></span>';
        echo '</div>';
        echo '<p class="description">URL of your local ComfyUI server (default: http://localhost:8188)</p>';
        echo '</td>';
        echo '</tr>';
        
        // Default Workflow
        echo '<tr>';
        echo '<th><label for="ai_blogpost_comfyui_default_workflow">Default Workflow</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_comfyui_default_workflow" id="ai_blogpost_comfyui_default_workflow">';
        
        $current_workflow = Helpers::getCachedOption('ai_blogpost_comfyui_default_workflow', '');
        $workflows = json_decode(Helpers::getCachedOption('ai_blogpost_comfyui_workflows', '[]'), true);
        
        if (empty($workflows)) {
            echo '<option value="">No workflows configured</option>';
        } else {
            foreach ($workflows as $workflow) {
                echo '<option value="' . esc_attr($workflow['name']) . '" ' . selected($current_workflow, $workflow['name'], false) . '>' . esc_html($workflow['name']) . '</option>';
            }
        }
        
        echo '</select>';
        echo '<p class="description">Select the workflow to use for image generation</p>';
        echo '</td>';
        echo '</tr>';
        
        // Workflow Management
        echo '<tr>';
        echo '<th><label>Workflow Management</label></th>';
        echo '<td>';
        echo '<p>To add or update workflows, upload JSON workflow files to the <code>workflows</code> directory.</p>';
        echo '<p>Available workflow files:</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        
        $workflow_files = glob(plugin_dir_path(dirname(__FILE__)) . 'workflows/*.json');
        if (empty($workflow_files)) {
            echo '<li>No workflow files found</li>';
        } else {
            foreach ($workflow_files as $file) {
                $filename = basename($file);
                echo '<li>' . esc_html($filename) . '</li>';
            }
        }
        
        echo '</ul>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render frequency field
     */
    public static function renderFrequencyField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>';
        echo '<td>';
        echo '<select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">';
        $frequency = Helpers::getCachedOption('ai_blogpost_post_frequency', 'daily');
        $frequencies = [
            'daily' => 'Daily (every day)',
            'weekly' => 'Weekly (every Monday)'
        ];
        foreach ($frequencies as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($frequency, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">How often should new posts be generated</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render categories field
     */
    public static function renderCategoriesField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_custom_categories">Post Categories</label></th>';
        echo '<td>';
        echo '<textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" rows="5" class="large-text code">' . esc_textarea(Helpers::getCachedOption('ai_blogpost_custom_categories', '')) . '</textarea>';
        echo '<p class="description">Enter one category per line. A random category will be selected for each post.</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render API key field
     */
    public static function renderApiKeyField(): void {
        echo '<tr>';
        echo '<th><label for="ai_blogpost_api_key">OpenAI API Key</label></th>';
        echo '<td>';
        echo '<div class="api-key-field">';
        echo '<input type="password" name="ai_blogpost_api_key" id="ai_blogpost_api_key" value="' . esc_attr(Helpers::getCachedOption('ai_blogpost_api_key', '')) . '" class="regular-text">';
        echo '<button type="button" class="toggle-password" aria-label="Toggle API key visibility"><span class="dashicons dashicons-visibility"></span></button>';
        echo '</div>';
        echo '<div class="connection-test mt-10">';
        echo '<button type="button" id="test-openai-connection" class="button">Test Connection</button>';
        echo '<span class="spinner"></span>';
        echo '<span class="openai-connection-status connection-status"></span>';
        echo '</div>';
        echo '<p class="description">Your OpenAI API key from <a href="https://platform.openai.com/account/api-keys" target="_blank">platform.openai.com</a></p>';
        echo '</td>';
        echo '</tr>';
    }
}
