<?php
session_start();
require_once '../config/database.php';
require_once '../config/gemini.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
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

    // Generate new summary using Gemini
    $gemini = new Gemini();
    $summary = $gemini->summarizeText($data['text']);
    
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