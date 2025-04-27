<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['task_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID and status are required']);
    exit();
}

$task_id = $data['task_id'];
$status = $data['status'];
$user_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // First verify that the task belongs to the user
    $verify_stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
    $verify_stmt->execute([$task_id, $user_id]);
    
    if (!$verify_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this task']);
        exit();
    }
    
    // Update the task status
    $update_stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
    $update_stmt->execute([$status, $task_id, $user_id]);
    
    if ($update_stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Task status updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    }
} catch (PDOException $e) {
    error_log("Error updating task status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the task status']);
}
?> 