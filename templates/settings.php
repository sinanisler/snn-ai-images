<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission - Use WordPress Settings API for consistency
if (isset($_POST['submit'])) {
    // Let WordPress Settings API handle this
    if (isset($_POST['snn_ai_images_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'snn_ai_images_settings-options')) {
        $admin = SNN_AI_Images_Admin::get_instance();
        $sanitized = $admin->sanitize_settings($_POST['snn_ai_images_settings']);
        update_option('snn_ai_images_settings', $sanitized);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'snn-ai-images') . '</p></div>';
    }
}

// Test API connection
if (isset($_POST['test_api']) && wp_verify_nonce($_POST['_wpnonce'], 'snn_ai_images_settings-options')) {
    $together_ai = SNN_AI_Images_Together_AI::get_instance();
    $test_result = $together_ai->test_connection();
    
    if (is_wp_error($test_result)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('API connection failed:', 'snn-ai-images') . ' ' . esc_html($test_result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('API connection successful!', 'snn-ai-images') . '</p></div>';
    }
}

$settings = get_option('snn_ai_images_settings', array(
    'api_key' => '',
    'model' => 'black-forest-labs/FLUX.1-schnell',
    'max_generations_per_user' => 50,
    'max_file_size' => wp_max_upload_size(),
    'max_files_per_upload' => 5,
    'max_image_dimension' => 1024,
    'temp_directory' => wp_upload_dir()['basedir'] . '/snn-ai-temp/',
    'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'webp')
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('SNN AI Images Settings', 'snn-ai-images'); ?></h1>
    
    <div class="snn-settings-container max-w-4xl mx-auto p-6">
        <form method="post" action="" class="snn-settings-form">
            <?php settings_fields('snn_ai_images_settings'); ?>
            <?php wp_nonce_field('snn_ai_images_settings-options'); ?>
            
            <!-- API Configuration -->
            <div class="snn-settings-section bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="snn-settings-title text-xl font-medium text-gray-800 mb-4 flex items-center">
                    <span class="text-blue-500 mr-2">🔑</span>
                    <?php _e('API Configuration', 'snn-ai-images'); ?>
                </h2>
                
                <div class="snn-settings-grid grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="snn-form-group md:col-span-2">
                        <label for="api_key" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Together AI API Key', 'snn-ai-images'); ?>
                            <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="api_key" 
                            name="snn_ai_images_settings[api_key]" 
                            value="<?php echo esc_attr($settings['api_key']); ?>" 
                            class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="<?php esc_attr_e('Enter your Together AI API key', 'snn-ai-images'); ?>"
                            data-tippy-content="<?php esc_attr_e('Get your API key from https://api.together.xyz/', 'snn-ai-images'); ?>"
                        >
                        <p class="snn-form-description text-sm text-gray-600 mt-1">
                            <?php _e('Get your API key from', 'snn-ai-images'); ?> 
                            <a href="https://api.together.xyz/" target="_blank" class="text-blue-600 hover:text-blue-800">https://api.together.xyz/</a>
                        </p>
                    </div>
                    
                    <div class="snn-form-group">
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('AI Model', 'snn-ai-images'); ?>
                        </label>
                        <select 
                            id="model" 
                            name="snn_ai_images_settings[model]" 
                            class="snn-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            data-tippy-content="<?php esc_attr_e('Choose the AI model for image generation', 'snn-ai-images'); ?>"
                        >
                            <?php
                            $together_ai = SNN_AI_Images_Together_AI::get_instance();
                            $models = $together_ai->get_available_models();
                            $current_model = $settings['model'] ?? 'black-forest-labs/FLUX.1-schnell';

                            if (!empty($models)) {
                                foreach ($models as $model) {
                                    $model_id = $model['id'];
                                    $display_name = $model['display_name'] ?? $model_id;
                                    echo '<option value="' . esc_attr($model_id) . '"' . selected($current_model, $model_id, false) . '>' . esc_html($display_name) . '</option>';
                                }
                            } else {
                                // Fallback if API fails
                                echo '<option value="black-forest-labs/FLUX.1-schnell">FLUX.1 Schnell (Fast)</option>';
                                echo '<option value="black-forest-labs/FLUX.1-dev">FLUX.1 Dev (Quality)</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="snn-form-group">
                        <label for="max_generations_per_user" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Max Generations Per User', 'snn-ai-images'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="max_generations_per_user" 
                            name="snn_ai_images_settings[max_generations_per_user]" 
                            value="<?php echo esc_attr($settings['max_generations_per_user']); ?>" 
                            min="1" 
                            max="1000" 
                            class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            data-tippy-content="<?php esc_attr_e('Maximum number of generations per user per month', 'snn-ai-images'); ?>"
                        >
                        <p class="snn-form-description text-sm text-gray-600 mt-1">
                            <?php _e('Maximum number of generations per user per month', 'snn-ai-images'); ?>
                        </p>
                    </div>

                    <div class="snn-form-group">
                        <label for="max_image_dimension" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Max Image Dimension', 'snn-ai-images'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="max_image_dimension" 
                            name="snn_ai_images_settings[max_image_dimension]" 
                            value="<?php echo esc_attr($settings['max_image_dimension'] ?? 1024); ?>" 
                            min="512" 
                            max="2048" 
                            step="64"
                            class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            data-tippy-content="<?php esc_attr_e('The maximum width or height for images sent to the AI. Larger images will be resized.', 'snn-ai-images'); ?>"
                        >
                        <p class="snn-form-description text-sm text-gray-600 mt-1">
                            <?php _e('Recommended: 1024. Affects cost and processing time.', 'snn-ai-images'); ?>
                        </p>
                    </div>

                    <div class="snn-form-group">
                        <label for="max_file_size" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Max File Size (bytes)', 'snn-ai-images'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="max_file_size" 
                            name="snn_ai_images_settings[max_file_size]" 
                            value="<?php echo esc_attr($settings['max_file_size'] ?? wp_max_upload_size()); ?>" 
                            min="1024" 
                            class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            data-tippy-content="<?php esc_attr_e('Maximum file size for uploads in bytes', 'snn-ai-images'); ?>"
                        >
                        <p class="snn-form-description text-sm text-gray-600 mt-1">
                            <?php printf(__('Current WordPress limit: %s', 'snn-ai-images'), size_format(wp_max_upload_size())); ?>
                        </p>
                    </div>

                    <div class="snn-form-group">
                        <label for="max_files_per_upload" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Max Files Per Upload', 'snn-ai-images'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="max_files_per_upload" 
                            name="snn_ai_images_settings[max_files_per_upload]" 
                            value="<?php echo esc_attr($settings['max_files_per_upload'] ?? 5); ?>" 
                            min="1" 
                            max="20"
                            class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            data-tippy-content="<?php esc_attr_e('Maximum number of files that can be uploaded at once', 'snn-ai-images'); ?>"
                        >
                        <p class="snn-form-description text-sm text-gray-600 mt-1">
                            <?php _e('Recommended: 5-10 files maximum', 'snn-ai-images'); ?>
                        </p>
                    </div>

                    <div class="snn-form-group">
                        <label for="temp_directory" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Temporary Directory', 'snn-ai-images'); ?>
                        </label>
                        <input 
                            type="text" 
                            id="temp_directory" 
                            name="snn_ai_images_settings[temp_directory]" 
                            value="<?php echo esc_attr($settings['temp_directory'] ?? wp_upload_dir()['basedir'] . '/snn-ai-temp/'); ?>" 
                            class="snn-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            data-tippy-content="<?php esc_attr_e('Directory for temporary files during processing. Must be writable.', 'snn-ai-images'); ?>"
                        >
                        <p class="snn-form-description text-sm text-gray-600 mt-1">
                            <?php _e('Directory must be writable by the web server', 'snn-ai-images'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="snn-api-test mt-6 pt-4 border-t border-gray-200">
                    <button type="submit" name="test_api" class="snn-button-secondary bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        <?php _e('Test API Connection', 'snn-ai-images'); ?>
                    </button>
                </div>
            </div>
            
            <!-- File Upload Settings -->
            <div class="snn-settings-section bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="snn-settings-title text-xl font-medium text-gray-800 mb-4 flex items-center">
                    <span class="text-green-500 mr-2">📁</span>
                    <?php _e('File Upload Settings', 'snn-ai-images'); ?>
                </h2>
                
                <div class="snn-form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('Allowed File Types', 'snn-ai-images'); ?>
                    </label>
                    <div class="snn-checkbox-group grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php
                        $file_types = array(
                            'jpg' => 'JPEG',
                            'jpeg' => 'JPEG (alternative)',
                            'png' => 'PNG',
                            'gif' => 'GIF',
                            'webp' => 'WebP'
                        );
                        
                        foreach ($file_types as $type => $label) {
                            $checked = in_array($type, $settings['allowed_file_types']) ? 'checked' : '';
                            echo '<label class="snn-checkbox-label flex items-center">';
                            echo '<input type="checkbox" name="snn_ai_images_settings[allowed_file_types][]" value="' . esc_attr($type) . '" ' . $checked . ' class="snn-checkbox mr-2">';
                            echo '<span class="text-sm text-gray-700">' . esc_html($label) . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="snn-upload-info mt-4 p-4 bg-gray-50 rounded-md">
                    <div class="snn-upload-limit text-sm text-gray-600">
                        <strong><?php _e('Current Upload Limits:', 'snn-ai-images'); ?></strong>
                        <ul class="mt-2 space-y-1">
                            <li>• <?php printf(__('Maximum file size: %s', 'snn-ai-images'), size_format(wp_max_upload_size())); ?></li>
                            <li>• <?php printf(__('Maximum image dimensions: %dx%d pixels', 'snn-ai-images'), 4096, 4096); ?></li>
                            <li>• <?php _e('Images will be automatically optimized for AI processing', 'snn-ai-images'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Performance Settings -->
            <div class="snn-settings-section bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="snn-settings-title text-xl font-medium text-gray-800 mb-4 flex items-center">
                    <span class="text-purple-500 mr-2">⚡</span>
                    <?php _e('Performance Settings', 'snn-ai-images'); ?>
                </h2>
                
                <div class="snn-performance-info">
                    <div class="snn-performance-item flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="snn-performance-label">
                            <div class="text-sm font-medium text-gray-700"><?php _e('Image Optimization', 'snn-ai-images'); ?></div>
                            <div class="text-xs text-gray-500"><?php _e('Automatically resize and compress images before processing', 'snn-ai-images'); ?></div>
                        </div>
                        <div class="snn-performance-status">
                            <span class="snn-status-enabled bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                ✓ <?php _e('Enabled', 'snn-ai-images'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="snn-performance-item flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="snn-performance-label">
                            <div class="text-sm font-medium text-gray-700"><?php _e('Caching', 'snn-ai-images'); ?></div>
                            <div class="text-xs text-gray-500"><?php _e('Cache AI-generated images to improve performance', 'snn-ai-images'); ?></div>
                        </div>
                        <div class="snn-performance-status">
                            <span class="snn-status-enabled bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                ✓ <?php _e('Enabled', 'snn-ai-images'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="snn-performance-item flex items-center justify-between py-3">
                        <div class="snn-performance-label">
                            <div class="text-sm font-medium text-gray-700"><?php _e('Background Processing', 'snn-ai-images'); ?></div>
                            <div class="text-xs text-gray-500"><?php _e('Process AI generations in the background for better user experience', 'snn-ai-images'); ?></div>
                        </div>
                        <div class="snn-performance-status">
                            <span class="snn-status-enabled bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                ✓ <?php _e('Enabled', 'snn-ai-images'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Usage Statistics -->
            <div class="snn-settings-section bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="snn-settings-title text-xl font-medium text-gray-800 mb-4 flex items-center">
                    <span class="text-orange-500 mr-2">📊</span>
                    <?php _e('Usage Statistics', 'snn-ai-images'); ?>
                </h2>
                
                <?php
                // Get usage statistics
                global $wpdb;
                $history_table = $wpdb->prefix . 'snn_ai_generation_history';
                
                $total_generations = $wpdb->get_var("SELECT COUNT(*) FROM $history_table");
                $successful_generations = $wpdb->get_var("SELECT COUNT(*) FROM $history_table WHERE status = 'completed'");
                $failed_generations = $wpdb->get_var("SELECT COUNT(*) FROM $history_table WHERE status = 'failed'");
                $this_month_generations = $wpdb->get_var("SELECT COUNT(*) FROM $history_table WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
                ?>
                
                <div class="snn-usage-stats grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="snn-usage-stat bg-blue-50 rounded-lg p-4">
                        <div class="snn-stat-number text-2xl font-bold text-blue-600"><?php echo esc_html($total_generations); ?></div>
                        <div class="snn-stat-label text-sm text-gray-600"><?php _e('Total Generations', 'snn-ai-images'); ?></div>
                    </div>
                    
                    <div class="snn-usage-stat bg-green-50 rounded-lg p-4">
                        <div class="snn-stat-number text-2xl font-bold text-green-600"><?php echo esc_html($successful_generations); ?></div>
                        <div class="snn-stat-label text-sm text-gray-600"><?php _e('Successful', 'snn-ai-images'); ?></div>
                    </div>
                    
                    <div class="snn-usage-stat bg-red-50 rounded-lg p-4">
                        <div class="snn-stat-number text-2xl font-bold text-red-600"><?php echo esc_html($failed_generations); ?></div>
                        <div class="snn-stat-label text-sm text-gray-600"><?php _e('Failed', 'snn-ai-images'); ?></div>
                    </div>
                    
                    <div class="snn-usage-stat bg-purple-50 rounded-lg p-4">
                        <div class="snn-stat-number text-2xl font-bold text-purple-600"><?php echo esc_html($this_month_generations); ?></div>
                        <div class="snn-stat-label text-sm text-gray-600"><?php _e('This Month', 'snn-ai-images'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Save Settings -->
            <div class="snn-settings-actions">
                <button type="submit" name="submit" class="snn-button-primary bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-md transition-colors">
                    <?php _e('Save Settings', 'snn-ai-images'); ?>
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=snn-ai-images-dashboard'); ?>" class="snn-button-secondary bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-md transition-colors ml-4">
                    <?php _e('Back to Dashboard', 'snn-ai-images'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.snn-checkbox:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.snn-status-enabled {
    display: inline-flex;
    align-items: center;
}

.snn-usage-stat {
    transition: transform 0.2s;
}

.snn-usage-stat:hover {
    transform: translateY(-2px);
}
</style>