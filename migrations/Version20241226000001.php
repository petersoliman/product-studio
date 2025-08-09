<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create products table for Product Studio Intelligence API
 * 
 * This migration creates the products table with all necessary fields
 * for SEO-optimized product data storage and management.
 */
final class Version20241226000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products table for Product Studio Intelligence API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (
            id SERIAL PRIMARY KEY,
            name VARCHAR(60) NOT NULL,
            model_number VARCHAR(100) DEFAULT NULL,
            brand VARCHAR(100) DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            seo_keywords JSON DEFAULT NULL,
            brief VARCHAR(100) DEFAULT NULL,
            description VARCHAR(200) DEFAULT NULL,
            gallery_images JSON DEFAULT NULL,
            image_alt_texts JSON DEFAULT NULL,
            video_url VARCHAR(500) DEFAULT NULL,
            seo_title VARCHAR(60) DEFAULT NULL,
            meta_description VARCHAR(160) DEFAULT NULL,
            price DECIMAL(10,2) DEFAULT NULL,
            specifications JSON DEFAULT NULL,
            enrichment_status VARCHAR(50) DEFAULT \'pending\',
            source_urls JSON DEFAULT NULL,
            ai_metadata JSON DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // Create indexes for better query performance
        $this->addSql('CREATE INDEX idx_products_model_number ON products (model_number)');
        $this->addSql('CREATE INDEX idx_products_brand ON products (brand)');
        $this->addSql('CREATE INDEX idx_products_category ON products (category)');
        $this->addSql('CREATE INDEX idx_products_enrichment_status ON products (enrichment_status)');
        $this->addSql('CREATE INDEX idx_products_created_at ON products (created_at)');
        $this->addSql('CREATE INDEX idx_products_updated_at ON products (updated_at)');

        // Add full-text search index for product names
        $this->addSql('CREATE INDEX idx_products_name_search ON products USING gin(to_tsvector(\'english\', name))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE products');
    }
}

