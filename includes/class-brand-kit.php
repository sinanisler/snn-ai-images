<?php
/**
 * Brand Kit manager for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_Brand_Kit {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor
    }
    
    public function get_user_brand_kits($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_brand_kits';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log('SNN AI Images - Database error in get_user_brand_kits: ' . $wpdb->last_error);
            return array();
        }
        
        return $results ? $results : array();
    }
    
    public function get_brand_kit($brand_kit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_brand_kits';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $brand_kit_id
        ));
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log('SNN AI Images - Database error in get_brand_kit: ' . $wpdb->last_error);
            return null;
        }
        
        // Check if user has permission to access this brand kit
        if ($result && $result->user_id != get_current_user_id() && !current_user_can('manage_options')) {
            return null;
        }
        
        return $result;
    }
    
    public function create_brand_kit($name, $colors = array(), $fonts = array(), $style_guidelines = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_brand_kits';
        
        // Validate input
        if (empty($name) || strlen($name) > 255) {
            return new WP_Error('invalid_name', __('Brand kit name is required and must be less than 255 characters.', 'snn-ai-images'));
        }
        
        // Sanitize input
        $name = sanitize_text_field($name);
        $style_guidelines = sanitize_textarea_field($style_guidelines);
        
        // Validate colors array
        if (!is_array($colors)) {
            $colors = array();
        }
        
        // Validate fonts array
        if (!is_array($fonts)) {
            $fonts = array();
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'name' => $name,
                'colors' => json_encode($colors),
                'fonts' => json_encode($fonts),
                'style_guidelines' => $style_guidelines
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('SNN AI Images - Database error in create_brand_kit: ' . $wpdb->last_error);
            return new WP_Error('db_insert_error', __('Failed to create brand kit.', 'snn-ai-images'));
        }
        
        return $wpdb->insert_id;
    }
    
    public function update_brand_kit($brand_kit_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_brand_kits';
        
        // Check permissions
        $brand_kit = $this->get_brand_kit($brand_kit_id);
        if (!$brand_kit) {
            return false;
        }
        
        $update_data = array();
        $format = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (isset($data['colors'])) {
            $update_data['colors'] = json_encode($data['colors']);
            $format[] = '%s';
        }
        
        if (isset($data['fonts'])) {
            $update_data['fonts'] = json_encode($data['fonts']);
            $format[] = '%s';
        }
        
        if (isset($data['style_guidelines'])) {
            $update_data['style_guidelines'] = sanitize_textarea_field($data['style_guidelines']);
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $brand_kit_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            error_log('SNN AI Images - Database error in update_brand_kit: ' . $wpdb->last_error);
            return new WP_Error('db_update_error', __('Failed to update brand kit.', 'snn-ai-images'));
        }
        
        return true;
    }
    
    public function delete_brand_kit($brand_kit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'snn_ai_brand_kits';
        
        // Check permissions
        $brand_kit = $this->get_brand_kit($brand_kit_id);
        if (!$brand_kit) {
            return false;
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $brand_kit_id),
            array('%d')
        );
        
        if ($result === false) {
            error_log('SNN AI Images - Database error in delete_brand_kit: ' . $wpdb->last_error);
            return new WP_Error('db_delete_error', __('Failed to delete brand kit.', 'snn-ai-images'));
        }
        
        return true;
    }
    
    public function get_default_brand_colors() {
        return array(
            '#1F2937', // Gray-800
            '#3B82F6', // Blue-500
            '#10B981', // Green-500
            '#F59E0B', // Yellow-500
            '#EF4444', // Red-500
            '#8B5CF6', // Purple-500
            '#F97316', // Orange-500
            '#06B6D4'  // Cyan-500
        );
    }
    
    public function get_default_fonts() {
        return array(
            'Arial',
            'Helvetica',
            'Times New Roman',
            'Georgia',
            'Verdana',
            'Tahoma',
            'Trebuchet MS',
            'Impact'
        );
    }
    
    public function duplicate_brand_kit($brand_kit_id) {
        $brand_kit = $this->get_brand_kit($brand_kit_id);
        if (!$brand_kit) {
            return false;
        }
        
        $colors = json_decode($brand_kit->colors, true);
        $fonts = json_decode($brand_kit->fonts, true);
        
        return $this->create_brand_kit(
            $brand_kit->name . ' (Copy)',
            $colors,
            $fonts,
            $brand_kit->style_guidelines
        );
    }
    
    public function export_brand_kit($brand_kit_id) {
        $brand_kit = $this->get_brand_kit($brand_kit_id);
        if (!$brand_kit) {
            return false;
        }
        
        $export_data = array(
            'name' => $brand_kit->name,
            'colors' => json_decode($brand_kit->colors, true),
            'fonts' => json_decode($brand_kit->fonts, true),
            'style_guidelines' => $brand_kit->style_guidelines,
            'exported_at' => current_time('c'),
            'exported_by' => get_userdata(get_current_user_id())->display_name
        );
        
        return $export_data;
    }
    
    public function import_brand_kit($import_data) {
        if (!isset($import_data['name'])) {
            return false;
        }
        
        $name = sanitize_text_field($import_data['name']);
        $colors = isset($import_data['colors']) ? $import_data['colors'] : array();
        $fonts = isset($import_data['fonts']) ? $import_data['fonts'] : array();
        $style_guidelines = isset($import_data['style_guidelines']) ? sanitize_textarea_field($import_data['style_guidelines']) : '';
        
        return $this->create_brand_kit($name, $colors, $fonts, $style_guidelines);
    }
}