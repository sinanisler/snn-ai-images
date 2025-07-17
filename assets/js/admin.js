/**
 * SNN AI Images Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeTooltips();
        initializeImageUpload();
        initializeBrandKitManager();
        initializeImageGeneration();
        initializeProgressModal();
        initializeUsageUpdate();
    });

    // Initialize Tippy tooltips
    function initializeTooltips() {
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]', {
                theme: 'light',
                arrow: true,
                delay: [500, 0]
            });
        }
    }

    // Initialize image upload functionality
    function initializeImageUpload() {
        const dropzone = $('#snn-image-dropzone');
        const fileInput = $('#snn-image-input');
        const selectedImages = $('.snn-selected-images');
        const imagePreviews = $('.snn-image-previews');
        const generateButton = $('#snn-generate-button');
        
        let selectedFiles = [];

        // Handle dropzone click
        dropzone.on('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });

        // Prevent file input click from bubbling up to dropzone
        fileInput.on('click', function(e) {
            e.stopPropagation();
        });

        // Handle file input change
        fileInput.on('change', function(e) {
            handleFileSelection(e.target.files);
        });

        // Handle drag and drop
        dropzone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.addClass('border-blue-500 bg-blue-50');
        });

        dropzone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.removeClass('border-blue-500 bg-blue-50');
        });

        dropzone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.removeClass('border-blue-500 bg-blue-50');
            
            const files = e.originalEvent.dataTransfer.files;
            handleFileSelection(files);
        });

        function handleFileSelection(files) {
            selectedFiles = [];
            imagePreviews.empty();
            
            Array.from(files).forEach(file => {
                if (validateFile(file)) {
                    selectedFiles.push(file);
                    createImagePreview(file);
                }
            });

            if (selectedFiles.length > 0) {
                selectedImages.removeClass('hidden');
                updateGenerateButton();
            } else {
                selectedImages.addClass('hidden');
            }
        }

        function validateFile(file) {
            // Check file type
            if (!snnAiAdmin.allowedTypes.includes(file.type.split('/')[1])) {
                showNotification(`Invalid file type "${file.name}". ${snnAiAdmin.strings.invalidFileType}`, 'error');
                return false;
            }

            // Check file size
            if (file.size > snnAiAdmin.maxFileSize) {
                showNotification(`File "${file.name}" is too large. ${snnAiAdmin.strings.fileTooLarge}`, 'error');
                return false;
            }
            
            // Check for suspicious file names
            if (file.name.includes('..') || file.name.includes('/') || file.name.includes('\\')) {
                showNotification(`Invalid file name "${file.name}".`, 'error');
                return false;
            }
            
            // Check minimum file size (prevent 0-byte files)
            if (file.size === 0) {
                showNotification(`File "${file.name}" is empty.`, 'error');
                return false;
            }
            
            // Check for reasonable file name length
            if (file.name.length > 255) {
                showNotification(`File name "${file.name}" is too long.`, 'error');
                return false;
            }
            
            return true;
        }

        function createImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $(`
                    <div class="snn-image-preview relative">
                        <img src="${e.target.result}" alt="${file.name}" class="w-full h-24 object-cover rounded-md">
                        <button type="button" class="snn-remove-image absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <div class="snn-image-name text-xs text-gray-600 mt-1 truncate">${file.name}</div>
                    </div>
                `);
                
                preview.find('.snn-remove-image').on('click', function() {
                    const index = imagePreviews.find('.snn-image-preview').index(preview);
                    selectedFiles.splice(index, 1);
                    preview.remove();
                    
                    if (selectedFiles.length === 0) {
                        selectedImages.addClass('hidden');
                    }
                    updateGenerateButton();
                });
                
                imagePreviews.append(preview);
            };
            reader.readAsDataURL(file);
        }

        function updateGenerateButton() {
            const prompt = $('#snn-prompt-input').val().trim();
            const hasFiles = selectedFiles.length > 0;
            const hasPrompt = prompt.length > 0;
            
            generateButton.prop('disabled', !(hasFiles && hasPrompt));
        }

        // Update generate button state when prompt changes
        $('#snn-prompt-input').on('input', updateGenerateButton);

        // Store selected files globally for generation
        window.snnAiSelectedFiles = selectedFiles;
    }

    // Initialize brand kit manager
    function initializeBrandKitManager() {
        // Quick brand kit saving
        $('#snn-save-quick-brand').on('click', function(e) {
            e.preventDefault();
            saveQuickBrandKit();
        });

        // Color input handlers
        $(document).on('change', '.snn-color-input', function() {
            // Update color preview or validation if needed
        });
    }

    function saveQuickBrandKit() {
        const name = $('#snn-quick-brand-name').val().trim();
        const colors = $('.snn-color-input').map(function() {
            return $(this).val();
        }).get();
        const style = $('#snn-quick-brand-style').val().trim();

        // Validate input
        if (!name) {
            showNotification('Please enter a brand name', 'error');
            $('#snn-quick-brand-name').focus();
            return;
        }
        
        if (name.length > 255) {
            showNotification('Brand name is too long (maximum 255 characters)', 'error');
            $('#snn-quick-brand-name').focus();
            return;
        }
        
        if (style.length > 1000) {
            showNotification('Style guidelines are too long (maximum 1000 characters)', 'error');
            $('#snn-quick-brand-style').focus();
            return;
        }
        
        // Validate colors
        if (colors.length === 0) {
            showNotification('Please select at least one color', 'error');
            return;
        }
        
        // Validate each color
        for (let i = 0; i < colors.length; i++) {
            if (!/^#[0-9A-F]{6}$/i.test(colors[i])) {
                showNotification(`Invalid color format: ${colors[i]}`, 'error');
                return;
            }
        }

        const data = {
            name: name,
            colors: colors,
            style_guidelines: style
        };

        // Show loading state
        const button = $('#snn-save-quick-brand');
        const originalText = button.text();
        button.prop('disabled', true).text('Saving...');

        wp.apiFetch({
            path: 'snn-ai/v1/brand-kits',
            method: 'POST',
            data: data
        }).then(function(response) {
            if (response.success) {
                showNotification('Brand kit saved successfully!', 'success');
                
                // Add to brand kit select
                const option = $('<option>').val(response.brand_kit_id).text(name);
                $('#snn-brand-kit-select').append(option);
                
                // Clear form
                $('#snn-quick-brand-name').val('');
                $('#snn-quick-brand-style').val('');
                
                // Update brand kit counter
                updateBrandKitCounter();
            } else {
                showNotification('Failed to save brand kit', 'error');
            }
        }).catch(function(error) {
            console.error('Brand kit save error:', error);
            showNotification('Failed to save brand kit', 'error');
        }).finally(function() {
            button.prop('disabled', false).text(originalText);
        });
    }

    // Initialize image generation
    function initializeImageGeneration() {
        $('#snn-generate-button').on('click', function(e) {
            e.preventDefault();
            startImageGeneration();
        });
    }

    function startImageGeneration() {
        const prompt = $('#snn-prompt-input').val().trim();
        const styleDescription = $('#snn-style-input').val().trim();
        const brandKitId = $('#snn-brand-kit-select').val();
        const generationType = $('#snn-generation-type-select').val();
        const selectedFiles = window.snnAiSelectedFiles || [];

        // Validate input
        if (!prompt) {
            showNotification('Please enter a prompt', 'error');
            $('#snn-prompt-input').focus();
            return;
        }
        
        if (prompt.length > 1000) {
            showNotification('Prompt is too long (maximum 1000 characters)', 'error');
            $('#snn-prompt-input').focus();
            return;
        }
        
        if (styleDescription.length > 500) {
            showNotification('Style description is too long (maximum 500 characters)', 'error');
            $('#snn-style-input').focus();
            return;
        }
        
        if (selectedFiles.length === 0) {
            showNotification('Please select at least one image', 'error');
            return;
        }
        
        // Validate generation type
        const validTypes = ['style_transfer', 'background_removal', 'product_variation', 'category_banner'];
        if (!validTypes.includes(generationType)) {
            showNotification('Invalid generation type selected', 'error');
            return;
        }

        showProgressModal();
        const progressModal = $('#snn-progress-modal');
        const progressFill = progressModal.find('.snn-progress-fill');
        const progressText = progressModal.find('.snn-progress-text');
        const progressCurrent = progressModal.find('.snn-progress-current');

        let completedCount = 0;
        const totalCount = selectedFiles.length;
        const results = [];

        // Update progress
        function updateProgress() {
            const percentage = (completedCount / totalCount) * 100;
            progressFill.css('width', percentage + '%');
            progressText.text(`Processing ${completedCount}/${totalCount} images...`);
        }

        // Process each file
        selectedFiles.forEach((file, index) => {
            uploadAndProcessFile(file, prompt, styleDescription, brandKitId, generationType)
                .then(function(result) {
                    results.push(result);
                    completedCount++;
                    progressCurrent.text(`Completed: ${file.name}`);
                    updateProgress();
                    
                    if (completedCount === totalCount) {
                        finishGeneration(results);
                    }
                })
                .catch(function(error) {
                    console.error('Generation error:', error);
                    completedCount++;
                    progressCurrent.text(`Failed: ${file.name}`);
                    updateProgress();
                    
                    if (completedCount === totalCount) {
                        finishGeneration(results);
                    }
                });
        });
    }

    function uploadAndProcessFile(file, prompt, styleDescription, brandKitId, generationType) {
        return new Promise((resolve, reject) => {
            // First upload the file
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'upload-attachment');
            formData.append('_wpnonce', snnAiAdmin.nonce);

            $.ajax({
                url: snnAiAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        const imageId = response.data.id;
                        
                        // Now process with AI
                        const aiData = {
                            image_id: imageId,
                            prompt: prompt,
                            style_description: styleDescription,
                            brand_kit_id: brandKitId || null,
                            generation_type: generationType
                        };

                        wp.apiFetch({
                            path: 'snn-ai/v1/generate',
                            method: 'POST',
                            data: aiData
                        }).then(function(aiResponse) {
                            if (aiResponse.success) {
                                resolve({
                                    success: true,
                                    original_id: imageId,
                                    generated_id: aiResponse.image_id,
                                    image_url: aiResponse.image_url,
                                    filename: file.name
                                });
                            } else {
                                reject(new Error('AI generation failed'));
                            }
                        }).catch(reject);
                    } else {
                        reject(new Error('Upload failed'));
                    }
                },
                error: function() {
                    reject(new Error('Upload failed'));
                }
            });
        });
    }

    function finishGeneration(results) {
        hideProgressModal();
        
        const successfulResults = results.filter(r => r.success);
        
        if (successfulResults.length > 0) {
            showNotification(`Successfully generated ${successfulResults.length} images!`, 'success');
            displayGenerationResults(successfulResults);
            updateUsageStats();
        } else {
            showNotification('All generations failed. Please try again.', 'error');
        }
    }

    function displayGenerationResults(results) {
        const resultsContainer = $('.snn-generation-results');
        const resultsGrid = $('.snn-results-grid');
        
        resultsGrid.empty();
        
        results.forEach(result => {
            const resultItem = $(`
                <div class="snn-result-item bg-white rounded-lg shadow-md p-4">
                    <div class="snn-result-image-container mb-3">
                        <img src="${result.image_url}" alt="Generated image" class="w-full h-48 object-cover rounded-md">
                    </div>
                    <div class="snn-result-actions flex space-x-2">
                        <a href="${result.image_url}" target="_blank" class="snn-result-btn bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm px-3 py-1 rounded transition-colors">
                            View
                        </a>
                        <a href="${result.image_url}" download class="snn-result-btn bg-green-100 hover:bg-green-200 text-green-700 text-sm px-3 py-1 rounded transition-colors">
                            Download
                        </a>
                        <button type="button" class="snn-result-btn bg-purple-100 hover:bg-purple-200 text-purple-700 text-sm px-3 py-1 rounded transition-colors" onclick="copyToClipboard('${result.image_url}')">
                            Copy URL
                        </button>
                    </div>
                    <div class="snn-result-meta text-xs text-gray-500 mt-2">
                        From: ${result.filename}
                    </div>
                </div>
            `);
            
            resultsGrid.append(resultItem);
        });
        
        resultsContainer.removeClass('hidden');
    }

    // Initialize progress modal
    function initializeProgressModal() {
        $(document).on('click', '.snn-modal-close', function() {
            hideProgressModal();
        });
        
        $(document).on('click', '.snn-modal', function(e) {
            if (e.target === this) {
                hideProgressModal();
            }
        });
    }

    function showProgressModal() {
        $('#snn-progress-modal').removeClass('hidden');
    }

    function hideProgressModal() {
        $('#snn-progress-modal').addClass('hidden');
    }

    // Initialize usage update
    function initializeUsageUpdate() {
        // Update usage stats after generation
        $(document).on('generation-complete', function() {
            updateUsageStats();
        });
    }

    function updateUsageStats() {
        wp.apiFetch({
            path: 'snn-ai/v1/usage',
            method: 'GET'
        }).then(function(response) {
            if (response.current_month !== undefined) {
                $('.snn-stat-card').eq(0).find('.snn-stat-number').text(response.current_month);
                $('.snn-stat-card').eq(1).find('.snn-stat-number').text(response.remaining);
                $('.snn-stat-card').eq(2).find('.snn-stat-number').text(response.total_usage);
            }
        }).catch(function(error) {
            console.error('Usage update error:', error);
        });
    }

    function updateBrandKitCounter() {
        // Update brand kit counter in stats
        const currentCount = parseInt($('.snn-stat-card').eq(3).find('.snn-stat-number').text());
        $('.snn-stat-card').eq(3).find('.snn-stat-number').text(currentCount + 1);
    }

    // Utility functions
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

    // Global utility functions
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            showNotification('URL copied to clipboard!', 'success');
        }).catch(function() {
            showNotification('Failed to copy URL', 'error');
        });
    };

})(jQuery);