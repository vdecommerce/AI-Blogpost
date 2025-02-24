/* assets/js/admin.js */
jQuery(document).ready(function($) {
    // Initialiseer de tabbladen
    var $tabs = $('#ai-blogpost-tabs');
    $tabs.find('.tab-content').hide();
    $tabs.find('ul li a').first().addClass('active');
    $tabs.find('.tab-content').first().show();

    $tabs.find('ul li a').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $tabs.find('ul li a').removeClass('active');
        $(this).addClass('active');
        $tabs.find('.tab-content').hide();
        $(target).fadeIn();
    });

    // Toggle tussen DALL·E en ComfyUI instellingen
    $('input[name="ai_blogpost_image_generation_type"]').on('change', function() {
        var type = $(this).val();
        if (type === 'dalle') {
            $('.comfyui-settings').hide();
            $('.dalle-settings').fadeIn(300);
        } else if (type === 'comfyui') {
            $('.dalle-settings').hide();
            $('.comfyui-settings').fadeIn(300);
        }
    });

    // Maak de hele optie-box klikbaar
    $('.generation-option').click(function() {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        $('.generation-option').removeClass('active');
        $(this).addClass('active');
    });

    // Verbeterde verbindingstest voor ComfyUI
    $('.test-comfyui-connection').click(function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var apiUrl = $('#ai_blogpost_comfyui_api_url').val();
        var $notification = $('<div class="ai-notification"></div>').hide().appendTo('body');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');

        $.post(ajaxurl, {
            action: 'test_comfyui_connection',
            url: apiUrl,
            nonce: '<?php echo wp_create_nonce("ai_blogpost_nonce"); ?>'
        }, function(response) {
            if (response.success) {
                $notification.html('✅ ComfyUI verbonden!').css('background', '#e7f5ea').fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
            } else {
                $notification.html('❌ ' + response.data).css('background', '#fde8e8').fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
            }
        }).fail(function() {
            $notification.html('❌ Verbinding mislukt.').css('background', '#fde8e8').fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
        }).always(function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
});
