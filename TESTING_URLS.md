# 🚀 Product Studio API - Testing URLs

**Server Status:** ✅ Running on `http://localhost:8000`

## ✅ Working Endpoints (No Database Required)

### 1. Health Check
```bash
curl http://localhost:8000/api/health
```
**Expected Response:**
```json
{
  "status": "success", 
  "data": {
    "api": "healthy",
    "database": "unhealthy",
    "timestamp": "2025-08-09T12:27:08+00:00",
    "version": "1.0.0"
  }
}
```

### 2. API Schema Documentation
```bash
curl http://localhost:8000/api/schema
```
**Expected Response:**
```json
{
  "status": "success",
  "data": {
    "api_name": "Product Studio Intelligence API",
    "version": "1.0.0", 
    "description": "AI-powered product data enrichment and SEO optimization",
    "endpoints": [...]
  }
}
```

### 3. Admin Health Check (Detailed)
```bash
curl http://localhost:8000/api/admin/health
```

## ⚠️ Database Required Endpoints

**Note:** These endpoints require database setup to work properly.

### 4. Process Product Intelligence (Main Feature)
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -d '{
    "name": "DeWalt 20V MAX Drill",
    "model_number": "DCD771C2", 
    "brand": "DeWalt",
    "category": "Power Tools"
  }'
```

### 5. List Products
```bash
curl http://localhost:8000/api/products
```

### 6. Get Product Details
```bash
curl http://localhost:8000/api/products/{id}
```

### 7. Get Processing Status
```bash
curl http://localhost:8000/api/products/{id}/status
```

### 8. Get SEO Data
```bash
curl http://localhost:8000/api/products/{id}/seo
```

### 9. Admin Analytics
```bash
curl http://localhost:8000/api/admin/analytics
```

## 🧪 Test with Different Data

### Minimal Product Data
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -d '{"name": "Generic Drill"}'
```

### Complete Product Data
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Milwaukee M18 FUEL Hammer Drill",
    "model_number": "2804-20",
    "brand": "Milwaukee", 
    "category": "Power Tools",
    "seo_keywords": ["hammer drill", "brushless", "cordless"],
    "brief": "Professional grade hammer drill with brushless motor"
  }'
```

### Validation Error Test (Should Fail)
```bash
curl -X POST http://localhost:8000/api/products/intelligence \
  -H "Content-Type: application/json" \
  -d '{
    "name": "",
    "seo_keywords": "not an array"
  }'
```

## 🔍 Browser Testing

You can also test these endpoints directly in your browser:

- **Health Check:** [http://localhost:8000/api/health](http://localhost:8000/api/health)
- **API Schema:** [http://localhost:8000/api/schema](http://localhost:8000/api/schema)
- **Admin Health:** [http://localhost:8000/api/admin/health](http://localhost:8000/api/admin/health)

## 🛠️ Setup Database (Optional)

To test the full functionality:

1. **Start PostgreSQL:**
   ```bash
   docker-compose up -d database
   ```

2. **Run Migrations:**
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

3. **Test Full API:**
   ```bash
   ./test_api.sh
   ```

## 📊 Expected API Response Format

### Success Response
```json
{
  "status": "success",
  "message": "Product intelligence processed successfully", 
  "data": {
    "product": {
      "id": 1,
      "name": "SEO-optimized product name",
      "brief": "100-character SEO brief...",
      "description": "200-character SEO description...",
      "seo_keywords": ["keyword1", "keyword2"],
      "gallery_images": ["url1.jpg", "url2.jpg"],
      "enrichment_status": "completed"
    },
    "seo_data": {
      "title": "SEO title",
      "meta_description": "Meta description",
      "structured_data": {...}
    }
  }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Validation failed",
  "code": 400,
  "details": {
    "field_name": ["Specific validation error"]
  }
}
```

## 🎯 Key Features Demonstrated

✅ **RESTful API Design** - Clean, predictable endpoints
✅ **JSON Responses** - Structured, consistent format  
✅ **Error Handling** - Detailed validation and error responses
✅ **Health Monitoring** - System status and diagnostics
✅ **API Documentation** - Self-documenting schema endpoint
✅ **Input Validation** - Security and data quality protection

## 🚀 Production Deployment

When ready for production:

1. **Set Environment Variables:**
   ```bash
   APP_ENV=prod
   DATABASE_URL=postgresql://...
   ```

2. **Deploy using provided guides:**
   - Docker: `docker-compose up -d`
   - AWS: See `docs/DEPLOYMENT.md`
   - Google Cloud: See deployment guide

---

**🎉 Your Product Studio Intelligence API is working and ready for testing!**





