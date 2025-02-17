<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost_OpenAI {
    private $logger;

    public function __construct() {
        $this->logger = new AI_Blogpost_Logger();
    }

    public function fetch_response($post_data) {
        // Implementation moved from original code
        // Handles OpenAI API requests
    }

    public function fetch_dalle_image($image_data) {
        // Implementation moved from original code
        // Handles DALL-E image generation
    }

    public function handle_refresh_models() {
        // Implementation moved from original code
        // Handles model refresh AJAX request
    }

    private function fetch_models() {
        // Implementation moved from original code
        // Fetches available models from OpenAI
    }
}