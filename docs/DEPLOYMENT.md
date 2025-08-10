# Deployment Guide

This guide covers deploying the Product Studio Intelligence API to production environments.

## 🎯 Pre-Deployment Checklist

### Security Configuration
- [ ] Set `APP_ENV=prod` and `APP_DEBUG=false`
- [ ] Generate strong `APP_SECRET` (32+ characters)
- [ ] Configure production database credentials
- [ ] Set up API authentication if required
- [ ] Configure rate limiting for production traffic
- [ ] Set trusted proxies if behind load balancer
- [ ] Review and configure CORS settings

### Database Setup
- [ ] Create production database
- [ ] Run migrations: `php bin/console doctrine:migrations:migrate --no-interaction`
- [ ] Set up database backups
- [ ] Configure connection pooling
- [ ] Set up monitoring for database performance

### External Services
- [ ] Configure production API keys (OpenAI, Unsplash, etc.)
- [ ] Set up monitoring for external API quotas
- [ ] Configure fallback mechanisms for API failures

### Performance Optimization
- [ ] Enable OPcache
- [ ] Configure application cache
- [ ] Set up Redis for session storage (if needed)
- [ ] Configure CDN for static assets

## 🐳 Docker Deployment

### Production Dockerfile
```dockerfile
FROM php:8.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    zip \
    unzip \
    curl \
    git

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    opcache

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Clear and warm up cache
RUN php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

EXPOSE 9000

CMD ["php-fpm"]
```

### Production Docker Compose
```yaml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - ./var:/var/www/html/var
    environment:
      - APP_ENV=prod
      - APP_DEBUG=false
      - DATABASE_URL=postgresql://app:${DB_PASSWORD}@db:5432/app
      - REDIS_URL=redis://redis:6379
    depends_on:
      - db
      - redis

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./public:/var/www/html/public:ro
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - app

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: app
      POSTGRES_USER: app
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./backups:/backups
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    restart: unless-stopped

volumes:
  postgres_data:
  redis_data:
```

### Nginx Configuration
```nginx
upstream php {
    server app:9000;
}

server {
    listen 80;
    server_name _;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/html/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # API Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req zone=api burst=20 nodelay;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Health check endpoint
    location /health {
        access_log off;
        proxy_pass http://php/api/health;
    }
}
```

## ☁️ Cloud Deployment

### AWS ECS with Fargate

1. **Build and push image to ECR**
```bash
# Create ECR repository
aws ecr create-repository --repository-name product-studio

# Get login token
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 123456789012.dkr.ecr.us-east-1.amazonaws.com

# Build and tag image
docker build -t product-studio .
docker tag product-studio:latest 123456789012.dkr.ecr.us-east-1.amazonaws.com/product-studio:latest

# Push image
docker push 123456789012.dkr.ecr.us-east-1.amazonaws.com/product-studio:latest
```

2. **Create ECS Task Definition**
```json
{
  "family": "product-studio",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "1024",
  "memory": "2048",
  "executionRoleArn": "arn:aws:iam::123456789012:role/ecsTaskExecutionRole",
  "containerDefinitions": [
    {
      "name": "app",
      "image": "123456789012.dkr.ecr.us-east-1.amazonaws.com/product-studio:latest",
      "portMappings": [
        {
          "containerPort": 9000,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {
          "name": "APP_ENV",
          "value": "prod"
        },
        {
          "name": "DATABASE_URL",
          "value": "postgresql://user:pass@rds-endpoint:5432/dbname"
        }
      ],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/product-studio",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "ecs"
        }
      }
    }
  ]
}
```

3. **Create ECS Service**
```bash
aws ecs create-service \
  --cluster default \
  --service-name product-studio \
  --task-definition product-studio:1 \
  --desired-count 2 \
  --launch-type FARGATE \
  --network-configuration "awsvpcConfiguration={subnets=[subnet-12345,subnet-67890],securityGroups=[sg-abcdef],assignPublicIp=ENABLED}"
```

### Google Cloud Run

1. **Build and deploy**
```bash
# Build image
gcloud builds submit --tag gcr.io/PROJECT-ID/product-studio

# Deploy to Cloud Run
gcloud run deploy product-studio \
  --image gcr.io/PROJECT-ID/product-studio \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --set-env-vars APP_ENV=prod,DATABASE_URL="postgresql://user:pass@CLOUD-SQL-IP:5432/dbname"
```

2. **Cloud SQL Setup**
```bash
# Create Cloud SQL instance
gcloud sql instances create product-studio-db \
  --database-version POSTGRES_15 \
  --tier db-f1-micro \
  --region us-central1

# Create database
gcloud sql databases create app --instance product-studio-db

# Create user
gcloud sql users create app-user \
  --instance product-studio-db \
  --password=secure-password
```

### Azure Container Instances

1. **Create resource group and container registry**
```bash
az group create --name ProductStudioRG --location eastus

az acr create --resource-group ProductStudioRG \
  --name productstudioregistry \
  --sku Basic
```

2. **Build and push image**
```bash
az acr build --registry productstudioregistry \
  --image product-studio:latest .
```

3. **Deploy container**
```bash
az container create \
  --resource-group ProductStudioRG \
  --name product-studio \
  --image productstudioregistry.azurecr.io/product-studio:latest \
  --cpu 1 \
  --memory 2 \
  --ports 80 \
  --environment-variables APP_ENV=prod \
  --secure-environment-variables DATABASE_URL="postgresql://user:pass@server:5432/db"
```

## 📊 Monitoring and Logging

### Application Performance Monitoring

1. **Install monitoring tools**
```bash
composer require symfony/monolog-bundle
composer require blackfire/blackfire-symfony
```

2. **Configure structured logging**
```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: nested
            excluded_http_codes: [404, 405]
            buffer_size: 50

        nested:
            type: stream
            path: php://stderr
            level: debug
            formatter: monolog.formatter.json

        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine", "!console"]
```

### Health Checks and Metrics

1. **Application metrics endpoint**
```php
// src/Controller/MetricsController.php
#[Route('/metrics', name: 'metrics')]
public function metrics(): Response
{
    $metrics = [
        'http_requests_total' => $this->getRequestCount(),
        'processing_duration_seconds' => $this->getProcessingDuration(),
        'database_connections_active' => $this->getDatabaseConnections(),
        'external_api_calls_total' => $this->getExternalApiCalls(),
    ];

    return new Response(
        $this->formatPrometheusMetrics($metrics),
        200,
        ['Content-Type' => 'text/plain']
    );
}
```

2. **Database monitoring**
```sql
-- Monitor slow queries
SELECT query, mean_time, calls, total_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;

-- Monitor connections
SELECT count(*) as active_connections
FROM pg_stat_activity
WHERE state = 'active';
```

### Log Aggregation

1. **ELK Stack (Elasticsearch, Logstash, Kibana)**
```yaml
# docker-compose.monitoring.yml
version: '3.8'
services:
  elasticsearch:
    image: elasticsearch:7.14.0
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    ports:
      - "9200:9200"

  logstash:
    image: logstash:7.14.0
    volumes:
      - ./logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    depends_on:
      - elasticsearch

  kibana:
    image: kibana:7.14.0
    ports:
      - "5601:5601"
    environment:
      ELASTICSEARCH_URL: http://elasticsearch:9200
    depends_on:
      - elasticsearch
```

2. **Grafana Dashboard**
```yaml
# grafana/dashboard.json
{
  "dashboard": {
    "title": "Product Studio API Metrics",
    "panels": [
      {
        "title": "Request Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(http_requests_total[5m])",
            "legendFormat": "{{method}} {{status}}"
          }
        ]
      },
      {
        "title": "Processing Time",
        "type": "graph",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, processing_duration_seconds)",
            "legendFormat": "95th percentile"
          }
        ]
      }
    ]
  }
}
```

## 🔒 Security Hardening

### SSL/TLS Configuration
```bash
# Generate SSL certificate with Let's Encrypt
certbot certonly --webroot -w /var/www/html/public -d your-domain.com

# Auto-renewal cron job
0 12 * * * /usr/bin/certbot renew --quiet
```

### Firewall Configuration
```bash
# UFW (Ubuntu)
ufw allow 22/tcp  # SSH
ufw allow 80/tcp  # HTTP
ufw allow 443/tcp # HTTPS
ufw deny 5432/tcp # Block direct database access
ufw enable

# AWS Security Group rules
aws ec2 authorize-security-group-ingress \
  --group-id sg-12345678 \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0
```

### Environment Security
```bash
# Secure file permissions
chmod 600 .env
chown root:root .env

# Disable unnecessary PHP functions
echo "disable_functions=exec,passthru,shell_exec,system,proc_open,popen" >> /etc/php/8.1/fpm/php.ini

# Hide PHP version
echo "expose_php = Off" >> /etc/php/8.1/fpm/php.ini
```

## 📈 Performance Optimization

### Database Optimization
```sql
-- Create indexes for better performance
CREATE INDEX CONCURRENTLY idx_products_brand_category ON products(brand, category);
CREATE INDEX CONCURRENTLY idx_products_created_at_desc ON products(created_at DESC);
CREATE INDEX CONCURRENTLY idx_products_enrichment_status ON products(enrichment_status);

-- Optimize PostgreSQL configuration
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';
ALTER SYSTEM SET checkpoint_completion_target = 0.9;
SELECT pg_reload_conf();
```

### PHP Optimization
```ini
; php.ini optimizations
memory_limit = 512M
max_execution_time = 120
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.preload = /var/www/html/config/preload.php
```

### Redis Caching
```php
// config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: redis://redis:6379

# Cache frequently accessed data
$cache = $this->cache->getItem('product_' . $id);
if (!$cache->isHit()) {
    $product = $this->repository->find($id);
    $cache->set($product);
    $cache->expiresAfter(3600); // 1 hour
    $this->cache->save($cache);
}
```

## 🔄 Backup and Recovery

### Database Backups
```bash
#!/bin/bash
# backup.sh
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="product_studio"

# Create backup
pg_dump $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/backup_$DATE.sql

# Remove backups older than 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

# Upload to cloud storage (AWS S3)
aws s3 cp $BACKUP_DIR/backup_$DATE.sql.gz s3://your-backup-bucket/
```

### Application Backups
```bash
#!/bin/bash
# app-backup.sh
APP_DIR="/var/www/html"
BACKUP_DIR="/backups/app"
DATE=$(date +%Y%m%d_%H%M%S)

# Create application backup
tar -czf $BACKUP_DIR/app_$DATE.tar.gz \
  --exclude='var/cache' \
  --exclude='var/log' \
  --exclude='vendor' \
  $APP_DIR

# Upload to cloud storage
aws s3 cp $BACKUP_DIR/app_$DATE.tar.gz s3://your-backup-bucket/app/
```

### Disaster Recovery Plan
1. **Recovery Time Objective (RTO)**: 30 minutes
2. **Recovery Point Objective (RPO)**: 15 minutes
3. **Backup verification**: Weekly restore tests
4. **Failover procedure**: Documented step-by-step process

## 🚀 CI/CD Pipeline

### GitHub Actions Workflow
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      - name: Run tests
        run: php bin/phpunit

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to ECS
        run: |
          aws ecs update-service \
            --cluster production \
            --service product-studio \
            --force-new-deployment
```

### Blue-Green Deployment
```bash
#!/bin/bash
# blue-green-deploy.sh

# Build new version
docker build -t product-studio:$BUILD_NUMBER .

# Deploy to green environment
docker service update --image product-studio:$BUILD_NUMBER product-studio-green

# Health check green environment
./health-check.sh green

# Switch traffic to green
./switch-traffic.sh green

# Remove blue environment
docker service rm product-studio-blue
```

This deployment guide provides comprehensive instructions for deploying the Product Studio Intelligence API to production environments with proper security, monitoring, and maintenance procedures.





