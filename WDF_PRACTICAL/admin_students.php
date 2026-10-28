<?php
session_start();
require_once 'db.php';

// Practical 10: Strict Role-Based Access Control (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If a guest or normal student tries to access this, kick them to login
    header("Location: login.php?error=unauthorized");
    exit;
}

// Fetch all students from the database
try {
    $stmt = $pdo->query("SELECT id, name, email, course, year, status, created_at FROM students");
    $students = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error fetching students: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin View Students</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #555; padding: 12px; text-align: left; }
        th { background-color: #3b82f6; color: white; }
        tr:nth-child(even) { background-color: #1f2937; }
        /* Light mode adjustments */
        body:not(.dark-mode) th { background-color: #2563eb; }
        body:not(.dark-mode) tr:nth-child(even) { background-color: #f9fafb; }
        body:not(.dark-mode) td { border-color: #ddd; }
    </style>
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo strtoupper($_SESSION['role']); ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
    </div>

    <header>
        <h1>StudentHub Administration</h1>
        <p>Registered Student Records</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <!-- Admin-specific links -->
                <li><a href="admin_registrations.php">Event Registrations</a></li>
                <li><a href="admin_students.php" class="active">Student Directory</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <h2>All Registered Students</h2>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['id']); ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td>Year <?php echo htmlspecialchars($student['year']); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85em; background-color: <?php echo $student['status'] === 'Active' ? '#10b981' : '#f59e0b'; ?>; color: white;">
                                        <?php echo htmlspecialchars($student['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No students found in the database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>