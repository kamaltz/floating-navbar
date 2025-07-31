/**
 * Tirtonic Floating Navbar Admin Panel JavaScript
 * Enhanced admin interface with real-time preview and Elementor-like functionality
 */

(function($) {
    'use strict';

    class TirtonicAdmin {
        constructor() {
            this.currentTab = 'general';
            this.previewFrame = null;
            this.settings = {};
            this.iconLibrary = tirtonicAdmin.icon_library || {};
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initColorPickers();
            this.initRangeSliders();
            this.initSortable();
            this.loadSettings();
            this.initPreview();
            
            // Show first tab by default
            this.switchTab('general');
        }

        bindEvents() {
            // Tab navigation
            $('.tirtonic-admin-nav a').on('click', (e) => {
                e.preventDefault();
                const tab = $(e.currentTarget).data('tab');
                this.switchTab(tab);
            });

            // Form submission
            $('#tirtonic-settings-form').on('submit', (e) => {
                e.preventDefault();
                this.saveSettings();
            });

            // Reset settings
            $('#reset-settings, #reset-to-default').on('click', (e) => {
                e.preventDefault();
                this.resetSettings();
            });

            // Icon library modal
            $('.icon-library-btn').on('click', (e) => {
                e.preventDefault();
                const target = $(e.currentTarget).data('target');
                this.openIconLibrary(target);
            });

            // Upload icon
            $('.upload-icon-btn').on('click', (e) => {
                e.preventDefault();
                const target = $(e.currentTarget).data('target');
                this.uploadIcon(target);
            });

            // Modal close
            $('.modal-close, .tirtonic-modal').on('click', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });

            // Icon selection
            $(document).on('click', '.icon-item', (e) => {
                const iconName = $(e.currentTarget).data('icon');
                this.selectIcon(iconName);
            });

            // Icon search
            $('#icon-search-input').on('input', (e) => {
                const searchTerm = $(e.target).val().toLowerCase();
                this.filterIcons(searchTerm);
            });

            // Menu builder
            $('#add-menu-item').on('click', () => this.addMenuItem());
            $('#add-menu-category').on('click', () => this.addMenuCategory());
            $(document).on('click', '.remove-menu-item', (e) => {
                $(e.currentTarget).closest('.menu-item').remove();
                this.updatePreview();
            });
            $(document).on('click', '.toggle-menu-item', (e) => {
                $(e.currentTarget).closest('.menu-item').toggleClass('expanded');
            });

            // Additional icons
            $('#add-icon').on('click', () => this.addAdditionalIcon());
            $(document).on('click', '.remove-icon', (e) => {
                $(e.currentTarget).closest('.additional-icon-item').remove();
                this.updatePreview();
            });

            // Preview controls
            $('.preview-device').on('click', (e) => {
                const device = $(e.currentTarget).data('device');
                this.switchPreviewDevice(device);
            });

            $('#refresh-preview').on('click', () => this.updatePreview());
            
            // Responsive device tabs
            $('.device-tab').on('click', (e) => {
                const device = $(e.currentTarget).data('device');
                
                $('.device-tab').removeClass('active');
                $(e.currentTarget).addClass('active');
                
                $('.device-settings').removeClass('active');
                $('.device-settings[data-device="' + device + '"]').addClass('active');
            });
            
            // Update functionality
            $('#check-updates').on('click', () => this.checkForUpdates());
            $('#update-now').on('click', () => this.updatePlugin());
            $('#clear-cache').on('click', () => this.clearUpdateCache());

            // Real-time updates
            $(document).on('change input', 'input, select, textarea', (e) => {
                clearTimeout(this.updateTimeout);
                const delay = e.target.type === 'range' ? 100 : 300;
                this.updateTimeout = setTimeout(() => this.updatePreview(), delay);
            });
            
            // Opacity slider updates
            $(document).on('input', '.opacity-slider', (e) => {
                const $slider = $(e.target);
                const value = $slider.val();
                const percentage = Math.round(value * 100);
                $slider.siblings('.opacity-value').text(percentage + '%');
            });

            // Conditional fields
            $('select[name="search_action"]').on('change', (e) => {
                const value = $(e.target).val();
                $('.search-custom-url').toggle(value === 'custom_url');
            });

            $('select[name="cart_action"]').on('change', (e) => {
                const value = $(e.target).val();
                $('.cart-custom-url').toggle(value === 'custom_url');
            });
        }

        switchTab(tab) {
            // Update navigation
            $('.tirtonic-admin-nav a').removeClass('active');
            $(`.tirtonic-admin-nav a[data-tab="${tab}"]`).addClass('active');

            // Update content
            $('.tab-content').removeClass('active');
            $(`#${tab}`).addClass('active');

            this.currentTab = tab;

            // Initialize tab-specific features
            if (tab === 'preview') {
                this.updatePreview();
            }
        }

        initColorPickers() {
            $('.color-picker').wpColorPicker({
                change: () => {
                    clearTimeout(this.updateTimeout);
                    this.updateTimeout = setTimeout(() => this.updatePreview(), 100);
                },
                clear: () => {
                    clearTimeout(this.updateTimeout);
                    this.updateTimeout = setTimeout(() => this.updatePreview(), 100);
                }
            });
        }

        initRangeSliders() {
            $('.range-slider').on('input', (e) => {
                const $slider = $(e.target);
                const value = $slider.val();
                const unit = $slider.data('unit') || 'px';
                
                $slider.next('.range-value').text(value + unit);
                
                clearTimeout(this.updateTimeout);
                this.updateTimeout = setTimeout(() => this.updatePreview(), 300);
            });
            
            // Initialize opacity sliders
            $('.opacity-slider').each(function() {
                const $slider = $(this);
                const value = $slider.val();
                const percentage = Math.round(value * 100);
                $slider.siblings('.opacity-value').text(percentage + '%');
            });
        }

        initSortable() {
            $('#menu-items-container').sortable({
                handle: '.drag-handle',
                placeholder: 'sortable-placeholder',
                update: () => this.updatePreview()
            });

            $('#additional-icons').sortable({
                handle: '.icon-preview',
                placeholder: 'sortable-placeholder',
                update: () => this.updatePreview()
            });
        }

        loadSettings() {
            // Load current settings from the form
            this.settings = this.getFormData();
        }

        getFormData() {
            const formData = {};
            const $form = $('#tirtonic-settings-form');

            // Get all form inputs
            $form.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                
                if (!name) return;

                if ($input.attr('type') === 'checkbox') {
                    formData[name] = $input.is(':checked');
                } else if ($input.attr('type') === 'radio') {
                    if ($input.is(':checked')) {
                        formData[name] = $input.val();
                    }
                } else {
                    formData[name] = $input.val();
                }
            });

            // Handle menu items
            formData.menu_items = [];
            $('.menu-item').each(function() {
                const $item = $(this);
                const title = $item.find('input[name*="[title]"]').val();
                const url = $item.find('input[name*="[url]"]').val();
                const icon = $item.find('input[name*="[icon]"]').val();

                if (title && url) {
                    formData.menu_items.push({ title, url, icon });
                }
            });

            // Handle additional icons
            formData.additional_icons = [];
            $('.additional-icon-item').each(function() {
                const $item = $(this);
                const title = $item.find('input[name*="[title]"]').val();
                const url = $item.find('input[name*="[url]"]').val();
                const icon = $item.find('input[name*="[icon]"]').val();

                if (title && url && icon) {
                    formData.additional_icons.push({ title, url, icon });
                }
            });

            return formData;
        }

        saveSettings() {
            const settings = this.getFormData();
            const $form = $('#tirtonic-settings-form');
            
            $form.addClass('loading');

            $.ajax({
                url: tirtonicAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tirtonic_save_settings',
                    nonce: tirtonicAdmin.nonce,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Settings saved successfully!', 'success');
                        this.settings = settings;
                    } else {
                        this.showMessage('Error saving settings: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Network error occurred while saving settings.', 'error');
                },
                complete: () => {
                    $form.removeClass('loading');
                }
            });
        }

        resetSettings() {
            if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                $.ajax({
                    url: tirtonicAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tirtonic_reset_settings',
                        nonce: tirtonicAdmin.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            location.reload();
                        } else {
                            this.showMessage('Error resetting settings.', 'error');
                        }
                    },
                    error: () => {
                        this.showMessage('Network error occurred.', 'error');
                    }
                });
            }
        }

        openIconLibrary(target) {
            this.currentIconTarget = target;
            $('#icon-library-modal').show();
            this.filterIcons(''); // Show all icons
        }

        closeModal() {
            $('.tirtonic-modal').hide();
            this.currentIconTarget = null;
        }

        filterIcons(searchTerm) {
            $('.icon-item').each(function() {
                const $item = $(this);
                const iconName = $item.data('icon');
                const iconLabel = $item.find('span').text().toLowerCase();
                
                const matches = iconName.includes(searchTerm) || iconLabel.includes(searchTerm);
                $item.toggle(matches);
            });
        }

        selectIcon(iconName) {
            if (!this.currentIconTarget) return;

            // Update the hidden input
            $(`input[name="${this.currentIconTarget}"]`).val(iconName);

            // Update the preview
            const iconSvg = this.iconLibrary[iconName] || '';
            $(`.icon-selector input[name="${this.currentIconTarget}"]`)
                .siblings('.current-icon')
                .html(iconSvg);

            this.closeModal();
            this.updatePreview();
        }

        uploadIcon(target) {
            const mediaUploader = wp.media({
                title: tirtonicAdmin.upload_title,
                button: {
                    text: tirtonicAdmin.upload_button
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', () => {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Update the hidden input with the image URL
                $(`input[name="${target}"]`).val(attachment.url);

                // Update the preview
                $(`.icon-selector input[name="${target}"]`)
                    .siblings('.current-icon')
                    .html(`<img src="${attachment.url}" alt="Custom Icon" style="width: 24px; height: 24px;">`);

                this.updatePreview();
            });

            mediaUploader.open();
        }

        addMenuItem() {
            const index = $('.menu-item').length;
            const html = `
                <div class="menu-item" data-index="${index}">
                    <div class="menu-item-header">
                        <span class="drag-handle">⋮⋮</span>
                        <input type="text" name="menu_items[${index}][title]" placeholder="Menu Title">
                        <button type="button" class="toggle-menu-item">▼</button>
                        <button type="button" class="remove-menu-item">×</button>
                    </div>
                    <div class="menu-item-content">
                        <input type="url" name="menu_items[${index}][url]" placeholder="Menu URL">
                        <div class="menu-item-icon">
                            <div class="current-icon">${this.iconLibrary.link || ''}</div>
                            <button type="button" class="button icon-library-btn" data-target="menu_items[${index}][icon]">Choose Icon</button>
                            <input type="hidden" name="menu_items[${index}][icon]" value="link">
                        </div>
                    </div>
                </div>
            `;
            
            $('#menu-items-container').append(html);
        }

        addMenuCategory() {
            // For future implementation
            this.showMessage('Menu categories will be available in a future update.', 'info');
        }

        addAdditionalIcon() {
            const index = $('.additional-icon-item').length;
            const html = `
                <div class="additional-icon-item" data-index="${index}">
                    <div class="icon-preview">${this.iconLibrary.link || ''}</div>
                    <div class="icon-fields">
                        <input type="text" name="additional_icons[${index}][title]" placeholder="Icon Title">
                        <select name="additional_icons[${index}][action]">
                            <option value="url">Custom URL</option>
                            <option value="search">Open Search</option>
                            <option value="cart">Open Cart</option>
                        </select>
                        <input type="url" name="additional_icons[${index}][url]" placeholder="URL (if action is Custom URL)">
                    </div>
                    <div class="icon-controls">
                        <button type="button" class="button icon-library-btn" data-target="additional_icons[${index}][icon]">Choose Icon</button>
                        <button type="button" class="button remove-icon">Remove</button>
                    </div>
                    <input type="hidden" name="additional_icons[${index}][icon]" value="link">
                </div>
            `;
            
            $('#additional-icons').append(html);
        }

        initPreview() {
            this.previewFrame = document.getElementById('navbar-preview');
            this.updatePreview();
        }

        switchPreviewDevice(device) {
            $('.preview-device').removeClass('active');
            $(`.preview-device[data-device="${device}"]`).addClass('active');
            
            // Update preview frame size
            const $frame = $('.preview-frame');
            switch(device) {
                case 'mobile':
                    $frame.css({ width: '375px', height: '667px', margin: '0 auto' });
                    break;
                case 'tablet':
                    $frame.css({ width: '768px', height: '1024px', margin: '0 auto' });
                    break;
                default:
                    $frame.css({ width: '100%', height: '500px', margin: '0' });
            }
            
            this.updatePreview();
        }

        updatePreview() {
            if (!this.previewFrame || this.currentTab !== 'preview') return;

            const settings = this.getFormData();
            const device = $('.preview-device.active').data('device') || 'desktop';

            // Show loading indicator
            const $previewFrame = $('.preview-frame');
            $previewFrame.addClass('loading');

            $.ajax({
                url: tirtonicAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tirtonic_get_preview',
                    nonce: tirtonicAdmin.nonce,
                    settings: settings,
                    device: device
                },
                success: (response) => {
                    if (response.success) {
                        const doc = this.previewFrame.contentDocument || this.previewFrame.contentWindow.document;
                        doc.open();
                        doc.write(response.data.html);
                        doc.close();
                        
                        // Initialize preview navbar after content loads
                        setTimeout(() => {
                            if (doc.defaultView && doc.defaultView.initPreviewNavbar) {
                                doc.defaultView.initPreviewNavbar();
                            }
                        }, 100);
                    }
                    $previewFrame.removeClass('loading');
                },
                error: () => {
                    // Preview update failed
                    $previewFrame.removeClass('loading');
                }
            });
        }

        checkForUpdates() {
            const $button = $('#check-updates');
            const $status = $('#update-status');
            
            $button.prop('disabled', true).text('Checking...');
            $status.html('<span class="spinner is-active"></span> Checking for updates...');
            
            $.ajax({
                url: tirtonicAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tirtonic_check_update',
                    nonce: tirtonicAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        if (data.needs_update) {
                            $status.html(`<span style="color: #d63638;">Update available: v${data.latest_version}</span>`);
                            $('#update-now').show().data('download-url', data.download_url);
                        } else {
                            $status.html('<span style="color: #00a32a;">Plugin is up to date</span>');
                            $('#update-now').hide();
                        }
                    } else {
                        $status.html('<span style="color: #d63638;">Error checking for updates</span>');
                    }
                },
                error: () => {
                    $status.html('<span style="color: #d63638;">Network error</span>');
                },
                complete: () => {
                    $button.prop('disabled', false).text('Check for Updates');
                }
            });
        }
        
        updatePlugin() {
            const $button = $('#update-now');
            const $status = $('#update-status');
            const downloadUrl = $button.data('download-url');
            
            if (!downloadUrl) return;
            
            $button.prop('disabled', true).text('Updating...');
            $status.html('<span class="spinner is-active"></span> Updating plugin...');
            
            $.ajax({
                url: tirtonicAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tirtonic_update_plugin',
                    nonce: tirtonicAdmin.nonce,
                    download_url: downloadUrl
                },
                success: (response) => {
                    if (response.success) {
                        $status.html('<span style="color: #00a32a;">Plugin updated successfully! Reloading...</span>');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        $status.html(`<span style="color: #d63638;">Update failed: ${response.data.message}</span>`);
                        $button.prop('disabled', false).text('Update Now');
                    }
                },
                error: () => {
                    $status.html('<span style="color: #d63638;">Network error during update</span>');
                    $button.prop('disabled', false).text('Update Now');
                }
            });
        }
        
        clearUpdateCache() {
            const $button = $('#clear-cache');
            const $status = $('#update-status');
            
            $button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: tirtonicAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tirtonic_clear_update_cache',
                    nonce: tirtonicAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $status.html('<span style="color: #00a32a;">Update cache cleared</span>');
                        $('#update-now').hide();
                    } else {
                        $status.html('<span style="color: #d63638;">Error clearing cache</span>');
                    }
                },
                error: () => {
                    $status.html('<span style="color: #d63638;">Network error</span>');
                },
                complete: () => {
                    $button.prop('disabled', false).text('Clear Update Cache');
                }
            });
        }

        showMessage(message, type = 'info') {
            const $message = $(`<div class="tirtonic-message ${type}">${message}</div>`);
            
            // Remove existing messages
            $('.tirtonic-message').remove();
            
            // Add new message
            $('.tirtonic-admin-content').prepend($message);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new TirtonicAdmin();
    });

})(jQuery);