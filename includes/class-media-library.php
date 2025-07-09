<?php
/**
 * Media Library integration for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_Media_Library {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes_attachment', array($this, 'add_ai_edit_metabox'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
        add_action('wp_ajax_snn_ai_media_edit', array($this, 'handle_media_edit'));
        add_filter('media_row_actions', array($this, 'add_media_row_actions'), 10, 2);
    }

    /**
     * Adds the AI Edit metabox to the attachment edit screen.
     */
    public function add_ai_edit_metabox($post) {
        if (wp_attachment_is_image($post->ID) && current_user_can('use_snn_ai_images')) {
            add_meta_box(
                'snn_ai_media_edit_metabox',
                __('AI Edit', 'snn-ai-images'),
                array($this, 'render_ai_edit_metabox'),
                'attachment',
                'side',
                'low'
            );
        }
    }

    /**
     * Renders the content of the AI Edit metabox.
     */
    public function render_ai_edit_metabox($post) {
        echo $this->get_ai_edit_html($post->ID);
    }
    
    public function enqueue_media_scripts($hook) {
        if ($hook !== 'upload.php' && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        wp_enqueue_script(
            'snn-ai-media',
            SNN_AI_IMAGES_PLUGIN_URL . 'assets/js/media-library.js',
            array('jquery', 'media-editor'),
            SNN_AI_IMAGES_VERSION,
            true
        );
        
        wp_localize_script('snn-ai-media', 'snnAiMedia', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('snn_ai_media_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'snn-ai-images'),
                'success' => __('AI edit completed successfully!', 'snn-ai-images'),
                'error' => __('AI edit failed. Please try again.', 'snn-ai-images'),
                'selectBrandKit' => __('Select Brand Kit', 'snn-ai-images'),
                'enterPrompt' => __('Enter your prompt...', 'snn-ai-images'),
                'styleDescription' => __('Style description (optional)', 'snn-ai-images'),
                'generate' => __('Generate', 'snn-ai-images'),
                'cancel' => __('Cancel', 'snn-ai-images')
            )
        ));
    }
    
    private function get_ai_edit_html($attachment_id) {
        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        
        ob_start();
        ?>
        <div class="snn-ai-media-edit-container" data-attachment-id="<?php echo esc_attr($attachment_id); ?>">
            <div class="snn-ai-media-preview" style="margin-bottom: 15px;">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title($attachment_id)); ?>" style="max-width: 100%; height: auto; border-radius: 4px;">
            </div>
            
            <div class="snn-ai-media-controls">
                <div class="snn-form-field" style="margin-bottom: 10px;">
                    <label for="snn-ai-prompt-<?php echo $attachment_id; ?>" style="display:block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Transformation Prompt:', 'snn-ai-images'); ?>
                    </label>
                    <textarea 
                        id="snn-ai-prompt-<?php echo $attachment_id; ?>" 
                        class="snn-ai-prompt-input" 
                        placeholder="<?php esc_attr_e('Describe how you want to transform this image...', 'snn-ai-images'); ?>"
                        rows="3"
                        style="width: 100%;"
                    ></textarea>
                </div>
                
                <div class="snn-form-field" style="margin-bottom: 10px;">
                    <label for="snn-ai-style-<?php echo $attachment_id; ?>" style="display:block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Style Description (Optional):', 'snn-ai-images'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="snn-ai-style-<?php echo $attachment_id; ?>" 
                        class="snn-ai-style-input" 
                        placeholder="<?php esc_attr_e('e.g., modern, vintage, artistic...', 'snn-ai-images'); ?>"
                        style="width: 100%;"
                    >
                </div>
                
                <div class="snn-form-field" style="margin-bottom: 10px;">
                    <label for="snn-ai-brand-kit-<?php echo $attachment_id; ?>" style="display:block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Brand Kit:', 'snn-ai-images'); ?>
                    </label>
                    <select id="snn-ai-brand-kit-<?php echo $attachment_id; ?>" class="snn-ai-brand-kit-select" style="width: 100%;">
                        <option value=""><?php _e('None', 'snn-ai-images'); ?></option>
                        <?php
                        $brand_kits = SNN_AI_Images_Brand_Kit::get_instance()->get_user_brand_kits(get_current_user_id());
                        foreach ($brand_kits as $brand_kit) {
                            echo '<option value="' . esc_attr($brand_kit->id) . '">' . esc_html($brand_kit->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="snn-form-field" style="margin-bottom: 15px;">
                    <label for="snn-ai-generation-type-<?php echo $attachment_id; ?>" style="display:block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Generation Type:', 'snn-ai-images'); ?>
                    </label>
                    <select id="snn-ai-generation-type-<?php echo $attachment_id; ?>" class="snn-ai-generation-type-select" style="width: 100%;">
                        <option value="style_transfer"><?php _e('Style Transfer', 'snn-ai-images'); ?></option>
                        <option value="background_removal"><?php _e('Background Removal', 'snn-ai-images'); ?></option>
                        <option value="product_variation"><?php _e('Product Variation', 'snn-ai-images'); ?></option>
                        <option value="category_banner"><?php _e('Category Banner', 'snn-ai-images'); ?></option>
                    </select>
                </div>
                
                <div class="snn-form-field">
                    <button type="button" class="button button-primary snn-ai-generate-btn" data-attachment-id="<?php echo esc_attr($attachment_id); ?>" style="width: 100%;">
                        <?php _e('Generate AI Image', 'snn-ai-images'); ?>
                    </button>
                    <span class="snn-ai-loading" style="display: none; margin-top: 8px; text-align: center; width: 100%;">
                        <span class="spinner is-active" style="float: none; vertical-align: middle;"></span>
                        <span style="vertical-align: middle; margin-left: 5px;"><?php _e('Generating...', 'snn-ai-images'); ?></span>
                    </span>
                </div>
                
                <div class="snn-ai-result" style="display: none; margin-top: 15px;">
                    <div class="snn-ai-result-image" style="margin-bottom: 10px;"></div>
                    <div class="snn-ai-result-actions">
                        <button type="button" class="button button-primary snn-ai-use-image" style="width: 100%; margin-bottom: 5px;">
                            <?php _e('Use This Image', 'snn-ai-images'); ?>
                        </button>
                        <button type="button" class="button snn-ai-try-again" style="width: 100%;">
                            <?php _e('Try Again', 'snn-ai-images'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function add_media_row_actions($actions, $post) {
        if (!current_user_can('use_snn_ai_images') || !wp_attachment_is_image($post->ID)) {
            return $actions;
        }
        
        $actions['snn_ai_edit'] = sprintf(
            '<a href="#" class="snn-ai-quick-edit" data-attachment-id="%d">%s</a>',
            $post->ID,
            __('AI Edit', 'snn-ai-images')
        );
        
        return $actions;
    }
    
    public function handle_media_edit() {
        check_ajax_referer('snn_ai_media_nonce', 'nonce');
        
        if (!current_user_can('use_snn_ai_images')) {
            wp_send_json_error(__('Insufficient permissions.', 'snn-ai-images'));
        }
        
        // Validate and sanitize input
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $style_description = isset($_POST['style_description']) ? sanitize_text_field($_POST['style_description']) : '';
        $brand_kit_id = !empty($_POST['brand_kit_id']) ? intval($_POST['brand_kit_id']) : null;
        $generation_type = isset($_POST['generation_type']) ? sanitize_text_field($_POST['generation_type']) : 'style_transfer';
        
        // Validate attachment ID
        if ($attachment_id <= 0) {
            wp_send_json_error(__('Invalid attachment ID.', 'snn-ai-images'));
        }
        
        // Validate generation type
        $valid_types = array('style_transfer', 'background_removal', 'product_variation', 'category_banner');
        if (!in_array($generation_type, $valid_types)) {
            wp_send_json_error(__('Invalid generation type.', 'snn-ai-images'));
        }
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt is required.', 'snn-ai-images'));
        }
        
        // Validate attachment
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            wp_send_json_error(__('Invalid attachment.', 'snn-ai-images'));
        }
        
        try {
            // Process the image
            $processor = SNN_AI_Images_Image_Processor::get_instance();
            $brand_kit = null;
            
            if ($brand_kit_id) {
                $brand_kit = SNN_AI_Images_Brand_Kit::get_instance()->get_brand_kit($brand_kit_id);
            }
            
            $result = $processor->process_image($attachment_id, $prompt, $style_description, $brand_kit, $generation_type);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            // Ensure the result is an array before accessing keys
            if (!is_array($result) || !isset($result['attachment_id'])) {
                wp_send_json_error(__('Failed to process image. The API returned an unexpected response.', 'snn-ai-images'));
            }

            wp_send_json_success(array(
                'image_id' => $result['attachment_id'],
                'image_url' => $result['image_url'],
                'thumbnail_url' => $result['thumbnail_url']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function create_attachment_from_base64($base64_data, $filename, $parent_id = 0) {
        // Validate inputs
        if (empty($base64_data) || empty($filename)) {
            return new WP_Error('invalid_input', __('Invalid base64 data or filename.', 'snn-ai-images'));
        }
        
        $upload_dir = wp_upload_dir();
        
        // Check if uploads directory is writable
        if (!wp_is_writable($upload_dir['path'])) {
            return new WP_Error('directory_not_writable', __('Upload directory is not writable.', 'snn-ai-images'));
        }
        
        // Decode base64 data
        $image_data = base64_decode($base64_data);
        if ($image_data === false) {
            return new WP_Error('invalid_base64', __('Invalid base64 data.', 'snn-ai-images'));
        }
        
        // Validate file extension
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $file_info = pathinfo($filename);
        $extension = isset($file_info['extension']) ? strtolower($file_info['extension']) : '';
        
        if (!in_array($extension, $allowed_types)) {
            return new WP_Error('invalid_file_type', __('Invalid file type.', 'snn-ai-images'));
        }
        
        // Create unique filename
        $filename = wp_unique_filename($upload_dir['path'], $filename);
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save file with error handling
        $bytes_written = file_put_contents($file_path, $image_data);
        if ($bytes_written === false) {
            return new WP_Error('file_write_error', __('Failed to write image file.', 'snn-ai-images'));
        }
        
        // Get file type
        $file_type = wp_check_filetype($filename, null);
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path, $parent_id);
        
        if (!is_wp_error($attachment_id)) {
            // Generate metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            // Add custom meta to identify AI-generated images
            add_post_meta($attachment_id, '_snn_ai_generated', true);
            add_post_meta($attachment_id, '_snn_ai_original_id', $parent_id);
            add_post_meta($attachment_id, '_snn_ai_generation_date', current_time('mysql'));
        }
        
        return $attachment_id;
    }
}