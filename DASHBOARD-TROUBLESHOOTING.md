# AI Blogpost Dashboard Troubleshooting Guide

This guide will help you troubleshoot issues with the AI Blogpost dashboard. If you're experiencing problems with the dashboard not starting up or not displaying correctly, follow these steps to diagnose and fix the issues.

## Common Issues

1. **Dashboard not loading at all**
   - This could be due to missing asset files or PHP errors
   
2. **Dashboard loads but without styling**
   - CSS files may not be loading correctly
   
3. **Dashboard loads but tabs don't work**
   - JavaScript files may not be loading or there might be JavaScript errors

4. **Dashboard shows error messages**
   - Check the error messages for clues about what's wrong

## Diagnostic Tools

We've included two diagnostic tools to help you troubleshoot dashboard issues:

1. **dashboard-test.html**
   - A simple HTML file that tests basic CSS and JavaScript functionality
   - Open this file directly in your browser (no WordPress needed)
   
2. **dashboard-test.php**
   - A more comprehensive PHP-based test that checks WordPress integration
   - Access this through your WordPress installation

## Step-by-Step Troubleshooting

### 1. Check File Structure

Ensure that all required files are present:

```
ai_blogpost.php
inc/
  ├── settings.php
  ├── logs.php
  ├── helpers.php
  ├── ai-api.php
  ├── post-creation.php
  └── cron.php
assets/
  ├── css/
  │   └── admin.css
  └── js/
      └── admin.js
```

### 2. Run the HTML Test

1. Open `dashboard-test.html` in your browser
2. Click the "Test CSS" button to check if CSS is loading correctly
3. Click the "Test JavaScript" button to check if JavaScript is loading correctly
4. Click the "Test Tabs" button to check if tab navigation works

### 3. Run the PHP Test

1. Access `dashboard-test.php` through your WordPress installation
2. Check the "File System Check" section to ensure all files exist
3. Check the "WordPress Integration Check" section to ensure all hooks are active
4. Review the "Environment Information" section for system details

### 4. Common Fixes

#### Missing Assets Directory

If the assets directory is missing:

1. Create the following directory structure:
   ```
   assets/
     ├── css/
     └── js/
   ```
2. Copy the CSS and JavaScript files to their respective directories

#### CSS Not Loading

If CSS is not loading:

1. Check that `assets/css/admin.css` exists
2. Ensure the file has the correct permissions (644)
3. Check for errors in the browser console
4. Try clearing your browser cache

#### JavaScript Not Working

If JavaScript is not working:

1. Check that `assets/js/admin.js` exists
2. Ensure the file has the correct permissions (644)
3. Check for JavaScript errors in the browser console
4. Ensure jQuery is loaded before your script

#### PHP Errors

If you're seeing PHP errors:

1. Check the PHP error log for details
2. Ensure all required files are present
3. Check that class names and namespaces are correct
4. Verify that WordPress hooks are registered correctly

## Advanced Troubleshooting

### Debugging Mode

You can enable debugging mode by adding the following to your `wp-config.php` file:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

This will log errors to `wp-content/debug.log` without displaying them on the screen.

### Manual Asset Loading

If assets are not loading automatically, you can try loading them manually by adding the following code to your theme's `functions.php` file:

```php
function load_ai_blogpost_assets() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai_blogpost') {
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            'ai-blogpost-admin-manual',
            plugin_dir_url('ai-blogpost/ai_blogpost.php') . 'assets/css/admin.css',
            ['dashicons'],
            time()
        );
        wp_enqueue_script(
            'ai-blogpost-admin-manual',
            plugin_dir_url('ai-blogpost/ai_blogpost.php') . 'assets/js/admin.js',
            ['jquery'],
            time(),
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'load_ai_blogpost_assets', 999);
```

### Fallback Interface

The plugin includes a fallback interface that will be displayed if the assets directory is missing. This ensures that you can still access basic functionality even if the dashboard is not loading correctly.

## Contact Support

If you're still experiencing issues after following these steps, please contact support with the following information:

1. The results of both diagnostic tests
2. Any error messages you're seeing
3. Your WordPress version and PHP version
4. Any recent changes you've made to the plugin

## Version History

- 1.1.0: Added dashboard troubleshooting tools
- 1.0.0: Initial release
