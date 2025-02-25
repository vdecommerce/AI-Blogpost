<?php declare(strict_types=1);

namespace AI_Blogpost;

// Prevent direct access
defined('ABSPATH') or exit;

/**
 * Handles blog post creation functionality
 */
class PostCreation {
    private const DEFAULT_CATEGORY = 'SEO';
    private const DEFAULT_FOCUS_KEYWORD = 'SEO optimalisatie';
    
    /**
     * Get post data from dashboard settings
     */
    private static function getPostData(): array {
        try {
            Logs::debug('Getting post data from dashboard settings');
            
            // Get categories from settings
            $categories_string = Helpers::getCachedOption('ai_blogpost_custom_categories', '');
            Logs::debug('Retrieved categories string:', $categories_string);
            
            // Split and clean categories
            $categories = array_filter(array_map('trim', explode("\n", $categories_string)));
            
            if (empty($categories)) {
                Logs::debug('No categories found, using default');
                return [
                    'category' => self::DEFAULT_CATEGORY,
                    'focus_keyword' => self::DEFAULT_FOCUS_KEYWORD
                ];
            }
            
            // Pick a random category
            $selected_category = $categories[array_rand($categories)];
            Logs::debug('Selected category:', $selected_category);
            
            $post_data = [
                'category' => $selected_category,
                'focus_keyword' => $selected_category
            ];
            
            Logs::debug('Final post data:', $post_data);
            return $post_data;
        } catch (\Exception $e) {
            Logs::debug('Error in getPostData:', $e->getMessage());
            return [
                'category' => self::DEFAULT_CATEGORY,
                'focus_keyword' => self::DEFAULT_FOCUS_KEYWORD
            ];
        }
    }
    
    /**
     * Create a new AI-generated blog post
     */
    public static function create(): int|false {
        try {
            Logs::debug('Starting blog post creation');
            
            $post_data = self::getPostData();
            Logs::debug('Got post data:', $post_data);
            
            // Generate text content
            $ai_result = AiApi::fetchResponse($post_data);
            if (!$ai_result) {
                throw new \Exception('No AI result received');
            }

            $parsed_content = self::parseAiContent($ai_result['content']);
            
            // Create post with SEO metadata
            $post_args = [
                'post_title' => wp_strip_all_tags($parsed_content['title']),
                'post_content' => wpautop($parsed_content['content']),
                'post_status' => 'publish',
                'post_author' => 1,
                'post_category' => [get_cat_ID($post_data['category']) ?: 1],
                'meta_input' => [
                    '_yoast_wpseo_metadesc' => $parsed_content['meta'] ?? '',
                    '_yoast_wpseo_focuskw' => $post_data['focus_keyword']
                ]
            ];

            $post_id = wp_insert_post($post_args);
            
            // Handle featured image generation
            $generation_type = Helpers::getCachedOption('ai_blogpost_image_generation_type', 'none');
            if ($generation_type !== 'none') {
                Logs::debug('Starting image generation with type:', $generation_type);
                
                // Use the appropriate template based on generation type
                $template = '';
                if ($generation_type === 'dalle') {
                    $template = Helpers::getCachedOption(
                        'ai_blogpost_dalle_prompt_template', 
                        'Create a professional blog header image about [category]. Style: Modern and professional.'
                    );
                } elseif ($generation_type === 'comfyui') {
                    // For ComfyUI, we use the workflow's built-in prompt template
                    $template = '[category]';
                }
                
                // Create image data with category
                $image_data = [
                    'category' => $post_data['category'],
                    'template' => $template
                ];
                
                Logs::debug('Image generation data:', $image_data);
                
                try {
                    $attach_id = AiApi::fetchDalleImage($image_data);
                    if ($attach_id) {
                        set_post_thumbnail($post_id, $attach_id);
                        Logs::debug('Successfully set featured image:', $attach_id);
                    } else {
                        throw new \Exception('Image generation failed - no attachment ID returned');
                    }
                } catch (\Exception $e) {
                    Logs::debug('Image generation error:', $e->getMessage());
                    // Don't fail the whole post creation if image fails
                    error_log('AI Blogpost Image Generation Error: ' . $e->getMessage());
                }
            }

            return $post_id;
        } catch (\Exception $e) {
            Logs::debug('Error in create:', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse AI-generated content into structured format
     */
    private static function parseAiContent(string $ai_content): array {
        Logs::debug('Raw AI Content:', $ai_content);

        // Remove thinking process if present
        if (str_contains($ai_content, '</think>')) {
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

        $parsed = [
            'title' => $title ?: 'AI-Generated Post',
            'content' => $content ? "<article>$content</article>" : '<article><p>Content generation failed</p></article>',
            'category' => $category ?: 'Uncategorized'
        ];

        Logs::debug('Parsed Content:', $parsed);
        return $parsed;
    }
    
    /**
     * Create a test post and handle the response
     */
    public static function createTest(): void {
        static $already_running = false;
        
        if (isset($_POST['test_ai_blogpost']) && !$already_running) {
            $already_running = true;
            
            try {
                // Create post and handle both text and image generation
                $post_id = self::create();
                
                if ($post_id) {
                    // Add success notice to transient to avoid duplicate messages
                    set_transient('ai_blogpost_test_notice', 'success', 30);
                }
            } catch (\Exception $e) {
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
    
    /**
     * Initialize post creation functionality
     */
    public static function initialize(): void {
        // Remove any existing hooks and add our new one
        remove_action('admin_notices', [self::class, 'createTest']);
        add_action('admin_notices', [self::class, 'createTest']);
    }
}

// Initialize post creation functionality
PostCreation::initialize();
