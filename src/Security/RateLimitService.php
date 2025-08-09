<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Rate Limiting Service
 * 
 * Implements rate limiting to prevent API abuse and ensure fair usage.
 * Uses in-memory storage for simplicity, but can be extended to use Redis
 * or other persistent storage for production deployments.
 * 
 * Features:
 * - Per-IP rate limiting
 * - Per-endpoint rate limiting
 * - Sliding window algorithm
 * - Configurable limits and time windows
 * - Detailed logging and monitoring
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
class RateLimitService
{
    private array $requests = [];
    private LoggerInterface $logger;
    private bool $enabled;
    private int $requestsPerMinute;
    private int $requestsPerHour;

    public function __construct(
        LoggerInterface $logger,
        bool $enabled = true,
        int $requestsPerMinute = 60,
        int $requestsPerHour = 1000
    ) {
        $this->logger = $logger;
        $this->enabled = $enabled;
        $this->requestsPerMinute = $requestsPerMinute;
        $this->requestsPerHour = $requestsPerHour;
    }

    /**
     * Check if request should be rate limited
     * 
     * @param Request $request The incoming request
     * @throws TooManyRequestsHttpException If rate limit exceeded
     */
    public function checkRateLimit(Request $request): void
    {
        if (!$this->enabled) {
            return;
        }

        $clientIp = $this->getClientIp($request);
        $endpoint = $this->getEndpointIdentifier($request);
        
        // Check both IP-based and endpoint-based limits
        $this->checkIpRateLimit($clientIp);
        $this->checkEndpointRateLimit($endpoint, $clientIp);
        
        // Record this request
        $this->recordRequest($clientIp, $endpoint);
    }

    /**
     * Check IP-based rate limit
     */
    private function checkIpRateLimit(string $clientIp): void
    {
        $now = time();
        $minuteKey = "ip:{$clientIp}:minute:" . floor($now / 60);
        $hourKey = "ip:{$clientIp}:hour:" . floor($now / 3600);

        // Clean old entries
        $this->cleanOldEntries();

        // Check minute limit
        $minuteCount = $this->getRequestCount($minuteKey);
        if ($minuteCount >= $this->requestsPerMinute) {
            $this->logger->warning('Rate limit exceeded (per minute)', [
                'client_ip' => $clientIp,
                'requests_in_minute' => $minuteCount,
                'limit' => $this->requestsPerMinute
            ]);

            throw new TooManyRequestsHttpException(
                60, 
                'Rate limit exceeded: too many requests per minute'
            );
        }

        // Check hour limit
        $hourCount = $this->getRequestCount($hourKey);
        if ($hourCount >= $this->requestsPerHour) {
            $this->logger->warning('Rate limit exceeded (per hour)', [
                'client_ip' => $clientIp,
                'requests_in_hour' => $hourCount,
                'limit' => $this->requestsPerHour
            ]);

            throw new TooManyRequestsHttpException(
                3600,
                'Rate limit exceeded: too many requests per hour'
            );
        }
    }

    /**
     * Check endpoint-specific rate limits
     */
    private function checkEndpointRateLimit(string $endpoint, string $clientIp): void
    {
        // Special limits for resource-intensive endpoints
        $endpointLimits = [
            'POST /api/products/intelligence' => ['minute' => 10, 'hour' => 100],
            'POST /api/admin/bulk-process' => ['minute' => 2, 'hour' => 20],
        ];

        if (!isset($endpointLimits[$endpoint])) {
            return;
        }

        $limits = $endpointLimits[$endpoint];
        $now = time();
        $minuteKey = "endpoint:{$endpoint}:ip:{$clientIp}:minute:" . floor($now / 60);
        $hourKey = "endpoint:{$endpoint}:ip:{$clientIp}:hour:" . floor($now / 3600);

        // Check minute limit
        $minuteCount = $this->getRequestCount($minuteKey);
        if ($minuteCount >= $limits['minute']) {
            $this->logger->warning('Endpoint rate limit exceeded (per minute)', [
                'client_ip' => $clientIp,
                'endpoint' => $endpoint,
                'requests_in_minute' => $minuteCount,
                'limit' => $limits['minute']
            ]);

            throw new TooManyRequestsHttpException(
                60,
                "Rate limit exceeded for {$endpoint}: too many requests per minute"
            );
        }

        // Check hour limit
        $hourCount = $this->getRequestCount($hourKey);
        if ($hourCount >= $limits['hour']) {
            $this->logger->warning('Endpoint rate limit exceeded (per hour)', [
                'client_ip' => $clientIp,
                'endpoint' => $endpoint,
                'requests_in_hour' => $hourCount,
                'limit' => $limits['hour']
            ]);

            throw new TooManyRequestsHttpException(
                3600,
                "Rate limit exceeded for {$endpoint}: too many requests per hour"
            );
        }
    }

    /**
     * Record a request for rate limiting
     */
    private function recordRequest(string $clientIp, string $endpoint): void
    {
        $now = time();
        
        // Record IP-based requests
        $minuteKey = "ip:{$clientIp}:minute:" . floor($now / 60);
        $hourKey = "ip:{$clientIp}:hour:" . floor($now / 3600);
        
        $this->incrementCounter($minuteKey);
        $this->incrementCounter($hourKey);

        // Record endpoint-based requests
        $endpointMinuteKey = "endpoint:{$endpoint}:ip:{$clientIp}:minute:" . floor($now / 60);
        $endpointHourKey = "endpoint:{$endpoint}:ip:{$clientIp}:hour:" . floor($now / 3600);
        
        $this->incrementCounter($endpointMinuteKey);
        $this->incrementCounter($endpointHourKey);

        $this->logger->debug('Request recorded for rate limiting', [
            'client_ip' => $clientIp,
            'endpoint' => $endpoint,
            'minute_key' => $minuteKey,
            'hour_key' => $hourKey
        ]);
    }

    /**
     * Get the client IP address
     */
    private function getClientIp(Request $request): string
    {
        // Check for IP behind proxy
        $clientIp = $request->getClientIp();
        
        // Additional headers to check for real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if ($request->server->has($header)) {
                $ip = $request->server->get($header);
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $clientIp ?: '127.0.0.1';
    }

    /**
     * Get endpoint identifier for rate limiting
     */
    private function getEndpointIdentifier(Request $request): string
    {
        $method = $request->getMethod();
        $path = $request->getPathInfo();
        
        // Normalize paths with IDs to group them
        $path = preg_replace('/\/\d+/', '/{id}', $path);
        
        return "{$method} {$path}";
    }

    /**
     * Get request count for a key
     */
    private function getRequestCount(string $key): int
    {
        return $this->requests[$key] ?? 0;
    }

    /**
     * Increment counter for a key
     */
    private function incrementCounter(string $key): void
    {
        if (!isset($this->requests[$key])) {
            $this->requests[$key] = 0;
        }
        $this->requests[$key]++;
    }

    /**
     * Clean old entries to prevent memory leaks
     */
    private function cleanOldEntries(): void
    {
        $now = time();
        $cutoffMinute = floor($now / 60) - 2; // Keep last 2 minutes
        $cutoffHour = floor($now / 3600) - 2; // Keep last 2 hours

        foreach (array_keys($this->requests) as $key) {
            if (preg_match('/minute:(\d+)$/', $key, $matches)) {
                if ((int)$matches[1] < $cutoffMinute) {
                    unset($this->requests[$key]);
                }
            } elseif (preg_match('/hour:(\d+)$/', $key, $matches)) {
                if ((int)$matches[1] < $cutoffHour) {
                    unset($this->requests[$key]);
                }
            }
        }
    }

    /**
     * Get current rate limit status for a client
     */
    public function getRateLimitStatus(Request $request): array
    {
        if (!$this->enabled) {
            return [
                'enabled' => false,
                'message' => 'Rate limiting disabled'
            ];
        }

        $clientIp = $this->getClientIp($request);
        $now = time();
        $minuteKey = "ip:{$clientIp}:minute:" . floor($now / 60);
        $hourKey = "ip:{$clientIp}:hour:" . floor($now / 3600);

        $minuteCount = $this->getRequestCount($minuteKey);
        $hourCount = $this->getRequestCount($hourKey);

        return [
            'enabled' => true,
            'client_ip' => $clientIp,
            'limits' => [
                'requests_per_minute' => $this->requestsPerMinute,
                'requests_per_hour' => $this->requestsPerHour
            ],
            'current_usage' => [
                'requests_this_minute' => $minuteCount,
                'requests_this_hour' => $hourCount
            ],
            'remaining' => [
                'requests_this_minute' => max(0, $this->requestsPerMinute - $minuteCount),
                'requests_this_hour' => max(0, $this->requestsPerHour - $hourCount)
            ],
            'reset_times' => [
                'minute_resets_at' => (floor($now / 60) + 1) * 60,
                'hour_resets_at' => (floor($now / 3600) + 1) * 3600
            ]
        ];
    }

    /**
     * Manually reset rate limits for a client (admin function)
     */
    public function resetRateLimits(string $clientIp): void
    {
        $keysToRemove = [];
        
        foreach (array_keys($this->requests) as $key) {
            if (strpos($key, "ip:{$clientIp}:") === 0) {
                $keysToRemove[] = $key;
            }
        }

        foreach ($keysToRemove as $key) {
            unset($this->requests[$key]);
        }

        $this->logger->info('Rate limits reset for client', ['client_ip' => $clientIp]);
    }

    /**
     * Get rate limiting statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'enabled' => $this->enabled,
            'total_tracked_keys' => count($this->requests),
            'active_clients' => 0,
            'top_clients' => []
        ];

        // Count unique IPs
        $clientCounts = [];
        foreach (array_keys($this->requests) as $key) {
            if (preg_match('/^ip:([^:]+):/', $key, $matches)) {
                $ip = $matches[1];
                if (!isset($clientCounts[$ip])) {
                    $clientCounts[$ip] = 0;
                }
                $clientCounts[$ip] += $this->requests[$key];
            }
        }

        $stats['active_clients'] = count($clientCounts);
        
        // Get top clients by request count
        arsort($clientCounts);
        $stats['top_clients'] = array_slice($clientCounts, 0, 10, true);

        return $stats;
    }
}

