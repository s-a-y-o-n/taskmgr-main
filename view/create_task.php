<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $due_time = $_POST['due_time'] ?? '00:00';
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    
    // Combine date and time
    $due_datetime = !empty($due_date) ? $due_date . ' ' . $due_time : '';
    
    if (!empty($title) && !empty($due_date)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, due_date, priority, status, created_at, updated_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $due_datetime, $priority, $status]);
            
            // Redirect to home page after successful creation
            header("Location: home.php");
            exit();
        } catch(PDOException $e) {
            error_log("Error creating task: " . $e->getMessage());
            $error_message = "Failed to create task. Please try again.";
        }
    } else {
        $error_message = "Title and due date are required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create New Task - Task Management App</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }
    
    body {
      background-color: #f7f7f7;
      display: flex;
      min-height: 100vh;
    }
    
    .sidebar {
      width: 300px;
      background-color: white;
      border-right: 1px solid #e0e0e0;
      padding: 20px;
      height: 100vh;
      position: fixed;
      overflow-y: auto;
    }
    
    .sidebar-item {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      margin-bottom: 8px;
      border-radius: 8px;
      cursor: pointer;
      color: #333;
      font-size: 14px;
      text-decoration: none;
    }
    
    .sidebar-item.active {
      background-color: #f2f2f2;
      font-weight: 500;
    }
    
    .sidebar-item:hover:not(.active) {
      background-color: #f8f8f8;
    }
    
    .sidebar-item svg {
      margin-right: 12px;
      color: #555;
    }
    
    .main-content {
      flex: 1;
      padding: 32px;
      margin-left: 300px;
    }
    
    h1 {
      font-size: 24px;
      font-weight: 600;
      color: #111;
      margin-bottom: 24px;
    }
    
    .form-container {
      background-color: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      max-width: 600px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    label {
      display: block;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 8px;
      color: #333;
    }
    
    input[type="text"],
    input[type="date"],
    textarea,
    select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      color: #333;
    }
    
    textarea {
      min-height: 120px;
      resize: vertical;
    }
    
    .btn {
      display: inline-block;
      padding: 10px 16px;
      background-color: #4a6cf7;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .btn:hover {
      background-color: #3a5ce5;
    }
    
    .error-message {
      color: #e53935;
      font-size: 14px;
      margin-bottom: 16px;
    }
    
    .bottom-buttons {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 300px;
      padding: 20px;
      background-color: white;
    }
    
    .button {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      margin-bottom: 8px;
      cursor: pointer;
      color: #333;
      font-size: 14px;
      border-radius: 8px;
    }
    
    .button:hover {
      background-color: #f8f8f8;
    }
    
    .button svg {
      margin-right: 12px;
      color: #555;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <a href="http://localhost/taskmgr-main/view/home.php" class="sidebar-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
      </svg>
      Home
    </a>
    
    <a href="http://localhost/taskmgr-main/view/mywork.php" class="sidebar-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
        <rect x="3" y="14" width="7" height="7"></rect>
      </svg>
      My Work
    </a>
    
    <a href="http://localhost/taskmgr-main/view/notification.php" class="sidebar-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
      Inbox
    </a>
    
    <a href="../controllers/logout.php" class="sidebar-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21" y1="12" x2="9" y2="12"></line>
      </svg>
      Logout
    </a>
    
    <div class="bottom-buttons">
      <a href="http://localhost/taskmgr-main/view/create_task.php" class="button active">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Create
      </a>
    </div>
  </div>
  
  <div class="main-content">
    <h1>Create New Task</h1>
    
    <?php if (isset($error_message)): ?>
      <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div class="form-container">
      <form method="POST" action="">
        <div class="form-group">
          <label for="title">Task Title</label>
          <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description"></textarea>
        </div>
        
        <div class="form-group">
          <label for="due_date">Due Date</label>
          <input type="date" id="due_date" name="due_date" required>
        </div>
        
        <div class="form-group">
          <label for="due_time">Due Time</label>
          <input type="time" id="due_time" name="due_time" value="00:00">
        </div>
        
        <div class="form-group">
          <label for="priority">Priority</label>
          <select id="priority" name="priority">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="pending" selected>Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        
        <button type="submit" class="btn">Create Task</button>
      </form>
    </div>
  </div>
</body>
</html> 