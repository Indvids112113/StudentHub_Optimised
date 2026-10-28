<?php
require_once 'auth_check.php'; // Secures the page, handles timeouts and remember-me token
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="top-bar-controls">
        <span class="auth-badge">Role: <?php echo strtoupper($_SESSION['role']); ?></span>
        <button id="themeToggleBtn" class="theme-toggle-btn">🌙 Dark</button>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Your centralized student portal</p>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h2>Student Dashboard</h2>
        
        <div class="dashboard-grid">
            <aside>
                <section>
                    <h3>Quick Links</h3>
                    <ul id="dashboardSidebar">
                        <li><a href="#grades" class="sidebar-link">My Grades</a></li>
                        <li><a href="#schedule" class="sidebar-link">Class Schedule</a></li>
                        <li><a href="#library" class="sidebar-link">Library Dues</a></li>
                    </ul>
                </section>
            </aside>

            <div id="dashboardContentArea">
                <section id="grades" class="dashboard-section" style="display: block;">
                    <h3>My Grades</h3>
                    <p>Semester 3 Results:</p>
                    <ul style="margin-top: 1rem; list-style-position: inside;">
                        <li>Web Development Frameworks (ITUE203): <strong>A</strong></li>
                        <li>Database Management: <strong>B+</strong></li>
                        <li>Computer Networks: <strong>A</strong></li>
                    </ul>
                </section>

                <section id="schedule" class="dashboard-section" style="display: none;">
                    <h3>Today's Classes</h3>
                    <p>You have 3 classes scheduled for today.</p>
                    <ul style="margin-top: 1rem; list-style-position: inside;">
                        <li>Web Development Frameworks - 10:00 AM</li>
                        <li>Database Management - 1:00 PM</li>
                        <li>Computer Networks - 3:00 PM</li>
                    </ul>
                </section>

                <section id="library" class="dashboard-section" style="display: none;">
                    <h3>Library Dues</h3>
                    <p>No pending dues. You have 2 books currently issued:</p>
                    <ul style="margin-top: 1rem; list-style-position: inside;">
                        <li>HTML5 & CSS3 Design Patterns - Due in 4 days</li>
                        <li>JavaScript: The Good Parts - Due in 7 days</li>
                    </ul>
                </section>
            </div>
        </div>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>