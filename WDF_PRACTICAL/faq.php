<?php
session_start();
require_once 'db.php';

$serverMessage = "";
$msgClass = "";
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isLoggedIn = isset($_SESSION['user_id']);

// 1. Admin Add FAQ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'create_faq') {
    if (!$isAdmin) {
        $serverMessage = "Unauthorized access.";
        $msgClass = "error-msg";
    } else {
        $question = trim($_POST['faq_question']);
        $answer = trim($_POST['faq_answer']);

        if (!empty($question) && !empty($answer)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
                $stmt->execute([$question, $answer]);

                // Audit Log Entry
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
                $log->execute(['Create FAQ', "Added FAQ: $question", $ip]);

                $serverMessage = "FAQ added successfully!";
                $msgClass = "success-msg";
            } catch (\PDOException $e) {
                $serverMessage = "Error adding FAQ: " . $e->getMessage();
                $msgClass = "error-msg";
            }
        } else {
            $serverMessage = "Please enter both question and answer.";
            $msgClass = "error-msg";
        }
    }
}

// 2. Admin Delete FAQ
if (isset($_GET['delete_faq'])) {
    if (!$isAdmin) {
        $serverMessage = "Unauthorized access.";
        $msgClass = "error-msg";
    } else {
        $faqId = intval($_GET['delete_faq']);
        try {
            $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
            $stmt->execute([$faqId]);

            // Audit Log Entry
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
            $log->execute(['Delete FAQ', "Deleted FAQ ID: $faqId", $ip]);

            $serverMessage = "FAQ deleted successfully.";
            $msgClass = "success-msg";
        } catch (\PDOException $e) {
            $serverMessage = "Error deleting FAQ: " . $e->getMessage();
            $msgClass = "error-msg";
        }
    }
}

// Fetch all FAQs from MySQL
try {
    $stmt = $pdo->query("SELECT * FROM faqs ORDER BY created_at DESC");
    $faqs = $stmt->fetchAll();
} catch (\PDOException $e) {
    $faqs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - FAQ</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .faq-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .faq-question-bar {
            padding: 1.2rem 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            user-select: none;
        }
        .faq-question-bar:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .faq-answer {
            padding: 0 1.5rem 1.2rem 1.5rem;
            color: var(--text-dark);
            line-height: 1.7;
            display: none;
            border-top: 1px solid var(--border-color);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }
        .faq-answer.show {
            display: block;
        }
        .faq-toggle-icon {
            font-size: 1.1rem;
            color: var(--primary-color);
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
        <p>Frequently Asked Questions</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php" class="active">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>

                <?php if ($isLoggedIn): ?>
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

    <main style="max-width: 850px; margin: 2rem auto; padding: 1.5rem;">
        <h2>Frequently Asked Questions</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);">
                <?php echo htmlspecialchars($serverMessage); ?>
            </div>
        <?php endif; ?>

        <!-- ADMIN CONTROLS: ADD NEW FAQ FORM -->
        <?php if ($isAdmin): ?>
            <section style="margin-bottom: 2.5rem; background: var(--card-bg); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: var(--border-radius);">
                <h3 style="margin-top: 0; color: var(--primary-color);">Admin: Add New FAQ</h3>
                <form method="POST" action="faq.php" style="max-width: 100%;">
                    <input type="hidden" name="action_type" value="create_faq">
                    
                    <div style="margin-bottom: 1rem;">
                        <label>Question:</label>
                        <input type="text" name="faq_question" required placeholder="e.g. How can I reset my portal password?" class="control-input" style="width: 100%;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label>Answer:</label>
                        <textarea name="faq_answer" rows="3" required placeholder="Type the explanation here..." class="control-input" style="width: 100%;"></textarea>
                    </div>

                    <button type="submit" style="width: auto; padding: 0.5rem 1.8rem;">Add FAQ</button>
                </form>
            </section>
        <?php endif; ?>

        <!-- FAQ LIST (Visible to Guests, Students, and Admins) -->
        <section>
            <?php if (count($faqs) > 0): ?>
                <?php foreach ($faqs as $item): ?>
                    <div class="faq-item">
                        <div class="faq-question-bar" onclick="toggleFaq(this)">
                            <span><?php echo htmlspecialchars($item['question']); ?></span>
                            <span class="faq-toggle-icon">+</span>
                        </div>
                        <div class="faq-answer">
                            <p style="margin: 0;"><?php echo htmlspecialchars($item['answer']); ?></p>
                            
                            <?php if ($isAdmin): ?>
                                <div style="margin-top: 1rem; text-align: right;">
                                    <a href="faq.php?delete_faq=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure you want to delete this FAQ?');" style="color: #ef4444; font-size: 0.85rem; text-decoration: none; font-weight: bold;">
                                        🗑️ Delete FAQ
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">No FAQs available at the moment.</div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
    <script>
        function toggleFaq(el) {
            const answer = el.nextElementSibling;
            const icon = el.querySelector('.faq-toggle-icon');
            const isOpen = answer.classList.contains('show');

            // Close other items
            document.querySelectorAll('.faq-answer').forEach(ans => ans.classList.remove('show'));
            document.querySelectorAll('.faq-toggle-icon').forEach(ic => ic.textContent = '+');

            if (!isOpen) {
                answer.classList.add('show');
                icon.textContent = '−';
            }
        }
    </script>
</body>
</html>