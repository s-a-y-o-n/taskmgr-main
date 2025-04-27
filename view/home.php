<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get user data and task statistics
try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Get tasks due today
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_count FROM tasks WHERE user_id = ? AND DATE(due_date) = CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $tasksToday = $stmt->fetch()['today_count'];

    // Get tasks in progress
    $stmt = $pdo->prepare("SELECT COUNT(*) as progress_count FROM tasks WHERE user_id = ? AND status = 'in_progress'");
    $stmt->execute([$_SESSION['user_id']]);
    $tasksInProgress = $stmt->fetch()['progress_count'];

    // Get completed tasks this week
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_count FROM tasks WHERE user_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)");
    $stmt->execute([$_SESSION['user_id']]);
    $completedThisWeek = $stmt->fetch()['completed_count'];

    // Get tasks organized by priority and due date
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND status != 'completed' ORDER BY 
                          CASE 
                            WHEN priority = 'high' THEN 1
                            WHEN priority = 'medium' THEN 2
                            WHEN priority = 'low' THEN 3
                          END,
                          due_date ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll();

    // Get completed tasks
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND status = 'completed' ORDER BY updated_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $completedTasks = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Management App - Home</title>
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
    
    .task-container {
      margin-top: 20px;
    }
    
    .task-item {
      cursor: pointer;
      transition: background-color 0.2s;
      display: flex;
      align-items: flex-start;
      padding: 16px;
      border-bottom: 1px solid #eee;
      background-color: white;
      margin-bottom: 8px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .task-item:hover {
      background-color: #f8f8f8;
    }
    
    .task-checkbox {
      width: 20px;
      height: 20px;
      border: 2px solid #ddd;
      border-radius: 4px;
      margin-right: 16px;
      cursor: pointer;
      margin-top: 2px;
      position: relative;
      flex-shrink: 0;
    }
    
    .task-checkbox.checked {
      background-color: #4CAF50;
      border-color: #4CAF50;
    }
    
    .task-checkbox.checked::after {
      content: '';
      position: absolute;
      left: 6px;
      top: 2px;
      width: 5px;
      height: 10px;
      border: solid white;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
    
    .task-content {
      flex: 1;
      cursor: pointer;
    }
    
    .task-details {
      flex: 1;
    }
    
    .task-title {
      font-size: 16px;
      font-weight: 500;
      color: #333;
      margin-bottom: 4px;
    }
    
    .task-due {
      font-size: 14px;
      color: #888;
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
    
    .content-card {
      background-color: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      margin-bottom: 24px;
    }
    
    .card-title {
      font-size: 18px;
      font-weight: 500;
      margin-bottom: 16px;
      color: #222;
    }
    
    .welcome-header {
      display: flex;
      align-items: center;
      margin-bottom: 32px;
    }
    
    .welcome-header .avatar {
      width: 64px;
      height: 64px;
      font-size: 24px;
      margin-right: 24px;
      background-color: #e0e0e0;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
      color: #666;
    }
    
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }
    
    .stat-card {
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }
    
    .stat-title {
      color: #666;
      font-size: 14px;
      margin-bottom: 8px;
    }
    
    .stat-value {
      font-size: 24px;
      font-weight: 600;
      color: #222;
    }
    
    .recent-section {
      margin-top: 32px;
    }
    
    h2 {
      font-size: 20px;
      font-weight: 500;
      color: #333;
      margin-bottom: 16px;
    }
    
    .priority-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
      margin-left: 8px;
    }
    
    .priority-high {
      background-color: #ffebee;
      color: #e53935;
    }
    
    .priority-medium {
      background-color: #fff8e1;
      color: #ffa000;
    }
    
    .priority-low {
      background-color: #e8f5e9;
      color: #43a047;
    }
    
    .task-meta {
      display: flex;
      align-items: center;
      margin-top: 4px;
    }
    
    .task-status {
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 4px;
      margin-right: 8px;
    }
    
    .status-pending {
      background-color: #e3f2fd;
      color: #1976d2;
    }
    
    .status-in-progress {
      background-color: #e8f5e9;
      color: #43a047;
    }
    
    .status-completed {
      background-color: #f5f5f5;
      color: #757575;
    }
    
    .empty-state {
      text-align: center;
      padding: 32px 0;
      color: #888;
    }
    
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }
    
    .modal-content {
      background-color: white;
      border-radius: 12px;
      padding: 32px;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
    }
    
    .close-modal {
      position: absolute;
      top: 16px;
      right: 16px;
      font-size: 24px;
      cursor: pointer;
      color: #666;
      background: none;
      border: none;
      padding: 4px;
    }
    
    .close-modal:hover {
      color: #333;
    }
    
    .modal-title {
      font-size: 20px;
      font-weight: 600;
      color: #222;
      margin-bottom: 16px;
      padding-right: 32px;
    }
    
    .modal-description {
      color: #666;
      font-size: 16px;
      line-height: 1.5;
      margin-bottom: 24px;
      white-space: pre-wrap;
    }
    
    .modal-meta {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
      font-size: 14px;
      color: #666;
    }
    
    .modal-meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .status-button {
      display: flex;
      align-items: center;
      gap: 8px;
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.2s;
    }
    
    .status-button:hover {
      background-color: #45a049;
    }
    
    .status-button.pending-button {
      background-color: #ff9800;
    }
    
    .status-button.pending-button:hover {
      background-color: #f57c00;
    }
    
    .button-group {
      display: flex;
      gap: 8px;
      margin-top: 16px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <a href="http://localhost/taskmgr-main/view/home.php" class="sidebar-item active">
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
      <a href="http://localhost/taskmgr-main/view/create_task.php" class="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Create
      </a>
    </div>
  </div>
  
  <div class="main-content">
    <div class="welcome-header">
      <div class="avatar">
        <?php 
        // Safely get the first letter of the user's name
        echo isset($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : '?'; 
        ?>
      </div>
      <div>
        <h1>Welcome back, <?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'User'; ?></h1>
        <div class="subtitle">Here's what's happening today</div>
      </div>
    </div>
    
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-title">Tasks Due Today</div>
        <div class="stat-value"><?php echo $tasksToday; ?></div>
      </div>
      
      <div class="stat-card">
        <div class="stat-title">Tasks In Progress</div>
        <div class="stat-value"><?php echo $tasksInProgress; ?></div>
      </div>
      
      <div class="stat-card">
        <div class="stat-title">Completed This Week</div>
        <div class="stat-value"><?php echo $completedThisWeek; ?></div>
      </div>
    </div>
    
    <div class="content-card">
      <h2 class="card-title">Your Tasks</h2>
      <div class="task-container">
        <?php if (empty($tasks)): ?>
          <div class="task-item">
            <div class="task-details">
              <div class="task-title">No tasks found</div>
              <div class="task-due">Create a new task to get started</div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($tasks as $task): ?>
            <div class="task-item">
              <div class="task-checkbox <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?>" 
                   onclick="event.stopPropagation(); toggleTaskStatus(<?php echo $task['id']; ?>, this)">
              </div>
              <div class="task-content" onclick="showTaskDetails(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                <div class="task-details">
                  <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                  <div class="task-due">Due: <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="recent-section">
      <h2>Recently Completed</h2>
      <div class="content-card">
        <div class="task-container">
          <?php if (empty($completedTasks)): ?>
            <div class="empty-state">No completed tasks yet.</div>
          <?php else: ?>
            <?php foreach ($completedTasks as $task): ?>
              <div class="task-item">
                <div class="checkbox"></div>
                <div class="task-details">
                  <div class="task-title">
                    <?php echo htmlspecialchars($task['title']); ?>
                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                      <?php echo ucfirst($task['priority']); ?>
                    </span>
                  </div>
                  <div class="task-meta">
                    <span class="task-status status-completed">Completed</span>
                    
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Task Details Modal -->
  <div id="taskModal" class="modal">
    <div class="modal-content">
      <button class="close-modal" onclick="closeTaskModal()">&times;</button>
      <h2 class="modal-title" id="modalTaskTitle"></h2>
      <div class="modal-meta">
        <div class="modal-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span id="modalTaskDue"></span>
        </div>
        <div class="modal-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
          </svg>
          <span id="modalTaskPriority"></span>
        </div>
        <div class="modal-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          <span id="modalTaskStatus"></span>
        </div>
      </div>
      <div class="modal-description" id="modalTaskDescription"></div>
      <div class="button-group">
        <button onclick="changeTaskStatus()" class="button status-button" id="modalStatusButton">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          <span id="modalStatusButtonText">Mark as Completed</span>
        </button>
        <a href="#" id="modalEditButton" class="button save-button">Edit Task</a>
        <button onclick="closeTaskModal()" class="button cancel-button">Close</button>
      </div>
    </div>
  </div>
  
  <script>
    let currentTaskId = null;
    let currentTaskStatus = null;

    function showTaskDetails(task) {
      currentTaskId = task.id;
      currentTaskStatus = task.status;
      const modal = document.getElementById('taskModal');
      const title = document.getElementById('modalTaskTitle');
      const description = document.getElementById('modalTaskDescription');
      const due = document.getElementById('modalTaskDue');
      const priority = document.getElementById('modalTaskPriority');
      const status = document.getElementById('modalTaskStatus');
      const editButton = document.getElementById('modalEditButton');
      const statusButton = document.getElementById('modalStatusButton');
      const statusButtonText = document.getElementById('modalStatusButtonText');
      
      title.textContent = task.title;
      description.textContent = task.description || 'No description provided';
      due.textContent = task.due_date ? `Due: ${new Date(task.due_date).toLocaleDateString()}` : 'No due date';
      priority.textContent = `${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)} Priority`;
      status.textContent = task.status.replace('_', ' ').charAt(0).toUpperCase() + task.status.slice(1);
      editButton.href = `edit_task.php?id=${task.id}`;
      
      // Update status button text and style based on current status
      if (task.status === 'completed') {
        statusButtonText.textContent = 'Mark as Pending';
        statusButton.classList.add('pending-button');
        statusButton.classList.remove('completed-button');
      } else {
        statusButtonText.textContent = 'Mark as Completed';
        statusButton.classList.add('completed-button');
        statusButton.classList.remove('pending-button');
      }
      
      modal.style.display = 'flex';
    }
    
    function changeTaskStatus() {
      if (!currentTaskId) return;
      
      const newStatus = currentTaskStatus === 'completed' ? 'pending' : 'completed';
      const statusButton = document.getElementById('modalStatusButton');
      const statusButtonText = document.getElementById('modalStatusButtonText');
      
      fetch('../controllers/update_task_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          task_id: currentTaskId,
          status: newStatus
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          currentTaskStatus = newStatus;
          if (newStatus === 'completed') {
            statusButtonText.textContent = 'Mark as Pending';
            statusButton.classList.add('pending-button');
            statusButton.classList.remove('completed-button');
          } else {
            statusButtonText.textContent = 'Mark as Completed';
            statusButton.classList.add('completed-button');
            statusButton.classList.remove('pending-button');
          }
          // Refresh the page to update the task lists
          window.location.reload();
        } else {
          alert('Error updating task status: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating task status. Please try again.');
      });
    }
    
    function closeTaskModal() {
      const modal = document.getElementById('taskModal');
      modal.style.display = 'none';
    }
    
    function toggleTaskStatus(taskId, checkbox) {
      const isCompleted = checkbox.classList.contains('checked');
      const newStatus = isCompleted ? 'pending' : 'completed';
      
      fetch('../controllers/update_task_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          task_id: taskId,
          status: newStatus
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          checkbox.classList.toggle('checked');
          // Move the task to completed list or remove it from the current list
          const taskItem = checkbox.closest('.task-item');
          if (newStatus === 'completed') {
            taskItem.style.opacity = '0';
            setTimeout(() => {
              taskItem.remove();
              // Refresh the page to update the completed tasks list
              window.location.reload();
            }, 300);
          }
        } else {
          alert('Error updating task status: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating task status. Please try again.');
      });
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('taskModal');
      if (event.target === modal) {
        closeTaskModal();
      }
    }
  </script>
</body>
</html>

<?php
// Helper function for time ago format
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            return "just now";
        }
        return $diff->h . " hours ago";
    }
    if ($diff->d == 1) {
        return "yesterday";
    }
    return $diff->d . " days ago";
}
?>