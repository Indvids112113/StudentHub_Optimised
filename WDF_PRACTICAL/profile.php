<?php
session_start();
require_once 'db.php';

// Verify the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// Fetch the authenticated user's details directly from MySQL
try {
    $stmt = $pdo->prepare("SELECT id, name, email, course, year, status, role, last_login, created_at FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STUDENT'; ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">☀️ Light</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Your Student Profile</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn" aria-label="Toggle navigation">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Dashboard</a></li>
                <?php else: ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                <?php endif; ?>

                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main style="max-width: 650px; margin: 2rem auto; padding: 2rem;">
        <h2>My Profile</h2>
        
        <?php if ($student): ?>
            <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.5rem; box-shadow: var(--shadow);">
                <ul style="list-style: none; padding: 0; line-height: 2.2;">
                    <li><strong>Student ID:</strong> <?php echo htmlspecialchars($student['id']); ?></li>
                    <li><strong>Full Name:</strong> <?php echo htmlspecialchars($student['name']); ?></li>
                    <li><strong>Email Address:</strong> <?php echo htmlspecialchars($student['email']); ?></li>
                    <li><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></li>
                    <li><strong>Year:</strong> Year <?php echo htmlspecialchars($student['year']); ?></li>
                    <li><strong>Role:</strong> <span class="auth-badge"><?php echo htmlspecialchars(strtoupper($student['role'] ?? 'STUDENT')); ?></span></li>
                    <li><strong>Account Status:</strong> 
                        <span class="status-badge <?php echo $student['status'] === 'Active' ? 'status-active' : 'status-pending'; ?>">
                            <?php echo htmlspecialchars($student['status']); ?>
                        </span>
                    </li>
                    <li><strong>Last Login:</strong> <?php echo htmlspecialchars($student['last_login'] ?? 'First login / Not recorded'); ?></li>
                    <li><strong>Member Since:</strong> <?php echo htmlspecialchars($student['created_at']); ?></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="error-msg" style="text-align: center; padding: 1rem; border: 1px solid #ef4444; border-radius: 4px;">
                Profile information could not be retrieved from the database.
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>