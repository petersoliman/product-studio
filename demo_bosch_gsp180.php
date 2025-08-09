<?php
/**
 * Demo: Product Intelligence for Bosch GSP180
 * 
 * This script demonstrates how the Product Studio API would process
 * the Bosch GSP180 model and generate comprehensive product data.
 */

// Simulated input data for Bosch GSP180
$inputData = [
    'model_number' => 'GSP180',
    'brand' => 'Bosch',
    'name' => 'Bosch GSP180'
];

echo "🔍 PRODUCT INTELLIGENCE DEMO: Bosch GSP180\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "📥 INPUT DATA:\n";
echo json_encode($inputData, JSON_PRETTY_PRINT) . "\n\n";

// Step 1: Manufacturer Data Scraping (what our scraper would find)
echo "🌐 MANUFACTURER DATA ENRICHMENT:\n";
$manufacturerData = [
    'name' => 'Bosch GSP180 18V Professional Drywall Screwdriver',
    'description' => 'Professional cordless drywall screwdriver with precise depth control and efficient fastening.',
    'specifications' => [
        'voltage' => '18V',
        'max_torque' => '8 Nm',
        'chuck_type' => 'Bit holder with magnetic bit holder',
        'weight' => '0.7 kg',
        'speed' => '0-4,500 rpm',
        'battery_compatible' => '18V Professional battery system'
    ],
    'category' => 'Power Tools - Screwdrivers',
    'price' => '129.99',
    'images' => [
        'https://bosch-professional.com/images/gsp180-main.jpg',
        'https://bosch-professional.com/images/gsp180-side.jpg'
    ]
];
echo json_encode($manufacturerData, JSON_PRETTY_PRINT) . "\n\n";

// Step 2: SEO Keyword Research
echo "🔍 SEO KEYWORD RESEARCH:\n";
$generatedKeywords = [
    'bosch gsp180',
    'drywall screwdriver',
    'cordless screwdriver',
    '18v screwdriver',
    'professional screwdriver',
    'bosch professional tools',
    'drywall installation',
    'construction screwdriver',
    'bosch 18v system',
    'magnetic bit holder',
    'depth control screwdriver',
    'commercial screwdriver'
];
echo json_encode($generatedKeywords, JSON_PRETTY_PRINT) . "\n\n";

// Step 3: AI-Generated SEO Content
echo "🤖 AI-GENERATED SEO CONTENT:\n";
$seoContent = [
    'brief' => 'Bosch GSP180 18V Professional drywall screwdriver with precise depth control for efficient fastening.',
    'description' => 'The Bosch GSP180 delivers professional-grade drywall installation performance. Features precise depth control, magnetic bit holder, and 18V battery compatibility for extended runtime.',
    'seo_title' => 'Bosch GSP180 18V Drywall Screwdriver | Professional Tool',
    'meta_description' => 'Shop Bosch GSP180 18V Professional drywall screwdriver - precise depth control, magnetic bit holder. Professional grade tool for contractors.'
];
echo json_encode($seoContent, JSON_PRETTY_PRINT) . "\n\n";

// Step 4: Image Discovery & SEO Alt Text
echo "📸 IMAGE DISCOVERY & SEO OPTIMIZATION:\n";
$imageData = [
    'gallery_images' => [
        'https://example.com/bosch-gsp180-main-view.jpg',
        'https://example.com/bosch-gsp180-side-profile.jpg',
        'https://example.com/bosch-gsp180-in-use.jpg'
    ],
    'image_alt_texts' => [
        'Bosch GSP180 18V Professional Drywall Screwdriver - Main Product Image',
        'Bosch GSP180 - Side Profile View with Magnetic Bit Holder',
        'Bosch GSP180 in Use - Professional Drywall Installation'
    ]
];
echo json_encode($imageData, JSON_PRETTY_PRINT) . "\n\n";

// Final Complete Product Data (what the API would return)
echo "✅ COMPLETE PRODUCT INTELLIGENCE RESPONSE:\n";
$completeResponse = [
    'status' => 'success',
    'data' => [
        'product' => [
            'id' => 123,
            'name' => 'Bosch GSP180 18V Professional Drywall Screwdriver',
            'model_number' => 'GSP180',
            'brand' => 'Bosch',
            'category' => 'Power Tools - Screwdrivers',
            'seo_keywords' => $generatedKeywords,
            'brief' => $seoContent['brief'],
            'description' => $seoContent['description'],
            'seo_title' => $seoContent['seo_title'],
            'meta_description' => $seoContent['meta_description'],
            'gallery_images' => $imageData['gallery_images'],
            'image_alt_texts' => $imageData['image_alt_texts'],
            'specifications' => $manufacturerData['specifications'],
            'price' => $manufacturerData['price'],
            'enrichment_status' => 'completed'
        ],
        'seo_data' => [
            'title' => $seoContent['seo_title'],
            'meta_description' => $seoContent['meta_description'],
            'keywords' => $generatedKeywords,
            'structured_data' => [
                '@context' => 'https://schema.org/',
                '@type' => 'Product',
                'name' => 'Bosch GSP180 18V Professional Drywall Screwdriver',
                'description' => $seoContent['description'],
                'brand' => ['@type' => 'Brand', 'name' => 'Bosch'],
                'model' => 'GSP180',
                'category' => 'Power Tools',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '129.99',
                    'priceCurrency' => 'USD'
                ]
            ]
        ]
    ]
];

echo json_encode($completeResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "🎯 HOW TO USE THIS DATA:\n";
echo "- Copy SEO title and meta description for your website\n";
echo "- Use the generated keywords for SEO optimization\n";
echo "- Download images from the provided URLs\n";
echo "- Implement structured data for better search visibility\n";
echo "- Use specifications for detailed product pages\n\n";

echo "🚀 TO GET REAL DATA:\n";
echo "1. Setup database: docker-compose up -d database\n";
echo "2. Run migrations: php bin/console doctrine:migrations:migrate\n";
echo "3. Make API request:\n";
echo "   curl -X POST http://localhost:8000/api/products/intelligence \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     -d '{\"model_number\": \"GSP180\", \"brand\": \"Bosch\"}'\n";




