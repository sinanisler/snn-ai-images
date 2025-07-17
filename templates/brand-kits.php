<?php
if (!defined('ABSPATH')) {
    exit;
}

$brand_kits = SNN_AI_Images_Brand_Kit::get_instance()->get_user_brand_kits(get_current_user_id());
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Brand Kits', 'snn-ai-images'); ?></h1>
    <a href="#" class="page-title-action" id="snn-add-brand-kit"><?php _e('Add New', 'snn-ai-images'); ?></a>
    <a href="#" class="page-title-action" id="snn-import-brand-kit"><?php _e('Import', 'snn-ai-images'); ?></a>
    
    <div class="snn-brand-kits-container max-w-7xl mx-auto p-6">
        <!-- Brand Kits Grid -->
        <div class="snn-brand-kits-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($brand_kits)): ?>
                <?php foreach ($brand_kits as $brand_kit): ?>
                    <?php 
                    $colors = json_decode($brand_kit->colors, true) ?: array();
                    $fonts = json_decode($brand_kit->fonts, true) ?: array();
                    ?>
                    <div class="snn-brand-kit-card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>">
                        <div class="snn-brand-kit-header flex items-center justify-between mb-4">
                            <h3 class="snn-brand-kit-name text-lg font-medium text-gray-800"><?php echo esc_html($brand_kit->name); ?></h3>
                            <div class="snn-brand-kit-actions">
                                <div class="dropdown">
                                    <button type="button" class="snn-brand-kit-menu text-gray-600 hover:text-gray-800" data-tippy-content="<?php esc_attr_e('More actions', 'snn-ai-images'); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zM12 13a1 1 0 110-2 1 1 0 010 2zM12 20a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                        <button type="button" class="snn-edit-brand-kit block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>">
                                            <?php _e('Edit', 'snn-ai-images'); ?>
                                        </button>
                                        <button type="button" class="snn-duplicate-brand-kit block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>">
                                            <?php _e('Duplicate', 'snn-ai-images'); ?>
                                        </button>
                                        <button type="button" class="snn-export-brand-kit block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>">
                                            <?php _e('Export', 'snn-ai-images'); ?>
                                        </button>
                                        <hr class="my-1">
                                        <button type="button" class="snn-delete-brand-kit block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>">
                                            <?php _e('Delete', 'snn-ai-images'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Color Palette -->
                        <?php if (!empty($colors)): ?>
                            <div class="snn-brand-kit-colors mb-4">
                                <div class="snn-colors-label text-sm font-medium text-gray-700 mb-2"><?php _e('Colors:', 'snn-ai-images'); ?></div>
                                <div class="snn-color-palette flex flex-wrap gap-2">
                                    <?php foreach ($colors as $color): ?>
                                        <div class="snn-color-swatch w-8 h-8 rounded-full border-2 border-gray-200" 
                                             style="background-color: <?php echo esc_attr($color); ?>"
                                             data-tippy-content="<?php echo esc_attr($color); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Fonts -->
                        <?php if (!empty($fonts)): ?>
                            <div class="snn-brand-kit-fonts mb-4">
                                <div class="snn-fonts-label text-sm font-medium text-gray-700 mb-2"><?php _e('Fonts:', 'snn-ai-images'); ?></div>
                                <div class="snn-font-list text-sm text-gray-600">
                                    <?php echo esc_html(implode(', ', $fonts)); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Style Guidelines -->
                        <?php if (!empty($brand_kit->style_guidelines)): ?>
                            <div class="snn-brand-kit-guidelines mb-4">
                                <div class="snn-guidelines-label text-sm font-medium text-gray-700 mb-2"><?php _e('Style Guidelines:', 'snn-ai-images'); ?></div>
                                <div class="snn-guidelines-text text-sm text-gray-600 line-clamp-3">
                                    <?php echo esc_html($brand_kit->style_guidelines); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Actions -->
                        <div class="snn-brand-kit-card-actions flex space-x-2 mt-4 pt-4 border-t border-gray-200">
                            <button type="button" class="snn-use-brand-kit flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>">
                                <?php _e('Use This Kit', 'snn-ai-images'); ?>
                            </button>
                            <button type="button" class="snn-duplicate-brand-kit bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors" data-brand-kit-id="<?php echo esc_attr($brand_kit->id); ?>" data-tippy-content="<?php esc_attr_e('Duplicate this brand kit', 'snn-ai-images'); ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Created Date -->
                        <div class="snn-brand-kit-meta text-xs text-gray-500 mt-2">
                            <?php _e('Created:', 'snn-ai-images'); ?> <?php echo esc_html(human_time_diff(strtotime($brand_kit->created_at))); ?> ago
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="snn-empty-state col-span-full text-center py-12">
                    <div class="snn-empty-icon text-6xl text-gray-400 mb-4">🎨</div>
                    <h3 class="text-lg font-medium text-gray-800 mb-2"><?php _e('No brand kits yet', 'snn-ai-images'); ?></h3>
                    <p class="text-gray-600 mb-4"><?php _e('Create your first brand kit to maintain consistent styling across your AI-generated images.', 'snn-ai-images'); ?></p>
                    <button type="button" class="snn-button-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors" id="snn-create-first-brand-kit">
                        <?php _e('Create Your First Brand Kit', 'snn-ai-images'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Brand Kit Modal -->
<div id="snn-brand-kit-modal" class="snn-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="snn-modal-content bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="snn-modal-header flex items-center justify-between mb-6">
            <h3 class="text-xl font-medium text-gray-800" id="snn-brand-kit-modal-title"><?php _e('Create Brand Kit', 'snn-ai-images'); ?></h3>
            <button type="button" class="snn-modal-close text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="snn-brand-kit-form" class="space-y-6">
            <input type="hidden" id="snn-brand-kit-id" value="">
            
            <div class="snn-form-group">
                <label for="snn-brand-kit-name" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php _e('Brand Kit Name', 'snn-ai-images'); ?>
                    <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="snn-brand-kit-name" 
                    class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="<?php esc_attr_e('My Brand Kit', 'snn-ai-images'); ?>"
                    required
                >
            </div>
            
            <div class="snn-form-group">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <?php _e('Brand Colors', 'snn-ai-images'); ?>
                </label>
                <div class="snn-color-picker-container">
                    <div class="snn-color-inputs-wrapper">
                        <div class="snn-color-inputs grid grid-cols-4 gap-3 mb-3">
                            <!-- Color inputs will be added dynamically -->
                        </div>
                        <button type="button" class="snn-add-color-btn bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded-md transition-colors">
                            <?php _e('+ Add Color', 'snn-ai-images'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="snn-form-group">
                <label for="snn-brand-kit-fonts" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php _e('Fonts', 'snn-ai-images'); ?>
                </label>
                <div class="snn-font-selector">
                    <select id="snn-brand-kit-fonts" class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" multiple>
                        <option value="Arial">Arial</option>
                        <option value="Helvetica">Helvetica</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Tahoma">Tahoma</option>
                        <option value="Trebuchet MS">Trebuchet MS</option>
                        <option value="Impact">Impact</option>
                        <option value="Courier New">Courier New</option>
                        <option value="Lucida Console">Lucida Console</option>
                    </select>
                    <div class="snn-font-preview mt-2 text-sm text-gray-600">
                        <?php _e('Hold Ctrl/Cmd to select multiple fonts', 'snn-ai-images'); ?>
                    </div>
                </div>
            </div>
            
            <div class="snn-form-group">
                <label for="snn-brand-kit-guidelines" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php _e('Style Guidelines', 'snn-ai-images'); ?>
                </label>
                <textarea 
                    id="snn-brand-kit-guidelines" 
                    rows="4" 
                    class="snn-textarea w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="<?php esc_attr_e('Describe your brand style, tone, and visual preferences...', 'snn-ai-images'); ?>"
                    data-tippy-content="<?php esc_attr_e('Provide detailed guidelines about your brand style, preferred aesthetics, tone, and any specific requirements for AI-generated images', 'snn-ai-images'); ?>"
                ></textarea>
            </div>
            
            <div class="snn-form-actions flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" class="snn-button-secondary bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors" id="snn-cancel-brand-kit">
                    <?php _e('Cancel', 'snn-ai-images'); ?>
                </button>
                <button type="submit" class="snn-button-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                    <span class="snn-save-text"><?php _e('Save Brand Kit', 'snn-ai-images'); ?></span>
                    <span class="snn-save-loading hidden">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <?php _e('Saving...', 'snn-ai-images'); ?>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Import Brand Kit Modal -->
<div id="snn-import-modal" class="snn-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="snn-modal-content bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="snn-modal-header p-6 border-b border-gray-200">
            <h3 class="snn-modal-title text-lg font-medium text-gray-800"><?php _e('Import Brand Kit', 'snn-ai-images'); ?></h3>
            <button type="button" class="snn-close-modal absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="snn-modal-body p-6">
            <form id="snn-import-form" enctype="multipart/form-data">
                <div class="snn-form-group mb-4">
                    <label for="snn-import-file" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('Select Brand Kit File', 'snn-ai-images'); ?>
                    </label>
                    <input 
                        type="file" 
                        id="snn-import-file" 
                        accept=".json"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    >
                    <p class="text-xs text-gray-500 mt-1"><?php _e('Upload a .json file exported from another brand kit', 'snn-ai-images'); ?></p>
                </div>
                
                <div class="snn-form-actions flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="snn-button-secondary bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors" id="snn-cancel-import">
                        <?php _e('Cancel', 'snn-ai-images'); ?>
                    </button>
                    <button type="submit" class="snn-button-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        <span class="snn-import-text"><?php _e('Import', 'snn-ai-images'); ?></span>
                        <span class="snn-import-loading hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?php _e('Importing...', 'snn-ai-images'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Color Input Template -->
<template id="snn-color-input-template">
    <div class="snn-color-input-wrapper relative">
        <input type="color" class="snn-color-input w-full h-12 rounded border-0 cursor-pointer">
        <button type="button" class="snn-remove-color absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</template>