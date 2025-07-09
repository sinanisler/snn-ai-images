<?php
/**
 * Together AI API integration for SNN AI Images plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SNN_AI_Images_Together_AI {
    
    private static $instance = null;
    private $api_key;
    private $api_url = 'https://api.together.xyz/v1/images/generations';
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $settings = get_option('snn_ai_images_settings');
        $this->api_key = $settings['api_key'] ?? '';
    }
    
    public function generate_image($prompt, $image_path = null, $style_description = '', $brand_kit = null, $generation_type = 'style_transfer') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Together AI API key is not configured.', 'snn-ai-images'));
        }

        $settings = get_option('snn_ai_images_settings');
        $model = $settings['model'] ?? 'black-forest-labs/FLUX.1-schnell';
        $enhanced_prompt = $this->build_enhanced_prompt($prompt, $style_description, $brand_kit, $generation_type);

        // All image generation tasks use the /images/generations endpoint.
        $api_url = 'https://api.together.xyz/v1/images/generations';

        // Determine appropriate steps based on the selected model to avoid API errors.
        $steps = 28; // Default for high-quality models like Dev or Pro
        if (stripos($model, 'schnell') !== false) {
            $steps = 4; // Schnell models require a lower number of steps.
        }

        $request_data = array(
            'model' => $model,
            'prompt' => $enhanced_prompt,
            'steps' => $steps,
            'n' => 1,
            'response_format' => 'b64_json',
        );

        if ($image_path && file_exists($image_path)) {
            // For image-to-image, send the image data as a base64 string.
            $image_data = file_get_contents($image_path);
            if ($image_data === false) {
                return new WP_Error('file_read_error', __('Could not read the image file.', 'snn-ai-images'));
            }
            $encoded_image = base64_encode($image_data);

            // Use 'condition_image' for Kontext models, 'image_b64' for others.
            if (stripos($model, 'kontext') !== false) {
                $request_data['condition_image'] = $encoded_image;
            } else {
                $request_data['image_b64'] = $encoded_image;
            }
        } else {
            // For text-to-image, specify dimensions.
            $request_data['width'] = 1024;
            $request_data['height'] = 1024;
        }

        // Make API request
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
            'timeout' => 120, // Increased timeout
            'data_format' => 'body'
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('SNN AI Images - API request failed: ' . $error_message);
            return new WP_Error('api_request_failed', sprintf('API Connection Error: %s', $error_message));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            // Return the exact error message from the API if available, otherwise return the full response body.
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : (isset($error_data['message']) ? $error_data['message'] : $response_body);
            
            error_log('SNN AI Images - API Error (' . $response_code . '): ' . $error_message . ' | Request: ' . json_encode($request_data));
            return new WP_Error('api_error', sprintf('API Error (%d): %s', $response_code, $error_message));
        }

        $data = json_decode($response_body, true);

        if (!isset($data['data'][0]['b64_json'])) {
            error_log('SNN AI Images - Invalid API response: Could not find image data in response. Body: ' . $response_body);
            return new WP_Error('invalid_response', __('Invalid response from the API. Image data not found.', 'snn-ai-images'));
        }

        return $data['data'][0]['b64_json'];
    }
    
    private function build_enhanced_prompt($prompt, $style_description, $brand_kit, $generation_type) {
        $enhanced_prompt = $prompt;
        
        // Add style description
        if (!empty($style_description)) {
            $enhanced_prompt .= ', ' . $style_description;
        }
        
        // Add brand kit information
        if ($brand_kit) {
            if (!empty($brand_kit->colors)) {
                $colors = json_decode($brand_kit->colors, true);
                if (is_array($colors) && !empty($colors)) {
                    $color_text = implode(', ', $colors);
                    $enhanced_prompt .= ', using brand colors: ' . $color_text;
                }
            }
            
            if (!empty($brand_kit->style_guidelines)) {
                $enhanced_prompt .= ', following style guidelines: ' . $brand_kit->style_guidelines;
            }
        }
        
        // Add generation type specific enhancements
        switch ($generation_type) {
            case 'background_removal':
                $enhanced_prompt .= ', on a transparent background, product photography style';
                break;
            case 'product_variation':
                $enhanced_prompt .= ', product photography, high quality, commercial style';
                break;
            case 'category_banner':
                $enhanced_prompt .= ', banner design, marketing style, eye-catching';
                break;
            case 'style_transfer':
            default:
                $enhanced_prompt .= ', high quality, professional style';
                break;
        }
        
        // Add general quality improvements
        $enhanced_prompt .= ', 8k resolution, highly detailed, professional lighting';
        
        return $enhanced_prompt;
    }
    
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key is not configured.', 'snn-ai-images'));
        }
        
        $test_prompt = 'A simple test image of a red circle on a white background';
        
        $request_data = array(
            'model' => 'black-forest-labs/FLUX.1-schnell',
            'prompt' => $test_prompt,
            'width' => 512,
            'height' => 512,
            'steps' => 1,
            'n' => 1,
            'response_format' => 'b64_json'
        );
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
            'timeout' => 30,
            'data_format' => 'body'
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('connection_failed', __('Failed to connect to Together AI API.', 'snn-ai-images'));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return true;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $error_data = json_decode($response_body, true);
        $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : __('API connection test failed.', 'snn-ai-images');
        
        return new WP_Error('test_failed', $error_message);
    }
    
    public function get_available_models() {
        // Try to get from cache first
        $cached_models = get_transient('snn_ai_together_models');
        if ($cached_models) {
            return $cached_models;
        }

        if (empty($this->api_key)) {
            return array(); // No API key, no models
        }

        $models_url = 'https://api.together.xyz/v1/models';
        
        $response = wp_remote_get($models_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('SNN AI Images - Failed to fetch models: ' . (is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response)));
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($data) || !is_array($data)) {
            return array();
        }
        
        // Filter for relevant image models based on API documentation and common types
        $image_models = array_filter($data, function($model) {
            // Check for a 'type' key indicating an image model
            if (isset($model['type']) && $model['type'] === 'image') {
                return true;
            }
            // Also check for models that are known to be for images but might lack the 'type' field
            if (isset($model['id']) && (
                stripos($model['id'], 'flux') !== false ||
                stripos($model['id'], 'stable-diffusion') !== false ||
                stripos($model['id'], 'sdxl') !== false ||
                stripos($model['id'], 'playground') !== false ||
                stripos($model['id'], 'openjourney') !== false
            )) {
                return true;
            }
            return false;
        });

        // Sort models by display name if available
        usort($image_models, function($a, $b) {
            $a_name = $a['display_name'] ?? $a['id'];
            $b_name = $b['display_name'] ?? $b['id'];
            return strcmp($a_name, $b_name);
        });

        // Cache the result for 12 hours
        set_transient('snn_ai_together_models', $image_models, 12 * HOUR_IN_SECONDS);
        
        return $image_models;
    }
}