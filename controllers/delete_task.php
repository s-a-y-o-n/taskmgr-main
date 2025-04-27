<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if task ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit();
}

$task_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // First verify that the task belongs to the user
    $verify_stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
    $verify_stmt->execute([$task_id, $user_id]);
    
    if (!$verify_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this task']);
        exit();
    }
    
    // Delete the task
    $delete_stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $delete_stmt->execute([$task_id, $user_id]);
    
    if ($delete_stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    }
} catch (PDOException $e) {
    error_log("Error deleting task: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the task']);
}
?> 