# SNN AI Images - WordPress Plugin

Transform any image into brand-consistent visuals using AI, with specialized features for e-commerce products.

## Features

### 🎨 Core Features
- **Style Transfer Tool**: Upload images and transform them with AI using custom prompts
- **Brand Kit Manager**: Create and manage brand colors, fonts, and style guidelines
- **One-Click Generation**: Simple and intuitive interface for quick content generation
- **Media Library Integration**: AI Edit button directly in WordPress media library
- **Generation History**: Track and manage all your AI-generated images

### 🛒 WooCommerce Integration
- **Product Variation Images**: Generate multiple unique images for product variations
- **Background Removal/Scene Generation**: Remove backgrounds and create lifestyle shots
- **Category Banner Generation**: Create branded banners for product categories
- **Product Gallery Integration**: Add generated images directly to product galleries

### 🔧 Technical Features
- **Together AI Integration**: Powered by FLUX models for high-quality image generation
- **REST API**: Full REST API for advanced integrations
- **User Permissions**: Capability-based access control
- **Usage Tracking**: Monitor generation limits and usage statistics
- **Error Handling**: Comprehensive error handling and user feedback

## Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'SNN AI Images' → 'Settings' to configure your API key

## Setup

### 1. Get Together AI API Key
1. Visit [Together AI](https://api.together.xyz/)
2. Create an account and get your API key
3. Go to WordPress Admin → SNN AI Images → Settings
4. Enter your API key and test the connection

### 2. Configure Settings
- Choose your preferred AI model (FLUX.1 Schnell, Dev, or Pro)
- Set usage limits per user
- Configure allowed file types

### 3. Create Brand Kits (Optional)
- Go to SNN AI Images → Brand Kits
- Create brand kits with your colors, fonts, and style guidelines
- Use these for consistent branding across all generated images

## Usage

### Dashboard Generation
1. Go to SNN AI Images → Dashboard
2. Upload images using drag & drop
3. Enter your transformation prompt
4. Optionally add style description and select brand kit
5. Click "Generate AI Images"

### Media Library Integration
1. Go to Media Library
2. Edit any image
3. Scroll to "AI Edit" section
4. Enter prompt and generate variations
5. Use generated images or download them

### WooCommerce Integration
1. Edit any product
2. Find "AI Product Images" meta box
3. Generate variations, remove backgrounds, or create lifestyle shots
4. Add generated images to product gallery

## API Endpoints

### Generate Image
```
POST /wp-json/snn-ai/v1/generate
```

### Brand Kits
```
GET    /wp-json/snn-ai/v1/brand-kits
POST   /wp-json/snn-ai/v1/brand-kits
PUT    /wp-json/snn-ai/v1/brand-kits/{id}
DELETE /wp-json/snn-ai/v1/brand-kits/{id}
```

### Generation History
```
GET /wp-json/snn-ai/v1/history
```

### Usage Statistics
```
GET /wp-json/snn-ai/v1/usage
```

## User Capabilities

The plugin adds the following capabilities:
- `use_snn_ai_images` - Generate AI images
- `manage_snn_ai_brand_kits` - Create and manage brand kits
- `view_snn_ai_history` - View generation history

## Database Tables

### Brand Kits (`wp_snn_ai_brand_kits`)
- `id` - Primary key
- `user_id` - Owner of the brand kit
- `name` - Brand kit name
- `colors` - JSON array of brand colors
- `fonts` - JSON array of brand fonts
- `style_guidelines` - Text description of brand style
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Generation History (`wp_snn_ai_generation_history`)
- `id` - Primary key
- `user_id` - User who generated the image
- `original_image_id` - WordPress attachment ID of original image
- `generated_image_id` - WordPress attachment ID of generated image
- `prompt` - Generation prompt
- `style_description` - Style description
- `brand_kit_id` - Associated brand kit
- `generation_type` - Type of generation
- `status` - Generation status (pending, completed, failed)
- `error_message` - Error message if failed
- `created_at` - Creation timestamp

## File Structure

```
snn-ai-images/
├── snn-ai-images.php           # Main plugin file
├── README.md                   # Documentation
├── assets/
│   ├── css/
│   │   └── admin.css          # Admin styles
│   └── js/
│       ├── admin.js           # Main admin JavaScript
│       ├── media-library.js   # Media library integration
│       ├── woocommerce.js     # WooCommerce integration
│       └── brand-kits.js      # Brand kit management
├── includes/
│   ├── class-admin.php        # Admin functionality
│   ├── class-api.php          # REST API endpoints
│   ├── class-together-ai.php  # Together AI integration
│   ├── class-media-library.php # Media library integration
│   ├── class-woocommerce.php  # WooCommerce integration
│   ├── class-brand-kit.php    # Brand kit manager
│   └── class-image-processor.php # Image processing
└── templates/
    ├── dashboard.php          # Main dashboard template
    ├── brand-kits.php         # Brand kits management
    ├── history.php            # Generation history
    └── settings.php           # Settings page
```

## Development

### Requirements
- WordPress 5.0+
- PHP 7.4+
- WooCommerce 3.0+ (optional, for e-commerce features)
 
### Customization
The plugin is built with extensibility in mind:
- All templates can be overridden in your theme
- Custom CSS can be added through WordPress hooks
- REST API endpoints can be extended
- Custom generation types can be added
