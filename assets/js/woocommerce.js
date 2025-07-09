/**
 * SNN AI Images WooCommerce JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeWooCommerceIntegration();
    });

    function initializeWooCommerceIntegration() {
        // Handle product variation generation
        $(document).on('click', '.snn-ai-generate-variations', function(e) {
            e.preventDefault();
            generateProductVariations($(this));
        });

        // Handle background removal
        $(document).on('click', '.snn-ai-remove-background', function(e) {
            e.preventDefault();
            generateProductBackground($(this));
        });

        // Handle lifestyle generation
        $(document).on('click', '.snn-ai-generate-lifestyle', function(e) {
            e.preventDefault();
            generateProductLifestyle($(this));
        });

        // Handle add to gallery
        $(document).on('click', '.snn-ai-add-to-gallery', function(e) {
            e.preventDefault();
            addToProductGallery($(this));
        });

        // Handle set as featured
        $(document).on('click', '.snn-ai-set-featured', function(e) {
            e.preventDefault();
            setAsFeaturedImage($(this));
        });
    }

    function generateProductVariations(button) {
        const container = button.closest('.snn-ai-product-container');
        const productId = button.data('product-id');
        const count = container.find('#snn-ai-variation-count').val() || 3;
        const brandKitId = container.find('#snn-ai-product-brand-kit').val();

        if (!productId) {
            showNotification('Product ID not found', 'error');
            return;
        }

        // Show loading state
        const originalText = button.text();
        button.prop('disabled', true).text(snnAiWooCommerce.strings.generating);

        const data = {
            action: 'snn_ai_generate_product_variations',
            product_id: productId,
            count: count,
            brand_kit_id: brandKitId || '',
            nonce: snnAiWooCommerce.nonce
        };

        $.ajax({
            url: snnAiWooCommerce.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    displayProductResults(container, response.data, 'variations');
                    showNotification(snnAiWooCommerce.strings.success, 'success');
                } else {
                    showNotification(response.data || snnAiWooCommerce.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(snnAiWooCommerce.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }

    function generateProductBackground(button) {
        const container = button.closest('.snn-ai-product-container');
        const productId = button.data('product-id');
        const brandKitId = container.find('#snn-ai-product-brand-kit').val();

        if (!productId) {
            showNotification('Product ID not found', 'error');
            return;
        }

        // Show loading state
        const originalText = button.text();
        button.prop('disabled', true).text(snnAiWooCommerce.strings.generating);

        const data = {
            action: 'snn_ai_generate_product_background',
            product_id: productId,
            lifestyle_prompt: 'clean white background, product photography',
            brand_kit_id: brandKitId || '',
            nonce: snnAiWooCommerce.nonce
        };

        $.ajax({
            url: snnAiWooCommerce.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    displayProductResults(container, [response.data], 'background');
                    showNotification(snnAiWooCommerce.strings.success, 'success');
                } else {
                    showNotification(response.data || snnAiWooCommerce.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(snnAiWooCommerce.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }

    function generateProductLifestyle(button) {
        const container = button.closest('.snn-ai-product-container');
        const productId = button.data('product-id');
        const lifestylePrompt = container.find('#snn-ai-lifestyle-prompt').val().trim();
        const brandKitId = container.find('#snn-ai-product-brand-kit').val();

        if (!productId) {
            showNotification('Product ID not found', 'error');
            return;
        }

        if (!lifestylePrompt) {
            showNotification('Please enter a lifestyle prompt', 'error');
            container.find('#snn-ai-lifestyle-prompt').focus();
            return;
        }

        // Show loading state
        const originalText = button.text();
        button.prop('disabled', true).text(snnAiWooCommerce.strings.generating);

        const data = {
            action: 'snn_ai_generate_product_background',
            product_id: productId,
            lifestyle_prompt: lifestylePrompt,
            brand_kit_id: brandKitId || '',
            nonce: snnAiWooCommerce.nonce
        };

        $.ajax({
            url: snnAiWooCommerce.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    displayProductResults(container, [response.data], 'lifestyle');
                    showNotification(snnAiWooCommerce.strings.success, 'success');
                } else {
                    showNotification(response.data || snnAiWooCommerce.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(snnAiWooCommerce.strings.error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }

    function displayProductResults(container, results, type) {
        const resultsContainer = container.find('.snn-ai-product-results');
        const resultsGrid = resultsContainer.find('.snn-ai-results-grid');
        
        resultsGrid.empty();
        
        results.forEach(result => {
            const resultItem = $(`
                <div class="snn-ai-result-item border rounded-lg p-4 mb-4">
                    <div class="snn-ai-result-image-container mb-3">
                        <img src="${result.image_url}" alt="Generated image" class="w-full h-48 object-cover rounded">
                    </div>
                    <div class="snn-ai-result-actions">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="snn-ai-add-to-gallery bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm" data-image-id="${result.attachment_id}">
                                ${snnAiWooCommerce.strings.addToGallery}
                            </button>
                            <button type="button" class="snn-ai-set-featured bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm" data-image-id="${result.attachment_id}">
                                ${snnAiWooCommerce.strings.setAsFeatured}
                            </button>
                            <a href="${result.image_url}" target="_blank" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                View
                            </a>
                            <a href="${result.image_url}" download class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded text-sm">
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            `);
            
            resultsGrid.append(resultItem);
        });
        
        resultsContainer.show();
    }

    function addToProductGallery(button) {
        const imageId = button.data('image-id');
        const productId = getProductId();
        
        if (!imageId || !productId) {
            showNotification('Missing image or product ID', 'error');
            return;
        }
        
        // Get current gallery IDs
        const galleryInput = $('#product_image_gallery');
        const currentGallery = galleryInput.val() ? galleryInput.val().split(',') : [];
        
        // Add new image to gallery
        if (!currentGallery.includes(imageId.toString())) {
            currentGallery.push(imageId.toString());
            galleryInput.val(currentGallery.join(','));
            
            // Trigger change event to update gallery display
            galleryInput.trigger('change');
            
            showNotification('Image added to product gallery', 'success');
            
            // Update gallery display if WooCommerce gallery exists
            updateWooCommerceGalleryDisplay(imageId);
        } else {
            showNotification('Image already in gallery', 'info');
        }
    }

    function setAsFeaturedImage(button) {
        const imageId = button.data('image-id');
        const productId = getProductId();
        
        if (!imageId || !productId) {
            showNotification('Missing image or product ID', 'error');
            return;
        }
        
        // Set as featured image
        const featuredInput = $('#_thumbnail_id');
        featuredInput.val(imageId);
        
        // Update featured image display
        updateFeaturedImageDisplay(imageId);
        
        showNotification('Set as featured image', 'success');
    }

    function updateWooCommerceGalleryDisplay(imageId) {
        // This function would update the WooCommerce gallery display
        // Implementation depends on WooCommerce version and gallery structure
        
        // Try to find and update the gallery container
        const galleryContainer = $('.product_images');
        if (galleryContainer.length) {
            // Add the new image to the gallery display
            const imageHtml = `
                <li class="image" data-attachment_id="${imageId}">
                    <img src="${wp.media.attachment(imageId).get('url')}" alt="" />
                    <a href="#" class="delete">×</a>
                </li>
            `;
            galleryContainer.find('ul').append(imageHtml);
        }
    }

    function updateFeaturedImageDisplay(imageId) {
        // Update the featured image display
        const featuredContainer = $('#postimagediv');
        if (featuredContainer.length) {
            // Update the featured image preview
            wp.media.attachment(imageId).fetch().then(function(attachment) {
                const imageHtml = `
                    <img src="${attachment.url}" alt="${attachment.alt}" />
                `;
                featuredContainer.find('.inside').html(imageHtml);
            });
        }
    }

    function getProductId() {
        // Get product ID from various possible sources
        const productId = $('#post_ID').val() || 
                         $('input[name="post_ID"]').val() || 
                         window.typenow === 'product' ? $('#post_ID').val() : null;
        
        return productId;
    }

    // Utility functions
    function showNotification(message, type = 'info') {
        const notificationClass = type === 'error' ? 'notice-error' : 
                                 type === 'success' ? 'notice-success' : 
                                 type === 'warning' ? 'notice-warning' : 'notice-info';
        
        const notification = $(`
            <div class="notice ${notificationClass} is-dismissible">
                <p>${message}</p>
            </div>
        `);
        
        $('.wrap h1').after(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut();
        }, 5000);
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

})(jQuery);