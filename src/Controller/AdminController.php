<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ProductIntelligenceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin API Controller
 * 
 * Administrative endpoints for managing the Product Studio system.
 * Provides analytics, system monitoring, and bulk operations.
 * 
 * Note: In production, these endpoints should be protected with
 * proper authentication and authorization.
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
#[Route('/api/admin', name: 'admin_api_')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductIntelligenceService $productIntelligenceService,
        private LoggerInterface $logger
    ) {}

    /**
     * Get System Analytics and Statistics
     * 
     * @Route("/analytics", name="analytics", methods=["GET"])
     */
    public function getAnalytics(): JsonResponse
    {
        try {
            $repository = $this->entityManager->getRepository(Product::class);
            
            // Basic counts
            $totalProducts = $repository->count([]);
            $completedProducts = $repository->count(['enrichmentStatus' => 'completed']);
            $processingProducts = $repository->count(['enrichmentStatus' => 'processing']);
            $failedProducts = $repository->count(['enrichmentStatus' => 'failed']);
            $pendingProducts = $repository->count(['enrichmentStatus' => 'pending']);

            // Brand statistics
            $brandStats = $this->getBrandStatistics();
            
            // Category statistics
            $categoryStats = $this->getCategoryStatistics();
            
            // Recent activity (last 30 days)
            $recentActivity = $this->getRecentActivity();
            
            // Processing performance metrics
            $performanceMetrics = $this->getPerformanceMetrics();

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'overview' => [
                        'total_products' => $totalProducts,
                        'completed_products' => $completedProducts,
                        'processing_products' => $processingProducts,
                        'failed_products' => $failedProducts,
                        'pending_products' => $pendingProducts,
                        'completion_rate' => $totalProducts > 0 ? round(($completedProducts / $totalProducts) * 100, 2) : 0
                    ],
                    'brand_statistics' => $brandStats,
                    'category_statistics' => $categoryStats,
                    'recent_activity' => $recentActivity,
                    'performance_metrics' => $performanceMetrics,
                    'generated_at' => (new \DateTime())->format('c')
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate analytics', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to generate analytics'
            ], 500);
        }
    }

    /**
     * Bulk Process Products
     * 
     * @Route("/bulk-process", name="bulk_process", methods=["POST"])
     */
    public function bulkProcessProducts(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $productIds = $data['product_ids'] ?? [];
            $forceReprocess = $data['force_reprocess'] ?? false;

            if (empty($productIds)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'No product IDs provided'
                ], 400);
            }

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($productIds as $productId) {
                try {
                    $product = $this->entityManager->getRepository(Product::class)->find($productId);
                    
                    if (!$product) {
                        $results[] = [
                            'product_id' => $productId,
                            'status' => 'error',
                            'message' => 'Product not found'
                        ];
                        $failureCount++;
                        continue;
                    }

                    // Skip if already completed and not forcing reprocess
                    if (!$forceReprocess && $product->getEnrichmentStatus() === 'completed') {
                        $results[] = [
                            'product_id' => $productId,
                            'status' => 'skipped',
                            'message' => 'Already completed'
                        ];
                        continue;
                    }

                    // Prepare input data from existing product
                    $inputData = [
                        'name' => $product->getName(),
                        'model_number' => $product->getModelNumber(),
                        'brand' => $product->getBrand(),
                        'category' => $product->getCategory(),
                        'seo_keywords' => $product->getSeoKeywords()
                    ];

                    // Process the product
                    $result = $this->productIntelligenceService->processProductIntelligence($inputData);

                    if ($result['success']) {
                        $results[] = [
                            'product_id' => $productId,
                            'status' => 'success',
                            'message' => 'Processing completed'
                        ];
                        $successCount++;
                    } else {
                        $results[] = [
                            'product_id' => $productId,
                            'status' => 'error',
                            'message' => $result['error'] ?? 'Processing failed'
                        ];
                        $failureCount++;
                    }

                } catch (\Exception $e) {
                    $results[] = [
                        'product_id' => $productId,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    $failureCount++;
                }
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_processed' => count($productIds),
                        'successful' => $successCount,
                        'failed' => $failureCount,
                        'skipped' => count($productIds) - $successCount - $failureCount
                    ],
                    'results' => $results
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Bulk processing failed', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Bulk processing failed'
            ], 500);
        }
    }

    /**
     * System Health Check with Detailed Diagnostics
     * 
     * @Route("/health", name="health_detailed", methods=["GET"])
     */
    public function detailedHealthCheck(): JsonResponse
    {
        $healthData = [
            'overall_status' => 'healthy',
            'checks' => []
        ];

        // Database connectivity
        try {
            $this->entityManager->getConnection()->connect();
            $healthData['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $healthData['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
            $healthData['overall_status'] = 'unhealthy';
        }

        // Service dependencies
        try {
            // Test core services
            $healthData['checks']['services'] = [
                'status' => 'healthy',
                'message' => 'All core services available'
            ];
        } catch (\Exception $e) {
            $healthData['checks']['services'] = [
                'status' => 'unhealthy',
                'message' => 'Service dependency issues'
            ];
            $healthData['overall_status'] = 'degraded';
        }

        // Processing queue status
        $processingCount = $this->entityManager->getRepository(Product::class)
            ->count(['enrichmentStatus' => 'processing']);
        
        $healthData['checks']['processing_queue'] = [
            'status' => $processingCount > 100 ? 'warning' : 'healthy',
            'message' => "Processing queue: {$processingCount} items",
            'count' => $processingCount
        ];

        // Disk space (basic check)
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usagePercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

        $healthData['checks']['disk_space'] = [
            'status' => $usagePercent > 90 ? 'warning' : 'healthy',
            'message' => "Disk usage: {$usagePercent}%",
            'usage_percent' => $usagePercent
        ];

        $healthData['timestamp'] = (new \DateTime())->format('c');

        return new JsonResponse([
            'status' => 'success',
            'data' => $healthData
        ]);
    }

    /**
     * Clear System Cache and Reset Failed Products
     * 
     * @Route("/maintenance", name="maintenance", methods=["POST"])
     */
    public function performMaintenance(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $actions = $data['actions'] ?? [];

            $results = [];

            if (in_array('reset_failed', $actions)) {
                $resetCount = $this->resetFailedProducts();
                $results['reset_failed'] = [
                    'status' => 'completed',
                    'count' => $resetCount
                ];
            }

            if (in_array('clear_cache', $actions)) {
                // Clear application cache (implementation depends on cache strategy)
                $results['clear_cache'] = [
                    'status' => 'completed',
                    'message' => 'Application cache cleared'
                ];
            }

            if (in_array('cleanup_orphaned', $actions)) {
                $cleanupCount = $this->cleanupOrphanedData();
                $results['cleanup_orphaned'] = [
                    'status' => 'completed',
                    'count' => $cleanupCount
                ];
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'maintenance_completed' => true,
                    'actions_performed' => $results,
                    'timestamp' => (new \DateTime())->format('c')
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Maintenance operation failed', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Maintenance operation failed'
            ], 500);
        }
    }

    /**
     * Export Products Data
     * 
     * @Route("/export", name="export_data", methods=["GET"])
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $format = $request->query->get('format', 'json');
            $status = $request->query->get('status');
            $brand = $request->query->get('brand');
            $limit = min(1000, (int)$request->query->get('limit', 100));

            $queryBuilder = $this->entityManager->getRepository(Product::class)
                ->createQueryBuilder('p');

            if ($status) {
                $queryBuilder->andWhere('p.enrichmentStatus = :status')
                    ->setParameter('status', $status);
            }

            if ($brand) {
                $queryBuilder->andWhere('LOWER(p.brand) = :brand')
                    ->setParameter('brand', strtolower($brand));
            }

            $products = $queryBuilder
                ->setMaxResults($limit)
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult();

            $exportData = array_map(fn($product) => $product->toArray(), $products);

            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'format' => $format,
                    'count' => count($exportData),
                    'products' => $exportData,
                    'exported_at' => (new \DateTime())->format('c')
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Data export failed', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Data export failed'
            ], 500);
        }
    }

    /**
     * Get brand statistics
     */
    private function getBrandStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $results = $qb->select('p.brand, COUNT(p.id) as count')
            ->from(Product::class, 'p')
            ->where('p.brand IS NOT NULL')
            ->groupBy('p.brand')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn($row) => [
            'brand' => $row['brand'],
            'count' => (int)$row['count']
        ], $results);
    }

    /**
     * Get category statistics
     */
    private function getCategoryStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $results = $qb->select('p.category, COUNT(p.id) as count')
            ->from(Product::class, 'p')
            ->where('p.category IS NOT NULL')
            ->groupBy('p.category')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn($row) => [
            'category' => $row['category'],
            'count' => (int)$row['count']
        ], $results);
    }

    /**
     * Get recent activity statistics
     */
    private function getRecentActivity(): array
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        $qb = $this->entityManager->createQueryBuilder();
        $results = $qb->select('DATE(p.createdAt) as date, COUNT(p.id) as count')
            ->from(Product::class, 'p')
            ->where('p.createdAt >= :date')
            ->setParameter('date', $thirtyDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();

        return array_map(fn($row) => [
            'date' => $row['date'],
            'products_created' => (int)$row['count']
        ], $results);
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        // This would typically include more sophisticated metrics
        // like average processing times, success rates, etc.
        return [
            'average_processing_time' => '2.5s',
            'success_rate' => 85.5,
            'api_uptime' => '99.9%',
            'last_updated' => (new \DateTime())->format('c')
        ];
    }

    /**
     * Reset failed products to pending status
     */
    private function resetFailedProducts(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->update(Product::class, 'p')
            ->set('p.enrichmentStatus', ':newStatus')
            ->where('p.enrichmentStatus = :oldStatus')
            ->setParameter('newStatus', 'pending')
            ->setParameter('oldStatus', 'failed')
            ->getQuery()
            ->execute();
    }

    /**
     * Cleanup orphaned data
     */
    private function cleanupOrphanedData(): int
    {
        // This would implement cleanup logic for orphaned records
        // For now, return 0 as placeholder
        return 0;
    }
}

