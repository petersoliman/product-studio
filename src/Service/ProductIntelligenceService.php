<?php

namespace App\Service;

use App\Entity\Product;
use App\Service\AI\SeoContentGeneratorService;
use App\Service\Scraper\ManufacturerScraperService;
use App\Service\SEO\KeywordResearchService;
use App\Service\Media\ImageDiscoveryService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Product Intelligence Service
 * 
 * Core orchestrator service that coordinates all AI-powered product data enrichment.
 * This is the main service that handles the complete workflow:
 * 1. Accept minimal product input
 * 2. Research and scrape manufacturer data
 * 3. Generate SEO-optimized content
 * 4. Discover and organize media assets
 * 5. Return complete product intelligence
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
class ProductIntelligenceService
{
    private EntityManagerInterface $entityManager;
    private SeoContentGeneratorService $seoGenerator;
    private ManufacturerScraperService $scraper;
    private KeywordResearchService $keywordResearch;
    private ImageDiscoveryService $imageDiscovery;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        SeoContentGeneratorService $seoGenerator,
        ManufacturerScraperService $scraper,
        KeywordResearchService $keywordResearch,
        ImageDiscoveryService $imageDiscovery,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->seoGenerator = $seoGenerator;
        $this->scraper = $scraper;
        $this->keywordResearch = $keywordResearch;
        $this->imageDiscovery = $imageDiscovery;
        $this->logger = $logger;
    }

    /**
     * Main method: Process product intelligence request
     * 
     * Takes minimal product data and returns complete SEO-optimized product information
     * 
     * @param array $inputData Minimal product information (name, model, keywords, etc.)
     * @return array Complete product intelligence response
     */
    public function processProductIntelligence(array $inputData): array
    {
        $this->logger->info('Starting product intelligence processing', ['input' => $inputData]);
        
        try {
            // Step 1: Create or find existing product
            $product = $this->createOrFindProduct($inputData);
            $product->setEnrichmentStatus('processing');
            $this->entityManager->flush();

            // Step 2: Manufacturer data enrichment (if model number provided)
            if ($product->getModelNumber()) {
                $this->enrichFromManufacturerData($product);
            }

            // Step 3: SEO keyword research and generation
            $this->generateSeoKeywords($product, $inputData);

            // Step 4: AI-powered content generation
            $this->generateSeoContent($product);

            // Step 5: Image and media discovery
            $this->discoverMediaAssets($product);

            // Step 6: Finalize and save
            $product->setEnrichmentStatus('completed');
            $this->entityManager->flush();

            $this->logger->info('Product intelligence processing completed', ['product_id' => $product->getId()]);

            return [
                'success' => true,
                'product' => $product->toArray(),
                'seo_data' => $product->getSeoData(),
                'structured_data' => $product->getStructuredData(),
                'processing_metadata' => [
                    'processing_time' => $this->calculateProcessingTime(),
                    'ai_models_used' => $product->getAiMetadata(),
                    'sources_consulted' => $product->getSourceUrls(),
                    'enrichment_status' => $product->getEnrichmentStatus()
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Product intelligence processing failed', [
                'error' => $e->getMessage(),
                'input' => $inputData
            ]);

            if (isset($product)) {
                $product->setEnrichmentStatus('failed');
                $this->entityManager->flush();
            }

            return [
                'success' => false,
                'error' => 'Failed to process product intelligence: ' . $e->getMessage(),
                'input_data' => $inputData
            ];
        }
    }

    /**
     * Create new product or find existing one based on input data
     */
    private function createOrFindProduct(array $inputData): Product
    {
        // Try to find existing product by model number or name
        if (!empty($inputData['model_number'])) {
            $existing = $this->entityManager->getRepository(Product::class)
                ->findOneBy(['modelNumber' => $inputData['model_number']]);
            if ($existing) {
                return $existing;
            }
        }

        // Create new product
        $product = new Product();
        
        // Set basic information from input
        if (!empty($inputData['name'])) {
            $product->setName($inputData['name']);
        }
        if (!empty($inputData['model_number'])) {
            $product->setModelNumber($inputData['model_number']);
        }
        if (!empty($inputData['brand'])) {
            $product->setBrand($inputData['brand']);
        }
        if (!empty($inputData['category'])) {
            $product->setCategory($inputData['category']);
        }
        if (!empty($inputData['seo_keywords'])) {
            $product->setSeoKeywords($inputData['seo_keywords']);
        }
        if (!empty($inputData['brief'])) {
            $product->setBrief($inputData['brief']);
        }
        if (!empty($inputData['description'])) {
            $product->setDescription($inputData['description']);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * Enrich product data from manufacturer websites
     */
    private function enrichFromManufacturerData(Product $product): void
    {
        $this->logger->info('Starting manufacturer data enrichment', [
            'model_number' => $product->getModelNumber(),
            'brand' => $product->getBrand()
        ]);

        $manufacturerData = $this->scraper->scrapeProductData(
            $product->getModelNumber(),
            $product->getBrand()
        );

        if ($manufacturerData) {
            // Update product with manufacturer data
            if (!$product->getName() && !empty($manufacturerData['name'])) {
                $product->setName($manufacturerData['name']);
            }
            if (!$product->getDescription() && !empty($manufacturerData['description'])) {
                $product->setDescription($manufacturerData['description']);
            }
            if (!$product->getPrice() && !empty($manufacturerData['price'])) {
                $product->setPrice($manufacturerData['price']);
            }
            if (!empty($manufacturerData['specifications'])) {
                $product->setSpecifications($manufacturerData['specifications']);
            }
            if (!empty($manufacturerData['images'])) {
                $product->setGalleryImages($manufacturerData['images']);
            }
            if (!empty($manufacturerData['source_url'])) {
                $product->setSourceUrls([$manufacturerData['source_url']]);
            }
        }
    }

    /**
     * Generate SEO keywords using research service
     */
    private function generateSeoKeywords(Product $product, array $inputData): void
    {
        // Use provided keywords or generate new ones
        $baseKeywords = $product->getSeoKeywords() ?: [];
        
        if (!empty($inputData['seo_focus_keywords'])) {
            $baseKeywords = array_merge($baseKeywords, $inputData['seo_focus_keywords']);
        }

        // Generate additional keywords based on product data
        $generatedKeywords = $this->keywordResearch->generateKeywords(
            $product->getName(),
            $product->getCategory(),
            $product->getBrand(),
            $baseKeywords
        );

        $product->setSeoKeywords(array_unique(array_merge($baseKeywords, $generatedKeywords)));
    }

    /**
     * Generate AI-powered SEO content
     */
    private function generateSeoContent(Product $product): void
    {
        $this->logger->info('Generating AI-powered SEO content', ['product_id' => $product->getId()]);

        $contentData = [
            'name' => $product->getName(),
            'category' => $product->getCategory(),
            'brand' => $product->getBrand(),
            'model_number' => $product->getModelNumber(),
            'keywords' => $product->getSeoKeywords(),
            'specifications' => $product->getSpecifications()
        ];

        $generatedContent = $this->seoGenerator->generateOptimizedContent($contentData);

        // Update product with AI-generated content
        if (!$product->getBrief() && !empty($generatedContent['brief'])) {
            $product->setBrief($generatedContent['brief']);
        }
        if (!$product->getDescription() && !empty($generatedContent['description'])) {
            $product->setDescription($generatedContent['description']);
        }
        if (!$product->getSeoTitle() && !empty($generatedContent['seo_title'])) {
            $product->setSeoTitle($generatedContent['seo_title']);
        }
        if (!$product->getMetaDescription() && !empty($generatedContent['meta_description'])) {
            $product->setMetaDescription($generatedContent['meta_description']);
        }

        // Store AI metadata
        $aiMetadata = $product->getAiMetadata() ?: [];
        $aiMetadata['content_generation'] = [
            'model' => $generatedContent['model_used'] ?? 'unknown',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'input_tokens' => $generatedContent['input_tokens'] ?? 0,
            'output_tokens' => $generatedContent['output_tokens'] ?? 0
        ];
        $product->setAiMetadata($aiMetadata);
    }

    /**
     * Discover and organize media assets
     */
    private function discoverMediaAssets(Product $product): void
    {
        $this->logger->info('Discovering media assets', ['product_id' => $product->getId()]);

        // If we don't have images yet, discover them
        if (empty($product->getGalleryImages())) {
            $discoveredImages = $this->imageDiscovery->findProductImages(
                $product->getName(),
                $product->getBrand(),
                $product->getModelNumber()
            );

            if ($discoveredImages) {
                $product->setGalleryImages($discoveredImages['urls']);
                $product->setImageAltTexts($discoveredImages['alt_texts']);
            }
        }

        // Generate alt texts if missing
        if (empty($product->getImageAltTexts()) && !empty($product->getGalleryImages())) {
            $altTexts = $this->seoGenerator->generateImageAltTexts(
                $product->getName(),
                $product->getSeoKeywords(),
                count($product->getGalleryImages())
            );
            $product->setImageAltTexts($altTexts);
        }

        // TODO: Implement video discovery/generation
        // This would integrate with services that can create 360° product videos
    }

    /**
     * Calculate processing time for metadata
     */
    private function calculateProcessingTime(): string
    {
        // This would be implemented with proper timing
        return '2.5s'; // Placeholder
    }

    /**
     * Validate input data for product intelligence processing
     */
    public function validateInputData(array $inputData): array
    {
        $errors = [];

        // At minimum, we need either a name or model number
        if (empty($inputData['name']) && empty($inputData['model_number'])) {
            $errors[] = 'Either product name or model number is required';
        }

        // Validate name length if provided
        if (!empty($inputData['name']) && strlen($inputData['name']) > 60) {
            $errors[] = 'Product name must be 60 characters or less for SEO optimization';
        }

        // Validate keywords if provided
        if (!empty($inputData['seo_keywords']) && !is_array($inputData['seo_keywords'])) {
            $errors[] = 'SEO keywords must be provided as an array';
        }

        return $errors;
    }

    /**
     * Get processing status for a product
     */
    public function getProcessingStatus(int $productId): array
    {
        $product = $this->entityManager->getRepository(Product::class)->find($productId);
        
        if (!$product) {
            return [
                'success' => false,
                'error' => 'Product not found'
            ];
        }

        return [
            'success' => true,
            'product_id' => $productId,
            'status' => $product->getEnrichmentStatus(),
            'progress' => $this->calculateCompletionProgress($product),
            'last_updated' => $product->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate how complete the product data is
     */
    private function calculateCompletionProgress(Product $product): array
    {
        $fields = [
            'name' => !empty($product->getName()),
            'brief' => !empty($product->getBrief()),
            'description' => !empty($product->getDescription()),
            'seo_keywords' => !empty($product->getSeoKeywords()),
            'seo_title' => !empty($product->getSeoTitle()),
            'meta_description' => !empty($product->getMetaDescription()),
            'gallery_images' => !empty($product->getGalleryImages()),
            'image_alt_texts' => !empty($product->getImageAltTexts())
        ];

        $completed = array_filter($fields);
        $percentage = count($completed) / count($fields) * 100;

        return [
            'percentage' => round($percentage, 1),
            'completed_fields' => array_keys($completed),
            'missing_fields' => array_keys(array_filter($fields, fn($v) => !$v))
        ];
    }
}
