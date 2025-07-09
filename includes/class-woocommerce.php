<?php
/**
 * WooCommerce integration for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_WooCommerce {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only initialize if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('wp_ajax_snn_ai_generate_product_variations', array($this, 'generate_product_variations'));
        add_action('wp_ajax_snn_ai_generate_product_background', array($this, 'generate_product_background'));
        add_action('wp_ajax_snn_ai_generate_category_banner', array($this, 'generate_category_banner'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_ai_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_ai_fields'));
        add_filter('woocommerce_admin_product_actions', array($this, 'add_product_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_woocommerce_scripts'));
    }
    
    public function add_product_meta_box() {
        add_meta_box(
            'snn-ai-product-images',
            __('AI Product Images', 'snn-ai-images'),
            array($this, 'product_meta_box_content'),
            'product',
            'side',
            'default'
        );
    }
    
    public function product_meta_box_content($post) {
        if (!current_user_can('use_snn_ai_images')) {
            return;
        }
        
        $product = wc_get_product($post->ID);
        $product_image_id = $product->get_image_id();
        
        wp_nonce_field('snn_ai_product_nonce', 'snn_ai_product_nonce');
        ?>
        <div class="snn-ai-product-container">
            <?php if ($product_image_id): ?>
                <div class="snn-ai-product-image">
                    <?php echo wp_get_attachment_image($product_image_id, 'thumbnail'); ?>
                </div>
                
                <div class="snn-ai-product-actions">
                    <button type="button" class="button button-primary snn-ai-generate-variations" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Generate Variations', 'snn-ai-images'); ?>
                    </button>
                    
                    <button type="button" class="button snn-ai-remove-background" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Remove Background', 'snn-ai-images'); ?>
                    </button>
                    
                    <button type="button" class="button snn-ai-generate-lifestyle" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Generate Lifestyle', 'snn-ai-images'); ?>
                    </button>
                </div>
                
                <div class="snn-ai-product-settings">
                    <label for="snn-ai-variation-count">
                        <?php _e('Number of Variations:', 'snn-ai-images'); ?>
                    </label>
                    <input type="number" id="snn-ai-variation-count" min="1" max="10" value="3">
                    
                    <label for="snn-ai-lifestyle-prompt">
                        <?php _e('Lifestyle Prompt:', 'snn-ai-images'); ?>
                    </label>
                    <textarea id="snn-ai-lifestyle-prompt" placeholder="<?php esc_attr_e('e.g., modern kitchen, outdoor setting, casual lifestyle...', 'snn-ai-images'); ?>"></textarea>
                    
                    <label for="snn-ai-product-brand-kit">
                        <?php _e('Brand Kit:', 'snn-ai-images'); ?>
                    </label>
                    <select id="snn-ai-product-brand-kit">
                        <option value=""><?php _e('None', 'snn-ai-images'); ?></option>
                        <?php
                        $brand_kits = SNN_AI_Images_Brand_Kit::get_instance()->get_user_brand_kits(get_current_user_id());
                        foreach ($brand_kits as $brand_kit) {
                            echo '<option value="' . esc_attr($brand_kit->id) . '">' . esc_html($brand_kit->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="snn-ai-product-results" style="display: none;">
                    <h4><?php _e('Generated Images:', 'snn-ai-images'); ?></h4>
                    <div class="snn-ai-results-grid"></div>
                </div>
            <?php else: ?>
                <p><?php _e('Please add a product image first to use AI features.', 'snn-ai-images'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function add_product_ai_fields() {
        global $post;
        
        if (!current_user_can('use_snn_ai_images')) {
            return;
        }
        
        echo '<div class="options_group">';
        
        woocommerce_wp_checkbox(array(
            'id' => '_snn_ai_auto_generate_variations',
            'label' => __('Auto-generate variations', 'snn-ai-images'),
            'description' => __('Automatically generate product variations when images are uploaded.', 'snn-ai-images')
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_snn_ai_product_description',
            'label' => __('AI Product Description', 'snn-ai-images'),
            'description' => __('Description to help AI generate better product images.', 'snn-ai-images'),
            'type' => 'text'
        ));
        
        echo '</div>';
    }
    
    public function save_product_ai_fields($post_id) {
        $auto_generate = isset($_POST['_snn_ai_auto_generate_variations']) ? 'yes' : 'no';
        update_post_meta($post_id, '_snn_ai_auto_generate_variations', $auto_generate);
        
        if (isset($_POST['_snn_ai_product_description'])) {
            update_post_meta($post_id, '_snn_ai_product_description', sanitize_text_field($_POST['_snn_ai_product_description']));
        }
    }
    
    public function add_product_actions($actions) {
        if (!current_user_can('use_snn_ai_images')) {
            return $actions;
        }
        
        $actions['snn_ai_generate_images'] = __('Generate AI Images', 'snn-ai-images');
        
        return $actions;
    }
    
    public function enqueue_woocommerce_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        wp_enqueue_script(
            'snn-ai-woocommerce',
            SNN_AI_IMAGES_PLUGIN_URL . 'assets/js/woocommerce.js',
            array('jquery', 'wp-api-fetch'),
            SNN_AI_IMAGES_VERSION,
            true
        );
        
        wp_localize_script('snn-ai-woocommerce', 'snnAiWooCommerce', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('snn_ai_woocommerce_nonce'),
            'strings' => array(
                'generating' => __('Generating...', 'snn-ai-images'),
                'success' => __('Images generated successfully!', 'snn-ai-images'),
                'error' => __('Generation failed. Please try again.', 'snn-ai-images'),
                'selectImages' => __('Select images to add to product gallery', 'snn-ai-images'),
                'addToGallery' => __('Add to Gallery', 'snn-ai-images'),
                'setAsFeatured' => __('Set as Featured', 'snn-ai-images')
            )
        ));
    }
    
    public function generate_product_variations() {
        check_ajax_referer('snn_ai_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('use_snn_ai_images')) {
            wp_send_json_error(__('Insufficient permissions.', 'snn-ai-images'));
        }
        
        $product_id = intval($_POST['product_id']);
        $count = intval($_POST['count']) ?: 3;
        $brand_kit_id = !empty($_POST['brand_kit_id']) ? intval($_POST['brand_kit_id']) : null;
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found.', 'snn-ai-images'));
        }
        
        $product_image_id = $product->get_image_id();
        if (!$product_image_id) {
            wp_send_json_error(__('Product image not found.', 'snn-ai-images'));
        }
        
        $results = array();
        $product_description = get_post_meta($product_id, '_snn_ai_product_description', true);
        $base_prompt = $product_description ?: $product->get_name();
        
        $brand_kit = null;
        if ($brand_kit_id) {
            $brand_kit = SNN_AI_Images_Brand_Kit::get_instance()->get_brand_kit($brand_kit_id);
        }
        
        for ($i = 0; $i < $count; $i++) {
            $variation_prompt = $base_prompt . ', variation ' . ($i + 1) . ', different angle or style';
            
            $processor = SNN_AI_Images_Image_Processor::get_instance();
            $result = $processor->process_image($product_image_id, $variation_prompt, 'product photography', $brand_kit, 'product_variation');
            
            if (!is_wp_error($result)) {
                $results[] = $result;
            }
        }
        
        if (empty($results)) {
            wp_send_json_error(__('Failed to generate variations.', 'snn-ai-images'));
        }
        
        wp_send_json_success($results);
    }
    
    public function generate_product_background() {
        check_ajax_referer('snn_ai_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('use_snn_ai_images')) {
            wp_send_json_error(__('Insufficient permissions.', 'snn-ai-images'));
        }
        
        $product_id = intval($_POST['product_id']);
        $lifestyle_prompt = sanitize_textarea_field($_POST['lifestyle_prompt']);
        $brand_kit_id = !empty($_POST['brand_kit_id']) ? intval($_POST['brand_kit_id']) : null;
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found.', 'snn-ai-images'));
        }
        
        $product_image_id = $product->get_image_id();
        if (!$product_image_id) {
            wp_send_json_error(__('Product image not found.', 'snn-ai-images'));
        }
        
        $product_description = get_post_meta($product_id, '_snn_ai_product_description', true);
        $base_prompt = $product_description ?: $product->get_name();
        $full_prompt = $base_prompt . ' in ' . $lifestyle_prompt;
        
        $brand_kit = null;
        if ($brand_kit_id) {
            $brand_kit = SNN_AI_Images_Brand_Kit::get_instance()->get_brand_kit($brand_kit_id);
        }
        
        $processor = SNN_AI_Images_Image_Processor::get_instance();
        $result = $processor->process_image($product_image_id, $full_prompt, 'lifestyle photography', $brand_kit, 'background_removal');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function generate_category_banner() {
        check_ajax_referer('snn_ai_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('use_snn_ai_images')) {
            wp_send_json_error(__('Insufficient permissions.', 'snn-ai-images'));
        }
        
        $category_id = intval($_POST['category_id']);
        $brand_kit_id = !empty($_POST['brand_kit_id']) ? intval($_POST['brand_kit_id']) : null;
        
        $category = get_term($category_id, 'product_cat');
        if (!$category) {
            wp_send_json_error(__('Category not found.', 'snn-ai-images'));
        }
        
        $prompt = 'Banner for ' . $category->name . ' category, ' . $category->description;
        
        $brand_kit = null;
        if ($brand_kit_id) {
            $brand_kit = SNN_AI_Images_Brand_Kit::get_instance()->get_brand_kit($brand_kit_id);
        }
        
        // Use a placeholder image for category banners
        $placeholder_id = $this->get_placeholder_image_id();
        
        $processor = SNN_AI_Images_Image_Processor::get_instance();
        $result = $processor->process_image($placeholder_id, $prompt, 'banner design', $brand_kit, 'category_banner');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Update category thumbnail
        update_term_meta($category_id, 'thumbnail_id', $result['attachment_id']);
        
        wp_send_json_success($result);
    }
    
    private function get_placeholder_image_id() {
        // Create or get a placeholder image for category banners
        $placeholder_id = get_option('snn_ai_placeholder_image_id');
        
        if (!$placeholder_id || !get_post($placeholder_id)) {
            // Create a simple placeholder image
            $placeholder_id = $this->create_placeholder_image();
            update_option('snn_ai_placeholder_image_id', $placeholder_id);
        }
        
        return $placeholder_id;
    }
    
    private function create_placeholder_image() {
        $upload_dir = wp_upload_dir();
        $placeholder_path = $upload_dir['path'] . '/snn-ai-placeholder.jpg';
        
        // Create a simple 1024x1024 white image
        $image = imagecreatetruecolor(1024, 1024);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        // Add some text
        $text_color = imagecolorallocate($image, 128, 128, 128);
        $text = 'AI Generated Content';
        imagestring($image, 5, 400, 500, $text, $text_color);
        
        // Save image
        imagejpeg($image, $placeholder_path, 90);
        imagedestroy($image);
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'SNN AI Placeholder',
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $placeholder_path);
        
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $placeholder_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
        }
        
        return $attachment_id;
    }
    
    public function auto_generate_variations($attachment_id) {
        // Check if this is a product image and auto-generation is enabled
        $parent_id = wp_get_post_parent_id($attachment_id);
        if (!$parent_id) {
            return;
        }
        
        $product = wc_get_product($parent_id);
        if (!$product) {
            return;
        }
        
        $auto_generate = get_post_meta($parent_id, '_snn_ai_auto_generate_variations', true);
        if ($auto_generate !== 'yes') {
            return;
        }
        
        // Generate variations in background
        wp_schedule_single_event(time() + 30, 'snn_ai_auto_generate_variations', array($parent_id, $attachment_id));
    }
}