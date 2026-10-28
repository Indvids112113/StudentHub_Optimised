<?php
session_start();
require_once 'db.php'; 
$serverMessage = "";

// Handle status or error messages redirected from auth_check.php
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'timeout') $serverMessage = "Session expired due to inactivity. Please log in again.";
    if ($_GET['error'] == 'unauthorized') $serverMessage = "Please log in to access this page.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['loginEmail']);
    $password = $_POST['loginPassword'];
    $remember = isset($_POST['rememberMe']);

    // Admin Verification (Hardcoded for master admin demo)
    if ($email === 'admin_master@studenthub.edu' && $password === 'Super$ecureAdmin99!') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = 'Administrator';
        $_SESSION['last_activity'] = time();
        header("Location: admin.php");
        exit;
    } else {
        // Student Verification
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'Active') {
                $serverMessage = "Your account is still pending admin approval.";
            } else {
                // Secure Session Initialization (Practical 10)
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'student';
                $_SESSION['name'] = $user['name'];
                $_SESSION['last_activity'] = time();

                // Intermediate Extension: Update last_login timestamp
                $updateLogin = $pdo->prepare("UPDATE students SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateLogin->execute([$user['id']]);

                // Advanced Extension: Remember Me Token
                if ($remember) {
                    $token = bin2hex(random_bytes(32)); 
                    $hashedToken = password_hash($token, PASSWORD_DEFAULT); 
                    
                    $updateToken = $pdo->prepare("UPDATE students SET remember_token = ? WHERE id = ?");
                    $updateToken->execute([$hashedToken, $user['id']]);
                    
                    setcookie("remember_me", $token, time() + (86400 * 30), "/", "", false, true);
                }

                header("Location: dashboard.php");
                exit;
            }
        } else {
            // Evaluates failed login as required by rubric post-laboratory work description
            $serverMessage = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Secure Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: GUEST</span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Account Login</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn" aria-label="Toggle navigation">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php" class="active">Login</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <section style="max-width: 400px; margin: 2rem auto; background: var(--bg-color); padding: 20px; border-radius: 8px; border: 1px solid #444;">
            <h2>Secure Sign In</h2>
            
            <?php if (!empty($serverMessage)): ?>
                <div style="padding: 1rem; border: 1px solid #ef4444; margin-bottom: 1rem; text-align: center; color: #ef4444; border-radius: 4px;">
                    <?php echo htmlspecialchars($serverMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div style="margin-bottom: 15px;">
                    <label for="loginEmail" style="display:block; margin-bottom:5px;">Email Address:</label>
                    <input type="email" id="loginEmail" name="loginEmail" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #555; background: #1f2937; color: #fff;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="loginPassword" style="display:block; margin-bottom:5px;">Password:</label>
                    <input type="password" id="loginPassword" name="loginPassword" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #555; background: #1f2937; color: #fff;">
                </div>

                <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="rememberMe" name="rememberMe">
                    <label for="rememberMe">Remember Me (30 Days)</label>
                </div>
                
                <button type="submit" style="width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Sign In</button>
            </form>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>