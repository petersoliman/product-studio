<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Product Entity for Product Studio Intelligence API
 * 
 * This entity represents a product in our system with AI-generated SEO-optimized content.
 * Designed specifically for power tools, hand tools, materials, and industrial products.
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Product name - SEO optimized for search visibility
     * Max 60 characters for optimal SEO title length
     */
    #[ORM\Column(type: Types::STRING, length: 60)]
    private ?string $name = null;

    /**
     * Manufacturer model number - for data validation and enrichment
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $modelNumber = null;

    /**
     * Brand/manufacturer name
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $brand = null;

    /**
     * Category for industrial/power tools classification
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $category = null;

    /**
     * SEO-focused keywords array (JSON)
     * Primary keywords for search optimization
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $seoKeywords = [];

    /**
     * AI-generated brief description - exactly 100 characters
     * Optimized for meta descriptions and snippets
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $brief = null;

    /**
     * AI-generated detailed description - exactly 200 characters
     * SEO-optimized with natural keyword integration
     */
    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    private ?string $description = null;

    /**
     * Product gallery image URLs (JSON array)
     * URLs to high-quality product images for download
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $galleryImages = [];

    /**
     * SEO alt text for images (JSON array)
     * Corresponds to galleryImages array
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $imageAltTexts = [];

    /**
     * Product rotation video URL (5 seconds max)
     * 360-degree product demonstration video
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $videoUrl = null;

    /**
     * SEO-optimized meta title
     * Different from name, specifically for <title> tag
     */
    #[ORM\Column(type: Types::STRING, length: 60, nullable: true)]
    private ?string $seoTitle = null;

    /**
     * SEO meta description
     * For search engine result snippets
     */
    #[ORM\Column(type: Types::STRING, length: 160, nullable: true)]
    private ?string $metaDescription = null;

    /**
     * Product price (decimal)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    /**
     * Product specifications (JSON)
     * Technical details, dimensions, power ratings, etc.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $specifications = [];

    /**
     * Data enrichment status
     * Tracks completion of AI processing and data gathering
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $enrichmentStatus = 'pending'; // pending, processing, completed, failed

    /**
     * Source URLs where data was scraped from
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $sourceUrls = [];

    /**
     * AI generation metadata
     * Tracks which AI models/services were used
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $aiMetadata = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->enrichmentStatus = 'pending';
        $this->seoKeywords = [];
        $this->galleryImages = [];
        $this->imageAltTexts = [];
        $this->specifications = [];
        $this->sourceUrls = [];
        $this->aiMetadata = [];
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getModelNumber(): ?string
    {
        return $this->modelNumber;
    }

    public function setModelNumber(?string $modelNumber): static
    {
        $this->modelNumber = $modelNumber;
        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getSeoKeywords(): ?array
    {
        return $this->seoKeywords;
    }

    public function setSeoKeywords(?array $seoKeywords): static
    {
        $this->seoKeywords = $seoKeywords;
        return $this;
    }

    public function getBrief(): ?string
    {
        return $this->brief;
    }

    public function setBrief(?string $brief): static
    {
        $this->brief = $brief;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getGalleryImages(): ?array
    {
        return $this->galleryImages;
    }

    public function setGalleryImages(?array $galleryImages): static
    {
        $this->galleryImages = $galleryImages;
        return $this;
    }

    public function getImageAltTexts(): ?array
    {
        return $this->imageAltTexts;
    }

    public function setImageAltTexts(?array $imageAltTexts): static
    {
        $this->imageAltTexts = $imageAltTexts;
        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;
        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getSpecifications(): ?array
    {
        return $this->specifications;
    }

    public function setSpecifications(?array $specifications): static
    {
        $this->specifications = $specifications;
        return $this;
    }

    public function getEnrichmentStatus(): ?string
    {
        return $this->enrichmentStatus;
    }

    public function setEnrichmentStatus(?string $enrichmentStatus): static
    {
        $this->enrichmentStatus = $enrichmentStatus;
        return $this;
    }

    public function getSourceUrls(): ?array
    {
        return $this->sourceUrls;
    }

    public function setSourceUrls(?array $sourceUrls): static
    {
        $this->sourceUrls = $sourceUrls;
        return $this;
    }

    public function getAiMetadata(): ?array
    {
        return $this->aiMetadata;
    }

    public function setAiMetadata(?array $aiMetadata): static
    {
        $this->aiMetadata = $aiMetadata;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convert product to array for API responses
     * 
     * @return array Complete product data optimized for JSON API responses
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model_number' => $this->modelNumber,
            'brand' => $this->brand,
            'category' => $this->category,
            'seo_keywords' => $this->seoKeywords,
            'brief' => $this->brief,
            'description' => $this->description,
            'gallery_images' => $this->galleryImages,
            'image_alt_texts' => $this->imageAltTexts,
            'video_url' => $this->videoUrl,
            'seo_title' => $this->seoTitle,
            'meta_description' => $this->metaDescription,
            'price' => $this->price,
            'specifications' => $this->specifications,
            'enrichment_status' => $this->enrichmentStatus,
            'source_urls' => $this->sourceUrls,
            'ai_metadata' => $this->aiMetadata,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get SEO-optimized data for website integration
     * 
     * @return array SEO-focused subset of product data
     */
    public function getSeoData(): array
    {
        return [
            'title' => $this->seoTitle ?: $this->name,
            'meta_description' => $this->metaDescription,
            'keywords' => $this->seoKeywords,
            'brief' => $this->brief,
            'description' => $this->description,
            'images' => array_map(function($url, $index) {
                return [
                    'url' => $url,
                    'alt' => $this->imageAltTexts[$index] ?? $this->name
                ];
            }, $this->galleryImages, array_keys($this->galleryImages)),
            'video' => $this->videoUrl,
            'structured_data' => $this->getStructuredData()
        ];
    }

    /**
     * Generate Schema.org structured data for SEO
     * 
     * @return array Schema.org Product markup
     */
    public function getStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $this->name,
            'description' => $this->description,
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->brand
            ],
            'model' => $this->modelNumber,
            'category' => $this->category,
            'image' => $this->galleryImages,
            'offers' => $this->price ? [
                '@type' => 'Offer',
                'price' => $this->price,
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock'
            ] : null,
            'additionalProperty' => array_map(function($key, $value) {
                return [
                    '@type' => 'PropertyValue',
                    'name' => $key,
                    'value' => $value
                ];
            }, array_keys($this->specifications ?: []), array_values($this->specifications ?: []))
        ];
    }
}
