<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get generation history
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

$api = SNN_AI_Images_API::get_instance();
$request = new WP_REST_Request();
$request->set_param('page', $page);
$request->set_param('per_page', $per_page);

$history = $api->get_generation_history($request);
if (is_wp_error($history)) {
    $history_data = array('items' => array(), 'total' => 0, 'total_pages' => 0);
} else {
    $history_data = $history->get_data();
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Generation History', 'snn-ai-images'); ?></h1>
    
    <div class="snn-history-container max-w-7xl mx-auto p-6">
        <!-- Filters -->
        <div class="snn-history-filters bg-white rounded-lg shadow-md p-4 mb-6">
            <form method="get" class="snn-filter-form">
                <input type="hidden" name="page" value="snn-ai-images-history">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="snn-filter-group">
                        <label for="snn-filter-type" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Generation Type', 'snn-ai-images'); ?>
                        </label>
                        <select 
                            id="snn-filter-type" 
                            name="type" 
                            class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value=""><?php _e('All Types', 'snn-ai-images'); ?></option>
                            <option value="style_transfer" <?php selected($filter_type, 'style_transfer'); ?>><?php _e('Style Transfer', 'snn-ai-images'); ?></option>
                            <option value="background_removal" <?php selected($filter_type, 'background_removal'); ?>><?php _e('Background Removal', 'snn-ai-images'); ?></option>
                            <option value="product_variation" <?php selected($filter_type, 'product_variation'); ?>><?php _e('Product Variation', 'snn-ai-images'); ?></option>
                            <option value="category_banner" <?php selected($filter_type, 'category_banner'); ?>><?php _e('Category Banner', 'snn-ai-images'); ?></option>
                        </select>
                    </div>
                    
                    <div class="snn-filter-group">
                        <label for="snn-filter-status" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Status', 'snn-ai-images'); ?>
                        </label>
                        <select 
                            id="snn-filter-status" 
                            name="status" 
                            class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value=""><?php _e('All Statuses', 'snn-ai-images'); ?></option>
                            <option value="completed" <?php selected($filter_status, 'completed'); ?>><?php _e('Completed', 'snn-ai-images'); ?></option>
                            <option value="failed" <?php selected($filter_status, 'failed'); ?>><?php _e('Failed', 'snn-ai-images'); ?></option>
                            <option value="pending" <?php selected($filter_status, 'pending'); ?>><?php _e('Pending', 'snn-ai-images'); ?></option>
                        </select>
                    </div>
                    
                    <div class="snn-filter-group">
                        <label for="snn-filter-date" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Date Range', 'snn-ai-images'); ?>
                        </label>
                        <select 
                            id="snn-filter-date" 
                            name="date" 
                            class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value=""><?php _e('All Time', 'snn-ai-images'); ?></option>
                            <option value="today" <?php selected($filter_date, 'today'); ?>><?php _e('Today', 'snn-ai-images'); ?></option>
                            <option value="week" <?php selected($filter_date, 'week'); ?>><?php _e('This Week', 'snn-ai-images'); ?></option>
                            <option value="month" <?php selected($filter_date, 'month'); ?>><?php _e('This Month', 'snn-ai-images'); ?></option>
                        </select>
                    </div>
                    
                    <div class="snn-filter-actions flex space-x-2">
                        <button type="submit" class="snn-button-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            <?php _e('Filter', 'snn-ai-images'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=snn-ai-images-history'); ?>" class="snn-button-secondary bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            <?php _e('Clear', 'snn-ai-images'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- History Items -->
        <div class="snn-history-items">
            <?php if (!empty($history_data['items'])): ?>
                <div class="snn-history-grid space-y-4">
                    <?php foreach ($history_data['items'] as $item): ?>
                        <div class="snn-history-item bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                            <div class="snn-history-content grid grid-cols-1 lg:grid-cols-4 gap-6">
                                <!-- Original Image -->
                                <div class="snn-history-original">
                                    <div class="snn-history-section-title text-sm font-medium text-gray-700 mb-2">
                                        <?php _e('Original Image', 'snn-ai-images'); ?>
                                    </div>
                                    <div class="snn-history-image-container">
                                        <?php if ($item->original_image_id): ?>
                                            <div class="snn-history-image w-full h-32 bg-gray-100 rounded-md overflow-hidden">
                                                <?php echo wp_get_attachment_image($item->original_image_id, 'medium', false, array('class' => 'w-full h-full object-cover')); ?>
                                            </div>
                                            <div class="snn-history-image-info mt-2 text-xs text-gray-500">
                                                <?php echo esc_html(get_the_title($item->original_image_id)); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="snn-history-image w-full h-32 bg-gray-100 rounded-md flex items-center justify-center text-gray-400">
                                                <span class="text-2xl">📷</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Generation Details -->
                                <div class="snn-history-details lg:col-span-2">
                                    <div class="snn-history-section-title text-sm font-medium text-gray-700 mb-2">
                                        <?php _e('Generation Details', 'snn-ai-images'); ?>
                                    </div>
                                    
                                    <div class="snn-history-prompt mb-3">
                                        <div class="snn-history-label text-xs text-gray-500 mb-1"><?php _e('Prompt:', 'snn-ai-images'); ?></div>
                                        <div class="snn-history-value text-sm text-gray-800"><?php echo esc_html($item->prompt); ?></div>
                                    </div>
                                    
                                    <?php if (!empty($item->style_description)): ?>
                                        <div class="snn-history-style mb-3">
                                            <div class="snn-history-label text-xs text-gray-500 mb-1"><?php _e('Style:', 'snn-ai-images'); ?></div>
                                            <div class="snn-history-value text-sm text-gray-800"><?php echo esc_html($item->style_description); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="snn-history-meta grid grid-cols-2 gap-4 text-xs">
                                        <div class="snn-history-type">
                                            <div class="snn-history-label text-gray-500 mb-1"><?php _e('Type:', 'snn-ai-images'); ?></div>
                                            <div class="snn-history-value">
                                                <?php
                                                $type_labels = array(
                                                    'style_transfer' => __('Style Transfer', 'snn-ai-images'),
                                                    'background_removal' => __('Background Removal', 'snn-ai-images'),
                                                    'product_variation' => __('Product Variation', 'snn-ai-images'),
                                                    'category_banner' => __('Category Banner', 'snn-ai-images')
                                                );
                                                echo esc_html($type_labels[$item->generation_type] ?? $item->generation_type);
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div class="snn-history-date">
                                            <div class="snn-history-label text-gray-500 mb-1"><?php _e('Created:', 'snn-ai-images'); ?></div>
                                            <div class="snn-history-value"><?php echo esc_html(human_time_diff(strtotime($item->created_at))); ?> ago</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Generated Image -->
                                <div class="snn-history-generated">
                                    <div class="snn-history-section-title text-sm font-medium text-gray-700 mb-2 flex items-center">
                                        <?php _e('Generated Image', 'snn-ai-images'); ?>
                                        <div class="snn-history-status ml-2">
                                            <?php if ($item->status === 'completed'): ?>
                                                <span class="snn-status-badge bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">✓ <?php _e('Completed', 'snn-ai-images'); ?></span>
                                            <?php elseif ($item->status === 'failed'): ?>
                                                <span class="snn-status-badge bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">✗ <?php _e('Failed', 'snn-ai-images'); ?></span>
                                            <?php else: ?>
                                                <span class="snn-status-badge bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">⏳ <?php _e('Pending', 'snn-ai-images'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($item->status === 'completed' && $item->generated_image_id): ?>
                                        <div class="snn-history-image-container">
                                            <div class="snn-history-image w-full h-32 bg-gray-100 rounded-md overflow-hidden">
                                                <?php echo wp_get_attachment_image($item->generated_image_id, 'medium', false, array('class' => 'w-full h-full object-cover')); ?>
                                            </div>
                                            <div class="snn-history-image-actions mt-2 flex space-x-2">
                                                <a href="<?php echo wp_get_attachment_url($item->generated_image_id); ?>" target="_blank" class="snn-action-btn bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs px-2 py-1 rounded transition-colors">
                                                    <?php _e('View', 'snn-ai-images'); ?>
                                                </a>
                                                <a href="<?php echo wp_get_attachment_url($item->generated_image_id); ?>" download class="snn-action-btn bg-green-100 hover:bg-green-200 text-green-700 text-xs px-2 py-1 rounded transition-colors">
                                                    <?php _e('Download', 'snn-ai-images'); ?>
                                                </a>
                                                <button type="button" class="snn-regenerate-btn bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs px-2 py-1 rounded transition-colors" data-history-id="<?php echo esc_attr($item->id); ?>">
                                                    <?php _e('Regenerate', 'snn-ai-images'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php elseif ($item->status === 'failed'): ?>
                                        <div class="snn-history-error w-full h-32 bg-red-50 rounded-md flex items-center justify-center text-red-500">
                                            <div class="text-center">
                                                <div class="text-2xl mb-2">⚠️</div>
                                                <div class="text-sm"><?php _e('Generation Failed', 'snn-ai-images'); ?></div>
                                                <?php if (!empty($item->error_message)): ?>
                                                    <div class="text-xs text-red-400 mt-1" data-tippy-content="<?php echo esc_attr($item->error_message); ?>">
                                                        <?php _e('Hover for details', 'snn-ai-images'); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="snn-history-retry-actions mt-2">
                                            <button type="button" class="snn-retry-btn bg-red-100 hover:bg-red-200 text-red-700 text-xs px-2 py-1 rounded transition-colors" data-history-id="<?php echo esc_attr($item->id); ?>">
                                                <?php _e('Retry', 'snn-ai-images'); ?>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="snn-history-pending w-full h-32 bg-yellow-50 rounded-md flex items-center justify-center text-yellow-600">
                                            <div class="text-center">
                                                <div class="text-2xl mb-2">⏳</div>
                                                <div class="text-sm"><?php _e('Processing...', 'snn-ai-images'); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($history_data['total_pages'] > 1): ?>
                    <div class="snn-history-pagination mt-8">
                        <div class="snn-pagination-info text-sm text-gray-600 mb-4">
                            <?php
                            $start = ($page - 1) * $per_page + 1;
                            $end = min($page * $per_page, $history_data['total']);
                            printf(
                                __('Showing %d to %d of %d generations', 'snn-ai-images'),
                                $start,
                                $end,
                                $history_data['total']
                            );
                            ?>
                        </div>
                        
                        <div class="snn-pagination-links flex justify-center space-x-2">
                            <?php
                            $current_url = remove_query_arg('paged');
                            
                            // Previous page
                            if ($page > 1) {
                                $prev_url = add_query_arg('paged', $page - 1, $current_url);
                                echo '<a href="' . esc_url($prev_url) . '" class="snn-pagination-link bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors">‹ ' . __('Previous', 'snn-ai-images') . '</a>';
                            }
                            
                            // Page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($history_data['total_pages'], $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $page_url = add_query_arg('paged', $i, $current_url);
                                $active_class = ($i === $page) ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-50 text-gray-700';
                                echo '<a href="' . esc_url($page_url) . '" class="snn-pagination-link ' . $active_class . ' font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors">' . $i . '</a>';
                            }
                            
                            // Next page
                            if ($page < $history_data['total_pages']) {
                                $next_url = add_query_arg('paged', $page + 1, $current_url);
                                echo '<a href="' . esc_url($next_url) . '" class="snn-pagination-link bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors">' . __('Next', 'snn-ai-images') . ' ›</a>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="snn-empty-state text-center py-12">
                    <div class="snn-empty-icon text-6xl text-gray-400 mb-4">📸</div>
                    <h3 class="text-lg font-medium text-gray-800 mb-2"><?php _e('No generations yet', 'snn-ai-images'); ?></h3>
                    <p class="text-gray-600 mb-4"><?php _e('Your AI image generation history will appear here.', 'snn-ai-images'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=snn-ai-images-dashboard'); ?>" class="snn-button-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        <?php _e('Start Generating Images', 'snn-ai-images'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>