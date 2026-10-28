<?php
session_start();
require_once 'db.php';

// Role-Based Access Control (Admin Only)[cite: 2]
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

$serverMessage = "";
$msgClass = "";

// 1. One-click status toggle / approve
if (isset($_GET['toggle_status']) && isset($_GET['current_status'])) {
    $targetId = $_GET['toggle_status'];
    $currentStatus = $_GET['current_status'];
    
    $newStatus = ($currentStatus === 'Pending') ? 'Active' : (($currentStatus === 'Active') ? 'Suspended' : 'Active');
    
    try {
        $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $targetId]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
        $log->execute(['Status Update', "Changed student ($targetId) status from $currentStatus to $newStatus", $ip]);

        $serverMessage = "Status for student $targetId updated to $newStatus successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error updating status: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// 2. Direct student edit submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'student_update') {
    $id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);
    $year = intval($_POST['year']);
    $status = trim($_POST['status']);

    try {
        $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, course = ?, year = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $email, $course, $year, $status, $id]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
        $log->execute(['Update Student Info', "Updated records for student ID: $id", $ip]);

        $serverMessage = "Student record updated successfully!";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// 3. Delete student[cite: 3]
if (isset($_GET['delete_student'])) {
    $delId = $_GET['delete_student'];
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$delId]);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
        $log->execute(['Delete Student', "Deleted student ID: $delId", $ip]);

        $serverMessage = "Student record deleted successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error deleting student: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// 4. Admin Post New Campus Event (with Max Participants)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'create_event') {
    $title = trim($_POST['title']);
    $event_date = trim($_POST['event_date']);
    $location = trim($_POST['location']);
    $category = trim($_POST['category']);
    $max_participants = intval($_POST['max_participants']);
    $description = trim($_POST['description']);

    if ($max_participants <= 0) {
        $max_participants = 50;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, event_date, location, category, max_participants, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $event_date, $location, $category, $max_participants, $description]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
        $log->execute(['Create Event', "Created event: $title (Cap: $max_participants)", $ip]);

        $serverMessage = "Event '$title' created successfully!";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error creating event: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// 5. Delete Event
if (isset($_GET['delete_event'])) {
    $delEventId = $_GET['delete_event'];
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$delEventId]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
        $log->execute(['Delete Event', "Deleted event ID: $delEventId", $ip]);

        $serverMessage = "Event deleted successfully.";
        $msgClass = "success-msg";
    } catch (\PDOException $e) {
        $serverMessage = "Error deleting event: " . $e->getMessage();
        $msgClass = "error-msg";
    }
}

// Dashboard Aggregates[cite: 23, 24]
try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalRegistrations = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();
} catch (\PDOException $e) {
    $totalStudents = 0; $totalEvents = 0; $totalRegistrations = 0;
}

// Audit Logs[cite: 23, 24]
try {
    $auditLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (\PDOException $e) {
    $auditLogs = [];
}

// Student Directory & Search Logic[cite: 3]
$searchStudent = $_GET['search_student'] ?? '';
$statusFilter = $_GET['status_filter'] ?? 'All';

$query = "SELECT id, name, email, course, year, status FROM students WHERE (name LIKE ? OR id LIKE ?)";
$params = ["%$searchStudent%", "%$searchStudent%"];

if ($statusFilter !== 'All') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmtStudents = $pdo->prepare($query);
$stmtStudents->execute($params);
$students = $stmtStudents->fetchAll();

// Fetch Events List with Registered Counts for Admin Control
try {
    $adminEvents = $pdo->query("
        SELECT e.*, COUNT(r.id) AS registered_count 
        FROM events e 
        LEFT JOIN event_registrations r ON e.id = r.event_id 
        GROUP BY e.id 
        ORDER BY e.event_date ASC
    ")->fetchAll();
} catch (\PDOException $e) {
    $adminEvents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .edit-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .edit-modal-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            max-width: 500px;
            width: 90%;
        }
        .btn-status-toggle {
            text-decoration: none;
            font-size: 0.78rem;
            padding: 3px 8px;
            border-radius: 4px;
            background: #3b82f6;
            color: #fff;
            margin-left: 6px;
            font-weight: normal;
        }
    </style>
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: ADMIN</span>
        <button id="themeToggleBtn" class="theme-toggle-btn">☀️ Light</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Admin Dashboard & Management</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="admin.php" class="active">Admin Dashboard</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h2>System Statistics & Monitoring</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);"><?php echo htmlspecialchars($serverMessage); ?></div>
        <?php endif; ?>

        <!-- Aggregation Cards[cite: 23, 24] -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); text-align: center;">
                <h3 style="color: var(--primary-color); font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalStudents; ?></h3>
                <p style="font-weight: 600;">Total Registered Students</p>
            </div>
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); text-align: center;">
                <h3 style="color: #10b981; font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalEvents; ?></h3>
                <p style="font-weight: 600;">Active Campus Events</p>
            </div>
            <div style="background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); text-align: center;">
                <h3 style="color: #f59e0b; font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $totalRegistrations; ?></h3>
                <p style="font-weight: 600;">Total Event Signups</p>
            </div>
        </div>

        <!-- Student Management Table[cite: 3] -->
        <section style="margin-bottom: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Student Directory & Management</h3>
                <a href="export_students.php" style="background: #10b981; color: white; padding: 0.4rem 0.8rem; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: bold;">Export CSV</a>
            </div>

            <form method="GET" action="admin.php" class="data-controls">
                <input type="text" name="search_student" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($searchStudent); ?>" class="control-input">
                
                <select name="status_filter" class="control-select">
                    <option value="All" <?php if($statusFilter=='All') echo 'selected'; ?>>All Statuses</option>
                    <option value="Active" <?php if($statusFilter=='Active') echo 'selected'; ?>>Active</option>
                    <option value="Pending" <?php if($statusFilter=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Suspended" <?php if($statusFilter=='Suspended') echo 'selected'; ?>>Suspended</option>
                </select>

                <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Filter</button>
            </form>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Status & Fast Action</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach($students as $st): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($st['id']); ?></td>
                                    <td><?php echo htmlspecialchars($st['name']); ?></td>
                                    <td><?php echo htmlspecialchars($st['email']); ?></td>
                                    <td><?php echo htmlspecialchars($st['course']); ?></td>
                                    <td>Year <?php echo htmlspecialchars($st['year']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $st['status'] === 'Active' ? 'status-active' : ($st['status'] === 'Pending' ? 'status-pending' : 'status-suspended'); ?>">
                                            <?php echo htmlspecialchars($st['status']); ?>
                                        </span>
                                        <a href="admin.php?toggle_status=<?php echo urlencode($st['id']); ?>&current_status=<?php echo urlencode($st['status']); ?>" class="btn-status-toggle">
                                            <?php echo $st['status'] === 'Pending' ? 'Approve' : 'Toggle'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <button type="button" onclick='openEditModal(<?php echo json_encode($st); ?>)' style="background: #3b82f6; color: #fff; border:none; padding: 4px 10px; border-radius:4px; cursor:pointer; width:auto; margin-right:6px; font-size: 0.85rem;">Edit</button>
                                        <a href="admin.php?delete_student=<?php echo urlencode($st['id']); ?>" onclick="return confirm('Delete this record?');" style="color: #ef4444; text-decoration: none; font-weight: bold; font-size: 0.85rem;">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center;">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Admin Event Creator Section (with Participation Limits) -->
        <section style="margin-bottom: 3rem;">
            <h3>Create Campus Event</h3>
            <form method="POST" action="admin.php" style="max-width: 100%;">
                <input type="hidden" name="action_type" value="create_event">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label>Event Title:</label>
                        <input type="text" name="title" required class="control-input" style="width: 100%;">
                    </div>
                    <div>
                        <label>Date:</label>
                        <input type="date" name="event_date" required class="control-input" style="width: 100%;">
                    </div>
                    <div>
                        <label>Location:</label>
                        <input type="text" name="location" required class="control-input" style="width: 100%;">
                    </div>
                    <div>
                        <label>Category:</label>
                        <select name="category" required class="control-select" style="width: 100%;">
                            <option value="Tech">Tech</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Sports">Sports</option>
                        </select>
                    </div>
                    <div>
                        <label>Max Capacity:</label>
                        <input type="number" name="max_participants" min="1" value="50" required class="control-input" style="width: 100%;">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Event Description:</label>
                    <textarea name="description" rows="3" required class="control-input" style="width: 100%;"></textarea>
                </div>
                <button type="submit" style="width: auto; padding: 0.6rem 2rem;">Post Event</button>
            </form>

            <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--primary-color);">Manage Existing Events</h4>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Enrollment Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($adminEvents) > 0): ?>
                            <?php foreach ($adminEvents as $ev): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ev['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['location']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['category']); ?></td>
                                    <td>
                                        <strong><?php echo $ev['registered_count']; ?></strong> / <?php echo $ev['max_participants'] ?? 50; ?>
                                        <?php if ($ev['registered_count'] >= ($ev['max_participants'] ?? 50)): ?>
                                            <span style="color: #ef4444; font-weight: bold; margin-left: 5px;">(Full)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="admin.php?delete_event=<?php echo $ev['id']; ?>" onclick="return confirm('Delete this event?');" style="color: #ef4444; text-decoration: none; font-weight: bold; font-size: 0.85rem;">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center;">No campus events created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Audit Trail Logs[cite: 23, 24] -->
        <section>
            <h3>Security & Audit Trail Logs</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Action Performed</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($auditLogs) > 0): ?>
                            <?php foreach($auditLogs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">No audit logs recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Inline Edit Modal -->
    <div id="studentEditModal" class="edit-modal">
        <div class="edit-modal-box">
            <h3 style="margin-top: 0; color: var(--primary-color);">Update Student Record</h3>
            <form method="POST" action="admin.php" style="max-width: 100%;">
                <input type="hidden" name="action_type" value="student_update">
                <input type="hidden" name="student_id" id="modalStudentId">

                <div style="margin-bottom: 12px;">
                    <label>Full Name:</label>
                    <input type="text" name="name" id="modalName" required class="control-input" style="width:100%;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Email Address:</label>
                    <input type="email" name="email" id="modalEmail" required class="control-input" style="width:100%;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Course:</label>
                    <select name="course" id="modalCourse" required class="control-select" style="width:100%;">
                        <option value="BTech CSE">B.Tech CSE</option>
                        <option value="BTech IT">B.Tech IT</option>
                        <option value="BTech CE">B.Tech CE</option>
                    </select>
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Year:</label>
                    <select name="year" id="modalYear" required class="control-select" style="width:100%;">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div style="margin-bottom: 18px;">
                    <label>Status:</label>
                    <select name="status" id="modalStatus" required class="control-select" style="width:100%;">
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" style="background: #64748b; width: auto; padding: 0.5rem 1rem;">Cancel</button>
                    <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
    <script>
        function openEditModal(st) {
            document.getElementById('modalStudentId').value = st.id;
            document.getElementById('modalName').value = st.name;
            document.getElementById('modalEmail').value = st.email;
            document.getElementById('modalCourse').value = st.course;
            document.getElementById('modalYear').value = st.year;
            document.getElementById('modalStatus').value = st.status;
            document.getElementById('studentEditModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('studentEditModal').style.display = 'none';
        }
    </script>
</body>
</html>