<?php
/**
 * Plugin Name: SNN AI Images
 * Description: Transform any image into brand-consistent visuals using AI, with specialized features for e-commerce products.
 * Version: 1.0.0
 * Author: Sinan Isler
 * Text Domain: snn-ai-images
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('SNN_AI_IMAGES_VERSION', '1.0.0');
define('SNN_AI_IMAGES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SNN_AI_IMAGES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SNN_AI_IMAGES_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Main plugin class
class SNN_AI_Images {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load plugin textdomain
        load_plugin_textdomain('snn-ai-images', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->includes();
        $this->hooks();
    }
    
    private function includes() {
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-admin.php';
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-api.php';
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-together-ai.php';
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-media-library.php';
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-woocommerce.php';
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-brand-kit.php';
        require_once SNN_AI_IMAGES_PLUGIN_PATH . 'includes/class-image-processor.php';
    }
    
    private function hooks() {
        // Initialize admin if in admin area
        if (is_admin()) {
            SNN_AI_Images_Admin::get_instance();
        }
        
        // Initialize API endpoints
        SNN_AI_Images_API::get_instance();
        
        // Initialize media library integration
        SNN_AI_Images_Media_Library::get_instance();
        
        // Initialize WooCommerce integration if WooCommerce is active
        if (class_exists('WooCommerce')) {
            SNN_AI_Images_WooCommerce::get_instance();
        }
        
        // Initialize brand kit manager
        SNN_AI_Images_Brand_Kit::get_instance();
        
        // Initialize image processor
        SNN_AI_Images_Image_Processor::get_instance();
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Add default options
        add_option('snn_ai_images_settings', array(
            'api_key' => '',
            'model' => 'black-forest-labs/FLUX.1-schnell',
            'max_generations_per_user' => 50,
            'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'webp')
        ));
        
        // Add capabilities
        $this->add_capabilities();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up temporary files
        $this->cleanup_temp_files();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Brand kits table
        $brand_kits_table = $wpdb->prefix . 'snn_ai_brand_kits';
        $brand_kits_sql = "CREATE TABLE $brand_kits_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            colors text,
            fonts text,
            style_guidelines text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Generation history table
        $history_table = $wpdb->prefix . 'snn_ai_generation_history';
        $history_sql = "CREATE TABLE $history_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            original_image_id bigint(20),
            generated_image_id bigint(20),
            prompt text,
            style_description text,
            brand_kit_id mediumint(9),
            generation_type varchar(50),
            status varchar(20) DEFAULT 'pending',
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY original_image_id (original_image_id),
            KEY generated_image_id (generated_image_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($brand_kits_sql);
        dbDelta($history_sql);
    }
    
    private function add_capabilities() {
        $admin_role = get_role('administrator');
        $editor_role = get_role('editor');
        
        $capabilities = array(
            'use_snn_ai_images',
            'manage_snn_ai_brand_kits',
            'view_snn_ai_history'
        );
        
        foreach ($capabilities as $cap) {
            if ($admin_role) {
                $admin_role->add_cap($cap);
            }
            if ($editor_role) {
                $editor_role->add_cap($cap);
            }
        }
    }
    
    private function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/snn-ai-temp/';
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}

// Initialize the plugin
SNN_AI_Images::get_instance();