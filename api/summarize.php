<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

class SimpleSummarizer {
    public function summarize($text, $minSentences = 5) {
        // Clean the text
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Split into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // If text is too short, return as is
        if (count($sentences) <= $minSentences) {
            return $text;
        }
        
        // Score sentences based on their position and content
        $scores = [];
        foreach ($sentences as $i => $sentence) {
            $score = 0;
            
            // Position scoring (first and last paragraphs are usually important)
            if ($i < 3) $score += 3; // First three sentences
            if ($i >= count($sentences) - 3) $score += 2; // Last three sentences
            
            // Length score (prefer medium-length sentences)
            $words = str_word_count($sentence);
            if ($words > 8 && $words < 30) $score += 2;
            
            // Contains numbers, dates, or percentages
            if (preg_match('/\d+%?/', $sentence)) $score += 2;
            
            // Contains quotes
            if (strpos($sentence, '"') !== false || strpos($sentence, '"') !== false) $score += 2;
            
            // Contains key phrases
            $keyPhrases = [
                'important', 'significant', 'according to', 'research', 'study', 'found',
                'announced', 'revealed', 'stated', 'reported', 'confirmed', 'launched',
                'developed', 'introduced', 'created', 'established', 'major', 'critical',
                'essential', 'key', 'primary', 'central', 'fundamental', 'crucial'
            ];
            foreach ($keyPhrases as $phrase) {
                if (stripos($sentence, $phrase) !== false) $score += 1.5;
            }
            
            // Contains proper nouns (basic check for capitalized words not at start)
            preg_match_all('/\b[A-Z][a-z]+\b/', $sentence, $matches);
            $score += count($matches[0]) * 0.5;
            
            $scores[$i] = $score;
        }
        
        // Get top scoring sentences
        arsort($scores);
        $topSentences = array_slice($scores, 0, max($minSentences, ceil(count($sentences) * 0.3)), true);
        ksort($topSentences);
        
        // Build summary with paragraph breaks
        $summary = '';
        $sentenceCount = 0;
        foreach ($topSentences as $i => $score) {
            $summary .= $sentences[$i] . ' ';
            $sentenceCount++;
            
            // Add paragraph break after every 2-3 sentences
            if ($sentenceCount % 3 == 0 && $sentenceCount < count($topSentences)) {
                $summary .= "\n\n";
            }
        }
        
        return trim($summary);
    }
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['text']) || empty(trim($data['text']))) {
        throw new Exception('Missing or empty text parameter');
    }

    $user_id = $_SESSION['user_id'];
    $article_url = $data['url'] ?? '';

    // Check cache first
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT summary FROM article_summaries WHERE user_id = ? AND article_url = ?");
    $stmt->bind_param("is", $user_id, $article_url);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0 && !isset($data['regenerate'])) {
        $cached = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'summary' => $cached['summary'],
            'cached' => true
        ]);
        exit();
    }

    // Generate new summary
    $summarizer = new SimpleSummarizer();
    $summary = $summarizer->summarize($data['text'], 6); // Minimum 6 sentences
    
    // Cache the summary
    $stmt = $conn->prepare("INSERT INTO article_summaries (user_id, article_url, summary) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE summary = ?");
    $stmt->bind_param("isss", $user_id, $article_url, $summary, $summary);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'cached' => false
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate summary',
        'message' => $e->getMessage()
    ]);
} 