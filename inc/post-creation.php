<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get post data from dashboard settings
 * 
 * @return array Post data including category and focus keyword
 */
function ai_blogpost_get_post_data() {
    try {
        ai_blogpost_debug_log('Getting post data from dashboard settings');
        
        // Get categories from settings
        $categories_string = get_cached_option('ai_blogpost_custom_categories', '');
        ai_blogpost_debug_log('Retrieved categories string:', $categories_string);
        
        // Split and clean categories
        $categories = array_filter(array_map('trim', explode("\n", $categories_string)));
        
        if (empty($categories)) {
            ai_blogpost_debug_log('No categories found, using default');
            return array(
                'category' => 'SEO',
                'focus_keyword' => 'SEO optimalisatie'
            );
        }
        
        // Pick a random category
        $selected_category = $categories[array_rand($categories)];
        ai_blogpost_debug_log('Selected category:', $selected_category);
        
        $post_data = array(
            'category' => $selected_category,
            'focus_keyword' => $selected_category
        );
        
        ai_blogpost_debug_log('Final post data:', $post_data);
        return $post_data;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in get_post_data:', $e->getMessage());
        return array(
            'category' => 'SEO',
            'focus_keyword' => 'SEO optimalisatie'
        );
    }
}

/**
 * Create a new AI-generated blog post
 * 
 * @return int|false Post ID on success, false on failure
 */
function create_ai_blogpost() {
    try {
        ai_blogpost_debug_log('Starting blog post creation');
        
        $post_data = ai_blogpost_get_post_data();
        ai_blogpost_debug_log('Got post data:', $post_data);
        
        // Generate text content
        $ai_result = fetch_ai_response($post_data);
        if (!$ai_result) {
            throw new Exception('No AI result received');
        }

        $parsed_content = parse_ai_content($ai_result['content']);
        
        // Create post with SEO metadata
        $post_args = array(
            'post_title' => wp_strip_all_tags($parsed_content['title']),
            'post_content' => wpautop($parsed_content['content']),
            'post_status' => 'publish',
            'post_author' => 1,
            'post_category' => array(get_cat_ID($post_data['category']) ?: 1),
            'meta_input' => array(
                '_yoast_wpseo_metadesc' => $parsed_content['meta'] ?? '',
                '_yoast_wpseo_focuskw' => $post_data['focus_keyword']
            )
        );

        $post_id = wp_insert_post($post_args);
        
        // Handle featured image generation
        $generation_type = get_cached_option('ai_blogpost_image_generation_type', 'none');
        if ($generation_type !== 'none') {
            ai_blogpost_debug_log('Starting image generation with type:', $generation_type);
            
            // Use the appropriate template based on generation type
            $template_key = $generation_type === 'dalle' ? 'ai_blogpost_dalle_prompt_template' : 'ai_blogpost_comfyui_prompt_template';
            $template = get_cached_option($template_key, 
                'Create a professional blog header image about [category]. Style: Modern and professional.');
            
            // Create image data with category
            $image_data = array(
                'category' => $post_data['category'],
                'template' => $template
            );
            
            ai_blogpost_debug_log('Image generation data:', $image_data);
            
            try {
                $attach_id = fetch_dalle_image_from_text($image_data);
                if ($attach_id) {
                    set_post_thumbnail($post_id, $attach_id);
                    ai_blogpost_debug_log('Successfully set featured image:', $attach_id);
                } else {
                    throw new Exception('Image generation failed - no attachment ID returned');
                }
            } catch (Exception $e) {
                ai_blogpost_debug_log('Image generation error:', $e->getMessage());
                // Don't fail the whole post creation if image fails
                error_log('AI Blogpost Image Generation Error: ' . $e->getMessage());
            }
        }

        return $post_id;
    } catch (Exception $e) {
        ai_blogpost_debug_log('Error in create_ai_blogpost:', $e->getMessage());
        return false;
    }
}

/**
 * Parse AI-generated content into structured format
 * 
 * @param string $ai_content Raw AI-generated content
 * @return array Parsed content with title, content, and category
 */
function parse_ai_content($ai_content) {
    ai_blogpost_debug_log('Raw AI Content:', $ai_content);

    // Remove thinking process if present
    if (strpos($ai_content, '</think>') !== false) {
        $ai_content = substr($ai_content, strpos($ai_content, '</think>') + 8);
    }

    // Initialize variables
    $title = '';
    $content = '';
    $category = '';

    // Extract title - improved pattern matching
    if (preg_match('/\|\|Title\|\|:\s*(?:"([^"]+)"|([^"\n]+))(?=\s*\|\||\s*<|\s*$)/s', $ai_content, $matches)) {
        $title = !empty($matches[1]) ? $matches[1] : $matches[2];
        $title = trim($title);
    } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $ai_content, $matches)) {
        // Backup: try to get title from H1 if ||Title|| format fails
        $title = trim(strip_tags($matches[1]));
    }

    // Extract content
    if (preg_match('/<article>(.*?)<\/article>/s', $ai_content, $matches)) {
        $content = trim($matches[1]);
    } elseif (preg_match('/\|\|Content\|\|:\s*(.+?)(?=\|\|Category\|\||\s*$)/s', $ai_content, $matches)) {
        $content = trim($matches[1]);
    }

    // Extract category
    if (preg_match('/\|\|Category\|\|:\s*([^\n]+)/s', $ai_content, $matches)) {
        $category = trim($matches[1]);
    }

    // Clean up the title
    $title = str_replace(['"', "'"], '', $title);
    $title = preg_replace('/\s+/', ' ', $title);

    // Clean up the content
    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
    $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
    $content = preg_replace('/#{3,}\s+(.+)/', '<h3>$1</h3>', $content);
    $content = preg_replace('/#{2}\s+(.+)/', '<h2>$1</h2>', $content);
    $content = preg_replace('/#{1}\s+(.+)/', '<h1>$1</h1>', $content);
    
    // Convert markdown lists to HTML
    $content = preg_replace('/^\s*[-\*]\s+(.+)$/m', '<li>$1</li>', $content);
    $content = preg_replace('/(<li>.*?<\/li>)/s', '<ul>$1</ul>', $content);

    // Add paragraph tags
    $content = wpautop($content);

    $parsed = array(
        'title' => $title ?: 'AI-Generated Post',
        'content' => $content ? "<article>$content</article>" : '<article><p>Content generation failed</p></article>',
        'category' => $category ?: 'Uncategorized'
    );

    ai_blogpost_debug_log('Parsed Content:', $parsed);
    return $parsed;
}

/**
 * Create a test post and handle the response
 */
function create_test_ai_blogpost() {
    static $already_running = false;
    
    if (isset($_POST['test_ai_blogpost']) && !$already_running) {
        $already_running = true;
        
        try {
            // Create post and handle both text and image generation
            $post_id = create_ai_blogpost();
            
            if ($post_id) {
                // Add success notice to transient to avoid duplicate messages
                set_transient('ai_blogpost_test_notice', 'success', 30);
            }
        } catch (Exception $e) {
            // Log error and set error notice
            error_log('Test post creation failed: ' . $e->getMessage());
            set_transient('ai_blogpost_test_notice', 'error', 30);
        }
    }
    
    // Display notice if set
    $notice_type = get_transient('ai_blogpost_test_notice');
    if ($notice_type) {
        delete_transient('ai_blogpost_test_notice');
        if ($notice_type === 'success') {
            echo '<div class="updated notice is-dismissible"><p>Test Post Created Successfully!</p></div>';
        } else {
            echo '<div class="error notice is-dismissible"><p>Test Post Creation Failed. Check error logs.</p></div>';
        }
    }
}

// Remove any existing hooks and add our new one
remove_action('admin_notices', 'create_test_ai_blogpost');
add_action('admin_notices', 'create_test_ai_blogpost', 10, 0);
