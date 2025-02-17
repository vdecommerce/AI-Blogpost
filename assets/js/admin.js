jQuery(document).ready(function($) {
    // Provider settings toggle
    function toggleProviderSettings() {
        var provider = $('#ai_blogpost_llm_provider').val();
        $('.provider-settings').removeClass('active').hide();
        $('.' + provider + '-settings').addClass('active').show();
    }
    
    $('#ai_blogpost_llm_provider').on('change', toggleProviderSettings);
    toggleProviderSettings();

    // DALL-E settings toggle
    function toggleDalleSettings() {
        var enabled = $('#ai_blogpost_dalle_enabled').is(':checked');
        $('.dalle-settings').toggle(enabled);
    }
    
    $('#ai_blogpost_dalle_enabled').on('change', toggleDalleSettings);
    toggleDalleSettings();

    // LM Studio connection test
    $('.test-lm-connection').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $status = $('#lm-studio-status');
        var url = $('#ai_blogpost_lm_api_url').val();
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.post(ajaxurl, {
            action: 'test_lm_studio',
            url: url,
            nonce: ai_blogpost_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $status.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
            } else {
                $status.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
            }
        })
        .fail(function() {
            $status.html('<div class="notice notice-error inline"><p>Connection test failed</p></div>');
        })
        .always(function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
});