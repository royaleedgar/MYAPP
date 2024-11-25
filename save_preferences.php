<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

if ($data['action'] === 'update_theme') {
    $theme = $data['theme'];
    
    $stmt = $conn->prepare("UPDATE user_settings SET theme = ? WHERE user_id = ?");
    $stmt->bind_param("si", $theme, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update theme']);
    }
} 