/**
 * SNN AI Images Brand Kits JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeBrandKits();
    });

    function initializeBrandKits() {
        // Handle add new brand kit
        $(document).on('click', '#snn-add-brand-kit, #snn-create-first-brand-kit', function(e) {
            e.preventDefault();
            openBrandKitModal();
        });

        // Handle edit brand kit
        $(document).on('click', '.snn-edit-brand-kit', function(e) {
            e.preventDefault();
            const brandKitId = $(this).data('brand-kit-id');
            openBrandKitModal(brandKitId);
        });

        // Handle delete brand kit
        $(document).on('click', '.snn-delete-brand-kit', function(e) {
            e.preventDefault();
            const brandKitId = $(this).data('brand-kit-id');
            deleteBrandKit(brandKitId);
        });

        // Handle duplicate brand kit
        $(document).on('click', '.snn-duplicate-brand-kit', function(e) {
            e.preventDefault();
            const brandKitId = $(this).data('brand-kit-id');
            duplicateBrandKit(brandKitId);
        });

        // Handle export brand kit
        $(document).on('click', '.snn-export-brand-kit', function(e) {
            e.preventDefault();
            const brandKitId = $(this).data('brand-kit-id');
            exportBrandKit(brandKitId);
        });

        // Handle import brand kit
        $(document).on('click', '#snn-import-brand-kit', function(e) {
            e.preventDefault();
            openImportModal();
        });

        // Handle import form submission
        $(document).on('submit', '#snn-import-form', function(e) {
            e.preventDefault();
            importBrandKit();
        });

        // Handle import modal close
        $(document).on('click', '#snn-cancel-import, #snn-import-modal .snn-close-modal', function(e) {
            e.preventDefault();
            closeImportModal();
        });

        // Handle use brand kit
        $(document).on('click', '.snn-use-brand-kit', function(e) {
            e.preventDefault();
            const brandKitId = $(this).data('brand-kit-id');
            useBrandKit(brandKitId);
        });

        // Handle brand kit form submission
        $(document).on('submit', '#snn-brand-kit-form', function(e) {
            e.preventDefault();
            saveBrandKit();
        });

        // Handle modal close
        $(document).on('click', '.snn-modal-close, #snn-cancel-brand-kit', function(e) {
            e.preventDefault();
            closeBrandKitModal();
        });

        // Handle add color button
        $(document).on('click', '.snn-add-color-btn', function(e) {
            e.preventDefault();
            addColorInput();
        });

        // Handle remove color button
        $(document).on('click', '.snn-remove-color', function(e) {
            e.preventDefault();
            $(this).closest('.snn-color-input-wrapper').remove();
        });

        // Handle dropdown menu toggle
        $(document).on('click', '.snn-brand-kit-menu', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close all other dropdowns
            $('.dropdown-menu').addClass('hidden');
            
            // Toggle this dropdown
            $(this).siblings('.dropdown-menu').toggleClass('hidden');
        });

        // Close dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $('.dropdown-menu').addClass('hidden');
            }
        });

        // Initialize color inputs
        initializeColorInputs();
    }

    function openBrandKitModal(brandKitId = null) {
        const modal = $('#snn-brand-kit-modal');
        const form = $('#snn-brand-kit-form');
        const title = $('#snn-brand-kit-modal-title');
        
        // Reset form
        form[0].reset();
        $('#snn-brand-kit-id').val(brandKitId || '');
        
        // Clear existing color inputs
        $('.snn-color-inputs').empty();
        
        if (brandKitId) {
            // Edit mode
            title.text('Edit Brand Kit');
            loadBrandKitData(brandKitId);
        } else {
            // Create mode
            title.text('Create Brand Kit');
            addInitialColorInputs();
        }
        
        modal.removeClass('hidden');
    }

    function closeBrandKitModal() {
        $('#snn-brand-kit-modal').addClass('hidden');
    }

    function loadBrandKitData(brandKitId) {
        // Find brand kit data from the page
        const brandKitCard = $(`.snn-brand-kit-card[data-brand-kit-id="${brandKitId}"]`);
        
        if (brandKitCard.length) {
            const name = brandKitCard.find('.snn-brand-kit-name').text();
            const colors = brandKitCard.find('.snn-color-swatch').map(function() {
                return $(this).css('background-color');
            }).get();
            
            // Convert RGB to hex
            const hexColors = colors.map(color => rgbToHex(color));
            
            // Fill form
            $('#snn-brand-kit-name').val(name);
            
            // Add color inputs
            hexColors.forEach(color => {
                addColorInput(color);
            });
            
            // If no colors, add default inputs
            if (hexColors.length === 0) {
                addInitialColorInputs();
            }
        } else {
            // Fallback: fetch from API
            wp.apiFetch({
                path: `snn-ai/v1/brand-kits/${brandKitId}`,
                method: 'GET'
            }).then(function(response) {
                if (response) {
                    $('#snn-brand-kit-name').val(response.name);
                    $('#snn-brand-kit-guidelines').val(response.style_guidelines);
                    
                    // Add colors
                    const colors = JSON.parse(response.colors || '[]');
                    colors.forEach(color => {
                        addColorInput(color);
                    });
                    
                    // Add fonts
                    const fonts = JSON.parse(response.fonts || '[]');
                    fonts.forEach(font => {
                        $(`#snn-brand-kit-fonts option[value="${font}"]`).prop('selected', true);
                    });
                }
            }).catch(function(error) {
                console.error('Error loading brand kit:', error);
                showNotification('Error loading brand kit data', 'error');
            });
        }
    }

    function saveBrandKit() {
        const brandKitId = $('#snn-brand-kit-id').val();
        const name = $('#snn-brand-kit-name').val().trim();
        const guidelines = $('#snn-brand-kit-guidelines').val().trim();
        
        if (!name) {
            showNotification('Please enter a brand kit name', 'error');
            $('#snn-brand-kit-name').focus();
            return;
        }
        
        // Collect colors
        const colors = $('.snn-color-input').map(function() {
            return $(this).val();
        }).get();
        
        // Collect fonts
        const fonts = $('#snn-brand-kit-fonts').val() || [];
        
        const data = {
            name: name,
            colors: colors,
            fonts: fonts,
            style_guidelines: guidelines
        };
        
        // Show loading state
        const saveButton = $('#snn-brand-kit-form button[type="submit"]');
        const saveText = saveButton.find('.snn-save-text');
        const saveLoading = saveButton.find('.snn-save-loading');
        
        saveButton.prop('disabled', true);
        saveText.addClass('hidden');
        saveLoading.removeClass('hidden');
        
        const apiPath = brandKitId ? `snn-ai/v1/brand-kits/${brandKitId}` : 'snn-ai/v1/brand-kits';
        const method = brandKitId ? 'PUT' : 'POST';
        
        wp.apiFetch({
            path: apiPath,
            method: method,
            data: data
        }).then(function(response) {
            if (response.success) {
                showNotification(brandKitId ? 'Brand kit updated successfully!' : 'Brand kit created successfully!', 'success');
                closeBrandKitModal();
                location.reload(); // Refresh page to show changes
            } else {
                showNotification('Failed to save brand kit', 'error');
            }
        }).catch(function(error) {
            console.error('Save error:', error);
            showNotification('Failed to save brand kit', 'error');
        }).finally(function() {
            saveButton.prop('disabled', false);
            saveText.removeClass('hidden');
            saveLoading.addClass('hidden');
        });
    }

    function deleteBrandKit(brandKitId) {
        if (!confirm('Are you sure you want to delete this brand kit? This action cannot be undone.')) {
            return;
        }
        
        wp.apiFetch({
            path: `snn-ai/v1/brand-kits/${brandKitId}`,
            method: 'DELETE'
        }).then(function(response) {
            if (response.success) {
                showNotification('Brand kit deleted successfully!', 'success');
                $(`.snn-brand-kit-card[data-brand-kit-id="${brandKitId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                showNotification('Failed to delete brand kit', 'error');
            }
        }).catch(function(error) {
            console.error('Delete error:', error);
            showNotification('Failed to delete brand kit', 'error');
        });
    }

    function duplicateBrandKit(brandKitId) {
        wp.apiFetch({
            path: `snn-ai/v1/brand-kits/${brandKitId}/duplicate`,
            method: 'POST'
        }).then(function(response) {
            if (response.success) {
                showNotification('Brand kit duplicated successfully!', 'success');
                location.reload(); // Refresh to show new brand kit
            } else {
                showNotification('Failed to duplicate brand kit', 'error');
            }
        }).catch(function(error) {
            console.error('Duplicate error:', error);
            showNotification('Failed to duplicate brand kit', 'error');
        });
    }

    function exportBrandKit(brandKitId) {
        wp.apiFetch({
            path: `snn-ai/v1/brand-kits/${brandKitId}/export`,
            method: 'GET'
        }).then(function(response) {
            if (response.success) {
                // Create and download JSON file
                const dataStr = JSON.stringify(response.export_data, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = `brand-kit-${response.export_data.name.replace(/[^a-z0-9]/gi, '-').toLowerCase()}.json`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                showNotification('Brand kit exported successfully!', 'success');
            } else {
                showNotification('Failed to export brand kit', 'error');
            }
        }).catch(function(error) {
            console.error('Export error:', error);
            showNotification('Failed to export brand kit', 'error');
        });
    }

    function openImportModal() {
        $('#snn-import-modal').removeClass('hidden');
        $('#snn-import-file').val('');
    }

    function closeImportModal() {
        $('#snn-import-modal').addClass('hidden');
        $('#snn-import-file').val('');
    }

    function importBrandKit() {
        const fileInput = document.getElementById('snn-import-file');
        const file = fileInput.files[0];
        
        if (!file) {
            showNotification('Please select a file to import', 'error');
            return;
        }
        
        if (!file.name.endsWith('.json')) {
            showNotification('Please select a valid JSON file', 'error');
            return;
        }
        
        // Show loading state
        $('.snn-import-text').addClass('hidden');
        $('.snn-import-loading').removeClass('hidden');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const brandKitData = JSON.parse(e.target.result);
                
                wp.apiFetch({
                    path: 'snn-ai/v1/brand-kits/import',
                    method: 'POST',
                    data: {
                        brand_kit_data: brandKitData
                    }
                }).then(function(response) {
                    if (response.success) {
                        showNotification('Brand kit imported successfully!', 'success');
                        closeImportModal();
                        location.reload(); // Refresh to show imported brand kit
                    } else {
                        showNotification('Failed to import brand kit', 'error');
                    }
                }).catch(function(error) {
                    console.error('Import error:', error);
                    showNotification('Failed to import brand kit', 'error');
                }).finally(function() {
                    // Hide loading state
                    $('.snn-import-text').removeClass('hidden');
                    $('.snn-import-loading').addClass('hidden');
                });
            } catch (error) {
                showNotification('Invalid JSON file format', 'error');
                $('.snn-import-text').removeClass('hidden');
                $('.snn-import-loading').addClass('hidden');
            }
        };
        reader.readAsText(file);
    }

    function useBrandKit(brandKitId) {
        // Redirect to dashboard with brand kit selected
        const dashboardUrl = new URL(window.location.origin + '/wp-admin/admin.php');
        dashboardUrl.searchParams.set('page', 'snn-ai-images-dashboard');
        dashboardUrl.searchParams.set('brand_kit', brandKitId);
        
        window.location.href = dashboardUrl.toString();
    }

    function initializeColorInputs() {
        // Add initial color inputs if none exist
        if ($('.snn-color-inputs').children().length === 0) {
            addInitialColorInputs();
        }
    }

    function addInitialColorInputs() {
        const defaultColors = ['#3B82F6', '#10B981', '#F59E0B'];
        defaultColors.forEach(color => {
            addColorInput(color);
        });
    }

    function addColorInput(color = '#3B82F6') {
        const template = $('#snn-color-input-template');
        if (!template.length) {
            // Fallback if template not found
            const colorInput = $(`
                <div class="snn-color-input-wrapper relative">
                    <input type="color" class="snn-color-input w-full h-12 rounded border-0 cursor-pointer" value="${color}">
                    <button type="button" class="snn-remove-color absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `);
            $('.snn-color-inputs').append(colorInput);
        } else {
            const colorInput = $(template.html());
            colorInput.find('.snn-color-input').val(color);
            $('.snn-color-inputs').append(colorInput);
        }
    }

    // Utility functions
    function rgbToHex(rgb) {
        // Convert RGB string to hex
        const result = rgb.match(/\d+/g);
        if (result) {
            return "#" + ((1 << 24) + (parseInt(result[0]) << 16) + (parseInt(result[1]) << 8) + parseInt(result[2])).toString(16).slice(1);
        }
        return rgb; // Return original if not RGB format
    }

    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="snn-notification fixed top-4 right-4 px-4 py-3 rounded-md shadow-lg z-50 ${getNotificationClasses(type)}">
                <div class="flex items-center">
                    <span class="snn-notification-icon mr-2">${getNotificationIcon(type)}</span>
                    <span class="snn-notification-message">${message}</span>
                    <button type="button" class="snn-notification-close ml-4 text-current opacity-70 hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manual close
        notification.find('.snn-notification-close').on('click', function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    function getNotificationClasses(type) {
        switch (type) {
            case 'success':
                return 'bg-green-100 text-green-800 border border-green-200';
            case 'error':
                return 'bg-red-100 text-red-800 border border-red-200';
            case 'warning':
                return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
            default:
                return 'bg-blue-100 text-blue-800 border border-blue-200';
        }
    }

    function getNotificationIcon(type) {
        switch (type) {
            case 'success':
                return '✓';
            case 'error':
                return '✗';
            case 'warning':
                return '⚠';
            default:
                return 'ℹ';
        }
    }

    // Initialize tooltips
    function initializeTooltips() {
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]', {
                theme: 'light',
                arrow: true,
                delay: [500, 0]
            });
        }
    }

    // Initialize tooltips on load
    initializeTooltips();

    // Re-initialize tooltips when modal opens
    $(document).on('click', '#snn-add-brand-kit', function() {
        setTimeout(initializeTooltips, 100);
    });

})(jQuery);