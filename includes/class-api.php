<?php
/**
 * REST API endpoints for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('snn-ai/v1', '/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_image'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'image_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_image_id')
                ),
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'style_description' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'brand_kit_id' => array(
                    'required' => false,
                    'type' => 'integer'
                ),
                'generation_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'style_transfer',
                    'enum' => array('style_transfer', 'background_removal', 'product_variation', 'category_banner')
                )
            )
        ));
        
        register_rest_route('snn-ai/v1', '/brand-kits', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_brand_kits'),
            'permission_callback' => array($this, 'check_brand_kit_permissions')
        ));
        
        register_rest_route('snn-ai/v1', '/brand-kits', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_brand_kit'),
            'permission_callback' => array($this, 'check_brand_kit_permissions'),
            'args' => array(
                'name' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'colors' => array(
                    'required' => false,
                    'type' => 'array'
                ),
                'fonts' => array(
                    'required' => false,
                    'type' => 'array'
                ),
                'style_guidelines' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        register_rest_route('snn-ai/v1', '/brand-kits/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_brand_kit'),
            'permission_callback' => array($this, 'check_brand_kit_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
        
        register_rest_route('snn-ai/v1', '/brand-kits/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_brand_kit'),
            'permission_callback' => array($this, 'check_brand_kit_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));

        register_rest_route('snn-ai/v1', '/brand-kits/(?P<id>\d+)/duplicate', array(
            'methods' => 'POST',
            'callback' => array($this, 'duplicate_brand_kit'),
            'permission_callback' => array($this, 'check_brand_kit_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));

        register_rest_route('snn-ai/v1', '/brand-kits/(?P<id>\d+)/export', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_brand_kit'),
            'permission_callback' => array($this, 'check_brand_kit_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));

        register_rest_route('snn-ai/v1', '/brand-kits/import', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_brand_kit'),
            'permission_callback' => array($this, 'check_brand_kit_permissions'),
            'args' => array(
                'brand_kit_data' => array(
                    'required' => true,
                    'type' => 'object'
                )
            )
        ));
        
        register_rest_route('snn-ai/v1', '/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_generation_history'),
            'permission_callback' => array($this, 'check_history_permissions'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'sanitize_callback' => function($value) {
                        return max(1, intval($value));
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                    'sanitize_callback' => function($value) {
                        return max(1, min(100, intval($value)));
                    }
                )
            )
        ));
        
        register_rest_route('snn-ai/v1', '/usage', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_usage_stats'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function check_permissions() {
        return current_user_can('use_snn_ai_images');
    }
    
    public function check_brand_kit_permissions() {
        return current_user_can('manage_snn_ai_brand_kits');
    }
    
    public function check_history_permissions() {
        return current_user_can('view_snn_ai_history');
    }
    
    public function validate_image_id($param) {
        // Validate param is numeric
        if (!is_numeric($param) || $param <= 0) {
            return false;
        }
        
        $attachment = get_post($param);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }
        
        // Check if it's actually an image
        if (!wp_attachment_is_image($attachment->ID)) {
            return false;
        }
        
        return true;
    }
    
    public function generate_image($request) {
        $image_id = $request['image_id'];
        $prompt = $request['prompt'];
        $style_description = $request['style_description'] ?? '';
        $brand_kit_id = $request['brand_kit_id'] ?? null;
        $generation_type = $request['generation_type'] ?? 'style_transfer';
        
        // Additional validation
        if (empty($prompt) || strlen($prompt) > 1000) {
            return new WP_Error('invalid_prompt', __('Prompt is required and must be less than 1000 characters.', 'snn-ai-images'), array('status' => 400));
        }
        
        if (strlen($style_description) > 500) {
            return new WP_Error('invalid_style', __('Style description must be less than 500 characters.', 'snn-ai-images'), array('status' => 400));
        }
        
        // Validate generation type
        $valid_types = array('style_transfer', 'background_removal', 'product_variation', 'category_banner');
        if (!in_array($generation_type, $valid_types)) {
            return new WP_Error('invalid_generation_type', __('Invalid generation type.', 'snn-ai-images'), array('status' => 400));
        }
        
        // Check user usage limits
        if (!$this->check_user_limits()) {
            return new WP_Error('usage_limit_exceeded', __('You have reached your generation limit for this month.', 'snn-ai-images'), array('status' => 429));
        }
        
        // Get original image
        $original_image = get_post($image_id);
        if (!$original_image || $original_image->post_type !== 'attachment') {
            return new WP_Error('invalid_image', __('Invalid image ID.', 'snn-ai-images'), array('status' => 400));
        }
        
        // Get brand kit if specified
        $brand_kit = null;
        if ($brand_kit_id) {
            $brand_kit = SNN_AI_Images_Brand_Kit::get_instance()->get_brand_kit($brand_kit_id);
            if (!$brand_kit) {
                return new WP_Error('invalid_brand_kit', __('Invalid brand kit ID.', 'snn-ai-images'), array('status' => 400));
            }
        }
        
        // Create generation history entry
        $history_id = $this->create_generation_history($image_id, $prompt, $style_description, $brand_kit_id, $generation_type);
        
        try {
            // Process image with AI
            $processor = SNN_AI_Images_Image_Processor::get_instance();
            $result = $processor->process_image($image_id, $prompt, $style_description, $brand_kit, $generation_type);
            
            if (is_wp_error($result)) {
                $this->update_generation_history($history_id, 'failed', $result->get_error_message());
                return $result;
            }
            
            // Update generation history with success
            $this->update_generation_history($history_id, 'completed', null, $result['attachment_id']);
            
            return new WP_REST_Response(array(
                'success' => true,
                'image_id' => $result['attachment_id'],
                'image_url' => $result['image_url'],
                'history_id' => $history_id
            ), 200);
            
        } catch (Exception $e) {
            $this->update_generation_history($history_id, 'failed', $e->getMessage());
            return new WP_Error('generation_failed', $e->getMessage(), array('status' => 500));
        }
    }
    
    public function get_brand_kits($request) {
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $brand_kits = $brand_kit_manager->get_user_brand_kits(get_current_user_id());
        
        return new WP_REST_Response($brand_kits, 200);
    }
    
    public function create_brand_kit($request) {
        $name = $request['name'];
        $colors = $request['colors'] ?? array();
        $fonts = $request['fonts'] ?? array();
        $style_guidelines = $request['style_guidelines'] ?? '';
        
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $brand_kit_id = $brand_kit_manager->create_brand_kit($name, $colors, $fonts, $style_guidelines);
        
        if ($brand_kit_id) {
            return new WP_REST_Response(array(
                'success' => true,
                'brand_kit_id' => $brand_kit_id
            ), 201);
        }
        
        return new WP_Error('creation_failed', __('Failed to create brand kit.', 'snn-ai-images'), array('status' => 500));
    }
    
    public function update_brand_kit($request) {
        $brand_kit_id = $request['id'];
        $data = $request->get_params();
        
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $success = $brand_kit_manager->update_brand_kit($brand_kit_id, $data);
        
        if ($success) {
            return new WP_REST_Response(array('success' => true), 200);
        }
        
        return new WP_Error('update_failed', __('Failed to update brand kit.', 'snn-ai-images'), array('status' => 500));
    }
    
    public function delete_brand_kit($request) {
        $brand_kit_id = $request['id'];
        
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $success = $brand_kit_manager->delete_brand_kit($brand_kit_id);
        
        if ($success) {
            return new WP_REST_Response(array('success' => true), 200);
        }
        
        return new WP_Error('deletion_failed', __('Failed to delete brand kit.', 'snn-ai-images'), array('status' => 500));
    }

    public function duplicate_brand_kit($request) {
        $brand_kit_id = $request['id'];
        
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $new_brand_kit_id = $brand_kit_manager->duplicate_brand_kit($brand_kit_id);
        
        if ($new_brand_kit_id && !is_wp_error($new_brand_kit_id)) {
            $new_brand_kit = $brand_kit_manager->get_brand_kit($new_brand_kit_id);
            return new WP_REST_Response(array(
                'success' => true,
                'brand_kit' => $new_brand_kit
            ), 200);
        }
        
        return new WP_Error('duplication_failed', __('Failed to duplicate brand kit.', 'snn-ai-images'), array('status' => 500));
    }

    public function export_brand_kit($request) {
        $brand_kit_id = $request['id'];
        
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $export_data = $brand_kit_manager->export_brand_kit($brand_kit_id);
        
        if ($export_data) {
            return new WP_REST_Response(array(
                'success' => true,
                'export_data' => $export_data
            ), 200);
        }
        
        return new WP_Error('export_failed', __('Failed to export brand kit.', 'snn-ai-images'), array('status' => 500));
    }

    public function import_brand_kit($request) {
        $brand_kit_data = $request['brand_kit_data'];
        
        $brand_kit_manager = SNN_AI_Images_Brand_Kit::get_instance();
        $new_brand_kit_id = $brand_kit_manager->import_brand_kit($brand_kit_data);
        
        if ($new_brand_kit_id && !is_wp_error($new_brand_kit_id)) {
            $new_brand_kit = $brand_kit_manager->get_brand_kit($new_brand_kit_id);
            return new WP_REST_Response(array(
                'success' => true,
                'brand_kit' => $new_brand_kit
            ), 200);
        }
        
        return new WP_Error('import_failed', __('Failed to import brand kit.', 'snn-ai-images'), array('status' => 500));
    }
    
    public function get_generation_history($request) {
        $page = isset($request['page']) ? max(1, intval($request['page'])) : 1;
        $per_page = isset($request['per_page']) ? max(1, intval($request['per_page'])) : 10;
        $user_id = get_current_user_id();
        
        // Validate per_page to prevent division by zero
        if ($per_page <= 0) {
            $per_page = 10;
        }
        
        // Limit per_page to reasonable maximum
        if ($per_page > 100) {
            $per_page = 100;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_generation_history';
        
        $offset = ($page - 1) * $per_page;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log('SNN AI Images - Database error in get_generation_history: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Database error occurred.', 'snn-ai-images'), array('status' => 500));
        }
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // Ensure total is not null and is a valid number
        $total = $total ? intval($total) : 0;
        
        // Calculate total pages safely
        $total_pages = $per_page > 0 ? ceil($total / $per_page) : 1;
        
        return new WP_REST_Response(array(
            'items' => $results ? $results : array(),
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages
        ), 200);
    }
    
    public function get_usage_stats($request) {
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_generation_history';
        
        // Get current month usage
        $current_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())",
            $user_id
        ));
        
        // Get total usage
        $total_usage = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log('SNN AI Images - Database error in get_usage_stats: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Database error occurred.', 'snn-ai-images'), array('status' => 500));
        }
        
        // Ensure values are valid integers
        $current_month = $current_month ? intval($current_month) : 0;
        $total_usage = $total_usage ? intval($total_usage) : 0;
        
        $settings = get_option('snn_ai_images_settings', array());
        $max_generations = isset($settings['max_generations_per_user']) ? intval($settings['max_generations_per_user']) : 50;
        
        // Ensure max_generations is positive
        if ($max_generations <= 0) {
            $max_generations = 50;
        }
        
        return new WP_REST_Response(array(
            'current_month' => $current_month,
            'total_usage' => $total_usage,
            'max_generations' => $max_generations,
            'remaining' => max(0, $max_generations - $current_month)
        ), 200);
    }
    
    private function check_user_limits() {
        $user_id = get_current_user_id();
        $settings = get_option('snn_ai_images_settings', array());
        $max_generations = isset($settings['max_generations_per_user']) ? intval($settings['max_generations_per_user']) : 50;
        
        // Ensure max_generations is positive
        if ($max_generations <= 0) {
            $max_generations = 50;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_generation_history';
        
        $current_month_usage = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())",
            $user_id
        ));
        
        // Ensure current_month_usage is valid
        $current_month_usage = $current_month_usage ? intval($current_month_usage) : 0;
        
        return $current_month_usage < $max_generations;
    }
    
    private function create_generation_history($image_id, $prompt, $style_description, $brand_kit_id, $generation_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_generation_history';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'original_image_id' => $image_id,
                'prompt' => $prompt,
                'style_description' => $style_description,
                'brand_kit_id' => $brand_kit_id,
                'generation_type' => $generation_type,
                'status' => 'pending'
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('SNN AI Images - Database error in create_generation_history: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    private function update_generation_history($history_id, $status, $error_message = null, $generated_image_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_generation_history';
        
        $data = array(
            'status' => $status
        );
        
        $format = array('%s');
        
        if ($error_message) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }
        
        if ($generated_image_id) {
            $data['generated_image_id'] = $generated_image_id;
            $format[] = '%d';
        }
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $history_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            error_log('SNN AI Images - Database error in update_generation_history: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
}