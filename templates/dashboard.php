<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get user usage stats
$api = SNN_AI_Images_API::get_instance();
$usage_stats = $api->get_usage_stats(new WP_REST_Request());
if (!is_wp_error($usage_stats)) {
    $usage_data = $usage_stats->get_data();
} else {
    $usage_data = array(
        'current_month' => 0,
        'total_usage' => 0,
        'max_generations' => 50,
        'remaining' => 50
    );
}

// Get recent generation history
$request = new WP_REST_Request('GET', '/snn-ai/v1/history');
$request->set_param('page', 1);
$request->set_param('per_page', 10);
$history = $api->get_generation_history($request);
if (!is_wp_error($history)) {
    $history_data = $history->get_data();
} else {
    $history_data = array('items' => array());
}

// Get brand kits
$brand_kits = SNN_AI_Images_Brand_Kit::get_instance()->get_user_brand_kits(get_current_user_id());
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('SNN AI Images Dashboard', 'snn-ai-images'); ?></h1>
    
    <div class="snn-ai-dashboard-container max-w-7xl mx-auto p-6">
        <!-- Usage Statistics -->
        <div class="snn-usage-stats mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="snn-stat-card bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="snn-stat-icon text-blue-500 text-2xl mb-2">📊</div>
                    <div class="snn-stat-number text-3xl font-bold text-gray-800"><?php echo esc_html($usage_data['current_month']); ?></div>
                    <div class="snn-stat-label text-gray-600"><?php _e('This Month', 'snn-ai-images'); ?></div>
                </div>
                
                <div class="snn-stat-card bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="snn-stat-icon text-green-500 text-2xl mb-2">🎯</div>
                    <div class="snn-stat-number text-3xl font-bold text-gray-800"><?php echo esc_html($usage_data['remaining']); ?></div>
                    <div class="snn-stat-label text-gray-600"><?php _e('Remaining', 'snn-ai-images'); ?></div>
                </div>
                
                <div class="snn-stat-card bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="snn-stat-icon text-purple-500 text-2xl mb-2">🔄</div>
                    <div class="snn-stat-number text-3xl font-bold text-gray-800"><?php echo esc_html($usage_data['total_usage']); ?></div>
                    <div class="snn-stat-label text-gray-600"><?php _e('Total Generated', 'snn-ai-images'); ?></div>
                </div>
                
                <div class="snn-stat-card bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                    <div class="snn-stat-icon text-orange-500 text-2xl mb-2">🎨</div>
                    <div class="snn-stat-number text-3xl font-bold text-gray-800"><?php echo count($brand_kits); ?></div>
                    <div class="snn-stat-label text-gray-600"><?php _e('Brand Kits', 'snn-ai-images'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Image Generation Panel -->
            <div class="lg:col-span-2">
                <div class="snn-generation-panel bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <span class="text-blue-500 mr-2">🎨</span>
                        <?php _e('Generate AI Images', 'snn-ai-images'); ?>
                    </h2>
                    
                    <!-- Upload Area -->
                    <div class="snn-upload-area mb-6">
                        <div class="snn-dropzone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer" 
                             id="snn-image-dropzone"
                             data-tippy-content="<?php esc_attr_e('Drop images here or click to select. Supports JPG, PNG, GIF, WebP up to 10MB', 'snn-ai-images'); ?>">
                            <div class="snn-upload-icon text-gray-400 text-4xl mb-4">📁</div>
                            <div class="snn-upload-text text-gray-600">
                                <p class="text-lg font-medium"><?php _e('Drop images here or click to select', 'snn-ai-images'); ?></p>
                                <p class="text-sm"><?php _e('Supports JPG, PNG, GIF, WebP up to 10MB', 'snn-ai-images'); ?></p>
                            </div>
                            <input type="file" id="snn-image-input" accept="image/*" multiple class="hidden">
                        </div>
                        
                        <div class="snn-selected-images mt-4 hidden">
                            <h3 class="text-lg font-medium text-gray-800 mb-2"><?php _e('Selected Images:', 'snn-ai-images'); ?></h3>
                            <div class="snn-image-previews grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                        </div>
                    </div>
                    
                    <!-- Generation Controls -->
                    <div class="snn-generation-controls space-y-4">
                        <div class="snn-form-group">
                            <label for="snn-prompt-input" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Transformation Prompt', 'snn-ai-images'); ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="snn-prompt-input" 
                                rows="3" 
                                class="snn-textarea w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="<?php esc_attr_e('Describe how you want to transform your images...', 'snn-ai-images'); ?>"
                                data-tippy-content="<?php esc_attr_e('Be specific about the style, mood, and transformation you want. Example: Transform this into a vintage poster style with warm colors and artistic typography', 'snn-ai-images'); ?>"
                            ></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="snn-form-group">
                                <label for="snn-style-input" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('Style Description', 'snn-ai-images'); ?>
                                </label>
                                <input 
                                    type="text" 
                                    id="snn-style-input" 
                                    class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="<?php esc_attr_e('e.g., modern, vintage, artistic, professional...', 'snn-ai-images'); ?>"
                                    data-tippy-content="<?php esc_attr_e('Optional: Add style keywords to enhance the transformation', 'snn-ai-images'); ?>"
                                >
                            </div>
                            
                            <div class="snn-form-group">
                                <label for="snn-brand-kit-select" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('Brand Kit', 'snn-ai-images'); ?>
                                </label>
                                <select 
                                    id="snn-brand-kit-select" 
                                    class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    data-tippy-content="<?php esc_attr_e('Select a brand kit to apply your brand colors and guidelines', 'snn-ai-images'); ?>"
                                >
                                    <option value=""><?php _e('None', 'snn-ai-images'); ?></option>
                                    <?php foreach ($brand_kits as $brand_kit): ?>
                                        <option value="<?php echo esc_attr($brand_kit->id); ?>"><?php echo esc_html($brand_kit->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="snn-form-group">
                            <label for="snn-generation-type-select" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Generation Type', 'snn-ai-images'); ?>
                            </label>
                            <select 
                                id="snn-generation-type-select" 
                                class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                data-tippy-content="<?php esc_attr_e('Choose the type of AI transformation', 'snn-ai-images'); ?>"
                            >
                                <option value="style_transfer"><?php _e('Style Transfer', 'snn-ai-images'); ?></option>
                                <option value="background_removal"><?php _e('Background Removal', 'snn-ai-images'); ?></option>
                                <option value="product_variation"><?php _e('Product Variation', 'snn-ai-images'); ?></option>
                                <option value="category_banner"><?php _e('Category Banner', 'snn-ai-images'); ?></option>
                            </select>
                        </div>
                        
                        <div class="snn-generate-button-container">
                            <button 
                                type="button" 
                                id="snn-generate-button" 
                                class="snn-button-primary w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled
                            >
                                <span class="snn-button-text"><?php _e('Generate AI Images', 'snn-ai-images'); ?></span>
                                <span class="snn-button-loading hidden">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <?php _e('Generating...', 'snn-ai-images'); ?>
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Generation Results -->
                    <div class="snn-generation-results mt-8 hidden">
                        <h3 class="text-lg font-medium text-gray-800 mb-4"><?php _e('Generated Images:', 'snn-ai-images'); ?></h3>
                        <div class="snn-results-grid grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Quick Brand Kit -->
                <div class="snn-quick-brand-kit bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <span class="text-purple-500 mr-2">🎨</span>
                        <?php _e('Quick Brand Kit', 'snn-ai-images'); ?>
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="snn-form-group">
                            <label for="snn-quick-brand-name" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Brand Name', 'snn-ai-images'); ?>
                            </label>
                            <input 
                                type="text" 
                                id="snn-quick-brand-name" 
                                class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="<?php esc_attr_e('My Brand', 'snn-ai-images'); ?>"
                            >
                        </div>
                        
                        <div class="snn-form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Brand Colors', 'snn-ai-images'); ?>
                            </label>
                            <div class="snn-color-picker-container">
                                <div class="snn-color-inputs grid grid-cols-3 gap-2">
                                    <input type="color" value="#3B82F6" class="snn-color-input w-full h-10 rounded border-0 cursor-pointer">
                                    <input type="color" value="#10B981" class="snn-color-input w-full h-10 rounded border-0 cursor-pointer">
                                    <input type="color" value="#F59E0B" class="snn-color-input w-full h-10 rounded border-0 cursor-pointer">
                                </div>
                            </div>
                        </div>
                        
                        <div class="snn-form-group">
                            <label for="snn-quick-brand-style" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Style Guidelines', 'snn-ai-images'); ?>
                            </label>
                            <textarea 
                                id="snn-quick-brand-style" 
                                rows="3" 
                                class="snn-textarea w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="<?php esc_attr_e('Modern, clean, professional...', 'snn-ai-images'); ?>"
                            ></textarea>
                        </div>
                        
                        <button 
                            type="button" 
                            id="snn-save-quick-brand" 
                            class="snn-button-secondary w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
                        >
                            <?php _e('Save Brand Kit', 'snn-ai-images'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Recent Generations -->
                <div class="snn-recent-generations bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <span class="text-green-500 mr-2">📸</span>
                        <?php _e('Recent Generations', 'snn-ai-images'); ?>
                    </h3>
                    
                    <div class="snn-recent-list space-y-3">
                        <?php if (!empty($history_data['items'])): ?>
                            <?php foreach (array_slice($history_data['items'], 0, 5) as $item): ?>
                                <div class="snn-recent-item flex items-center p-3 bg-gray-50 rounded-md">
                                    <div class="snn-recent-image w-12 h-12 bg-gray-200 rounded-md mr-3 flex-shrink-0">
                                        <?php if ($item->generated_image_id): ?>
                                            <?php echo wp_get_attachment_image($item->generated_image_id, 'thumbnail', false, array('class' => 'w-full h-full object-cover rounded-md')); ?>
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <span class="text-xs">📷</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="snn-recent-info flex-1 min-w-0">
                                        <div class="snn-recent-prompt text-sm text-gray-800 truncate"><?php echo esc_html($item->prompt); ?></div>
                                        <div class="snn-recent-date text-xs text-gray-500"><?php echo esc_html(human_time_diff(strtotime($item->created_at))); ?> ago</div>
                                    </div>
                                    <div class="snn-recent-status">
                                        <?php if ($item->status === 'completed'): ?>
                                            <span class="text-green-500 text-sm">✓</span>
                                        <?php elseif ($item->status === 'failed'): ?>
                                            <span class="text-red-500 text-sm">✗</span>
                                        <?php else: ?>
                                            <span class="text-yellow-500 text-sm">⏳</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="snn-empty-state text-center py-6 text-gray-500">
                                <div class="text-2xl mb-2">🎨</div>
                                <p><?php _e('No generations yet', 'snn-ai-images'); ?></p>
                                <p class="text-sm"><?php _e('Start by uploading an image above', 'snn-ai-images'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="snn-recent-actions mt-4 pt-4 border-t border-gray-200">
                        <a href="<?php echo admin_url('admin.php?page=snn-ai-images-history'); ?>" class="snn-link text-blue-600 hover:text-blue-800 text-sm">
                            <?php _e('View all generations →', 'snn-ai-images'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div id="snn-progress-modal" class="snn-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="snn-modal-content bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="snn-modal-header flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-800"><?php _e('Generating Images', 'snn-ai-images'); ?></h3>
            <button type="button" class="snn-modal-close text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="snn-modal-body">
            <div class="snn-progress-info mb-4">
                <div class="snn-progress-text text-sm text-gray-600 mb-2"><?php _e('Processing your images...', 'snn-ai-images'); ?></div>
                <div class="snn-progress-bar bg-gray-200 rounded-full h-2">
                    <div class="snn-progress-fill bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
            
            <div class="snn-progress-details text-xs text-gray-500">
                <div class="snn-progress-current"><?php _e('Starting...', 'snn-ai-images'); ?></div>
            </div>
        </div>
    </div>
</div>