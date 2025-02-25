<?php
/**
 * AI Blogpost Dashboard Test Script
 * 
 * This script helps diagnose issues with the AI Blogpost dashboard.
 * Place this file in the plugin directory and access it via the browser.
 */

// Security check - only run in WordPress environment
if (!defined('ABSPATH')) {
    if (file_exists('../../../wp-load.php')) {
        require_once('../../../wp-load.php');
    } else {
        die('This script must be run within WordPress');
    }
}

// Ensure user is logged in and has admin privileges
if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

// Get plugin information
$plugin_dir = plugin_dir_path(__FILE__);
$plugin_url = plugin_dir_url(__FILE__);
$assets_dir = $plugin_dir . 'assets';
$css_file = $assets_dir . '/css/admin.css';
$js_file = $assets_dir . '/js/admin.js';

// Check if files exist
$css_exists = file_exists($css_file);
$js_exists = file_exists($js_file);
$assets_dir_exists = is_dir($assets_dir);

// Check WordPress hooks
$menu_hook_exists = has_action('admin_menu', ['AI_Blogpost\Settings', 'initializeMenu']);
$assets_hook_exists = has_action('admin_enqueue_scripts', ['AI_Blogpost\Settings', 'enqueueAssets']);

// Check if required classes exist
$settings_class_exists = class_exists('AI_Blogpost\Settings');
$renderer_class_exists = class_exists('AI_Blogpost\SettingsRenderer');

// Output HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Blogpost Dashboard Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #23282d;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .test-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
            margin-bottom: 20px;
        }
        .test-section h2 {
            margin-top: 0;
        }
        .test-item {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
            background: #f9f9f9;
        }
        .success {
            border-left: 4px solid #46b450;
        }
        .error {
            border-left: 4px solid #dc3232;
        }
        .warning {
            border-left: 4px solid #ffb900;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        table th {
            background: #f5f5f5;
        }
        .actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .button {
            background: #2271b1;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .button:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <h1>AI Blogpost Dashboard Test</h1>
    
    <div class="test-section">
        <h2>File System Check</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            <tr class="<?php echo $assets_dir_exists ? 'success' : 'error'; ?>">
                <td>Assets Directory</td>
                <td><?php echo $assets_dir_exists ? 'Found' : 'Missing'; ?></td>
                <td><?php echo $assets_dir; ?></td>
            </tr>
            <tr class="<?php echo $css_exists ? 'success' : 'error'; ?>">
                <td>CSS File</td>
                <td><?php echo $css_exists ? 'Found' : 'Missing'; ?></td>
                <td><?php echo $css_file; ?></td>
            </tr>
            <tr class="<?php echo $js_exists ? 'success' : 'error'; ?>">
                <td>JS File</td>
                <td><?php echo $js_exists ? 'Found' : 'Missing'; ?></td>
                <td><?php echo $js_file; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>WordPress Integration Check</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            <tr class="<?php echo $settings_class_exists ? 'success' : 'error'; ?>">
                <td>Settings Class</td>
                <td><?php echo $settings_class_exists ? 'Found' : 'Missing'; ?></td>
                <td>AI_Blogpost\Settings</td>
            </tr>
            <tr class="<?php echo $renderer_class_exists ? 'success' : 'error'; ?>">
                <td>SettingsRenderer Class</td>
                <td><?php echo $renderer_class_exists ? 'Found' : 'Missing'; ?></td>
                <td>AI_Blogpost\SettingsRenderer</td>
            </tr>
            <tr class="<?php echo $menu_hook_exists ? 'success' : 'error'; ?>">
                <td>Admin Menu Hook</td>
                <td><?php echo $menu_hook_exists ? 'Active' : 'Inactive'; ?></td>
                <td>admin_menu -> Settings::initializeMenu</td>
            </tr>
            <tr class="<?php echo $assets_hook_exists ? 'success' : 'error'; ?>">
                <td>Assets Hook</td>
                <td><?php echo $assets_hook_exists ? 'Active' : 'Inactive'; ?></td>
                <td>admin_enqueue_scripts -> Settings::enqueueAssets</td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>Environment Information</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>WordPress Version</td>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Plugin Version</td>
                <td><?php echo defined('AI_BLOGPOST_VERSION') ? AI_BLOGPOST_VERSION : 'Unknown'; ?></td>
            </tr>
            <tr>
                <td>Plugin Directory</td>
                <td><?php echo $plugin_dir; ?></td>
            </tr>
            <tr>
                <td>Plugin URL</td>
                <td><?php echo $plugin_url; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="actions">
        <a href="<?php echo admin_url('admin.php?page=ai_blogpost'); ?>" class="button">Go to Dashboard</a>
        <a href="<?php echo admin_url(); ?>" class="button">Return to Admin</a>
    </div>
</body>
</html>
