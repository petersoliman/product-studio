<?php

namespace App\Security;

use Psr\Log\LoggerInterface;

/**
 * Input Validation Service
 * 
 * Comprehensive input validation and sanitization for the Product Studio API.
 * Protects against common attacks like XSS, SQL injection, and malformed data.
 * 
 * Features:
 * - Data type validation
 * - Length and format validation
 * - XSS protection
 * - SQL injection prevention
 * - Business logic validation
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
class InputValidator
{
    private LoggerInterface $logger;
    private array $validationRules;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->initializeValidationRules();
    }

    /**
     * Validate product intelligence input data
     */
    public function validateProductIntelligenceInput(array $data): array
    {
        $errors = [];
        $sanitized = [];

        // Validate product name
        if (isset($data['name'])) {
            $nameValidation = $this->validateProductName($data['name']);
            if ($nameValidation['valid']) {
                $sanitized['name'] = $nameValidation['value'];
            } else {
                $errors['name'] = $nameValidation['errors'];
            }
        }

        // Validate model number
        if (isset($data['model_number'])) {
            $modelValidation = $this->validateModelNumber($data['model_number']);
            if ($modelValidation['valid']) {
                $sanitized['model_number'] = $modelValidation['value'];
            } else {
                $errors['model_number'] = $modelValidation['errors'];
            }
        }

        // Validate brand
        if (isset($data['brand'])) {
            $brandValidation = $this->validateBrand($data['brand']);
            if ($brandValidation['valid']) {
                $sanitized['brand'] = $brandValidation['value'];
            } else {
                $errors['brand'] = $brandValidation['errors'];
            }
        }

        // Validate category
        if (isset($data['category'])) {
            $categoryValidation = $this->validateCategory($data['category']);
            if ($categoryValidation['valid']) {
                $sanitized['category'] = $categoryValidation['value'];
            } else {
                $errors['category'] = $categoryValidation['errors'];
            }
        }

        // Validate SEO keywords
        if (isset($data['seo_keywords'])) {
            $keywordsValidation = $this->validateSeoKeywords($data['seo_keywords']);
            if ($keywordsValidation['valid']) {
                $sanitized['seo_keywords'] = $keywordsValidation['value'];
            } else {
                $errors['seo_keywords'] = $keywordsValidation['errors'];
            }
        }

        // Validate brief
        if (isset($data['brief'])) {
            $briefValidation = $this->validateBrief($data['brief']);
            if ($briefValidation['valid']) {
                $sanitized['brief'] = $briefValidation['value'];
            } else {
                $errors['brief'] = $briefValidation['errors'];
            }
        }

        // Validate description
        if (isset($data['description'])) {
            $descValidation = $this->validateDescription($data['description']);
            if ($descValidation['valid']) {
                $sanitized['description'] = $descValidation['value'];
            } else {
                $errors['description'] = $descValidation['errors'];
            }
        }

        // Business logic validation
        if (empty($sanitized['name']) && empty($sanitized['model_number'])) {
            $errors['_general'] = ['Either product name or model number is required'];
        }

        if (!empty($errors)) {
            $this->logger->warning('Input validation failed', [
                'errors' => $errors,
                'input_keys' => array_keys($data)
            ]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_data' => $sanitized
        ];
    }

    /**
     * Validate product name
     */
    private function validateProductName(string $name): array
    {
        $errors = [];
        $sanitized = $this->sanitizeString($name);

        // Length validation
        if (strlen($sanitized) < 2) {
            $errors[] = 'Product name must be at least 2 characters long';
        }
        if (strlen($sanitized) > 60) {
            $errors[] = 'Product name must not exceed 60 characters for SEO optimization';
        }

        // Format validation
        if (!preg_match('/^[a-zA-Z0-9\s\-\.\+\/\&]+$/', $sanitized)) {
            $errors[] = 'Product name contains invalid characters';
        }

        // Security validation
        if ($this->containsSqlPatterns($sanitized) || $this->containsXssPatterns($sanitized)) {
            $errors[] = 'Product name contains potentially harmful content';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $sanitized
        ];
    }

    /**
     * Validate model number
     */
    private function validateModelNumber(string $modelNumber): array
    {
        $errors = [];
        $sanitized = $this->sanitizeString($modelNumber);

        // Length validation
        if (strlen($sanitized) > 100) {
            $errors[] = 'Model number must not exceed 100 characters';
        }

        // Format validation (alphanumeric, dashes, dots)
        if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $sanitized)) {
            $errors[] = 'Model number can only contain letters, numbers, dashes, and dots';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $sanitized
        ];
    }

    /**
     * Validate brand name
     */
    private function validateBrand(string $brand): array
    {
        $errors = [];
        $sanitized = $this->sanitizeString($brand);

        // Length validation
        if (strlen($sanitized) > 100) {
            $errors[] = 'Brand name must not exceed 100 characters';
        }

        // Format validation
        if (!preg_match('/^[a-zA-Z0-9\s\-\.\&]+$/', $sanitized)) {
            $errors[] = 'Brand name contains invalid characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $sanitized
        ];
    }

    /**
     * Validate category
     */
    private function validateCategory(string $category): array
    {
        $errors = [];
        $sanitized = $this->sanitizeString($category);

        // Length validation
        if (strlen($sanitized) > 100) {
            $errors[] = 'Category must not exceed 100 characters';
        }

        // Validate against known categories
        $validCategories = [
            'power tools', 'hand tools', 'cutting tools', 'measuring tools',
            'safety equipment', 'electrical tools', 'plumbing tools',
            'automotive tools', 'woodworking tools', 'metalworking tools',
            'construction tools', 'industrial equipment', 'batteries & chargers'
        ];

        if (!empty($sanitized) && !in_array(strtolower($sanitized), $validCategories)) {
            // Allow custom categories but log for review
            $this->logger->info('Custom category used', ['category' => $sanitized]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $sanitized
        ];
    }

    /**
     * Validate SEO keywords array
     */
    private function validateSeoKeywords($keywords): array
    {
        $errors = [];
        $sanitized = [];

        if (!is_array($keywords)) {
            return [
                'valid' => false,
                'errors' => ['SEO keywords must be an array'],
                'value' => []
            ];
        }

        if (count($keywords) > 50) {
            $errors[] = 'Maximum 50 SEO keywords allowed';
        }

        foreach ($keywords as $keyword) {
            if (!is_string($keyword)) {
                $errors[] = 'All keywords must be strings';
                continue;
            }

            $sanitizedKeyword = $this->sanitizeString($keyword);
            
            if (strlen($sanitizedKeyword) > 100) {
                $errors[] = 'Individual keywords must not exceed 100 characters';
                continue;
            }

            if (strlen($sanitizedKeyword) < 2) {
                continue; // Skip very short keywords
            }

            if (!$this->containsSqlPatterns($sanitizedKeyword) && !$this->containsXssPatterns($sanitizedKeyword)) {
                $sanitized[] = $sanitizedKeyword;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => array_unique($sanitized)
        ];
    }

    /**
     * Validate brief description
     */
    private function validateBrief(string $brief): array
    {
        $errors = [];
        $sanitized = $this->sanitizeString($brief);

        // Length validation
        if (strlen($sanitized) > 100) {
            $errors[] = 'Brief description must not exceed 100 characters for SEO optimization';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $sanitized
        ];
    }

    /**
     * Validate description
     */
    private function validateDescription(string $description): array
    {
        $errors = [];
        $sanitized = $this->sanitizeString($description);

        // Length validation
        if (strlen($sanitized) > 200) {
            $errors[] = 'Description must not exceed 200 characters for SEO optimization';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $sanitized
        ];
    }

    /**
     * Sanitize string input
     */
    private function sanitizeString(string $input): string
    {
        // Remove null bytes
        $sanitized = str_replace("\0", '', $input);
        
        // Trim whitespace
        $sanitized = trim($sanitized);
        
        // Convert HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $sanitized;
    }

    /**
     * Check for SQL injection patterns
     */
    private function containsSqlPatterns(string $input): bool
    {
        $sqlPatterns = [
            '/union\s+select/i',
            '/select\s+.*\s+from/i',
            '/insert\s+into/i',
            '/update\s+.*\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/exec\s*\(/i',
            '/script\s*:/i'
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS patterns
     */
    private function containsXssPatterns(string $input): bool
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>/i'
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Initialize validation rules
     */
    private function initializeValidationRules(): void
    {
        $this->validationRules = [
            'max_lengths' => [
                'name' => 60,
                'model_number' => 100,
                'brand' => 100,
                'category' => 100,
                'brief' => 100,
                'description' => 200,
                'keyword' => 100
            ],
            'required_fields' => [
                'product_intelligence' => ['name', 'model_number'] // Either one required
            ],
            'allowed_characters' => [
                'name' => '/^[a-zA-Z0-9\s\-\.\+\/\&]+$/',
                'model_number' => '/^[a-zA-Z0-9\-\.]+$/',
                'brand' => '/^[a-zA-Z0-9\s\-\.\&]+$/'
            ]
        ];
    }

    /**
     * Validate pagination parameters
     */
    public function validatePaginationParams(array $params): array
    {
        $errors = [];
        $sanitized = [];

        // Validate page
        $page = $params['page'] ?? 1;
        if (!is_numeric($page) || $page < 1 || $page > 10000) {
            $errors['page'] = 'Page must be a number between 1 and 10000';
        } else {
            $sanitized['page'] = (int)$page;
        }

        // Validate limit
        $limit = $params['limit'] ?? 20;
        if (!is_numeric($limit) || $limit < 1 || $limit > 100) {
            $errors['limit'] = 'Limit must be a number between 1 and 100';
        } else {
            $sanitized['limit'] = (int)$limit;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_data' => $sanitized
        ];
    }

    /**
     * Validate search/filter parameters
     */
    public function validateFilterParams(array $params): array
    {
        $errors = [];
        $sanitized = [];

        $allowedFilters = ['brand', 'category', 'status', 'search'];

        foreach ($allowedFilters as $filter) {
            if (isset($params[$filter])) {
                $value = $this->sanitizeString($params[$filter]);
                
                if (strlen($value) > 100) {
                    $errors[$filter] = "Filter '{$filter}' must not exceed 100 characters";
                } else {
                    $sanitized[$filter] = $value;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_data' => $sanitized
        ];
    }
}

