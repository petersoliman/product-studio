#!/bin/bash

# Product Studio Intelligence API Testing Script
# This script tests all the main API endpoints

BASE_URL="http://localhost:3000"
API_BASE="$BASE_URL/api"

echo "🚀 Testing Product Studio Intelligence API"
echo "=========================================="
echo "Base URL: $BASE_URL"
echo ""

# Test 1: Health Check
echo "1. Testing Health Check..."
curl -s "$API_BASE/health" | jq '.' || echo "Health check response (raw): $(curl -s $API_BASE/health)"
echo ""

# Test 2: API Schema
echo "2. Testing API Schema..."
curl -s "$API_BASE/schema" | jq '.data.api_name' || echo "Schema response (raw): $(curl -s $API_BASE/schema)"
echo ""

# Test 3: Product Intelligence Processing (Basic)
echo "3. Testing Product Intelligence Processing (Basic)..."
RESPONSE=$(curl -s -X POST "$API_BASE/products/intelligence" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Power Drill",
    "model_number": "TPD123",
    "brand": "TestBrand"
  }')

echo "Response: $RESPONSE" | jq '.' 2>/dev/null || echo "Raw response: $RESPONSE"

# Extract product ID if successful
PRODUCT_ID=$(echo "$RESPONSE" | jq -r '.data.product.id // empty' 2>/dev/null)
echo "Created Product ID: $PRODUCT_ID"
echo ""

# Test 4: Get Product Details (if we have an ID)
if [ ! -z "$PRODUCT_ID" ] && [ "$PRODUCT_ID" != "null" ]; then
  echo "4. Testing Get Product Details (ID: $PRODUCT_ID)..."
  curl -s "$API_BASE/products/$PRODUCT_ID" | jq '.data.product.name' || echo "Get product failed"
  echo ""

  # Test 5: Get Processing Status
  echo "5. Testing Processing Status..."
  curl -s "$API_BASE/products/$PRODUCT_ID/status" | jq '.data.status' || echo "Status check failed"
  echo ""

  # Test 6: Get SEO Data
  echo "6. Testing SEO Data..."
  curl -s "$API_BASE/products/$PRODUCT_ID/seo" | jq '.data.seo_data.title' || echo "SEO data failed"
  echo ""
else
  echo "4. Skipping product-specific tests (no product ID)"
  echo ""
fi

# Test 7: List Products
echo "7. Testing List Products..."
curl -s "$API_BASE/products?limit=5" | jq '.data.pagination.total' || echo "List products failed"
echo ""

# Test 8: Product Intelligence with More Data
echo "8. Testing Product Intelligence (Complete Data)..."
curl -s -X POST "$API_BASE/products/intelligence" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "DeWalt 20V MAX Circular Saw",
    "model_number": "DCS570B",
    "brand": "DeWalt",
    "category": "Power Tools",
    "seo_keywords": ["circular saw", "cordless saw", "20v max"],
    "brief": "Professional cordless circular saw for construction"
  }' | jq '.status' || echo "Complete processing failed"
echo ""

# Test 9: Admin Analytics
echo "9. Testing Admin Analytics..."
curl -s "$API_BASE/admin/analytics" | jq '.data.overview.total_products' || echo "Analytics failed"
echo ""

# Test 10: Validation Error Test
echo "10. Testing Validation (Should Fail)..."
curl -s -X POST "$API_BASE/products/intelligence" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "",
    "seo_keywords": "not an array"
  }' | jq '.status' || echo "Validation test completed"
echo ""

echo "✅ API Testing Complete!"
echo ""
echo "Available Endpoints:"
echo "==================="
echo "Health Check:        GET  $API_BASE/health"
echo "API Schema:          GET  $API_BASE/schema"
echo "Process Product:     POST $API_BASE/products/intelligence"
echo "Get Product:         GET  $API_BASE/products/{id}"
echo "Product Status:      GET  $API_BASE/products/{id}/status"
echo "Product SEO:         GET  $API_BASE/products/{id}/seo"
echo "List Products:       GET  $API_BASE/products"
echo "Delete Product:      DELETE $API_BASE/products/{id}"
echo "Admin Analytics:     GET  $API_BASE/admin/analytics"
echo "Admin Health:        GET  $API_BASE/admin/health"
echo ""
echo "📖 For detailed API documentation, see README.md"



