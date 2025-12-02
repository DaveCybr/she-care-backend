<?php
require_once __DIR__ . '/../utils/Response.php';

/**
 * Article Controller (External API)
 */
class ArticleController {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }
    
    /**
     * Get health articles from News API
     * GET /articles
     */
    public function getArticles() {
        $limit = (int) ($_GET['limit'] ?? 10);
        $query = $_GET['q'] ?? 'women health';
        
        $apiKey = $this->config['api']['news_api_key'];
        
        // If no API key, return dummy data
        if (empty($apiKey)) {
            Response::success([
                'count' => 3,
                'data' => $this->getDummyArticles()
            ]);
        }
        
        try {
            $url = "https://newsapi.org/v2/everything?" . http_build_query([
                'q' => $query,
                'language' => 'en',
                'sortBy' => 'publishedAt',
                'pageSize' => $limit,
                'apiKey' => $apiKey
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('API request failed');
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['articles'])) {
                throw new Exception('Invalid API response');
            }
            
            $articles = array_map(function($article) {
                return [
                    'title' => $article['title'],
                    'description' => $article['description'],
                    'url' => $article['url'],
                    'urlToImage' => $article['urlToImage'],
                    'publishedAt' => $article['publishedAt'],
                    'source' => $article['source']
                ];
            }, $data['articles']);
            
            Response::success([
                'count' => count($articles),
                'data' => $articles
            ]);
            
        } catch (Exception $e) {
            error_log('Article API error: ' . $e->getMessage());
            
            // Return dummy data on error
            Response::success([
                'count' => 3,
                'data' => $this->getDummyArticles()
            ]);
        }
    }
    
    /**
     * Get dummy articles (fallback)
     */
    private function getDummyArticles() {
        return [
            [
                'title' => 'Understanding Women\'s Health: A Comprehensive Guide',
                'description' => 'Learn about common women\'s health issues and how to maintain reproductive health.',
                'url' => 'https://example.com/article1',
                'urlToImage' => 'https://via.placeholder.com/400x200',
                'publishedAt' => date('c'),
                'source' => ['name' => 'Health Magazine']
            ],
            [
                'title' => 'Endometriosis: Symptoms and Treatment Options',
                'description' => 'Everything you need to know about endometriosis and modern treatment approaches.',
                'url' => 'https://example.com/article2',
                'urlToImage' => 'https://via.placeholder.com/400x200',
                'publishedAt' => date('c'),
                'source' => ['name' => 'Medical News']
            ],
            [
                'title' => 'Preventing Vaginal Infections: Tips and Best Practices',
                'description' => 'Expert advice on maintaining vaginal health and preventing common infections.',
                'url' => 'https://example.com/article3',
                'urlToImage' => 'https://via.placeholder.com/400x200',
                'publishedAt' => date('c'),
                'source' => ['name' => 'Women\'s Health Today']
            ]
        ];
    }
}
?>