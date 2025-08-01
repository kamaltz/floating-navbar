/**
 * Tirtonic Floating Navbar Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        const targetTab = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show target content
        $('.tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');
    });
    
    // Color picker initialization
    $('.color-picker').wpColorPicker();
    
    // Range slider updates
    $('.range-slider').on('input', function() {
        const value = $(this).val();
        const unit = $(this).data('unit') || 'px';
        $(this).next('.range-value').text(value + unit);
    });
    
    // Opacity slider updates
    $('.opacity-slider').on('input', function() {
        const value = $(this).val();
        const percentage = Math.round(value * 100);
        $(this).siblings('.opacity-value').text(percentage + '%');
    });
    
    // Device tabs for responsive settings
    $('.device-tab').on('click', function() {
        const device = $(this).data('device');
        
        $('.device-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.device-settings').removeClass('active');
        $(`.device-settings[data-device="${device}"]`).addClass('active');
    });
    
    // Preview device switching
    $('.preview-device').on('click', function() {
        const device = $(this).data('device');
        
        $('.preview-device').removeClass('active');
        $(this).addClass('active');
        
        // Update preview iframe
        updatePreview(device);
    });
    
    // Icon library modal
    let currentIconTarget = '';
    
    $('.icon-library-btn').on('click', function() {
        currentIconTarget = $(this).data('target');
        $('#icon-library-modal').show();
    });
    
    $('.modal-close').on('click', function() {
        $('#icon-library-modal').hide();
    });
    
    // Icon selection
    $(document).on('click', '.icon-item', function() {
        const iconName = $(this).data('icon');
        const iconSvg = $(this).html();
        
        // Update the target field
        if (currentIconTarget) {
            $(`input[name="${currentIconTarget}"]`).val(iconName);
            $(`[data-target="${currentIconTarget}"]`).siblings('.current-icon').html(iconSvg);
        }
        
        $('#icon-library-modal').hide();
    });
    
    // Upload custom icon
    $('.upload-icon-btn').on('click', function() {
        const target = $(this).data('target');
        
        const mediaUploader = wp.media({
            title: 'Choose Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $(`input[name="${target}"]`).val(attachment.url);
            $(`[data-target="${target}"]`).siblings('.current-icon').html(`<img src="${attachment.url}" alt="Custom Icon" style="width: 24px; height: 24px;">`);
        });
        
        mediaUploader.open();
    });
    
    // Search/Cart action conditional fields
    $('select[name="search_action"]').on('change', function() {
        if ($(this).val() === 'custom_url') {
            $('.search-custom-url').show();
        } else {
            $('.search-custom-url').hide();
        }
    }).trigger('change');
    
    $('select[name="cart_action"]').on('change', function() {
        if ($(this).val() === 'custom_url') {
            $('.cart-custom-url').show();
        } else {
            $('.cart-custom-url').hide();
        }
    }).trigger('change');
    
    // Menu item toggle
    $(document).on('click', '.toggle-menu-item', function() {
        const content = $(this).closest('.menu-item').find('.menu-item-content');
        content.slideToggle();
        $(this).text(content.is(':visible') ? '▲' : '▼');
    });
    
    // Reset to defaults
    $('#reset-to-default').on('click', function() {
        if (confirm('Are you sure you want to reset all settings to default values?')) {
            $.post(ajaxurl, {
                action: 'tirtonic_reset_settings',
                nonce: tirtonicAdmin.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to reset settings');
                }
            });
        }
    });
    
    // Live preview refresh
    $('#refresh-preview').on('click', function() {
        const activeDevice = $('.preview-device.active').data('device') || 'desktop';
        updatePreview(activeDevice);
    });
    
    function updatePreview(device) {
        const formData = $('#tirtonic-settings-form').serialize();
        
        $.post(ajaxurl, {
            action: 'tirtonic_get_preview',
            device: device,
            settings: formData,
            nonce: tirtonicAdmin.nonce
        }, function(response) {
            if (response.success) {
                const iframe = $('#navbar-preview')[0];
                iframe.srcdoc = response.data.html;
            }
        });
    }
    
    // Auto-save draft (optional)
    let saveTimeout;
    $('#tirtonic-settings-form input, #tirtonic-settings-form select, #tirtonic-settings-form textarea').on('change', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            // Auto-save logic can be added here
        }, 2000);
    });
    
    // Icon search
    $('#icon-search-input').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.icon-item').each(function() {
            const iconName = $(this).data('icon').toLowerCase();
            if (iconName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[title]').tooltip();
    }
    
    // Form validation
    $('#tirtonic-settings-form').on('submit', function(e) {
        let isValid = true;
        
        // Validate required fields
        $(this).find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
});