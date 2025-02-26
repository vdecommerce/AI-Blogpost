/**
 * AI Blogpost Admin JavaScript
 * Handles all interactive elements in the admin interface
 */

jQuery(document).ready(function($) {
    // Initialize tabs
    $("#ai-tabs").tabs();
    
    // Initialize tooltips
    $('[title]').tooltip({
        position: { my: "center bottom-10", at: "center top" },
        show: { duration: 200 },
        hide: { duration: 200 }
    });
    
    // Handle radio button selection
    $('.ai-radio-label').click(function() {
        $(this).closest('.ai-radio-group').find('.ai-radio-label').removeClass('ai-radio-selected');
        $(this).addClass('ai-radio-selected');
    });
    
    // Handle sliders
    $('.ai-slider').on('input', function() {
        $(this).next('.ai-slider-value').val($(this).val());
    });
    
    $('.ai-slider-value').on('input', function() {
        $(this).prev('.ai-slider').val($(this).val());
    });
    
    // Toggle password visibility
    $('.ai-toggle-password').click(function() {
        var $input = $(this).prev('input');
        var $icon = $(this).find('.dashicons');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // Toggle LM Studio settings
    $('#ai_blogpost_lm_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('.lm-studio-setting').slideDown(200);
        } else {
            $('.lm-studio-setting').slideUp(200);
        }
    });
    
    // Template guide toggle
    $('.template-guide-header').click(function() {
        $(this).next('.template-guide-content').slideToggle(200);
        $(this).find('.dashicons').toggleClass('dashicons-editor-help dashicons-no-alt');
    });
    
    // Workflow details toggle
    $('.ai-workflow-header').click(function() {
        $(this).next('.ai-workflow-content').slideToggle(200);
        $(this).find('.dashicons').toggleClass('dashicons-info dashicons-no-alt');
    });
    
    // Handle generation type selection
    $('.ai-generation-card').click(function() {
        var type = $(this).data('type');
        
        // Update radio button
        $(this).find('input[type="radio"]').prop('checked', true);
        
        // Update card styling
        $('.ai-generation-card').removeClass('ai-card-selected');
        $(this).addClass('ai-card-selected');
        
        // Show/hide appropriate settings panel
        if (type === 'dalle') {
            $('#comfyui-settings-panel').fadeOut(200, function() {
                $('#dalle-settings-panel').fadeIn(200);
            });
        } else if (type === 'comfyui') {
            $('#dalle-settings-panel').fadeOut(200, function() {
                $('#comfyui-settings-panel').fadeIn(200);
            });
        }
    });
    
    // Refresh models button
    $('#refresh-models').click(function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.post(ajaxurl, {
            action: 'refresh_openai_models',
            nonce: aiSettings.refreshNonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                showNotification(aiSettings.strings.error, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
    
    // Test LM Studio connection
    $('.test-lm-connection').click(function() {
        var $button = $(this);
        var $spinner = $button.closest('td').find('.spinner');
        var apiUrl = $('#ai_blogpost_lm_api_url').val();
        
        if (!apiUrl) {
            showNotification('Please enter a server URL first', 'error');
            return;
        }
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.post(ajaxurl, {
            action: 'test_lm_studio',
            url: apiUrl,
            nonce: aiSettings.nonce
        }, function(response) {
            if (response.success) {
                showNotification(aiSettings.strings.testSuccess, 'success');
            } else {
                showNotification(aiSettings.strings.testFailed, 'error');
            }
        }).fail(function() {
            showNotification(aiSettings.strings.testFailed, 'error');
        }).always(function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
    
    // Test ComfyUI connection
    $('.test-comfyui-connection').click(function() {
        var $button = $(this);
        var $spinner = $button.closest('td').find('.spinner');
        var apiUrl = $('#ai_blogpost_comfyui_api_url').val();
        
        if (!apiUrl) {
            showNotification('Please enter a server URL first', 'error');
            return;
        }
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.post(ajaxurl, {
            action: 'test_comfyui_connection',
            url: apiUrl,
            nonce: aiSettings.nonce
        }, function(response) {
            if (response.success) {
                showNotification('✅ ComfyUI connected successfully!', 'success');
            } else {
                showNotification('❌ ' + (response.data || 'Connection failed'), 'error');
            }
        }).fail(function() {
            showNotification('❌ Connection failed. Please check the server URL.', 'error');
        }).always(function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
    
    // Refresh logs
    $('.ai-refresh-logs').click(function() {
        var $button = $(this);
        $button.find('.dashicons').addClass('dashicons-rotation');
        
        $.post(ajaxurl, {
            action: 'refresh_ai_logs',
            nonce: aiSettings.nonce
        }, function(response) {
            if (response.success) {
                $('#text-generation-logs').html(response.data.text_logs);
                $('#image-generation-logs').html(response.data.image_logs);
                
                // Initialize log toggles
                initLogToggles();
            }
        }).always(function() {
            $button.find('.dashicons').removeClass('dashicons-rotation');
        });
    });
    
    // Initialize log toggles
    function initLogToggles() {
        $('.ai-log-header').off('click').on('click', function() {
            var $content = $(this).next('.ai-log-content');
            $content.slideToggle(200);
            $(this).find('.ai-log-toggle .dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
        });
    }
    
    // Call once on page load
    initLogToggles();
    
    // Handle form submission via AJAX
    $('#ai-settings-form').submit(function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $saveButton = $('#ai-save-settings');
        var $spinner = $('.ai-save-indicator .spinner');
        var $message = $('.ai-save-message');
        
        $saveButton.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.text(aiSettings.strings.saving).removeClass('success error');
        
        $.post(ajaxurl, {
            action: 'save_ai_blogpost_settings',
            data: $form.serialize(),
            nonce: aiSettings.nonce
        }, function(response) {
            if (response.success) {
                $message.text(aiSettings.strings.saved).addClass('success');
                
                if (response.data.reload) {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } else {
                $message.text(aiSettings.strings.error).addClass('error');
            }
        }).fail(function() {
            $message.text(aiSettings.strings.error).addClass('error');
        }).always(function() {
            $saveButton.prop('disabled', false);
            $spinner.removeClass('is-active');
            
            setTimeout(function() {
                $message.text('').removeClass('success error');
            }, 3000);
        });
    });
    
    // Helper function to show notifications
    function showNotification(message, type) {
        // Remove any existing notifications
        $('#ai-notification').remove();
        
        var bgColor = type === 'success' ? '#e7f5ea' : '#fde8e8';
        var textColor = type === 'success' ? '#00a32a' : '#d63638';
        
        var $notification = $('<div id="ai-notification">')
            .css({
                'background': bgColor,
                'color': textColor
            })
            .html(message)
            .appendTo('body')
            .fadeIn()
            .delay(3000)
            .fadeOut(function() { $(this).remove(); });
    }
});
