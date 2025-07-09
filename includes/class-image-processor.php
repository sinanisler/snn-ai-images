<?php
/**
 * Image processor for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_Image_Processor {
    
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
    
    public function process_image($image_id, $prompt, $style_description = '', $brand_kit = null, $generation_type = 'style_transfer') {
        // Get original image path
        $original_image_path = get_attached_file($image_id);
        if (!$original_image_path || !file_exists($original_image_path)) {
            return new WP_Error('image_not_found', __('Original image file not found.', 'snn-ai-images'));
        }

        // Optimize image for processing (converts to JPEG, resizes)
        $optimized_image_path = $this->optimize_image_for_processing($original_image_path);
        if (is_wp_error($optimized_image_path)) {
            return $optimized_image_path;
        }
        
        try {
            // Generate image using Together AI
            $together_ai = SNN_AI_Images_Together_AI::get_instance();
            $base64_result = $together_ai->generate_image($prompt, $optimized_image_path, $style_description, $brand_kit, $generation_type);
            
            if (is_wp_error($base64_result)) {
                return $base64_result;
            }
            
            // Create attachment from base64 data
            $filename = $this->generate_filename($image_id, $generation_type);
            $attachment_id = $this->create_attachment_from_base64($base64_result, $filename, $image_id);
            
            if (is_wp_error($attachment_id)) {
                return $attachment_id;
            }
            
            // Add metadata
            $this->add_generation_metadata($attachment_id, $image_id, $prompt, $style_description, $brand_kit, $generation_type);
            
            // Get image URLs
            $image_url = wp_get_attachment_url($attachment_id);
            $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
            
            return array(
                'attachment_id' => $attachment_id,
                'image_url' => $image_url,
                'thumbnail_url' => $thumbnail_url
            );
            
        } catch (Exception $e) {
            return new WP_Error('processing_failed', $e->getMessage());
        } finally {
            // Clean up optimized image if it's different from original
            if ($optimized_image_path !== $original_image_path && file_exists($optimized_image_path)) {
                unlink($optimized_image_path);
            }
        }
    }
    
    private function optimize_image_for_processing($image_path) {
        // Check memory limits before processing
        $this->check_memory_limits($image_path);

        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return new WP_Error('invalid_image_info', __('Could not get image information.', 'snn-ai-images'));
        }
        $width = $image_info[0];
        $height = $image_info[1];
        $mime_type = $image_info['mime'];

        $settings = get_option('snn_ai_images_settings', array());
        // Ensure max_dimension has a sensible default value if it's not set or is zero.
        $max_dimension = !empty($settings['max_image_dimension']) ? intval($settings['max_image_dimension']) : 1024;
        $max_file_size = !empty($settings['max_file_size']) ? intval($settings['max_file_size']) : (2 * 1024 * 1024); // 2MB

        // If image is already within limits, return original path
        if ($width <= $max_dimension && $height <= $max_dimension && filesize($image_path) < $max_file_size) {
            return $image_path;
        }

        // Create a temporary directory for optimized images if it doesn't exist
        $upload_dir = wp_upload_dir();
        $temp_dir = !empty($settings['temp_directory']) ? $settings['temp_directory'] : $upload_dir['basedir'] . '/snn-ai-temp/';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        if (!is_writable($temp_dir)) {
            return new WP_Error('temp_dir_not_writable', __('Temporary directory is not writable.', 'snn-ai-images'));
        }

        $optimized_filename = 'optimized_' . uniqid() . '.jpg';
        $optimized_path = $temp_dir . $optimized_filename;

        // Load image from path
        $image = null;
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($image_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($image_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($image_path);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($image_path);
                break;
            default:
                return new WP_Error('unsupported_image_type', __('Unsupported image type for optimization.', 'snn-ai-images'));
        }

        if (!$image) {
            return new WP_Error('image_load_failed', __('Could not load image from path.', 'snn-ai-images'));
        }

        // Calculate new dimensions, ensuring they are at least 1px.
        if ($width > $height) {
            $new_width = $max_dimension;
            $new_height = intval($height * ($max_dimension / $width));
        } else {
            $new_height = $max_dimension;
            $new_width = intval($width * ($max_dimension / $height));
        }
        $new_width = max(1, $new_width);
        $new_height = max(1, $new_height);

        // Create the new image resource
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        if ($resized_image === false) {
            imagedestroy($image);
            return new WP_Error('image_create_failed', __('Could not create new image resource.', 'snn-ai-images'));
        }

        // Preserve transparency for PNG and WebP
        if ($mime_type === 'image/png' || $mime_type === 'image/webp') {
            imagealphablending($resized_image, false);
            imagesavealpha($resized_image, true);
            $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
            imagefill($resized_image, 0, 0, $transparent);
        }

        // Resize the image
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Save the optimized image as a JPEG
        imagejpeg($resized_image, $optimized_path, 85);

        // Clean up memory
        imagedestroy($image);
        imagedestroy($resized_image);

        return $optimized_path;
    }
    
    private function generate_filename($original_image_id, $generation_type) {
        $original_filename = basename(get_attached_file($original_image_id));
        $name_parts = pathinfo($original_filename);
        $base_name = $name_parts['filename'];
        
        $timestamp = date('YmdHis');
        $type_suffix = $generation_type === 'style_transfer' ? 'ai' : $generation_type;
        
        return $base_name . '_' . $type_suffix . '_' . $timestamp . '.jpg';
    }
    
    private function create_attachment_from_base64($base64_data, $filename, $parent_id = 0) {
        $upload_dir = wp_upload_dir();
        
        // Decode base64 data
        $image_data = base64_decode($base64_data);
        
        // Create unique filename
        $filename = wp_unique_filename($upload_dir['path'], $filename);
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save file
        $result = file_put_contents($file_path, $image_data);
        if ($result === false) {
            return new WP_Error('file_save_failed', __('Failed to save generated image.', 'snn-ai-images'));
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
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Generate metadata
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return $attachment_id;
    }
    
    private function add_generation_metadata($attachment_id, $original_image_id, $prompt, $style_description, $brand_kit, $generation_type) {
        // Add custom meta to identify AI-generated images
        add_post_meta($attachment_id, '_snn_ai_generated', true);
        add_post_meta($attachment_id, '_snn_ai_original_id', $original_image_id);
        add_post_meta($attachment_id, '_snn_ai_prompt', $prompt);
        add_post_meta($attachment_id, '_snn_ai_style_description', $style_description);
        add_post_meta($attachment_id, '_snn_ai_generation_type', $generation_type);
        add_post_meta($attachment_id, '_snn_ai_generation_date', current_time('mysql'));
        add_post_meta($attachment_id, '_snn_ai_user_id', get_current_user_id());
        
        if ($brand_kit) {
            add_post_meta($attachment_id, '_snn_ai_brand_kit_id', $brand_kit->id);
            add_post_meta($attachment_id, '_snn_ai_brand_kit_name', $brand_kit->name);
        }
    }
    
    public function get_generation_metadata($attachment_id) {
        $metadata = array();
        
        $metadata['is_ai_generated'] = get_post_meta($attachment_id, '_snn_ai_generated', true);
        $metadata['original_id'] = get_post_meta($attachment_id, '_snn_ai_original_id', true);
        $metadata['prompt'] = get_post_meta($attachment_id, '_snn_ai_prompt', true);
        $metadata['style_description'] = get_post_meta($attachment_id, '_snn_ai_style_description', true);
        $metadata['generation_type'] = get_post_meta($attachment_id, '_snn_ai_generation_type', true);
        $metadata['generation_date'] = get_post_meta($attachment_id, '_snn_ai_generation_date', true);
        $metadata['user_id'] = get_post_meta($attachment_id, '_snn_ai_user_id', true);
        $metadata['brand_kit_id'] = get_post_meta($attachment_id, '_snn_ai_brand_kit_id', true);
        $metadata['brand_kit_name'] = get_post_meta($attachment_id, '_snn_ai_brand_kit_name', true);
        
        return $metadata;
    }
    
    public function is_ai_generated($attachment_id) {
        return get_post_meta($attachment_id, '_snn_ai_generated', true) === true;
    }
    
    private function check_memory_limits($image_path) {
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return;
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        $bits = isset($image_info['bits']) ? $image_info['bits'] : 8;
        $channels = isset($image_info['channels']) ? $image_info['channels'] : 3;
        
        // Calculate memory needed (in bytes)
        $memory_needed = $width * $height * $bits * $channels / 8;
        
        // Add safety margin (multiply by 2)
        $memory_needed *= 2;
        
        // Get current memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit == -1) {
            return; // No memory limit
        }
        
        // Convert memory limit to bytes
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $memory_usage = memory_get_usage(true);
        
        // Check if we have enough memory
        if ($memory_usage + $memory_needed > $memory_limit_bytes) {
            // Try to increase memory limit
            $new_limit = ceil(($memory_usage + $memory_needed) / (1024 * 1024)) . 'M';
            if (!ini_set('memory_limit', $new_limit)) {
                throw new Exception(__('Insufficient memory to process image. Please try a smaller image.', 'snn-ai-images'));
            }
        }
    }
    
    private function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    public function get_supported_formats() {
        return array(
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/gif' => 'GIF',
            'image/webp' => 'WebP'
        );
    }
    
    public function validate_image_format($file_path) {
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return false;
        }
        
        $supported_formats = $this->get_supported_formats();
        return array_key_exists($image_info['mime'], $supported_formats);
    }
}