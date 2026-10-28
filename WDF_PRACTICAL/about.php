<?php
session_start();
require_once 'db.php';

$serverMessage = "";
$msgClass = "";
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Admin Content Update Handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_about'])) {
    if (!$isAdmin) {
        $serverMessage = "Unauthorized: Only administrators can modify this page.";
        $msgClass = "error-msg";
    } else {
        $title = trim($_POST['page_title']);
        $content = trim($_POST['page_content']);

        try {
            $stmt = $pdo->prepare("INSERT INTO site_content (id, title, content) VALUES ('about_page', ?, ?) ON DUPLICATE KEY UPDATE title = ?, content = ?");
            $stmt->execute([$title, $content, $title, $content]);

            // Audit Log Entry
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
            $log->execute(['Update About Page', 'Admin modified About page text.', $ip]);

            $serverMessage = "About page content updated successfully!";
            $msgClass = "success-msg";
        } catch (\PDOException $e) {
            $serverMessage = "Error updating content: " . $e->getMessage();
            $msgClass = "error-msg";
        }
    }
}

// Fetch About page content
try {
    $stmt = $pdo->query("SELECT title, content, updated_at FROM site_content WHERE id = 'about_page' LIMIT 1");
    $pageData = $stmt->fetch();
    if (!$pageData) {
        $pageData = [
            'title' => 'About StudentHub',
            'content' => 'StudentHub is a centralized academic and event management portal built for students and faculty.',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
} catch (\PDOException $e) {
    $pageData = [
        'title' => 'About StudentHub',
        'content' => 'Content currently unavailable.',
        'updated_at' => date('Y-m-d H:i:s')
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - About Us</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .edit-container {
            display: none;
            margin-top: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
        }
    </style>
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'GUEST'; ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">☀️ Light</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Campus Portal Information</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php" class="active">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($isAdmin): ?>
                        <li><a href="admin.php">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main style="max-width: 800px; margin: 2rem auto; padding: 1.5rem;">
        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);">
                <?php echo htmlspecialchars($serverMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Content Display Area (Visible to Guests, Students, and Admins) -->
        <article style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 2rem; box-shadow: var(--shadow);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <h2 style="margin: 0; color: var(--primary-color);"><?php echo htmlspecialchars($pageData['title']); ?></h2>
                
                <?php if ($isAdmin): ?>
                    <button type="button" onclick="toggleEditPanel()" style="width: auto; padding: 0.4rem 1rem; background: #3b82f6; font-size: 0.85rem;">
                        ✏️ Edit Content
                    </button>
                <?php endif; ?>
            </div>

            <p style="line-height: 1.8; font-size: 1.05rem; white-space: pre-line; color: var(--text-dark);">
                <?php echo htmlspecialchars($pageData['content']); ?>
            </p>

            <small style="display: block; margin-top: 1.5rem; color: #64748b;">
                Last updated: <?php echo htmlspecialchars($pageData['updated_at']); ?>
            </small>
        </article>

        <!-- Admin In-Place Editing Panel (Visible Only to Admin) -->
        <?php if ($isAdmin): ?>
            <div id="adminEditBox" class="edit-container">
                <h3 style="margin-top: 0; color: var(--primary-color);">Edit Page Content</h3>
                <form method="POST" action="about.php" style="max-width: 100%;">
                    <input type="hidden" name="update_about" value="1">
                    
                    <div style="margin-bottom: 1rem;">
                        <label>Header Title:</label>
                        <input type="text" name="page_title" required class="control-input" style="width: 100%;" value="<?php echo htmlspecialchars($pageData['title']); ?>">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label>About Us Description:</label>
                        <textarea name="page_content" rows="6" required class="control-input" style="width: 100%;"><?php echo htmlspecialchars($pageData['content']); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="toggleEditPanel()" style="background: #64748b; width: auto; padding: 0.5rem 1rem;">Cancel</button>
                        <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Save Changes</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
    <script>
        function toggleEditPanel() {
            var panel = document.getElementById('adminEditBox');
            if (panel) {
                panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
            }
        }
    </script>
</body>
</html>