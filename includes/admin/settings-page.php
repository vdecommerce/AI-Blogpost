<?php
if (!defined('ABSPATH')) exit;

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a href="#text-generation" class="nav-tab nav-tab-active">Text Generation</a>
        <a href="#image-generation" class="nav-tab">Image Generation</a>
        <a href="#scheduling" class="nav-tab">Scheduling</a>
    </h2>

    <form action="options.php" method="post">
        <?php settings_fields('ai_blogpost_settings'); ?>

        <div id="text-generation" class="tab-content active">
            <table class="form-table">
                <?php display_text_settings(); ?>
            </table>
        </div>

        <div id="image-generation" class="tab-content">
            <table class="form-table">
                <?php display_image_settings(); ?>
            </table>
        </div>

        <div id="scheduling" class="tab-content">
            <table class="form-table">
                <tr>
                    <th><label for="ai_blogpost_post_frequency">Post Frequency</label></th>
                    <td>
                        <select name="ai_blogpost_post_frequency" id="ai_blogpost_post_frequency">
                            <?php
                            $frequency = get_option('ai_blogpost_post_frequency', 'daily');
                            ?>
                            <option value="daily" <?php selected($frequency, 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected($frequency, 'weekly'); ?>>Weekly</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_blogpost_custom_categories">Categories</label></th>
                    <td>
                        <textarea name="ai_blogpost_custom_categories" id="ai_blogpost_custom_categories" 
                                rows="5" class="large-text"><?php 
                            echo esc_textarea(get_option('ai_blogpost_custom_categories')); 
                        ?></textarea>
                        <p class="description">Enter categories (one per line) that will be used for post generation</p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>

    <div class="postbox">
        <h3>Generate Test Post</h3>
        <div class="inside">
            <form method="post">
                <?php wp_nonce_field('ai_blogpost_test'); ?>
                <input type="submit" name="test_ai_blogpost" class="button button-primary" value="Generate Test Post">
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active').hide();
        $($(this).attr('href')).addClass('active').show();
    });

    // Show first tab on load
    $('#text-generation').show();
});
</script>