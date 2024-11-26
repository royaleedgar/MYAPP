<?php
define('OPENAI_API_KEY', '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');

class OpenAI {
    private $api_key;
    
    public function __construct($api_key = OPENAI_API_KEY) {
        $this->api_key = $api_key;
    }
    
    public function summarizeText($text) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-4-turbo-preview',  // Using the latest model
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional news summarizer. Create clear, concise, and objective summaries that capture the key points while maintaining journalistic integrity. Focus on facts and main ideas.'
                ],
                [
                    'role' => 'user',
                    'content' => "Please summarize this news article in 2-3 concise paragraphs, highlighting the most important information:\n\n" . $text
                ]
            ],
            'temperature' => 0.3, // Lower temperature for more focused outputs
            'max_tokens' => 250,  // Increased for better summaries
            'top_p' => 0.8,      // Nucleus sampling for better quality
            'frequency_penalty' => 0.5, // Reduce repetition
            'presence_penalty' => 0.2,  // Encourage focused content
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v1'  // Using latest API version
            ],
            CURLOPT_SSL_VERIFYPEER => true,   // Enable SSL verification
            CURLOPT_TIMEOUT => 30,            // Set timeout
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception('OpenAI API error: ' . ($error['error']['message'] ?? 'Unknown error'));
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response format from OpenAI');
        }
        
        return $result['choices'][0]['message']['content'];
    }

    // Add error handling method
    private function handleError($response, $http_code) {
        $error = json_decode($response, true);
        $message = $error['error']['message'] ?? 'Unknown error';
        $code = $error['error']['code'] ?? $http_code;
        
        switch ($http_code) {
            case 401:
                throw new Exception('Authentication error: Invalid API key');
            case 429:
                throw new Exception('Rate limit exceeded or quota exceeded');
            case 500:
                throw new Exception('OpenAI server error');
            default:
                throw new Exception("OpenAI API error ($code): $message");
        }
    }
} 