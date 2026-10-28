<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'GUEST'; ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Your centralized student portal</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn" aria-label="Toggle navigation">☰</button>
            <ul id="navMenu">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
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

    <main>
        <section style="text-align: center; padding: 3rem 1rem;">
            <h2>Welcome to StudentHub</h2>
            <p style="font-size: 1.1rem; margin-top: 1rem; color: #aaa;">Your secure portal for campus events, student directories, and academic tracking.</p>
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                <div style="margin-top: 2rem;">
                    <a href="login.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; margin-right: 10px;">Login</a>
                    <a href="register.php" style="background: #4b5563; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">Register</a>
                </div>
            <?php else: ?>
                <div style="margin-top: 2rem;">
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <a href="admin.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">Go to Admin Panel</a>
                    <?php else: ?>
                        <a href="dashboard.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>