<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blogpost_LM_Studio {
    private $logger;

    public function __construct() {
        $this->logger = new AI_Blogpost_Logger();
    }

    public function fetch_response($post_data) {
        // Implementation moved from original code
        // Handles LM Studio API requests
    }

    public function handle_connection_test() {
        // Implementation moved from original code
        // Handles connection test AJAX request
    }
}