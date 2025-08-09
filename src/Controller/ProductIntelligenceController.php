<?php

namespace App\Controller;

use App\Service\ProductIntelligenceService;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Product Intelligence API Controller
 * 
 * RESTful API for the Product Studio Intelligence service.
 * Provides endpoints for product data enrichment, processing status,
 * and retrieving optimized product information.
 * 
 * Endpoints:
 * - POST /api/products/intelligence - Process product intelligence
 * - GET /api/products/{id} - Get product details
 * - GET /api/products/{id}/status - Get processing status
 * - GET /api/products/{id}/seo - Get SEO-optimized data
 * - GET /api/products - List products with filtering
 * - DELETE /api/products/{id} - Delete product
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
#[Route('/api', name: 'api_')]
class ProductIntelligenceController extends AbstractController
{
    public function __construct(
        private ProductIntelligenceService $productIntelligenceService,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Process Product Intelligence Request
     * 
     * Main endpoint that accepts product data and returns complete SEO-optimized information.
     * 
     * @Route("/products/intelligence", name="process_intelligence", methods=["POST"])
     */
    public function processIntelligence(Request $request): JsonResponse
    {
        $this->logger->info('Product intelligence request received', [
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent')
        ]);

        try {
            // Parse and validate input data
            $inputData = $this->parseRequestData($request);
            if (!$inputData) {
                return $this->createErrorResponse('Invalid JSON data', 400);
            }

            // Validate input data
            $validationErrors = $this->productIntelligenceService->validateInputData($inputData);
            if (!empty($validationErrors)) {
                return $this->createErrorResponse('Validation failed', 400, $validationErrors);
            }

            // Process the product intelligence
            $result = $this->productIntelligenceService->processProductIntelligence($inputData);

            if ($result['success']) {
                $this->logger->info('Product intelligence processing completed successfully', [
                    'product_id' => $result['product']['id'] ?? null
                ]);

                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Product intelligence processed successfully',
                    'data' => $result,
                    'processing_time' => $result['processing_metadata']['processing_time'] ?? null
                ], Response::HTTP_CREATED);
            } else {
                return $this->createErrorResponse(
                    $result['error'] ?? 'Processing failed',
                    500,
                    ['input_data' => $result['input_data'] ?? null]
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Product intelligence processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->createErrorResponse('Internal server error', 500);
        }
    }

    /**
     * Get Product Details
     * 
     * @Route("/products/{id}", name="get_product", methods=["GET"], requirements={"id"="\d+"})
     */
    public function getProduct(int $id): JsonResponse
    {
        try {
            $product = $this->entityManager->getRepository(Product::class)->find($id);

            if (!$product) {
                return $this->createErrorResponse('Product not found', 404);
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'product' => $product->toArray(),
                    'seo_data' => $product->getSeoData(),
                    'structured_data' => $product->getStructuredData()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get product', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->createErrorResponse('Failed to retrieve product', 500);
        }
    }

    /**
     * Get Product Processing Status
     * 
     * @Route("/products/{id}/status", name="get_status", methods=["GET"], requirements={"id"="\d+"})
     */
    public function getProcessingStatus(int $id): JsonResponse
    {
        try {
            $status = $this->productIntelligenceService->getProcessingStatus($id);

            if (!$status['success']) {
                return $this->createErrorResponse($status['error'], 404);
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => $status
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get processing status', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->createErrorResponse('Failed to get processing status', 500);
        }
    }

    /**
     * Get SEO-Optimized Product Data
     * 
     * @Route("/products/{id}/seo", name="get_seo_data", methods=["GET"], requirements={"id"="\d+"})
     */
    public function getSeoData(int $id): JsonResponse
    {
        try {
            $product = $this->entityManager->getRepository(Product::class)->find($id);

            if (!$product) {
                return $this->createErrorResponse('Product not found', 404);
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'seo_data' => $product->getSeoData(),
                    'structured_data' => $product->getStructuredData(),
                    'enrichment_status' => $product->getEnrichmentStatus()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get SEO data', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->createErrorResponse('Failed to retrieve SEO data', 500);
        }
    }

    /**
     * List Products with Filtering and Pagination
     * 
     * @Route("/products", name="list_products", methods=["GET"])
     */
    public function listProducts(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int)$request->query->get('page', 1));
            $limit = min(100, max(1, (int)$request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            // Build query with filters
            $queryBuilder = $this->entityManager->getRepository(Product::class)
                ->createQueryBuilder('p');

            // Filter by brand
            if ($brand = $request->query->get('brand')) {
                $queryBuilder->andWhere('LOWER(p.brand) LIKE :brand')
                    ->setParameter('brand', '%' . strtolower($brand) . '%');
            }

            // Filter by category
            if ($category = $request->query->get('category')) {
                $queryBuilder->andWhere('LOWER(p.category) LIKE :category')
                    ->setParameter('category', '%' . strtolower($category) . '%');
            }

            // Filter by enrichment status
            if ($status = $request->query->get('status')) {
                $queryBuilder->andWhere('p.enrichmentStatus = :status')
                    ->setParameter('status', $status);
            }

            // Search by name or model number
            if ($search = $request->query->get('search')) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        'LOWER(p.name) LIKE :search',
                        'LOWER(p.modelNumber) LIKE :search'
                    )
                )->setParameter('search', '%' . strtolower($search) . '%');
            }

            // Get total count for pagination
            $totalQuery = clone $queryBuilder;
            $total = $totalQuery->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

            // Apply pagination and ordering
            $products = $queryBuilder
                ->orderBy('p.updatedAt', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            // Convert to array
            $productData = array_map(fn($product) => $product->toArray(), $products);

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'products' => $productData,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => (int)ceil($total / $limit)
                    ],
                    'filters' => [
                        'brand' => $request->query->get('brand'),
                        'category' => $request->query->get('category'),
                        'status' => $request->query->get('status'),
                        'search' => $request->query->get('search')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to list products', [
                'error' => $e->getMessage(),
                'query_params' => $request->query->all()
            ]);

            return $this->createErrorResponse('Failed to retrieve products', 500);
        }
    }

    /**
     * Delete Product
     * 
     * @Route("/products/{id}", name="delete_product", methods=["DELETE"], requirements={"id"="\d+"})
     */
    public function deleteProduct(int $id): JsonResponse
    {
        try {
            $product = $this->entityManager->getRepository(Product::class)->find($id);

            if (!$product) {
                return $this->createErrorResponse('Product not found', 404);
            }

            $this->entityManager->remove($product);
            $this->entityManager->flush();

            $this->logger->info('Product deleted', ['product_id' => $id]);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete product', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->createErrorResponse('Failed to delete product', 500);
        }
    }

    /**
     * Get API Health Status
     * 
     * @Route("/health", name="health_check", methods=["GET"])
     */
    public function healthCheck(): JsonResponse
    {
        try {
            // Test database connection
            $this->entityManager->getConnection()->connect();
            $dbStatus = 'healthy';
        } catch (\Exception $e) {
            $dbStatus = 'unhealthy';
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'api' => 'healthy',
                'database' => $dbStatus,
                'timestamp' => (new \DateTime())->format('c'),
                'version' => '1.0.0'
            ]
        ]);
    }

    /**
     * Homepage - API Information
     * 
     * @Route("/", name="homepage", methods=["GET"])
     */
    public function homepage(): JsonResponse
    {
        return new JsonResponse([
            'message' => '🚀 Product Studio Intelligence API',
            'version' => '1.0.0',
            'description' => 'AI-powered product data enrichment and SEO optimization',
            'status' => 'online',
            'endpoints' => [
                'health_check' => '/api/health',
                'api_schema' => '/api/schema',
                'process_product' => 'POST /api/products/intelligence',
                'list_products' => 'GET /api/products',
                'admin_analytics' => '/api/admin/analytics'
            ],
            'documentation' => 'See /api/schema for complete API documentation',
            'quick_test' => 'curl http://localhost:8000/api/health'
        ]);
    }

    /**
     * Get API Documentation/Schema
     * 
     * @Route("/schema", name="api_schema", methods=["GET"])
     */
    public function getApiSchema(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'api_name' => 'Product Studio Intelligence API',
                'version' => '1.0.0',
                'description' => 'AI-powered product data enrichment and SEO optimization',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/products/intelligence',
                        'description' => 'Process product intelligence request',
                        'parameters' => [
                            'name' => 'string (optional)',
                            'model_number' => 'string (optional)',
                            'brand' => 'string (optional)',
                            'category' => 'string (optional)',
                            'seo_keywords' => 'array (optional)',
                            'brief' => 'string (optional)',
                            'description' => 'string (optional)'
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/products/{id}',
                        'description' => 'Get complete product details'
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/products/{id}/status',
                        'description' => 'Get product processing status'
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/products/{id}/seo',
                        'description' => 'Get SEO-optimized product data'
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/products',
                        'description' => 'List products with filtering',
                        'parameters' => [
                            'page' => 'integer (default: 1)',
                            'limit' => 'integer (default: 20, max: 100)',
                            'brand' => 'string (filter)',
                            'category' => 'string (filter)',
                            'status' => 'string (filter)',
                            'search' => 'string (search)'
                        ]
                    ],
                    [
                        'method' => 'DELETE',
                        'path' => '/api/products/{id}',
                        'description' => 'Delete product'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Parse and validate request data
     */
    private function parseRequestData(Request $request): ?array
    {
        try {
            $content = $request->getContent();
            if (empty($content)) {
                return null;
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse request data', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create standardized error response
     */
    private function createErrorResponse(string $message, int $code, array $details = []): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'code' => $code
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return new JsonResponse($response, $code);
    }
}
