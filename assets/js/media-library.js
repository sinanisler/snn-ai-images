/**
 * SNN AI Images Media Library JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeMediaLibraryIntegration();
    });

    function initializeMediaLibraryIntegration() {
        // Handle AI Edit button clicks
        $(document).on('click', '.snn-ai-generate-btn', function(e) {
            e.preventDefault();
            handleAIGeneration($(this));
        });

        // Handle quick edit links
        $(document).on('click', '.snn-ai-quick-edit', function(e) {
            e.preventDefault();
            const attachmentId = $(this).data('attachment-id');
            openQuickEditModal(attachmentId);
        });

        // Handle use generated image
        $(document).on('click', '.snn-ai-use-image', function(e) {
            e.preventDefault();
            const resultContainer = $(this).closest('.snn-ai-result');
            const imageUrl = resultContainer.find('.snn-ai-result-image img').attr('src');
            
            if (imageUrl) {
                // Replace original image with generated one
                replaceOriginalImage(imageUrl);
                showNotification('Image replaced successfully!', 'success');
            }
        });

        // Handle try again
        $(document).on('click', '.snn-ai-try-again', function(e) {
            e.preventDefault();
            const resultContainer = $(this).closest('.snn-ai-result');
            resultContainer.hide();
            
            // Reset form
            const container = $(this).closest('.snn-ai-media-edit-container');
            container.find('.snn-ai-prompt-input').focus();
        });

        // Handle generation type change
        $(document).on('change', '.snn-ai-generation-type-select', function() {
            const type = $(this).val();
            const container = $(this).closest('.snn-ai-media-edit-container');
            updatePromptPlaceholder(container, type);
        });
    }

    function handleAIGeneration(button) {
        const container = button.closest('.snn-ai-media-edit-container');
        const attachmentId = container.data('attachment-id');
        const prompt = container.find('.snn-ai-prompt-input').val().trim();
        const styleDescription = container.find('.snn-ai-style-input').val().trim();
        const brandKitId = container.find('.snn-ai-brand-kit-select').val();
        const generationType = container.find('.snn-ai-generation-type-select').val();

        if (!prompt) {
            showNotification('Please enter a prompt', 'error');
            container.find('.snn-ai-prompt-input').focus();
            return;
        }

        // Show loading state
        const originalText = button.text();
        button.prop('disabled', true);
        container.find('.snn-ai-loading').show();

        // Hide previous results
        container.find('.snn-ai-result').hide();

        const data = {
            action: 'snn_ai_media_edit',
            attachment_id: attachmentId,
            prompt: prompt,
            style_description: styleDescription,
            brand_kit_id: brandKitId || '',
            generation_type: generationType,
            nonce: snnAiMedia.nonce
        };

        $.ajax({
            url: snnAiMedia.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    displayGenerationResult(container, response.data);
                    showNotification(snnAiMedia.strings.success, 'success');
                } else {
                    showNotification(response.data || snnAiMedia.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(snnAiMedia.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
                container.find('.snn-ai-loading').hide();
            }
        });
    }

    function displayGenerationResult(container, data) {
        const resultContainer = container.find('.snn-ai-result');
        const resultImage = resultContainer.find('.snn-ai-result-image');
        
        resultImage.html(`
            <img src="${data.image_url}" alt="Generated image" class="max-w-full h-auto rounded-md">
        `);
        
        // Store data for use actions
        resultContainer.data('generated-image-id', data.image_id);
        resultContainer.data('generated-image-url', data.image_url);
        
        resultContainer.show();
    }

    function openQuickEditModal(attachmentId) {
        // Create modal HTML
        const modal = $(`
            <div class="snn-ai-quick-edit-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="snn-ai-modal-content bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="snn-ai-modal-header flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-800">AI Edit Image</h3>
                        <button type="button" class="snn-ai-modal-close text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="snn-ai-modal-body">
                        <div class="snn-ai-quick-edit-form" data-attachment-id="${attachmentId}">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prompt</label>
                                <textarea class="snn-ai-prompt-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Describe how you want to transform this image..."></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Style</label>
                                <input type="text" class="snn-ai-style-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., modern, vintage, artistic...">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Generation Type</label>
                                <select class="snn-ai-generation-type-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="style_transfer">Style Transfer</option>
                                    <option value="background_removal">Background Removal</option>
                                    <option value="product_variation">Product Variation</option>
                                    <option value="category_banner">Category Banner</option>
                                </select>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" class="snn-ai-modal-close bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">Cancel</button>
                                <button type="button" class="snn-ai-quick-generate bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Generate</button>
                            </div>
                        </div>
                        
                        <div class="snn-ai-quick-result hidden mt-4">
                            <div class="snn-ai-result-preview mb-4">
                                <img src="" alt="Generated image" class="w-full h-48 object-cover rounded-md">
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" class="snn-ai-try-again bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">Try Again</button>
                                <button type="button" class="snn-ai-use-generated bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">Use This Image</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Handle modal close
        modal.find('.snn-ai-modal-close').on('click', function() {
            modal.remove();
        });
        
        // Handle quick generation
        modal.find('.snn-ai-quick-generate').on('click', function() {
            const form = modal.find('.snn-ai-quick-edit-form');
            const prompt = form.find('.snn-ai-prompt-input').val().trim();
            const style = form.find('.snn-ai-style-input').val().trim();
            const generationType = form.find('.snn-ai-generation-type-select').val();
            
            if (!prompt) {
                showNotification('Please enter a prompt', 'error');
                return;
            }
            
            // Show loading
            const button = $(this);
            const originalText = button.text();
            button.prop('disabled', true).text('Generating...');
            
            const data = {
                action: 'snn_ai_media_edit',
                attachment_id: attachmentId,
                prompt: prompt,
                style_description: style,
                generation_type: generationType,
                nonce: snnAiMedia.nonce
            };
            
            $.ajax({
                url: snnAiMedia.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        const resultContainer = modal.find('.snn-ai-quick-result');
                        const resultImage = resultContainer.find('img');
                        
                        resultImage.attr('src', response.data.image_url);
                        resultContainer.data('generated-image-id', response.data.image_id);
                        resultContainer.data('generated-image-url', response.data.image_url);
                        
                        form.hide();
                        resultContainer.removeClass('hidden');
                        
                        showNotification('Image generated successfully!', 'success');
                    } else {
                        showNotification(response.data || 'Generation failed', 'error');
                    }
                },
                error: function() {
                    showNotification('Generation failed', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle try again
        modal.find('.snn-ai-try-again').on('click', function() {
            modal.find('.snn-ai-quick-result').addClass('hidden');
            modal.find('.snn-ai-quick-edit-form').show();
        });
        
        // Handle use generated image
        modal.find('.snn-ai-use-generated').on('click', function() {
            const resultContainer = modal.find('.snn-ai-quick-result');
            const imageUrl = resultContainer.data('generated-image-url');
            
            if (imageUrl) {
                // Update the media library view
                updateMediaLibraryImage(attachmentId, imageUrl);
                modal.remove();
                showNotification('Image updated successfully!', 'success');
            }
        });
    }

    function updatePromptPlaceholder(container, type) {
        const promptInput = container.find('.snn-ai-prompt-input');
        const placeholders = {
            'style_transfer': 'Transform this image into a different style...',
            'background_removal': 'Remove background and place in new scene...',
            'product_variation': 'Generate variations of this product...',
            'category_banner': 'Create a banner for this category...'
        };
        
        promptInput.attr('placeholder', placeholders[type] || 'Describe your transformation...');
    }

    function replaceOriginalImage(imageUrl) {
        // Find the current image container and replace it
        const imageContainer = $('.attachment-details .thumbnail-image');
        if (imageContainer.length) {
            imageContainer.find('img').attr('src', imageUrl);
        }
    }

    function updateMediaLibraryImage(attachmentId, imageUrl) {
        // Update the media library grid item
        const mediaItem = $(`.media-item[data-id="${attachmentId}"]`);
        if (mediaItem.length) {
            mediaItem.find('img').attr('src', imageUrl);
        }
        
        // Update the media modal if open
        const modalImage = $('.media-modal .thumbnail-image img');
        if (modalImage.length) {
            modalImage.attr('src', imageUrl);
        }
    }

    // Utility functions
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="snn-notification notice notice-${type} is-dismissible">
                <p>${message}</p>
            </div>
        `);
        
        $('.wrap h1').after(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut();
        }, 5000);
    }

    // Initialize tooltips for media library
    function initializeTooltips() {
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]', {
                theme: 'light',
                arrow: true,
                delay: [500, 0]
            });
        }
    }

    // Re-initialize tooltips when media modal opens
    $(document).on('DOMNodeInserted', '.media-modal', function() {
        setTimeout(initializeTooltips, 100);
    });

})(jQuery);