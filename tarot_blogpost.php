<?php
/*
Plugin Name: AI-Generated Tarot Blogpost with DALL·E
Description: Genereert een dagelijkse/wekelijkse blogpost met tarot-achtige correspondenties en een DALL·E afbeelding. DALL·E prompt komt uit de tekst output en DALL·E model is instelbaar in het dashboard. Nu met datum shortcode [datum].
Version: 1.0
Author: Your Name
*/

// ------------------ SETTINGS INIT ------------------
function ai_blogpost_initialize_settings() {
    register_setting('ai_blogpost_settings', 'ai_blogpost_temperature');
    register_setting('ai_blogpost_settings', 'ai_blogpost_max_tokens');
    register_setting('ai_blogpost_settings', 'ai_blogpost_role');
    register_setting('ai_blogpost_settings', 'ai_blogpost_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_prompt');
    register_setting('ai_blogpost_settings', 'ai_blogpost_post_frequency');
    register_setting('ai_blogpost_settings', 'ai_blogpost_custom_categories');

    // DALL·E settings
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_enabled');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_api_key');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_size');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_style');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_quality');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_model');
    register_setting('ai_blogpost_settings', 'ai_blogpost_dalle_prompt_template');
}
add_action('admin_init', 'ai_blogpost_initialize_settings');

// ------------------ ADMIN PAGE ------------------
function ai_blogpost_admin_menu() {
    add_menu_page('AI Blogpost Settings', 'AI Blogpost', 'manage_options', 'ai_blogpost', 'ai_blogpost_admin_page');
}
add_action('admin_menu', 'ai_blogpost_admin_menu');

function ai_blogpost_admin_page() {
    echo '<h1>AI Blogpost Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('ai_blogpost_settings');
    do_settings_sections('ai_blogpost_settings');
    echo '<table class="form-table">';

    // Tekst API
    echo '<tr><th>OpenAI Tekst API Key</th><td>';
    echo '<input type="text" name="ai_blogpost_api_key" value="'.esc_attr(get_option('ai_blogpost_api_key')).'">';
    echo '<p class="description">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a>. ';
    echo 'New to OpenAI? <a href="https://platform.openai.com/signup" target="_blank">Sign up here</a>.</p></td></tr>';
    echo '<tr><th>OpenAI Tekst Model</th><td><input type="text" name="ai_blogpost_model" value="'.esc_attr(get_option('ai_blogpost_model')).'"><p>Bijv: gpt-4</p></td></tr>';
    echo '<tr><th>AI Prompt (tekst)</th><td><textarea name="ai_blogpost_prompt" rows="10" cols="80">'.esc_textarea(get_option('ai_blogpost_prompt')).'</textarea><p>Gebruik placeholders [categorie], [datum], [planeet], [element], [kleur], [dier], [getal], [kristal], [kruid], [chakra], [boom], [tarot], [seizoen], [wereld], [alchemie]</p></td></tr>';
    echo '<tr><th>Post Frequency</th><td><input type="radio" name="ai_blogpost_post_frequency" value="daily" '.checked('daily', get_option('ai_blogpost_post_frequency'), false).'> Daily <input type="radio" name="ai_blogpost_post_frequency" value="weekly" '.checked('weekly', get_option('ai_blogpost_post_frequency'), false).'> Weekly</td></tr>';
    echo '<tr><th>Role</th><td><textarea name="ai_blogpost_role" rows="4" cols="50">'.esc_textarea(get_option('ai_blogpost_role')).'</textarea></td></tr>';
    echo '<tr><th>Categories</th><td><textarea name="ai_blogpost_custom_categories" rows="4" cols="50">'.esc_textarea(get_option('ai_blogpost_custom_categories')).'</textarea><p>Één per regel</p></td></tr>';
    echo '<tr><th>Temperature</th><td><input type="number" name="ai_blogpost_temperature" min="0" max="1" step="0.1" value="'.esc_attr(get_option('ai_blogpost_temperature')).'"></td></tr>';
    echo '<tr><th>Max Tokens</th><td><input type="number" name="ai_blogpost_max_tokens" min="1" max="16000" value="'.esc_attr(get_option('ai_blogpost_max_tokens')).'"></td></tr>';

    // DALL·E settings
    echo '<tr><th colspan="2"><h2>DALL·E Instellingen (uitgelichte afbeelding)</h2></th></tr>';
    echo '<tr><th>Uitgelichte afbeelding genereren?</th><td><input type="checkbox" name="ai_blogpost_dalle_enabled" value="1" '.checked(1,get_option('ai_blogpost_dalle_enabled'),false).'> Ja</td></tr>';
    echo '<tr><th>DALL·E Prompt Template</th><td>';
    echo '<textarea name="ai_blogpost_dalle_prompt_template" rows="4" cols="80">'.esc_textarea(get_option('ai_blogpost_dalle_prompt_template', 'Create a mystical and ethereal [style] illustration featuring symbolic elements related to [categorie], with [kleur] color palette in a [seizoen] atmosphere.')).'</textarea>';
    echo '<p class="description">Template voor DALL·E afbeelding prompt. Gebruik placeholders: [categorie], [kleur], [style], [seizoen], etc.</p></td></tr>';
    echo '<tr><th>DALL·E API Key</th><td>';
    echo '<input type="text" name="ai_blogpost_dalle_api_key" value="'.esc_attr(get_option('ai_blogpost_dalle_api_key')).'">';
    echo '<p class="description">Use your OpenAI API key. Make sure you have <a href="https://platform.openai.com/account/billing/overview" target="_blank">billing enabled</a> ';
    echo 'and sufficient credits for image generation.</p></td></tr>';
    echo '<tr><th>DALL·E Model</th><td><input type="text" name="ai_blogpost_dalle_model" value="'.esc_attr(get_option('ai_blogpost_dalle_model')).'"><p>Bijv: dall-e-3 (indien ondersteund)</p></td></tr>';
    echo '<tr><th>DALL·E Afbeeldingsformaat</th><td><select name="ai_blogpost_dalle_size">';
    $selected_size = get_option('ai_blogpost_dalle_size','1024x1024');
    $sizes = array('1024x1024','512x512','256x256');
    foreach($sizes as $sz) echo '<option value="'.$sz.'" '.selected($selected_size,$sz,false).'>'.$sz.'</option>';
    echo '</select></td></tr>';

    echo '<tr><th>DALL·E Style</th><td>';
    $dalle_style = get_option('ai_blogpost_dalle_style','');
    echo '<select name="ai_blogpost_dalle_style">';
    echo '<option value="" '.selected($dalle_style,'',false).'>Geen</option>';
    echo '<option value="vivid" '.selected($dalle_style,'vivid',false).'>Vivid</option>';
    echo '<option value="natural" '.selected($dalle_style,'natural',false).'>Natural</option>';
    echo '</select></td></tr>';

    echo '<tr><th>DALL·E Quality</th><td>';
    $dalle_quality = get_option('ai_blogpost_dalle_quality','');
    echo '<select name="ai_blogpost_dalle_quality">';
    echo '<option value="" '.selected($dalle_quality,'',false).'>Standaard</option>';
    echo '<option value="hd" '.selected($dalle_quality,'hd',false).'>HD</option>';
    echo '</select></td></tr>';

    echo '</table>';
    submit_button();
    echo '</form>';

    echo '<form method="post">';
    echo '<input type="submit" name="test_ai_blogpost" class="button button-primary" value="Run Test Post">';
    echo '</form>';

    $next_post_time = wp_next_scheduled('ai_blogpost_cron_hook');
    if($next_post_time){
        $next_post_time_formatted = get_date_from_gmt(date('Y-m-d H:i:s',$next_post_time), 'Y-m-d H:i:s');
        echo '<p>Next Post Time: '.$next_post_time_formatted.'</p>';
    }

    // Add Status Panel
    echo '<div class="ai-status-panel" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
    echo '<h2>OpenAI Communication Status</h2>';
    
    // Get latest logs
    $logs = get_option('ai_blogpost_api_logs', array());
    $logs = array_slice($logs, -5); // Show last 5 entries
    
    if (empty($logs)) {
        echo '<p>No API communications logged yet.</p>';
    } else {
        echo '<table class="widefat" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($logs as $log) {
            $status_color = $log['success'] ? '#46b450' : '#dc3232';
            echo sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td><span style="color: %s">%s</span></td>
                    <td><button type="button" class="button" onclick="toggleDetails(\'%s\')">Show Details</button>
                        <div id="%s" style="display:none; margin-top: 10px; white-space: pre-wrap;">%s</div>
                    </td>
                </tr>',
                esc_html(date('Y-m-d H:i:s', $log['time'])),
                esc_html($log['type']),
                $status_color,
                $log['success'] ? 'Success' : 'Failed',
                esc_attr('log_' . $log['time']),
                esc_attr('log_' . $log['time']),
                esc_html(print_r($log['data'], true))
            );
        }
        
        echo '</tbody></table>';
    }
    
    // Add JavaScript for toggling details
    echo '<script>
    function toggleDetails(id) {
        var element = document.getElementById(id);
        element.style.display = element.style.display === "none" ? "block" : "none";
    }
    </script>';
    
    echo '<p><button type="button" class="button" onclick="window.location.reload();">Refresh Status</button></p>';
    echo '</div>';
}

// Test post
function create_test_ai_blogpost(){
    if(isset($_POST['test_ai_blogpost'])){
        create_ai_blogpost();
        echo '<div class="updated notice"><p>Test Post Created Successfully!</p></div>';
    }
}
add_action('admin_notices','create_test_ai_blogpost');

// ------------------ CORRESPONDENTIES LOGICA ------------------
function ai_blogpost_get_correspondences() {
    static $data = null;
    
    if ($data === null) {
        $data = array(
            'planeet' => array('Zon', 'Maan', 'Mars', 'Mercurius', 'Jupiter', 'Venus', 'Saturnus'),
            'regenboogkleur' => array('Rood', 'Oranje', 'Geel', 'Groen', 'Blauw', 'Indigo', 'Violet'),
            'dag' => array('Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'),
            'hemellichaam'   => array('Zon', 'Maan', 'Mars', 'Mercurius', 'Jupiter', 'Venus', 'Saturnus'),
            'hermetisch_principe' => array('Geest', 'Overeenkomst', 'Trilling', 'Polariteit', 'Ritme', 'Oorzaak en Gevolg', 'Geslacht'),
            'mineraal'       => array('Goud', 'Zilver', 'IJzer', 'Kwik', 'Tin', 'Koper', 'Lood'),
            'geur'           => array('Wierook', 'Sandelhout', 'Muskus', 'Lavendel', 'Cederhout', 'Rozengeur', 'Patchouli'),
            'plant_kruid'    => array('Zonnebloem', 'Maanbloem', 'Brandnetel', 'Munt', 'Eik', 'Roos', 'Cipres'),
            'pantheon_grieks' => array('Helios', 'Selene', 'Ares', 'Hermes', 'Zeus', 'Aphrodite', 'Cronus')
        );
        $data = apply_filters('ai_blogpost_correspondences_data', $data);
    }

    return array_map(function($arr) {
        return $arr[array_rand($arr)];
    }, $data);
}

// ------------------ FETCH AI TEXT ------------------
function fetch_ai_response($correspondences) {
    try {
        $api_key = get_cached_option('ai_blogpost_api_key');
        if (empty($api_key)) {
            throw new Exception('API key is missing');
        }

        $prompt = prepare_ai_prompt($correspondences);
        $response = send_ai_request($prompt);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }

        return array(
            'content' => $response['choices'][0]['message']['content'],
            'category' => get_cached_option('ai_blogpost_custom_categories', 'Uncategorized'),
            'correspondences' => $correspondences
        );
    } catch (Exception $e) {
        error_log('AI Response Error: ' . $e->getMessage());
        return null;
    }
}

function prepare_ai_prompt($correspondences) {
    $base_prompt = "Schrijf een Nederlandse blog over [categorie]. Gebruik secties:
||Title||:
||Content||:
||Category||:[categorie]
Schrijf de inhoud van de content sectie binnen de <article> </article> tags en gebruik <p> en <h1> <h2>.";

    $prompt = $base_prompt;
    foreach ($correspondences as $key => $value) {
        $prompt = str_replace("[$key]", $value, $prompt);
    }
    
    return str_replace('[datum]', date_i18n(get_option('date_format')), $prompt);
}

function prepare_dalle_prompt($correspondences) {
    $template = get_cached_option('ai_blogpost_dalle_prompt_template', 
        'Create a mystical and ethereal tarot-inspired digital art featuring [categorie] symbolism, with magical elements in a dreamlike atmosphere.');
    
    $prompt = $template;
    foreach ($correspondences as $key => $value) {
        $prompt = str_replace("[$key]", $value, $prompt);
    }
    
    return $prompt;
}

function send_ai_request($prompt) {
    try {
        $args = array(
            'body' => json_encode(array(
                'model' => get_cached_option('ai_blogpost_model', 'gpt-4'),
                'messages' => array(
                    array("role" => "system", "content" => get_cached_option('ai_blogpost_role')),
                    array("role" => "user", "content" => $prompt)
                ),
                'temperature' => (float)get_cached_option('ai_blogpost_temperature', 0.7),
                'max_tokens' => (int)get_cached_option('ai_blogpost_max_tokens', 1024)
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_cached_option('ai_blogpost_api_key')
            ),
            'timeout' => 60
        );

        // Log the request (excluding API key)
        $log_args = $args;
        $log_args['headers']['Authorization'] = 'Bearer [HIDDEN]';
        ai_blogpost_log_api_call('Text Generation', false, array(
            'request' => $log_args,
            'prompt' => $prompt
        ));

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log the successful response
        ai_blogpost_log_api_call('Text Generation', true, array(
            'response' => $result,
            'prompt' => $prompt
        ));
        
        return $result;
    } catch (Exception $e) {
        // Log the error
        ai_blogpost_log_api_call('Text Generation', false, array(
            'error' => $e->getMessage(),
            'prompt' => $prompt
        ));
        throw $e;
    }
}

// ------------------ FETCH DALL·E IMAGE ------------------
function fetch_dalle_image_from_text($dalle_prompt) {
    $dalle_enabled = get_cached_option('ai_blogpost_dalle_enabled', 0);
    if (!$dalle_enabled) {
        return null;
    }

    if (empty($dalle_prompt)) {
        error_log('No DALL·E prompt provided');
        return null;
    }

    $api_key = get_cached_option('ai_blogpost_dalle_api_key', '');
    if (empty($api_key)) {
        error_log('DALL·E API key is missing');
        return null;
    }

    $size = get_cached_option('ai_blogpost_dalle_size', '1024x1024');
    $dalle_style = get_cached_option('ai_blogpost_dalle_style', '');
    $dalle_quality = get_cached_option('ai_blogpost_dalle_quality', '');
    $dalle_model = get_cached_option('ai_blogpost_dalle_model', 'dall-e-3');

    // Prepare the payload
    $payload = array(
        'model' => $dalle_model,
        'prompt' => $dalle_prompt,
        'n' => 1,
        'size' => $size,
        'response_format' => 'b64_json'
    );

    if (!empty($dalle_style)) {
        $payload['style'] = $dalle_style;
    }
    if (!empty($dalle_quality)) {
        $payload['quality'] = $dalle_quality;
    }

    // Debug log
    error_log('DALL·E Request Payload: ' . print_r($payload, true));

    try {
        // Log DALL·E request
        ai_blogpost_log_api_call('Image Generation', false, array(
            'prompt' => $dalle_prompt,
            'settings' => $payload
        ));
        
        $response = wp_remote_post('https://api.openai.com/v1/images/generations', array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 60
        ));

        if(is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        // Log raw response for debugging
        error_log('DALL·E Raw Response: ' . wp_remote_retrieve_body($response));
        
        $response_body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body,true);
        if(empty($decoded_response['data'][0]['b64_json'])){
            error_log('DALL·E returned no base64 image data. Response: '.$response_body);
            return null;
        }

        $image_base64 = $decoded_response['data'][0]['b64_json'];
        $image_data = base64_decode($image_base64);
        if($image_data===false){
            error_log('Failed to decode base64 image data.');
            return null;
        }

        $filename='dalle_image_'.time().'.png';
        $upload = wp_upload_bits($filename,null,$image_data);
        if($upload['error']){
            error_log('Image upload failed: '.$upload['error']);
            return null;
        }

        require_once(ABSPATH.'wp-admin/includes/image.php');
        require_once(ABSPATH.'wp-admin/includes/file.php');
        require_once(ABSPATH.'wp-admin/includes/media.php');

        $wp_filetype = wp_check_filetype($upload['file'],null);
        $attachment = array(
            'post_mime_type'=>$wp_filetype['type'],
            'post_title'=>sanitize_file_name($filename),
            'post_content'=>'',
            'post_status'=>'inherit'
        );

        $attach_id = wp_insert_attachment($attachment,$upload['file']);
        if(is_wp_error($attach_id)){
            error_log('Failed to insert attachment: '.$attach_id->get_error_message());
            return null;
        }

        $attach_data = wp_generate_attachment_metadata($attach_id,$upload['file']);
        wp_update_attachment_metadata($attach_id,$attach_data);

        // Log success
        ai_blogpost_log_api_call('Image Generation', true, array(
            'prompt' => $dalle_prompt,
            'image_id' => $attach_id
        ));
        
        return $attach_id;
    } catch (Exception $e) {
        // Log error
        ai_blogpost_log_api_call('Image Generation', false, array(
            'error' => $e->getMessage(),
            'prompt' => $dalle_prompt
        ));
        return null;
    }
}

// ------------------ CREATE POST ------------------
function create_ai_blogpost() {
    try {
        $correspondences = ai_blogpost_get_correspondences();
        
        // First generate text content
        $ai_result = fetch_ai_response($correspondences);
        if (!$ai_result) {
            throw new Exception('No AI result received');
        }

        $parsed_content = parse_ai_content($ai_result['content']);
        
        // Create post first
        $post_data = array(
            'post_title' => wp_strip_all_tags($parsed_content['title']),
            'post_content' => wpautop($parsed_content['content']),
            'post_status' => 'publish',
            'post_author' => 1,
            'post_category' => array(get_cat_ID($parsed_content['category']) ?: 1)
        );

        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }

        // Then handle DALL-E image separately if enabled
        if (get_cached_option('ai_blogpost_dalle_enabled', 0)) {
            $dalle_prompt = prepare_dalle_prompt($correspondences);
            error_log('DALL-E Prompt: ' . $dalle_prompt);
            
            $attach_id = fetch_dalle_image_from_text($dalle_prompt);
            if ($attach_id) {
                set_post_thumbnail($post_id, $attach_id);
                error_log('Featured image set for post ID: ' . $post_id);
            } else {
                error_log('Failed to generate or attach DALL-E image');
            }
        }

        return $post_id;
    } catch (Exception $e) {
        error_log('Error in create_ai_blogpost: ' . $e->getMessage());
        return null;
    }
}

function parse_ai_content($ai_content) {
    $patterns = array(
        'title' => '/\|\|Title\|\|:\s*(.*?)(?=\|\|Content\|\|:)/s',
        'content' => '/\|\|Content\|\|:\s*(.*?)(?=\|\|Category\|\|:)/s',
        'category' => '/\|\|Category\|\|:\s*(.*?)(?=\|\|DALL_E_Prompt\|\|:|$)/s',
        'dalle_prompt' => '/\|\|DALL_E_Prompt\|\|:\s*(.*?)$/s'
    );

    $parsed = array();
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $ai_content, $matches)) {
            $parsed[$key] = trim($matches[1]);
        } else {
            error_log("Failed to parse {$key} from AI response");
            $parsed[$key] = $key === 'title' ? 'AI-Generated Post' : '';
        }
    }

    // Debug log
    error_log('Parsed Content: ' . print_r($parsed, true));

    return $parsed;
}

// ------------------ CRON SCHEDULING ------------------
function ai_blogpost_schedule_cron() {
    $frequency = get_cached_option('ai_blogpost_post_frequency','daily');
    $is_scheduled = get_cached_option('ai_blogpost_is_scheduled',false);

    if($frequency=='weekly'){
        add_filter('cron_schedules','ai_blogpost_weekly_schedule');
    }

    if(!$is_scheduled){
        if($frequency=='daily'){
            wp_schedule_event(time(),'daily','ai_blogpost_cron_hook');
        } elseif($frequency=='weekly'){
            wp_schedule_event(time(),'weekly','ai_blogpost_cron_hook');
        }
        update_option('ai_blogpost_is_scheduled',true);
    }
}
add_action('wp_loaded','ai_blogpost_schedule_cron');

function ai_blogpost_weekly_schedule($schedules){
    if(!isset($schedules['weekly'])){
        $schedules['weekly']=array(
            'interval'=>604800, // 1 week
            'display'=>__('Once Weekly')
        );
    }
    return $schedules;
}

add_action('ai_blogpost_cron_hook','create_ai_blogpost');

// Deactivatie
function ai_blogpost_deactivation(){
    wp_clear_scheduled_hook('ai_blogpost_cron_hook');
    update_option('ai_blogpost_is_scheduled',false);
}
register_deactivation_hook(__FILE__,'ai_blogpost_deactivation');

function get_cached_option($option_name, $default = '') {
    static $cache = array();
    
    if (!isset($cache[$option_name])) {
        $cache[$option_name] = get_option($option_name, $default);
    }
    
    return $cache[$option_name];
}

// Add logging function
function ai_blogpost_log_api_call($type, $success, $data) {
    $logs = get_option('ai_blogpost_api_logs', array());
    
    // Add new log entry
    $logs[] = array(
        'time' => time(),
        'type' => $type,
        'success' => $success,
        'data' => $data
    );
    
    // Keep only last 20 entries
    if (count($logs) > 20) {
        $logs = array_slice($logs, -20);
    }
    
    update_option('ai_blogpost_api_logs', $logs);
}
