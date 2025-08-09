<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * API Key Authenticator
 * 
 * Provides API key-based authentication for the Product Studio API.
 * Supports both header-based and query parameter API keys.
 * 
 * Authentication methods:
 * 1. Header: Authorization: Bearer YOUR_API_KEY
 * 2. Header: X-API-Key: YOUR_API_KEY
 * 3. Query parameter: ?api_key=YOUR_API_KEY
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private LoggerInterface $logger;
    private bool $authRequired;
    private array $validApiKeys;

    public function __construct(LoggerInterface $logger, bool $authRequired = false)
    {
        $this->logger = $logger;
        $this->authRequired = $authRequired;
        $this->initializeApiKeys();
    }

    /**
     * Check if this authenticator supports the request
     */
    public function supports(Request $request): ?bool
    {
        // Only authenticate if auth is required or API key is provided
        return $this->authRequired || $this->getApiKeyFromRequest($request) !== null;
    }

    /**
     * Authenticate the request
     */
    public function authenticate(Request $request): Passport
    {
        $apiKey = $this->getApiKeyFromRequest($request);
        
        if ($apiKey === null) {
            if ($this->authRequired) {
                throw new CustomUserMessageAuthenticationException('API key required');
            }
            // If auth not required and no key provided, create guest user
            return new SelfValidatingPassport(new UserBadge('guest'));
        }

        if (!$this->isValidApiKey($apiKey)) {
            $this->logger->warning('Invalid API key attempted', [
                'api_key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent')
            ]);
            
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        $userId = $this->getUserIdFromApiKey($apiKey);
        
        $this->logger->info('Successful API key authentication', [
            'user_id' => $userId,
            'ip' => $request->getClientIp()
        ]);

        return new SelfValidatingPassport(new UserBadge($userId));
    }

    /**
     * Handle authentication success
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Return null to continue to the controller
        return null;
    }

    /**
     * Handle authentication failure
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->warning('API authentication failed', [
            'error' => $exception->getMessage(),
            'ip' => $request->getClientIp(),
            'path' => $request->getPathInfo()
        ]);

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Authentication failed',
            'error' => $exception->getMessageKey()
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Extract API key from request
     */
    private function getApiKeyFromRequest(Request $request): ?string
    {
        // Method 1: Authorization header with Bearer token
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Method 2: X-API-Key header
        $apiKeyHeader = $request->headers->get('X-API-Key');
        if ($apiKeyHeader) {
            return $apiKeyHeader;
        }

        // Method 3: Query parameter
        $apiKeyParam = $request->query->get('api_key');
        if ($apiKeyParam) {
            return $apiKeyParam;
        }

        return null;
    }

    /**
     * Check if API key is valid
     */
    private function isValidApiKey(string $apiKey): bool
    {
        return in_array($apiKey, $this->validApiKeys, true);
    }

    /**
     * Get user ID from API key
     */
    private function getUserIdFromApiKey(string $apiKey): string
    {
        // In a real implementation, this would look up the user from database
        // For now, return a mapping based on the API key
        $keyMap = [
            'demo_key_12345' => 'demo_user',
            'admin_key_67890' => 'admin_user',
            'test_key_abcdef' => 'test_user'
        ];

        return $keyMap[$apiKey] ?? 'api_user_' . substr(md5($apiKey), 0, 8);
    }

    /**
     * Initialize valid API keys
     */
    private function initializeApiKeys(): void
    {
        // In production, these would come from environment variables or database
        $this->validApiKeys = [
            'demo_key_12345',     // Demo/development key
            'admin_key_67890',    // Admin key
            'test_key_abcdef',    // Test key
        ];

        // Load from environment if available
        $envApiKeys = $_ENV['VALID_API_KEYS'] ?? '';
        if ($envApiKeys) {
            $additionalKeys = explode(',', $envApiKeys);
            $this->validApiKeys = array_merge($this->validApiKeys, $additionalKeys);
        }
    }

    /**
     * Generate a new API key (admin function)
     */
    public function generateApiKey(string $prefix = 'ps'): string
    {
        return $prefix . '_' . bin2hex(random_bytes(16));
    }

    /**
     * Validate API key format
     */
    public function isValidApiKeyFormat(string $apiKey): bool
    {
        // API keys should be at least 16 characters and contain only alphanumeric and underscores
        return strlen($apiKey) >= 16 && preg_match('/^[a-zA-Z0-9_]+$/', $apiKey);
    }
}

