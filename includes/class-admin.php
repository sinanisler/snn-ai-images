<?php
/**
 * Admin functionality for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SNN AI Images', 'snn-ai-images'),
            __('SNN AI Images', 'snn-ai-images'),
            'use_snn_ai_images',
            'snn-ai-images-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            30
        );
        
        add_submenu_page(
            'snn-ai-images-dashboard',
            __('Dashboard', 'snn-ai-images'),
            __('Dashboard', 'snn-ai-images'),
            'use_snn_ai_images',
            'snn-ai-images-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'snn-ai-images-dashboard',
            __('Brand Kits', 'snn-ai-images'),
            __('Brand Kits', 'snn-ai-images'),
            'manage_snn_ai_brand_kits',
            'snn-ai-images-brand-kits',
            array($this, 'brand_kits_page')
        );
        
        add_submenu_page(
            'snn-ai-images-dashboard',
            __('Generation History', 'snn-ai-images'),
            __('History', 'snn-ai-images'),
            'view_snn_ai_history',
            'snn-ai-images-history',
            array($this, 'history_page')
        );
        
        add_submenu_page(
            'snn-ai-images-dashboard',
            __('Settings', 'snn-ai-images'),
            __('Settings', 'snn-ai-images'),
            'manage_options',
            'snn-ai-images-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'snn-ai-images') === false) {
            return;
        }
        
        // Enqueue Tailwind CSS
        wp_enqueue_script('tailwind-css', 'https://cdn.tailwindcss.com', array(), SNN_AI_IMAGES_VERSION, false);
        
        // Enqueue TippyJS
        wp_enqueue_script('tippy-js', 'https://unpkg.com/@popperjs/core@2', array(), SNN_AI_IMAGES_VERSION, false);
        wp_enqueue_script('tippy-js-main', 'https://unpkg.com/tippy.js@6', array('tippy-js'), SNN_AI_IMAGES_VERSION, false);
        
        // Enqueue custom admin JS
        wp_enqueue_script(
            'snn-ai-admin',
            SNN_AI_IMAGES_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api-fetch', 'tippy-js-main'),
            SNN_AI_IMAGES_VERSION,
            true
        );
        
        // Enqueue custom admin CSS
        wp_enqueue_style(
            'snn-ai-admin',
            SNN_AI_IMAGES_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SNN_AI_IMAGES_VERSION
        );
        
        // Get settings for security configuration
        $settings = get_option('snn_ai_images_settings', array());
        
        // Localize script with data
        wp_localize_script('snn-ai-admin', 'snnAiAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('snn-ai/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'maxFileSize' => isset($settings['max_file_size']) ? intval($settings['max_file_size']) : wp_max_upload_size(),
            'maxFiles' => isset($settings['max_files_per_upload']) ? intval($settings['max_files_per_upload']) : 5,
            'allowedTypes' => isset($settings['allowed_file_types']) ? $settings['allowed_file_types'] : array('jpg', 'jpeg', 'png', 'gif', 'webp'),
            'securityChecks' => array(
                'checkFileExtension' => true,
                'checkMimeType' => true,
                'checkFileSize' => true,
                'checkFileName' => true
            ),
            'strings' => array(
                'uploadError' => __('Upload failed. Please try again.', 'snn-ai-images'),
                'processingError' => __('Processing failed. Please try again.', 'snn-ai-images'),
                'invalidFileType' => __('Invalid file type. Please upload an image.', 'snn-ai-images'),
                'fileTooLarge' => __('File is too large. Please upload a smaller image.', 'snn-ai-images'),
                'generating' => __('Generating...', 'snn-ai-images'),
                'success' => __('Image generated successfully!', 'snn-ai-images'),
                'suspiciousFile' => __('File appears to be suspicious and was rejected.', 'snn-ai-images'),
                'tooManyFiles' => __('Too many files selected. Please select fewer files.', 'snn-ai-images')
            )
        ));
    }
    
    public function register_settings() {
        register_setting('snn_ai_images_settings', 'snn_ai_images_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        
        add_settings_section(
            'snn_ai_images_api_section',
            __('API Configuration', 'snn-ai-images'),
            array($this, 'api_section_callback'),
            'snn-ai-images-settings'
        );
        
        add_settings_field(
            'api_key',
            __('Together AI API Key', 'snn-ai-images'),
            array($this, 'api_key_callback'),
            'snn-ai-images-settings',
            'snn_ai_images_api_section'
        );
        
        add_settings_field(
            'model',
            __('AI Model', 'snn-ai-images'),
            array($this, 'model_callback'),
            'snn-ai-images-settings',
            'snn_ai_images_api_section'
        );
        
        add_settings_field(
            'max_generations_per_user',
            __('Max Generations Per User', 'snn-ai-images'),
            array($this, 'max_generations_callback'),
            'snn-ai-images-settings',
            'snn_ai_images_api_section'
        );
        
        // Add security settings section
        add_settings_section(
            'snn_ai_images_security_section',
            __('Security Settings', 'snn-ai-images'),
            array($this, 'security_section_callback'),
            'snn-ai-images-settings'
        );
        
        add_settings_field(
            'max_file_size',
            __('Max File Size (bytes)', 'snn-ai-images'),
            array($this, 'max_file_size_callback'),
            'snn-ai-images-settings',
            'snn_ai_images_security_section'
        );
        
        add_settings_field(
            'max_files_per_upload',
            __('Max Files Per Upload', 'snn-ai-images'),
            array($this, 'max_files_per_upload_callback'),
            'snn-ai-images-settings',
            'snn_ai_images_security_section'
        );
        
        add_settings_field(
            'allowed_file_types',
            __('Allowed File Types', 'snn-ai-images'),
            array($this, 'allowed_file_types_callback'),
            'snn-ai-images-settings',
            'snn_ai_images_security_section'
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        $sanitized['model'] = sanitize_text_field($input['model']);
        $sanitized['max_generations_per_user'] = absint($input['max_generations_per_user']);
        $sanitized['max_file_size'] = absint($input['max_file_size']);
        $sanitized['max_files_per_upload'] = absint($input['max_files_per_upload']);
        $sanitized['max_image_dimension'] = absint($input['max_image_dimension']);
        $sanitized['temp_directory'] = sanitize_text_field($input['temp_directory']);
        
        // Validate and sanitize allowed file types
        if (isset($input['allowed_file_types']) && is_array($input['allowed_file_types'])) {
            $valid_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $sanitized['allowed_file_types'] = array_intersect($input['allowed_file_types'], $valid_types);
        } else {
            $sanitized['allowed_file_types'] = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        }
        
        return $sanitized;
    }
    
    public function api_section_callback() {
        echo '<p>' . __('Configure your Together AI API settings.', 'snn-ai-images') . '</p>';
    }
    
    public function api_key_callback() {
        $settings = get_option('snn_ai_images_settings');
        $api_key = $settings['api_key'] ?? '';
        echo '<input type="password" name="snn_ai_images_settings[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Together AI API key.', 'snn-ai-images') . '</p>';
    }
    
    public function model_callback() {
        $settings = get_option('snn_ai_images_settings');
        $model = $settings['model'] ?? 'black-forest-labs/FLUX.1-schnell';
        
        $models = array(
            'black-forest-labs/FLUX.1-schnell' => 'FLUX.1 Schnell (Fast)',
            'black-forest-labs/FLUX.1-dev' => 'FLUX.1 Dev (Quality)',
            'black-forest-labs/FLUX.1-pro' => 'FLUX.1 Pro (Premium)'
        );
        
        echo '<select name="snn_ai_images_settings[model]">';
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($model, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    public function security_section_callback() {
        echo '<p>' . __('Configure security settings for file uploads and processing.', 'snn-ai-images') . '</p>';
    }
    
    public function max_file_size_callback() {
        $settings = get_option('snn_ai_images_settings');
        $max_file_size = $settings['max_file_size'] ?? wp_max_upload_size();
        echo '<input type="number" name="snn_ai_images_settings[max_file_size]" value="' . esc_attr($max_file_size) . '" class="regular-text" />';
        echo '<p class="description">' . __('Maximum file size in bytes. Default: WordPress maximum upload size.', 'snn-ai-images') . '</p>';
    }
    
    public function max_files_per_upload_callback() {
        $settings = get_option('snn_ai_images_settings');
        $max_files = $settings['max_files_per_upload'] ?? 5;
        echo '<input type="number" name="snn_ai_images_settings[max_files_per_upload]" value="' . esc_attr($max_files) . '" class="regular-text" min="1" max="20" />';
        echo '<p class="description">' . __('Maximum number of files that can be uploaded at once.', 'snn-ai-images') . '</p>';
    }
    
    public function allowed_file_types_callback() {
        $settings = get_option('snn_ai_images_settings');
        $allowed_types = $settings['allowed_file_types'] ?? array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        $available_types = array(
            'jpg' => 'JPG',
            'jpeg' => 'JPEG',
            'png' => 'PNG',
            'gif' => 'GIF',
            'webp' => 'WebP'
        );
        
        foreach ($available_types as $type => $label) {
            $checked = in_array($type, $allowed_types) ? 'checked' : '';
            echo '<label><input type="checkbox" name="snn_ai_images_settings[allowed_file_types][]" value="' . esc_attr($type) . '" ' . $checked . ' /> ' . esc_html($label) . '</label><br>';
        }
        echo '<p class="description">' . __('Select which file types are allowed for upload.', 'snn-ai-images') . '</p>';
    }
    
    public function max_generations_callback() {
        $settings = get_option('snn_ai_images_settings');
        $max_generations = $settings['max_generations_per_user'] ?? 50;
        echo '<input type="number" name="snn_ai_images_settings[max_generations_per_user]" value="' . esc_attr($max_generations) . '" min="1" max="1000" />';
        echo '<p class="description">' . __('Maximum number of generations per user per month.', 'snn-ai-images') . '</p>';
    }
    
    public function dashboard_page() {
        include SNN_AI_IMAGES_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    public function brand_kits_page() {
        include SNN_AI_IMAGES_PLUGIN_PATH . 'templates/brand-kits.php';
    }
    
    public function history_page() {
        include SNN_AI_IMAGES_PLUGIN_PATH . 'templates/history.php';
    }
    
    public function settings_page() {
        include SNN_AI_IMAGES_PLUGIN_PATH . 'templates/settings.php';
    }
}