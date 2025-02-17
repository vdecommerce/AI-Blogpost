<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost {
    private $settings;
    private $cron;
    private $openai;
    private $lm_studio;
    private $logger;

    // Add constructor
    public function __construct() {
        $this->logger = new AI_Blogpost_Logger();
        $this->settings = new AI_Blogpost_Settings();
        $this->cron = new AI_Blogpost_Cron();
        $this->openai = new AI_Blogpost_OpenAI();
        $this->lm_studio = new AI_Blogpost_LM_Studio();
    }

    public function init() {
        // Initialize components
        $this->logger = new AI_Blogpost_Logger();
        $this->settings = new AI_Blogpost_Settings();
        $this->cron = new AI_Blogpost_Cron();
        $this->openai = new AI_Blogpost_OpenAI();
        $this->lm_studio = new AI_Blogpost_LM_Studio();

        // Initialize settings
        $this->settings->init();

        // Setup cron
        $this->cron->init();

        // Add AJAX handlers
        add_action('wp_ajax_refresh_openai_models', array($this->openai, 'handle_refresh_models'));
        add_action('wp_ajax_test_lm_studio', array($this->lm_studio, 'handle_connection_test'));
    }

    public function create_post() {
        try {
            $post_data = $this->get_post_data();
            
            // Generate content
            $ai_result = $this->get_ai_provider()->fetch_response($post_data);
            if (!$ai_result) {
                throw new Exception('No AI result received');
            }

            $parsed_content = $this->parse_ai_content($ai_result['content']);
            
            // Create post
            $post_id = $this->create_wp_post($parsed_content, $post_data);
            
            // Handle image if enabled
            if (get_option('ai_blogpost_dalle_enabled', 0)) {
                $this->handle_featured_image($post_id, $post_data);
            }

            return $post_id;
        } catch (Exception $e) {
            $this->logger->log('Error creating post: ' . $e->getMessage());
            throw $e;
        }
    }

    private function get_ai_provider() {
        return get_option('ai_blogpost_lm_enabled', 0) ? $this->lm_studio : $this->openai;
    }

    private function get_post_data() {
        // Implementation moved from original code
        // Returns array with category and focus_keyword
    }

    private function parse_ai_content($content) {
        // Implementation moved from original code
        // Returns array with title, content, and category
    }

    private function create_wp_post($parsed_content, $post_data) {
        // Implementation moved from original code
        // Returns post ID
    }

    private function handle_featured_image($post_id, $post_data) {
        // Implementation moved from original code
        // Handles DALL-E image generation and attachment
    }
}