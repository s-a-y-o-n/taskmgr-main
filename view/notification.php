<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Get tasks due today
    $stmt = $pdo->prepare("
        SELECT * FROM tasks 
        WHERE user_id = ? 
        AND DATE(due_date) = CURDATE() 
        AND status != 'completed'
        ORDER BY priority ASC, due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tasksDueToday = $stmt->fetchAll();

    // Get tasks due tomorrow
    $stmt = $pdo->prepare("
        SELECT * FROM tasks 
        WHERE user_id = ? 
        AND DATE(due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        AND status != 'completed'
        ORDER BY priority ASC, due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tasksDueTomorrow = $stmt->fetchAll();

    // Get overdue tasks
    $stmt = $pdo->prepare("
        SELECT * FROM tasks 
        WHERE user_id = ? 
        AND DATE(due_date) < CURDATE() 
        AND status != 'completed'
        ORDER BY due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $overdueTasks = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Function to format due date
function formatDueDate($due_date) {
    if (!$due_date) return 'No due date';
    return date('M d, Y', strtotime($due_date));
}

// Function to get priority color
function getPriorityColor($priority) {
    switch ($priority) {
        case 'high':
            return '#ff4d4d';
        case 'medium':
            return '#ffa64d';
        case 'low':
            return '#4dff4d';
        default:
            return '#888';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Management App - Notifications</title>
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
      margin-bottom: 8px;
    }
    
    .subtitle {
      color: #666;
      font-size: 14px;
      margin-bottom: 24px;
    }
    
    .notification-section {
      margin-bottom: 32px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 500;
      color: #333;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
    }
    
    .section-title svg {
      margin-right: 8px;
      color: #666;
    }
    
    .notification-list {
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }
    
    .notification-item {
      padding: 16px;
      border-bottom: 1px solid #eee;
      display: flex;
      align-items: flex-start;
    }
    
    .notification-item:last-child {
      border-bottom: none;
    }
    
    .notification-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 16px;
      flex-shrink: 0;
    }
    
    .notification-content {
      flex: 1;
    }
    
    .notification-title {
      font-weight: 500;
      margin-bottom: 4px;
      color: #333;
    }
    
    .notification-meta {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 12px;
      color: #666;
    }
    
    .priority-badge {
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .empty-state {
      text-align: center;
      padding: 32px;
      color: #666;
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
    <a href="home.php" class="sidebar-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
      </svg>
      Home
    </a>
    
    <a href="mywork.php" class="sidebar-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
        <rect x="3" y="14" width="7" height="7"></rect>
      </svg>
      My Work
    </a>
    
    <a href="notification.php" class="sidebar-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
      Notifications
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
      <a href="create_task.php" class="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Create Task
      </a>
    </div>
  </div>
  
  <div class="main-content">
    <h1>Notifications</h1>
    <div class="subtitle">Stay updated with your tasks</div>
    
    <!-- Overdue Tasks Section -->
    <div class="notification-section">
      <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Overdue Tasks
      </div>
      <div class="notification-list">
        <?php if (empty($overdueTasks)): ?>
          <div class="empty-state">No overdue tasks</div>
        <?php else: ?>
          <?php foreach ($overdueTasks as $task): ?>
            <div class="notification-item">
              <div class="notification-icon" style="background-color: #fee2e2;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
              </div>
              <div class="notification-content">
                <div class="notification-title"><?php echo htmlspecialchars($task['title']); ?></div>
                <div class="notification-meta">
                  <span>Due: <?php echo formatDueDate($task['due_date']); ?></span>
                  <span class="priority-badge" style="background-color: <?php echo getPriorityColor($task['priority']); ?>20; color: <?php echo getPriorityColor($task['priority']); ?>">
                    <?php echo ucfirst($task['priority']); ?> Priority
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Tasks Due Today Section -->
    <div class="notification-section">
      <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        Due Today
      </div>
      <div class="notification-list">
        <?php if (empty($tasksDueToday)): ?>
          <div class="empty-state">No tasks due today</div>
        <?php else: ?>
          <?php foreach ($tasksDueToday as $task): ?>
            <div class="notification-item">
              <div class="notification-icon" style="background-color: #e0f2fe;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
              </div>
              <div class="notification-content">
                <div class="notification-title"><?php echo htmlspecialchars($task['title']); ?></div>
                <div class="notification-meta">
                  <span>Due: Today</span>
                  <span class="priority-badge" style="background-color: <?php echo getPriorityColor($task['priority']); ?>20; color: <?php echo getPriorityColor($task['priority']); ?>">
                    <?php echo ucfirst($task['priority']); ?> Priority
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Tasks Due Tomorrow Section -->
    <div class="notification-section">
      <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        Due Tomorrow
      </div>
      <div class="notification-list">
        <?php if (empty($tasksDueTomorrow)): ?>
          <div class="empty-state">No tasks due tomorrow</div>
        <?php else: ?>
          <?php foreach ($tasksDueTomorrow as $task): ?>
            <div class="notification-item">
              <div class="notification-icon" style="background-color: #f0fdf4;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
              </div>
              <div class="notification-content">
                <div class="notification-title"><?php echo htmlspecialchars($task['title']); ?></div>
                <div class="notification-meta">
                  <span>Due: Tomorrow</span>
                  <span class="priority-badge" style="background-color: <?php echo getPriorityColor($task['priority']); ?>20; color: <?php echo getPriorityColor($task['priority']); ?>">
                    <?php echo ucfirst($task['priority']); ?> Priority
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>

