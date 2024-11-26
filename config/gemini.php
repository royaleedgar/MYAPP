<?php
require_once __DIR__ . '/env.php';

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));

class Gemini {
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    public function __construct($api_key = GEMINI_API_KEY) {
        $this->api_key = $api_key;
    }
    
    public function summarizeText($text) {
        $prompt = "Please provide a concise 2-3 paragraph summary of this news article, highlighting the key points and main takeaways: \n\n" . $text;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 1024,
            ]
        ];
        
        $url = $this->api_url . '?key=' . $this->api_key;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception('Gemini API error: ' . ($error['error']['message'] ?? 'Unknown error'));
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid response format from Gemini');
        }
        
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
} 