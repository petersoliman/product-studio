<?php

namespace App\Service\SEO;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * SEO Keyword Research Service
 * 
 * Advanced keyword research and generation service for industrial tools and equipment.
 * Uses multiple data sources and algorithms to generate high-value SEO keywords.
 * 
 * Features:
 * - Industry-specific keyword databases
 * - Search volume analysis integration
 * - Competitor keyword analysis
 * - Long-tail keyword generation
 * - Local SEO keyword variants
 * - Semantic keyword clustering
 * 
 * @author Product Studio Team
 * @version 1.0.0
 */
class KeywordResearchService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private array $industryKeywords;
    private array $modifierWords;
    private array $locationModifiers;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->initializeKeywordDatabases();
    }

    /**
     * Generate comprehensive keyword list for a product
     * 
     * @param string $productName Product name
     * @param string $category Product category
     * @param string $brand Brand name
     * @param array $seedKeywords Base keywords provided by user
     * @return array Comprehensive keyword list with metrics
     */
    public function generateKeywords(
        string $productName, 
        string $category, 
        string $brand, 
        array $seedKeywords = []
    ): array {
        $this->logger->info('Starting keyword research', [
            'product' => $productName,
            'category' => $category,
            'brand' => $brand,
            'seed_keywords' => count($seedKeywords)
        ]);

        $keywords = [];

        // 1. Base product keywords
        $keywords = array_merge($keywords, $this->generateBaseKeywords($productName, $brand));

        // 2. Category-specific keywords
        $keywords = array_merge($keywords, $this->generateCategoryKeywords($category));

        // 3. Industry-specific keywords
        $keywords = array_merge($keywords, $this->generateIndustryKeywords($category));

        // 4. Long-tail variations
        $keywords = array_merge($keywords, $this->generateLongTailKeywords($productName, $category));

        // 5. Modifier-based keywords
        $keywords = array_merge($keywords, $this->generateModifierKeywords($productName));

        // 6. Competitor-inspired keywords
        $keywords = array_merge($keywords, $this->generateCompetitorKeywords($brand, $category));

        // 7. Local SEO keywords
        $keywords = array_merge($keywords, $this->generateLocalKeywords($productName, $category));

        // 8. Incorporate seed keywords
        $keywords = array_merge($keywords, $seedKeywords);

        // Remove duplicates and rank by relevance
        $keywords = array_unique($keywords);
        $rankedKeywords = $this->rankKeywordsByRelevance($keywords, $productName, $category);

        // Get search volume estimates (would integrate with real APIs in production)
        $keywordsWithMetrics = $this->addKeywordMetrics($rankedKeywords);

        $this->logger->info('Keyword research completed', [
            'total_keywords' => count($keywordsWithMetrics),
            'high_value_keywords' => count(array_filter($keywordsWithMetrics, fn($k) => $k['score'] > 80))
        ]);

        return array_slice($keywordsWithMetrics, 0, 50); // Return top 50 keywords
    }

    /**
     * Generate base keywords from product name and brand
     */
    private function generateBaseKeywords(string $productName, string $brand): array
    {
        $keywords = [];
        $productWords = $this->extractSignificantWords($productName);
        
        // Product name variations
        $keywords[] = $productName;
        $keywords[] = strtolower($productName);
        $keywords[] = "{$brand} {$productName}";
        
        // Individual significant words
        foreach ($productWords as $word) {
            if (strlen($word) > 3) { // Skip short words
                $keywords[] = $word;
                $keywords[] = "{$brand} {$word}";
            }
        }

        // Brand variations
        $keywords[] = $brand;
        $keywords[] = strtolower($brand);
        $keywords[] = "{$brand} tools";
        $keywords[] = "{$brand} equipment";

        return $keywords;
    }

    /**
     * Generate category-specific keywords
     */
    private function generateCategoryKeywords(string $category): array
    {
        $keywords = [];
        $category = strtolower($category);
        
        // Direct category keywords
        $keywords[] = $category;
        $keywords[] = "{$category} tools";
        $keywords[] = "{$category} equipment";
        $keywords[] = "professional {$category}";
        $keywords[] = "industrial {$category}";
        $keywords[] = "commercial {$category}";

        // Category-specific variations based on common tool categories
        $categoryMappings = [
            'power tools' => ['cordless tools', 'electric tools', 'pneumatic tools', 'battery tools'],
            'hand tools' => ['manual tools', 'mechanic tools', 'precision tools', 'specialty tools'],
            'cutting tools' => ['saw blades', 'drill bits', 'cutting discs', 'router bits'],
            'measuring tools' => ['levels', 'squares', 'calipers', 'rulers'],
            'safety equipment' => ['protective gear', 'safety tools', 'ppe equipment']
        ];

        foreach ($categoryMappings as $cat => $variations) {
            if (strpos($category, $cat) !== false || strpos($cat, $category) !== false) {
                $keywords = array_merge($keywords, $variations);
            }
        }

        return $keywords;
    }

    /**
     * Generate industry-specific keywords
     */
    private function generateIndustryKeywords(string $category): array
    {
        $keywords = [];
        $category = strtolower($category);

        // Get relevant industry keywords
        foreach ($this->industryKeywords as $industry => $terms) {
            // Check if category matches this industry
            $industryCategories = [
                'construction' => ['power', 'drill', 'saw', 'hammer', 'nail'],
                'automotive' => ['wrench', 'socket', 'impact', 'torque', 'mechanic'],
                'electrical' => ['wire', 'electrical', 'voltage', 'meter', 'circuit'],
                'plumbing' => ['pipe', 'plumbing', 'water', 'fitting', 'valve'],
                'woodworking' => ['wood', 'router', 'chisel', 'plane', 'jointer'],
                'metalworking' => ['metal', 'cutting', 'grinding', 'welding', 'machining']
            ];

            foreach ($industryCategories[$industry] ?? [] as $trigger) {
                if (strpos($category, $trigger) !== false) {
                    $keywords = array_merge($keywords, array_slice($terms, 0, 10));
                    break;
                }
            }
        }

        return $keywords;
    }

    /**
     * Generate long-tail keyword variations
     */
    private function generateLongTailKeywords(string $productName, string $category): array
    {
        $keywords = [];
        $productName = strtolower($productName);
        $category = strtolower($category);

        $longTailTemplates = [
            'best {product} for {use_case}',
            'professional {product} reviews',
            '{product} vs {alternative}',
            'how to use {product}',
            '{product} buying guide',
            'top rated {product}',
            '{product} for professionals',
            'heavy duty {product}',
            'commercial grade {product}',
            '{product} specifications',
            '{product} replacement parts',
            '{product} accessories'
        ];

        $useCases = [
            'construction', 'contractors', 'professionals', 'home improvement',
            'industrial use', 'commercial projects', 'heavy duty work'
        ];

        foreach ($longTailTemplates as $template) {
            if (strpos($template, '{use_case}') !== false) {
                foreach ($useCases as $useCase) {
                    $keywords[] = str_replace(['{product}', '{use_case}'], [$productName, $useCase], $template);
                }
            } else {
                $keywords[] = str_replace('{product}', $productName, $template);
            }
        }

        return $keywords;
    }

    /**
     * Generate modifier-based keywords
     */
    private function generateModifierKeywords(string $productName): array
    {
        $keywords = [];
        $productName = strtolower($productName);

        foreach ($this->modifierWords as $modifier) {
            $keywords[] = "{$modifier} {$productName}";
            $keywords[] = "{$productName} {$modifier}";
        }

        return $keywords;
    }

    /**
     * Generate competitor-inspired keywords
     */
    private function generateCompetitorKeywords(string $brand, string $category): array
    {
        $keywords = [];
        $competitors = $this->getCompetitorBrands($brand);
        $category = strtolower($category);

        foreach ($competitors as $competitor) {
            $keywords[] = "{$competitor} vs {$brand}";
            $keywords[] = "{$competitor} {$category}";
            $keywords[] = "alternative to {$competitor}";
        }

        return $keywords;
    }

    /**
     * Generate local SEO keywords
     */
    private function generateLocalKeywords(string $productName, string $category): array
    {
        $keywords = [];
        $productName = strtolower($productName);
        $category = strtolower($category);

        foreach ($this->locationModifiers as $location) {
            $keywords[] = "{$productName} in {$location}";
            $keywords[] = "{$category} {$location}";
            $keywords[] = "buy {$productName} {$location}";
            $keywords[] = "{$productName} suppliers {$location}";
        }

        return $keywords;
    }

    /**
     * Rank keywords by relevance and commercial value
     */
    private function rankKeywordsByRelevance(array $keywords, string $productName, string $category): array
    {
        $scoredKeywords = [];

        foreach ($keywords as $keyword) {
            $score = $this->calculateKeywordScore($keyword, $productName, $category);
            $scoredKeywords[] = [
                'keyword' => $keyword,
                'score' => $score
            ];
        }

        // Sort by score descending
        usort($scoredKeywords, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_column($scoredKeywords, 'keyword');
    }

    /**
     * Calculate keyword relevance score
     */
    private function calculateKeywordScore(string $keyword, string $productName, string $category): int
    {
        $score = 0;
        $keyword = strtolower($keyword);
        $productName = strtolower($productName);
        $category = strtolower($category);

        // Exact product name match = high score
        if ($keyword === $productName) {
            $score += 100;
        }

        // Contains product name = good score
        if (strpos($keyword, $productName) !== false) {
            $score += 80;
        }

        // Contains category = moderate score
        if (strpos($keyword, $category) !== false) {
            $score += 60;
        }

        // Commercial intent keywords = higher score
        $commercialTerms = ['buy', 'purchase', 'price', 'cost', 'for sale', 'best', 'top', 'review'];
        foreach ($commercialTerms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 40;
                break;
            }
        }

        // Professional/industrial terms = higher score
        $professionalTerms = ['professional', 'industrial', 'commercial', 'heavy duty', 'contractor'];
        foreach ($professionalTerms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 30;
                break;
            }
        }

        // Long-tail keywords = moderate score
        if (str_word_count($keyword) >= 3) {
            $score += 20;
        }

        // Penalize very generic terms
        $genericTerms = ['tool', 'equipment', 'item', 'product'];
        if (in_array($keyword, $genericTerms)) {
            $score -= 50;
        }

        return max(0, $score);
    }

    /**
     * Add search volume and competition metrics
     */
    private function addKeywordMetrics(array $keywords): array
    {
        $keywordsWithMetrics = [];

        foreach ($keywords as $keyword) {
            // In production, this would integrate with Google Keyword Planner API
            // or other keyword research tools. For now, we'll estimate based on keyword characteristics
            $metrics = $this->estimateKeywordMetrics($keyword);
            
            $keywordsWithMetrics[] = [
                'keyword' => $keyword,
                'score' => $this->calculateKeywordScore($keyword, '', ''),
                'estimated_volume' => $metrics['volume'],
                'competition' => $metrics['competition'],
                'commercial_value' => $metrics['commercial_value']
            ];
        }

        return $keywordsWithMetrics;
    }

    /**
     * Estimate keyword metrics (would be replaced with real API data)
     */
    private function estimateKeywordMetrics(string $keyword): array
    {
        $wordCount = str_word_count($keyword);
        
        // Estimate search volume based on keyword characteristics
        $volume = match(true) {
            $wordCount === 1 => rand(10000, 50000), // Single words = high volume
            $wordCount === 2 => rand(1000, 10000),  // Two words = medium volume
            $wordCount >= 3 => rand(100, 1000),     // Long-tail = low volume
            default => rand(100, 1000)
        };

        // Estimate competition based on commercial intent
        $commercialTerms = ['buy', 'price', 'best', 'review', 'for sale'];
        $hasCommercialIntent = false;
        foreach ($commercialTerms as $term) {
            if (strpos(strtolower($keyword), $term) !== false) {
                $hasCommercialIntent = true;
                break;
            }
        }

        $competition = $hasCommercialIntent ? 'high' : ($wordCount <= 2 ? 'medium' : 'low');
        $commercialValue = $hasCommercialIntent ? 'high' : 'medium';

        return [
            'volume' => $volume,
            'competition' => $competition,
            'commercial_value' => $commercialValue
        ];
    }

    /**
     * Extract significant words from product name
     */
    private function extractSignificantWords(string $productName): array
    {
        // Remove common stop words and extract meaningful terms
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = explode(' ', strtolower($productName));
        
        return array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
    }

    /**
     * Get competitor brands for a given brand
     */
    private function getCompetitorBrands(string $brand): array
    {
        $brand = strtolower($brand);
        
        $competitorMap = [
            'dewalt' => ['milwaukee', 'makita', 'bosch', 'ryobi', 'craftsman'],
            'milwaukee' => ['dewalt', 'makita', 'bosch', 'ridgid', 'porter-cable'],
            'makita' => ['dewalt', 'milwaukee', 'bosch', 'hitachi', 'metabo'],
            'bosch' => ['dewalt', 'milwaukee', 'makita', 'festool', 'hilti'],
            'ryobi' => ['dewalt', 'black+decker', 'craftsman', 'kobalt', 'hart']
        ];

        return $competitorMap[$brand] ?? ['dewalt', 'milwaukee', 'makita', 'bosch'];
    }

    /**
     * Initialize keyword databases
     */
    private function initializeKeywordDatabases(): void
    {
        $this->industryKeywords = [
            'construction' => [
                'contractor tools', 'building supplies', 'construction equipment',
                'job site tools', 'framing tools', 'concrete tools', 'masonry tools',
                'roofing tools', 'drywall tools', 'flooring tools'
            ],
            'automotive' => [
                'automotive tools', 'mechanic tools', 'car repair tools',
                'diagnostic tools', 'engine tools', 'brake tools', 'suspension tools',
                'transmission tools', 'automotive specialty tools'
            ],
            'electrical' => [
                'electrical tools', 'electrician tools', 'wire tools',
                'conduit tools', 'circuit tools', 'voltage tools', 'electrical testing',
                'electrical installation', 'electrical maintenance'
            ],
            'plumbing' => [
                'plumbing tools', 'pipe tools', 'drain tools',
                'water tools', 'plumbing installation', 'plumbing repair',
                'pipe fitting tools', 'plumbing diagnostic tools'
            ],
            'woodworking' => [
                'woodworking tools', 'carpentry tools', 'cabinet tools',
                'furniture tools', 'wood cutting tools', 'wood shaping tools',
                'wood finishing tools', 'precision woodworking'
            ],
            'metalworking' => [
                'metalworking tools', 'machining tools', 'cutting tools',
                'grinding tools', 'welding tools', 'fabrication tools',
                'metal finishing tools', 'precision metalworking'
            ]
        ];

        $this->modifierWords = [
            'professional', 'industrial', 'commercial', 'heavy duty',
            'precision', 'high performance', 'durable', 'reliable',
            'cordless', 'electric', 'pneumatic', 'hydraulic',
            'compact', 'portable', 'lightweight', 'ergonomic',
            'variable speed', 'brushless', 'lithium ion'
        ];

        $this->locationModifiers = [
            'near me', 'local', 'nearby', 'in my area',
            'USA', 'America', 'North America'
        ];
    }

    /**
     * Get keyword suggestions for autocomplete
     */
    public function getKeywordSuggestions(string $partialKeyword, int $limit = 10): array
    {
        $suggestions = [];
        $partialKeyword = strtolower($partialKeyword);

        // Search through industry keywords
        foreach ($this->industryKeywords as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos(strtolower($keyword), $partialKeyword) === 0) {
                    $suggestions[] = $keyword;
                }
            }
        }

        return array_slice(array_unique($suggestions), 0, $limit);
    }
}

