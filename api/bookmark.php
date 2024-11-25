<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

try {
    // Check if article is already bookmarked
    $check_sql = "SELECT id FROM bookmarks WHERE user_id = ? AND article_url = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $user_id, $data['url']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmarks WHERE user_id = ? AND article_url = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $user_id, $data['url']);
        $success = $delete_stmt->execute();
        
        echo json_encode([
            'success' => $success,
            'bookmarked' => false,
            'message' => $success ? 'Bookmark removed' : 'Failed to remove bookmark'
        ]);
    } else {
        // Add bookmark
        $insert_sql = "INSERT INTO bookmarks (user_id, article_url, article_title, article_image, article_source, article_description, published_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("issssss", 
            $user_id, 
            $data['url'],
            $data['title'],
            $data['image'],
            $data['source'],
            $data['description'],
            $data['publishedAt']
        );
        $success = $insert_stmt->execute();
        
        echo json_encode([
            'success' => $success,
            'bookmarked' => true,
            'message' => $success ? 'Article bookmarked' : 'Failed to bookmark article'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} 