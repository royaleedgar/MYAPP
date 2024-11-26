<?php
require_once __DIR__ . '/env.php';

define('NEWS_API_KEY', getenv('NEWS_API_KEY'));
define('NEWS_API_URL', 'https://newsapi.org/v2/');

class NewsAPI {
    private $api_key;
    
    public function __construct($api_key = NEWS_API_KEY) {
        $this->api_key = $api_key;
    }
    
    /**
     * Fetch top headlines
     * @param array $params - Available parameters:
     * - category: business, entertainment, general, health, science, sports, technology
     * - country: 2-letter ISO 3166-1 code
     * - q: Keywords or phrases to search for
     * - pageSize: int (max 100)
     * - page: int
     */
    public function getTopHeadlines($params = []) {
        $default_params = [
            'language' => 'en',
            'pageSize' => 10,
            'page' => 1
        ];
        
        return $this->makeRequest('top-headlines', array_merge($default_params, $params));
    }
    
    /**
     * Search all articles
     * @param array $params - Available parameters:
     * - q: Keywords or a phrase to search for
     * - searchIn: title,description,content
     * - from: ISO date string
     * - to: ISO date string
     * - sortBy: relevancy, popularity, publishedAt
     * - pageSize: int (max 100)
     * - page: int
     */
    public function getEverything($params = []) {
        $default_params = [
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => 10,
            'page' => 1
        ];
        
        return $this->makeRequest('everything', array_merge($default_params, $params));
    }
    
    private function makeRequest($endpoint, $params = []) {
        $params['apiKey'] = $this->api_key;
        $url = NEWS_API_URL . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Omni News/1.0',
                'X-Api-Key: ' . $this->api_key
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception($error['message'] ?? 'Failed to fetch news');
        }
        
        return json_decode($response, true);
    }
}

// Helper function for backward compatibility
function fetchNews($category = null, $query = null, $page = 1) {
    $newsapi = new NewsAPI();
    $params = [
        'page' => $page,
        'pageSize' => 10
    ];
    
    if ($category) {
        $params['category'] = $category;
    }
    
    if ($query) {
        $params['q'] = $query;
    }
    
    try {
        return $newsapi->getTopHeadlines($params);
    } catch (Exception $e) {
        error_log("NewsAPI Error: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage(),
            'articles' => []
        ];
    }
} 