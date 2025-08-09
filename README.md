# Product Studio Intelligence API

> **AI-Powered Product Data Enrichment & SEO Optimization Platform**

Transform minimal product information into comprehensive, SEO-optimized catalogs with our intelligent API. Built for manufacturers, retailers, and e-commerce platforms who need professional product data at scale.

[![API Version](https://img.shields.io/badge/API-v1.0.0-blue.svg)](https://github.com/your-org/product-studio)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-6.4-green.svg)](https://symfony.com/)
[![Database](https://img.shields.io/badge/Database-PostgreSQL-blue.svg)](https://postgresql.org/)

## 🚀 Features

### 🎯 **Core Intelligence Processing**
- **Manufacturer Data Scraping**: Automatically extract product details from brand websites
- **AI-Powered Content Generation**: Create SEO-optimized descriptions, titles, and metadata
- **Advanced Keyword Research**: Generate targeted SEO keywords with commercial intent analysis
- **Professional Image Discovery**: Find high-quality product images with SEO alt-text generation
- **Structured Data Generation**: Schema.org markup for enhanced search visibility

### 📊 **API Capabilities**
- **RESTful JSON API**: Clean, predictable endpoints with comprehensive responses
- **Real-time Processing**: Get complete product intelligence in seconds
- **Batch Operations**: Process multiple products efficiently
- **Status Tracking**: Monitor processing progress and completion status
- **Advanced Filtering**: Search and filter products by brand, category, status, and more

### 🔒 **Enterprise Security**
- **Rate Limiting**: Prevent abuse with configurable request limits
- **Input Validation**: Comprehensive sanitization and XSS protection
- **API Key Authentication**: Secure access control (optional)
- **Request Logging**: Detailed audit trails for monitoring and debugging

### 🎛️ **Management & Analytics**
- **Admin Dashboard APIs**: System analytics and performance metrics
- **Health Monitoring**: Detailed system status and dependency checks
- **Data Export**: Bulk export capabilities for integration
- **Maintenance Tools**: Reset failed processes, clear caches, cleanup data

## 📖 Table of Contents

- [Quick Start](#quick-start)
- [Installation](#installation)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Examples](#examples)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- PostgreSQL 12+
- Composer
- Docker (optional, for easy setup)

### 1. Clone and Install
```bash
git clone https://github.com/your-org/product-studio.git
cd product-studio
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database credentials and API keys
```

### 3. Setup Database
```bash
# With Docker
docker-compose up -d database

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Start Development Server
```bash
symfony serve -d
# API available at: http://localhost:8000
```

### 5. Test the API
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -d '{
    "name": "DeWalt 20V MAX Drill",
    "model_number": "DCD771C2",
    "brand": "DeWalt"
  }'
```

## 📋 Installation

### Option 1: Docker Setup (Recommended)
```bash
# Clone repository
git clone https://github.com/your-org/product-studio.git
cd product-studio

# Start services
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# API available at: http://localhost:8000
```

### Option 2: Manual Setup
```bash
# Clone and install dependencies
git clone https://github.com/your-org/product-studio.git
cd product-studio
composer install

# Configure environment
cp .env.example .env
# Edit .env file with your configuration

# Setup database
createdb product_studio_db
php bin/console doctrine:migrations:migrate --no-interaction

# Start server
symfony serve
```

### Required Extensions
- php-pgsql
- php-curl
- php-json
- php-mbstring
- php-xml

## 🔧 Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```bash
# Application
APP_ENV=prod
APP_SECRET=your-32-character-secret-key

# Database
DATABASE_URL="postgresql://user:password@host:5432/dbname"

# External APIs (Optional - will use fallback/mock data if not provided)
UNSPLASH_ACCESS_KEY=your-unsplash-api-key
GOOGLE_SHOPPING_API_KEY=your-google-shopping-api-key
OPENAI_API_KEY=your-openai-api-key

# Rate Limiting
RATE_LIMIT_REQUESTS_PER_MINUTE=60
RATE_LIMIT_REQUESTS_PER_HOUR=1000
API_RATE_LIMIT_ENABLED=true

# Security
API_AUTH_REQUIRED=false
VALID_API_KEYS=demo_key_12345,admin_key_67890

# Processing
MAX_CONCURRENT_PROCESSES=5
PROCESSING_TIMEOUT=120
```

### Service Configuration

The application uses Symfony's dependency injection. Key services are configured in `config/services.yaml`.

## 📡 API Reference

### Base URL
```
https://your-domain.com/api
```

### Authentication
Optional API key authentication via:
- Header: `Authorization: Bearer YOUR_API_KEY`
- Header: `X-API-Key: YOUR_API_KEY`
- Query: `?api_key=YOUR_API_KEY`

### Core Endpoints

#### 1. Process Product Intelligence
**Create complete product data from minimal input**

```http
POST /api/products/intelligence
Content-Type: application/json

{
  "name": "Product Name",
  "model_number": "ABC123",
  "brand": "Brand Name",
  "category": "Power Tools",
  "seo_keywords": ["keyword1", "keyword2"],
  "brief": "Optional brief description",
  "description": "Optional detailed description"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Product intelligence processed successfully",
  "data": {
    "success": true,
    "product": {
      "id": 1,
      "name": "Optimized Product Name",
      "model_number": "ABC123",
      "brand": "Brand Name",
      "category": "Power Tools",
      "seo_keywords": ["keyword1", "keyword2", "generated1", "generated2"],
      "brief": "AI-generated 100-char SEO brief...",
      "description": "AI-generated 200-char SEO description with natural keyword integration...",
      "seo_title": "SEO-optimized title under 60 chars",
      "meta_description": "SEO meta description under 160 chars",
      "gallery_images": ["https://url1.jpg", "https://url2.jpg"],
      "image_alt_texts": ["SEO alt text 1", "SEO alt text 2"],
      "specifications": {"power": "20V", "torque": "300 UWO"},
      "enrichment_status": "completed",
      "created_at": "2024-01-01T12:00:00Z",
      "updated_at": "2024-01-01T12:00:05Z"
    },
    "seo_data": {
      "title": "SEO-optimized title",
      "meta_description": "Meta description",
      "keywords": ["keyword1", "keyword2"],
      "images": [
        {"url": "https://url1.jpg", "alt": "SEO alt text"}
      ],
      "structured_data": {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "Product Name"
      }
    },
    "processing_metadata": {
      "processing_time": "2.5s",
      "ai_models_used": {"content_generation": "gpt-4"},
      "sources_consulted": ["manufacturer_website"]
    }
  }
}
```

#### 2. Get Product Details
```http
GET /api/products/{id}
```

#### 3. Get Processing Status
```http
GET /api/products/{id}/status
```

#### 4. Get SEO Data
```http
GET /api/products/{id}/seo
```

#### 5. List Products
```http
GET /api/products?page=1&limit=20&brand=DeWalt&category=Power%20Tools&search=drill
```

#### 6. Delete Product
```http
DELETE /api/products/{id}
```

### Admin Endpoints

#### System Analytics
```http
GET /api/admin/analytics
```

#### Bulk Processing
```http
POST /api/admin/bulk-process
Content-Type: application/json

{
  "product_ids": [1, 2, 3],
  "force_reprocess": false
}
```

#### Health Check
```http
GET /api/admin/health
```

#### Maintenance
```http
POST /api/admin/maintenance
Content-Type: application/json

{
  "actions": ["reset_failed", "clear_cache", "cleanup_orphaned"]
}
```

### Error Responses

All errors follow this format:
```json
{
  "status": "error",
  "message": "Human readable error message",
  "code": 400,
  "details": {
    "field_name": ["Specific validation error"]
  }
}
```

Common HTTP status codes:
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid API key)
- `404` - Not Found (resource doesn't exist)
- `429` - Too Many Requests (rate limit exceeded)
- `500` - Internal Server Error

## 💡 Examples

### Example 1: Basic Product Processing
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Milwaukee M18 Drill",
    "model_number": "2804-20"
  }'
```

### Example 2: Complete Product Data
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -H "X-API-Key: demo_key_12345" \
  -d '{
    "name": "DeWalt 20V MAX Circular Saw",
    "model_number": "DCS570B",
    "brand": "DeWalt",
    "category": "Power Tools",
    "seo_keywords": ["circular saw", "cordless saw", "20v max"],
    "brief": "Professional cordless circular saw"
  }'
```

### Example 3: Get Product with SEO Data
```bash
# First create a product, then get its SEO data
PRODUCT_ID=1
curl -X GET http://localhost:8000/api/products/${PRODUCT_ID}/seo
```

### Example 4: List and Filter Products
```bash
curl -X GET "http://localhost:8000/api/products?brand=DeWalt&category=Power%20Tools&limit=10"
```

## 🧪 Testing

### Run Test Suite
```bash
# Unit tests
php bin/phpunit tests/Unit

# Integration tests
php bin/phpunit tests/Integration

# All tests
php bin/phpunit
```

### Test Coverage
```bash
php bin/phpunit --coverage-html coverage/
```

### API Testing with Postman
Import the Postman collection from `docs/postman/Product-Studio-API.postman_collection.json`

### Load Testing
```bash
# Using Apache Bench
ab -n 100 -c 10 -H "Content-Type: application/json" -p test-data.json http://localhost:8000/api/products/intelligence
```

## 🚀 Deployment

### Production Checklist

1. **Environment Configuration**
```bash
# Set production environment
APP_ENV=prod
APP_DEBUG=false

# Use strong secret key
APP_SECRET=$(openssl rand -hex 16)

# Configure production database
DATABASE_URL="postgresql://user:password@prod-host:5432/dbname"

# Set up external API keys
OPENAI_API_KEY=your-production-openai-key
UNSPLASH_ACCESS_KEY=your-production-unsplash-key
```

2. **Security Setup**
```bash
# Enable authentication
API_AUTH_REQUIRED=true
VALID_API_KEYS=prod_key_1,prod_key_2

# Configure rate limiting
RATE_LIMIT_REQUESTS_PER_MINUTE=30
RATE_LIMIT_REQUESTS_PER_HOUR=500

# Set trusted proxies (if behind load balancer)
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

3. **Database Migration**
```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

4. **Cache Optimization**
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### Docker Production Deployment

1. **Build Production Image**
```dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    postgresql-client \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
```

2. **Production Docker Compose**
```yaml
version: '3.8'

services:
  app:
    build: .
    environment:
      - APP_ENV=prod
      - DATABASE_URL=postgresql://user:password@db:5432/dbname
    ports:
      - "8000:8000"
    depends_on:
      - db
      - redis

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: product_studio
      POSTGRES_USER: app_user
      POSTGRES_PASSWORD: secure_password
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app

volumes:
  postgres_data:
  redis_data:
```

### Cloud Deployment

#### AWS ECS/Fargate
1. Build and push to ECR
2. Create ECS task definition
3. Set up Application Load Balancer
4. Configure RDS PostgreSQL instance
5. Set up CloudWatch monitoring

#### Google Cloud Run
1. Build container image
2. Deploy to Cloud Run
3. Configure Cloud SQL PostgreSQL
4. Set up Cloud Monitoring

#### Azure Container Instances
1. Build and push to ACR
2. Deploy to Container Instances
3. Configure Azure Database for PostgreSQL
4. Set up Application Insights

### Monitoring & Logging

#### Health Checks
```bash
# Application health
curl http://your-domain/api/health

# Detailed health with dependencies
curl http://your-domain/api/admin/health
```

#### Logging Setup
Configure structured logging in `config/packages/monolog.yaml`:

```yaml
monolog:
    handlers:
        main:
            type: rotating_file
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: info
            max_files: 10
        
        error:
            type: fingers_crossed
            action_level: error
            handler: main
```

#### Metrics Collection
Set up application metrics collection:
- Request count and latency
- Processing success/failure rates
- Database query performance
- External API response times

## 🔧 Advanced Configuration

### Custom AI Models
Extend the `SeoContentGeneratorService` to integrate custom models:

```php
// In config/services.yaml
App\Service\AI\SeoContentGeneratorService:
    arguments:
        $customModelEndpoint: '%env(CUSTOM_AI_MODEL_URL)%'
        $modelConfig: 
            temperature: 0.7
            max_tokens: 500
```

### Custom Scrapers
Add new manufacturer scrapers in `src/Service/Scraper/`:

```php
namespace App\Service\Scraper;

class CustomManufacturerScraper implements ScraperInterface
{
    public function scrape(string $modelNumber): ?array
    {
        // Custom scraping logic
    }
}
```

### Webhook Integration
Set up webhooks for processing completion:

```php
// In your controller
$this->webhookService->notifyCompletion($product);
```

## 📊 Performance

### Benchmarks
- **Single product processing**: ~2.5 seconds average
- **Throughput**: 20-30 products/minute (depending on external APIs)
- **Concurrent requests**: Handles 50+ concurrent users
- **Database performance**: Optimized indexes for sub-100ms queries

### Optimization Tips
1. **Use Redis for caching** frequently accessed data
2. **Enable opcache** in production
3. **Configure connection pooling** for database
4. **Implement CDN** for image assets
5. **Use async processing** for bulk operations

## 🤝 Contributing

### Development Setup
```bash
git clone https://github.com/your-org/product-studio.git
cd product-studio
composer install
cp .env.example .env.local
symfony serve
```

### Code Standards
- Follow PSR-12 coding standards
- Use PHPStan for static analysis
- Write tests for new features
- Update documentation

### Submit Changes
1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests and ensure they pass
4. Commit changes (`git commit -m 'Add amazing feature'`)
5. Push to branch (`git push origin feature/amazing-feature`)
6. Create Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Documentation**: [https://docs.productstudio.com](https://docs.productstudio.com)
- **Issues**: [GitHub Issues](https://github.com/your-org/product-studio/issues)
- **Support Email**: support@productstudio.com
- **Community**: [Discord](https://discord.gg/productstudio)

## 🔮 Roadmap

- [ ] **GraphQL API** - Alternative query interface
- [ ] **Real-time WebSocket** updates for processing status
- [ ] **Advanced Analytics** - ML-powered insights
- [ ] **Multi-language Support** - International product data
- [ ] **Video Processing** - Auto-generate product videos
- [ ] **Integration Hub** - Pre-built connectors for major platforms
- [ ] **AI Model Training** - Custom models for specific industries

---

**Built with ❤️ by the Product Studio Team**

*Transform your product catalogs with AI-powered intelligence.*




