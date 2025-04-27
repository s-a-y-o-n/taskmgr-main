<!DOCTYPE html>
<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch tasks for the current user, sorted by priority and due date
$stmt = $pdo->prepare("
    SELECT * FROM tasks 
    WHERE user_id = ? 
    ORDER BY 
        CASE priority
            WHEN 'high' THEN 1
            WHEN 'medium' THEN 2
            WHEN 'low' THEN 3
        END,
        CASE 
            WHEN due_date IS NULL THEN 1
            ELSE 0
        END,
        due_date ASC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

// Function to format due date
function formatDueDate($due_date) {
    if (!$due_date) return 'No due date';
    
    $due = new DateTime($due_date);
    $now = new DateTime();
    $diff = $now->diff($due);
    
    if ($diff->days == 0) return 'Due Today';
    if ($diff->days == 1) return 'Due Tomorrow';
    if ($diff->days < 7) return 'Due in ' . $diff->days . ' days';
    return 'Due ' . $due->format('M d, Y');
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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Management App - My Work</title>
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
    
    .search-bar {
      width: 100%;
      padding: 12px;
      background-color: #f2f2f2;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      margin-bottom: 20px;
      color: #333;
    }
    
    .search-bar::placeholder {
      color: #888;
    }
    
    .task-container {
      margin-top: 20px;
    }
    
    .task-item {
      display: flex;
      align-items: flex-start;
      padding: 16px;
      border-bottom: 1px solid #eee;
      background-color: white;
      margin-bottom: 8px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .priority-indicator {
      width: 4px;
      height: 100%;
      border-radius: 2px;
      margin-right: 12px;
    }
    
    .task-status {
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 12px;
      margin-left: 8px;
      text-transform: capitalize;
    }
    
    .status-pending {
      background-color: #f0f0f0;
      color: #666;
    }
    
    .status-in_progress {
      background-color: #e6f3ff;
      color: #0066cc;
    }
    
    .status-completed {
      background-color: #e6ffe6;
      color: #008000;
    }
    
    .task-meta {
      display: flex;
      align-items: center;
      margin-top: 4px;
      font-size: 12px;
      color: #666;
    }
    
    .task-description {
      font-size: 14px;
      color: #666;
      margin-top: 4px;
    }
    
    .task-actions {
      display: flex;
      gap: 8px;
      margin-left: auto;
    }
    
    .action-button {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: background-color 0.2s;
    }
    
    .edit-button {
      background-color: #e6f3ff;
      color: #0066cc;
    }
    
    .edit-button:hover {
      background-color: #cce6ff;
    }
    
    .delete-button {
      background-color: #ffe6e6;
      color: #cc0000;
    }
    
    .delete-button:hover {
      background-color: #ffcccc;
    }
    
    .action-button svg {
      width: 14px;
      height: 14px;
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
    
    <a href="http://localhost/taskmgr-main/view/mywork.php" class="sidebar-item active">
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
    
    <div class="bottom-buttons">
      <div class="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Create
      </div>
    </div>
  </div>
  
  <div class="main-content">
    <h1>My Work</h1>
    <div class="subtitle">Manage your tasks efficiently</div>
    
    <input type="text" class="search-bar" id="searchInput" placeholder="Search for tasks...">
    
    <div class="task-container" id="taskContainer">
      <?php if (empty($tasks)): ?>
        <div class="task-item">
          <div class="task-details">
            <div class="task-title">No tasks found</div>
            <div class="task-description">Create a new task to get started</div>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($tasks as $task): ?>
          <div class="task-item" data-title="<?php echo htmlspecialchars(strtolower($task['title'])); ?>" 
               data-description="<?php echo htmlspecialchars(strtolower($task['description'] ?? '')); ?>"
               data-status="<?php echo htmlspecialchars($task['status']); ?>"
               data-priority="<?php echo htmlspecialchars($task['priority']); ?>">
            <div class="priority-indicator" style="background-color: <?php echo getPriorityColor($task['priority']); ?>"></div>
            <div class="task-details">
              <div class="task-title">
                <?php echo htmlspecialchars($task['title']); ?>
                <span class="task-status status-<?php echo $task['status']; ?>">
                  <?php echo str_replace('_', ' ', $task['status']); ?>
                </span>
              </div>
              <?php if ($task['description']): ?>
                <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
              <?php endif; ?>
              <div class="task-meta">
                <span class="task-due"><?php echo formatDueDate($task['due_date']); ?></span>
                <span style="margin: 0 8px">•</span>
                <span class="task-priority" style="color: <?php echo getPriorityColor($task['priority']); ?>">
                  <?php echo ucfirst($task['priority']); ?> Priority
                </span>
                <div class="task-actions">
                  <button class="action-button edit-button" onclick="editTask(<?php echo $task['id']; ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit
                  </button>
                  <button class="action-button delete-button" onclick="deleteTask(<?php echo $task['id']; ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
<script>
function editTask(taskId) {
    window.location.href = `edit_task.php?id=${taskId}`;
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch(`../controllers/delete_task.php?id=${taskId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error deleting task: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting task. Please try again.');
        });
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const taskItems = document.querySelectorAll('.task-item');
    let hasVisibleTasks = false;

    taskItems.forEach(item => {
        const title = item.getAttribute('data-title');
        const description = item.getAttribute('data-description');
        const status = item.getAttribute('data-status');
        const priority = item.getAttribute('data-priority');

        // Check if the search term matches any of the task attributes
        const matchesSearch = 
            title.includes(searchTerm) || 
            description.includes(searchTerm) ||
            status.includes(searchTerm) ||
            priority.includes(searchTerm);

        // Show/hide the task based on the search result
        item.style.display = matchesSearch ? 'flex' : 'none';
        if (matchesSearch) hasVisibleTasks = true;
    });

    // Show "No tasks found" message if no tasks match the search
    const noTasksMessage = document.querySelector('.task-item .task-title');
    if (noTasksMessage && noTasksMessage.textContent === 'No tasks found') {
        noTasksMessage.parentElement.parentElement.style.display = hasVisibleTasks ? 'none' : 'flex';
    }
});
</script>
</html>