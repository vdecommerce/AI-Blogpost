/**
 * AI Blogpost Admin JavaScript
 * Optimized for better UX and organization
 */

jQuery(document).ready(function($) {
    // Debug logging
    function debug(message, data) {
        if (window.console && window.console.log) {
            console.log('AI Blogpost Debug:', message, data || '');
        }
    }
    
    // Error handling
    function handleError(functionName, error) {
        console.error('AI Blogpost Error in ' + functionName + ':', error);
        // Display error to user if in admin area
        if ($('.ai-blogpost-dashboard').length) {
            if (!$('#ai-blogpost-error-notice').length) {
                $('.ai-blogpost-dashboard').prepend(
                    '<div id="ai-blogpost-error-notice" class="notice notice-error">' +
                    '<p><strong>Error initializing dashboard:</strong> ' + error.message + '</p>' +
                    '<p>Please check browser console for more details.</p>' +
                    '</div>'
                );
            }
        }
    }
    
    // Tab Navigation
    function initTabs() {
        try {
            debug('Initializing tabs');
            
            // Save form data before switching tabs
            function saveFormData(formId) {
                const formData = {};
                $('#' + formId + ' :input').each(function() {
                    const input = $(this);
                    const name = input.attr('name');
                    if (name) {
                        if (input.is(':checkbox')) {
                            formData[name] = input.is(':checked');
                        } else {
                            formData[name] = input.val();
                        }
                    }
                });
                return formData;
            }
            
            // Restore form data after switching tabs
            function restoreFormData(formId, data) {
                if (!data) return;
                
                $.each(data, function(name, value) {
                    const input = $('#' + formId + ' :input[name="' + name + '"]');
                    if (input.length) {
                        if (input.is(':checkbox')) {
                            input.prop('checked', value);
                        } else {
                            input.val(value);
                        }
                    }
                });
            }
            
            // Store form data for each tab
            const formData = {
                'tab-content': null,
                'tab-text-generation': null,
                'tab-image-generation': null
            };
            
            // Function to save form data to server via AJAX
            function saveFormToServer(formId) {
                const $form = $('#' + formId + ' form');
                if (!$form.length) return;
                
                const formData = new FormData($form[0]);
                formData.append('action', 'save_ai_blogpost_settings');
                formData.append('nonce', aiBlogpostAdmin.nonce);
                formData.append('tab', formId);
                
                // Show saving indicator
                if (!$('#' + formId + ' .save-indicator').length) {
                    $form.append('<div class="save-indicator" style="margin-top: 10px; color: #999;">Saving...</div>');
                } else {
                    $('#' + formId + ' .save-indicator').text('Saving...').show();
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#' + formId + ' .save-indicator').text('Settings saved').css('color', '#46b450');
                            setTimeout(function() {
                                $('#' + formId + ' .save-indicator').fadeOut();
                            }, 2000);
                        } else {
                            $('#' + formId + ' .save-indicator').text('Error saving settings').css('color', '#dc3232');
                        }
                    },
                    error: function() {
                        $('#' + formId + ' .save-indicator').text('Error saving settings').css('color', '#dc3232');
                    }
                });
            }
            
            $('.ai-blogpost-tabs a').on('click', function(e) {
                e.preventDefault();
                
                // Save form data from current tab
                const currentTab = $('.ai-blogpost-tab-content.active').attr('id');
                if (currentTab && $('#' + currentTab + ' form').length) {
                    formData[currentTab] = saveFormData(currentTab);
                    
                    // Save to server
                    saveFormToServer(currentTab);
                }
                
                // Update active tab
                $('.ai-blogpost-tabs a').removeClass('active');
                $(this).addClass('active');
                
                // Show corresponding content
                const tabId = $(this).attr('href').replace('#', ''); // Remove the # from href
                $('.ai-blogpost-tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
                
                // Restore form data for new tab
                if (formData[tabId]) {
                    restoreFormData(tabId, formData[tabId]);
                }
                
                // Save active tab to localStorage
                localStorage.setItem('ai_blogpost_active_tab', tabId);
            });
            
            // Add save button event handlers
            $('.ai-blogpost-tab-content form').on('submit', function(e) {
                const tabId = $(this).closest('.ai-blogpost-tab-content').attr('id');
                formData[tabId] = saveFormData(tabId);
            });
            
            // Restore active tab from localStorage
            const savedTab = localStorage.getItem('ai_blogpost_active_tab');
            if (savedTab && $('#' + savedTab).length) {
                $('.ai-blogpost-tabs a[href="#' + savedTab + '"]').trigger('click');
            } else {
                // Default to first tab
                $('.ai-blogpost-tabs a:first').trigger('click');
            }
            
            debug('Tabs initialized successfully');
        } catch (error) {
            handleError('initTabs', error);
        }
    }
    
    // Toggle Password Visibility
    function initPasswordToggles() {
        try {
            debug('Initializing password toggles');
            $('.toggle-password').on('click', function() {
                const input = $(this).prev('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                
                // Toggle icon
                $(this).find('.dashicons')
                    .toggleClass('dashicons-visibility')
                    .toggleClass('dashicons-hidden');
            });
            debug('Password toggles initialized successfully');
        } catch (error) {
            handleError('initPasswordToggles', error);
        }
    }
    
    // Conditional Fields
    function initConditionalFields() {
        try {
            debug('Initializing conditional fields');
            // Function to toggle conditional fields
            function toggleConditionalFields() {
                // Image generation type
                const imageType = $('input[name="ai_blogpost_image_generation_type"]:checked').val();
                $('.image-settings').hide();
                $('.image-settings-' + imageType).show();
                
                // LM Studio toggle
                const lmEnabled = $('#ai_blogpost_lm_enabled').is(':checked');
                $('.lm-studio-fields').toggle(lmEnabled);
                
                // DALL·E toggle
                const dalleEnabled = $('#ai_blogpost_dalle_enabled').is(':checked');
                $('.dalle-fields').toggle(dalleEnabled);
            }
            
            // Bind change events
            $('input[name="ai_blogpost_image_generation_type"]').on('change', toggleConditionalFields);
            $('#ai_blogpost_lm_enabled').on('change', toggleConditionalFields);
            $('#ai_blogpost_dalle_enabled').on('change', toggleConditionalFields);
            
            // Initial state
            toggleConditionalFields();
            
            debug('Conditional fields initialized successfully');
        } catch (error) {
            handleError('initConditionalFields', error);
        }
    }
    
    // Image Type Selector
    function initImageTypeSelector() {
        try {
            debug('Initializing image type selector');
            $('.image-type-option').on('click', function() {
                const value = $(this).data('value');
                
                // Update visual selection
                $('.image-type-option').removeClass('selected');
                $(this).addClass('selected');
                
                // Update hidden input
                $('input[name="ai_blogpost_image_generation_type"]').val(value);
                
                // Show corresponding settings
                $('.image-settings').hide();
                $('.image-settings-' + value).show();
            });
            
            // Set initial state
            const currentValue = $('input[name="ai_blogpost_image_generation_type"]').val();
            if (currentValue) {
                $('.image-type-option[data-value="' + currentValue + '"]').addClass('selected');
            }
            
            debug('Image type selector initialized successfully');
        } catch (error) {
            handleError('initImageTypeSelector', error);
        }
    }
    
    // Tooltips
    function initTooltips() {
        try {
            debug('Initializing tooltips');
            // No action needed - CSS handles the display
            debug('Tooltips initialized successfully');
        } catch (error) {
            handleError('initTooltips', error);
        }
    }
    
    // API Connection Tests
    function initConnectionTests() {
        try {
            debug('Initializing connection tests');
            
            // OpenAI Connection Test
            $('#test-openai-connection').on('click', function() {
                const $button = $(this);
                const $spinner = $button.next('.spinner');
                const $status = $('.openai-connection-status');
                const apiKey = $('#ai_blogpost_api_key').val();
                
                if (!apiKey) {
                    $status.text('Please enter an API key first').removeClass('success').addClass('error');
                    return;
                }
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $status.text('Testing connection...').removeClass('success error');
                
                $.post(ajaxurl, {
                    action: 'test_openai_connection',
                    nonce: aiBlogpostAdmin.nonce,
                    api_key: apiKey
                }, function(response) {
                    if (response.success) {
                        $status.text('Connection successful!').addClass('success').removeClass('error');
                    } else {
                        $status.text('Connection failed: ' + response.data).addClass('error').removeClass('success');
                    }
                }).fail(function() {
                    $status.text('Request failed. Please try again.').addClass('error').removeClass('success');
                }).always(function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
            });
            
            // LM Studio Connection Test
            $('#test-lm-studio').on('click', function() {
                const $button = $(this);
                const $spinner = $button.next('.spinner');
                const $status = $('.lm-connection-status');
                const apiUrl = $('#ai_blogpost_lm_api_url').val();
                
                if (!apiUrl) {
                    $status.text('Please enter an API URL first').removeClass('success').addClass('error');
                    return;
                }
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $status.text('Testing connection...').removeClass('success error');
                
                $.post(ajaxurl, {
                    action: 'test_lm_studio',
                    nonce: aiBlogpostAdmin.nonce,
                    url: apiUrl
                }, function(response) {
                    if (response.success) {
                        $status.text('Connection successful!').addClass('success').removeClass('error');
                        
                        // Update model dropdown if models were returned
                        if (response.data && response.data.models) {
                            const $select = $('#ai_blogpost_lm_model');
                            $select.empty();
                            
                            $.each(response.data.models, function(i, model) {
                                const modelId = model.id || model;
                                $select.append($('<option></option>').val(modelId).text(modelId));
                            });
                        }
                    } else {
                        $status.text('Connection failed: ' + response.data).addClass('error').removeClass('success');
                    }
                }).fail(function() {
                    $status.text('Request failed. Please try again.').addClass('error').removeClass('success');
                }).always(function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
            });
            
            // ComfyUI Connection Test
            $('#test-comfyui-connection').on('click', function() {
                const $button = $(this);
                const $spinner = $button.next('.spinner');
                const $status = $('.comfyui-connection-status');
                const apiUrl = $('#ai_blogpost_comfyui_api_url').val();
                
                if (!apiUrl) {
                    $status.text('Please enter an API URL first').removeClass('success').addClass('error');
                    return;
                }
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $status.text('Testing connection...').removeClass('success error');
                
                $.post(ajaxurl, {
                    action: 'test_comfyui_connection',
                    nonce: aiBlogpostAdmin.nonce,
                    url: apiUrl
                }, function(response) {
                    if (response.success) {
                        $status.text('Connection successful!').addClass('success').removeClass('error');
                    } else {
                        $status.text('Connection failed: ' + response.data).addClass('error').removeClass('success');
                    }
                }).fail(function() {
                    $status.text('Request failed. Please try again.').addClass('error').removeClass('success');
                }).always(function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
            });
            
            debug('Connection tests initialized successfully');
        } catch (error) {
            handleError('initConnectionTests', error);
        }
    }
    
    // Refresh Models Button
    function initRefreshModels() {
        try {
            debug('Initializing refresh models button');
            
            $('#refresh-models').click(function() {
                var $button = $(this);
                var $spinner = $button.next('.spinner');
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                
                $.post(ajaxurl, {
                    action: 'refresh_openai_models',
                    nonce: aiBlogpostAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to fetch models. Please check your API key.');
                    }
                }).fail(function() {
                    alert('Request failed. Please try again.');
                }).always(function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
            });
            
            debug('Refresh models button initialized successfully');
        } catch (error) {
            handleError('initRefreshModels', error);
        }
    }
    
    // Toggle Switches
    function initToggleSwitches() {
        try {
            debug('Initializing toggle switches');
            
            $('.toggle-switch input').on('change', function() {
                const isChecked = $(this).is(':checked');
                const targetSelector = $(this).data('target');
                
                if (targetSelector) {
                    $(targetSelector).toggle(isChecked);
                }
            });
            
            // Initial state
            $('.toggle-switch input').each(function() {
                const isChecked = $(this).is(':checked');
                const targetSelector = $(this).data('target');
                
                if (targetSelector) {
                    $(targetSelector).toggle(isChecked);
                }
            });
            
            debug('Toggle switches initialized successfully');
        } catch (error) {
            handleError('initToggleSwitches', error);
        }
    }
    
    // Initialize all components
    function init() {
        try {
            debug('Starting initialization');
            
            initTabs();
            initPasswordToggles();
            initConditionalFields();
            initImageTypeSelector();
            initTooltips();
            initConnectionTests();
            initRefreshModels();
            initToggleSwitches();
            
            debug('All components initialized successfully');
        } catch (error) {
            handleError('init', error);
        }
    }
    
    // Run initialization
    init();
});
