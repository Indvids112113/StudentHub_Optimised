<?php
session_start();
require_once 'db.php';

$serverMessage = "";
$msgClass = "";
$isLoggedIn = isset($_SESSION['user_id']);

// Handle Student Event Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_event'])) {
    // 1. Strict Guest Check: Block any guest registration attempt
    if (!$isLoggedIn) {
        $serverMessage = "Access Denied: You must be logged in to register for events. Please log in first.";
        $msgClass = "error-msg";
    } else {
        $eventId = intval($_POST['event_id']);
        $enrollmentNo = trim($_POST['enrollment_no']);
        $studentName = trim($_POST['student_name']);
        $branch = trim($_POST['branch']);
        $year = intval($_POST['year']);

        if (empty($enrollmentNo) || empty($studentName) || empty($branch) || empty($year)) {
            $serverMessage = "Please complete all registration fields.";
            $msgClass = "error-msg";
        } else {
            try {
                // Check event capacity
                $capStmt = $pdo->prepare("
                    SELECT e.max_participants, COUNT(r.id) as current_reg 
                    FROM events e 
                    LEFT JOIN event_registrations r ON e.id = r.event_id 
                    WHERE e.id = ? 
                    GROUP BY e.id
                ");
                $capStmt->execute([$eventId]);
                $capData = $capStmt->fetch();

                $maxAllowed = $capData ? intval($capData['max_participants']) : 50;
                $currentReg = $capData ? intval($capData['current_reg']) : 0;

                if ($currentReg >= $maxAllowed) {
                    $serverMessage = "Registration closed: This event has reached maximum capacity ($maxAllowed participants).";
                    $msgClass = "error-msg";
                } else {
                    // Prevent duplicate registration for this event
                    $check = $pdo->prepare("SELECT id FROM event_registrations WHERE student_id = ? AND event_id = ?");
                    $check->execute([$enrollmentNo, $eventId]);

                    if ($check->rowCount() > 0) {
                        $serverMessage = "Enrollment Number $enrollmentNo is already registered for this event.";
                        $msgClass = "error-msg";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO event_registrations (student_id, event_id, student_name, branch, study_year) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$enrollmentNo, $eventId, $studentName, $branch, $year]);

                        $serverMessage = "Registration successful! You have been enrolled in the event.";
                        $msgClass = "success-msg";
                    }
                }
            } catch (\PDOException $e) {
                $serverMessage = "Registration Error: " . $e->getMessage();
                $msgClass = "error-msg";
            }
        }
    }
}

// Fetch all events along with live registration counts
try {
    $stmt = $pdo->query("
        SELECT e.*, COUNT(r.id) AS current_reg 
        FROM events e 
        LEFT JOIN event_registrations r ON e.id = r.event_id 
        GROUP BY e.id 
        ORDER BY e.event_date ASC
    ");
    $events = $stmt->fetchAll();
} catch (\PDOException $e) {
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Events</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reg-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.75);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .reg-modal-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            max-width: 480px;
            width: 90%;
        }
        .capacity-indicator {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.08);
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
        <p>Campus Events & Registrations</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php" class="active">Events</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Dashboard</a></li>
                <?php elseif ($isLoggedIn): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                    <li><a href="logout.php" style="color: #ef4444; font-weight: bold;">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main>
        <h2>Upcoming Campus Events</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);">
                <?php echo htmlspecialchars($serverMessage); ?>
            </div>
        <?php endif; ?>

        <div class="events-grid" id="eventsContainer">
            <?php if (!empty($events)): ?>
                <?php foreach($events as $event): ?>
                    <?php 
                        $maxCap = $event['max_participants'] ?? 50;
                        $isFull = $event['current_reg'] >= $maxCap;
                    ?>
                    <article class="event-card" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: var(--border-radius); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                            <p><strong>Category:</strong> <span class="auth-badge"><?php echo htmlspecialchars($event['category']); ?></span></p>
                            
                            <div class="capacity-indicator">
                                <strong>Registered:</strong> <?php echo $event['current_reg']; ?> / <?php echo $maxCap; ?> seats
                                <?php if ($isFull): ?>
                                    <span style="color: #ef4444; font-weight: bold; margin-left: 6px;">(Housefull)</span>
                                <?php endif; ?>
                            </div>

                            <p style="margin-top: 1rem; color: var(--text-dark);"><?php echo htmlspecialchars($event['description']); ?></p>
                        </div>
                        
                        <?php if ($isFull): ?>
                            <button type="button" disabled style="margin-top: 1.5rem; width: 100%; opacity: 0.5; background: #64748b; cursor: not-allowed;">
                                Seats Full
                            </button>
                        <?php else: ?>
                            <button type="button" onclick="openRegisterModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['title']); ?>')" style="margin-top: 1.5rem; width: 100%;">
                                Register Now
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">No upcoming events found.</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Student Event Registration Modal -->
    <div id="registerModal" class="reg-modal">
        <div class="reg-modal-box">
            <h3 id="modalEventTitle" style="margin-top: 0; color: var(--primary-color);">Event Registration</h3>
            <p style="font-size: 0.9rem; margin-bottom: 1.2rem; color: #94a3b8;">Enter your details to confirm your attendance.</p>
            
            <form method="POST" action="events.php" onsubmit="return validateRegistration(event)" style="max-width: 100%;">
                <input type="hidden" name="register_event" value="1">
                <input type="hidden" name="event_id" id="modalEventId">

                <div style="margin-bottom: 12px;">
                    <label>Enrollment / Student ID:</label>
                    <input type="text" name="enrollment_no" required value="<?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?>" placeholder="e.g. 23DCS182" class="control-input" style="width: 100%;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label>Full Name:</label>
                    <input type="text" name="student_name" required value="<?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>" placeholder="e.g. Vidhan Hardik" class="control-input" style="width: 100%;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label>Branch / Course:</label>
                    <select name="branch" required class="control-select" style="width: 100%;">
                        <option value="BTech CSE">B.Tech CSE</option>
                        <option value="BTech IT">B.Tech IT</option>
                        <option value="BTech CE">B.Tech CE</option>
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label>Year of Study:</label>
                    <select name="year" required class="control-select" style="width: 100%;">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeRegisterModal()" style="background: #64748b; width: auto; padding: 0.5rem 1rem;">Cancel</button>
                    <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Confirm Registration</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
    <script>
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

        function openRegisterModal(id, title) {
            document.getElementById('modalEventId').value = id;
            document.getElementById('modalEventTitle').textContent = 'Register for: ' + title;
            document.getElementById('registerModal').style.display = 'flex';
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        // Intercept guest submissions and show alert
        function validateRegistration(e) {
            if (!isLoggedIn) {
                e.preventDefault();
                alert("You have to log in first to register for events, even with a valid Student ID!");
                window.location.href = "login.php?error=unauthorized";
                return false;
            }
            return true;
        }
    </script>
</body>
</html>