<?php
session_start();
require_once 'db.php';

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$serverMessage = "";
$msgClass = "";

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_id'])) {
    $eventId = filter_var($_POST['event_id'], FILTER_VALIDATE_INT);
    $studentId = htmlspecialchars(trim($_POST['student_id']));

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $serverMessage = "Security Error: Invalid CSRF token.";
        $msgClass = "error-msg server-msg";
    } elseif (!$eventId || empty($studentId)) {
        $serverMessage = "Error: You must be logged in to register.";
        $msgClass = "error-msg server-msg";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM event_registrations WHERE student_id = ? AND event_id = ?");
            $check->execute([$studentId, $eventId]);
            
            if ($check->rowCount() > 0) {
                $serverMessage = "You are already registered for this event!";
                $msgClass = "error-msg server-msg";
            } else {
                $stmt = $pdo->prepare("INSERT INTO event_registrations (student_id, event_id) VALUES (?, ?)");
                if ($stmt->execute([$studentId, $eventId])) {
                    $serverMessage = "Successfully registered for the event!";
                    $msgClass = "success-msg server-msg";
                }
            }
        } catch (\PDOException $e) {
            $serverMessage = "Database Error: " . $e->getMessage();
            $msgClass = "error-msg server-msg";
        }
    }
}

// Fetch Events from MySQL
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Events</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sql-events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .event-card { background: var(--bg-color); border: 1px solid #444; padding: 20px; border-radius: 8px; }
        .event-card h3 { color: #3b82f6; margin-top: 0; }
        .btn-register { background: #3b82f6; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; margin-top: 15px; font-weight: bold; }
        .btn-register:hover { background: #2563eb; }
    </style>
</head>
<body class="dark-mode">
    <div class="top-bar-controls">
        <span id="authBadge" class="auth-badge">Role: GUEST</span>
    </div>

    <header>
        <h1>StudentHub</h1>
        <p>Upcoming Campus Events</p>
    </header>
    
    <nav>
        <div class="nav-container">
            <ul id="navMenu">
                <li><a href="index.html">Home</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
                <li><a href="events.php" class="active">Events</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <section>
            <?php if (!empty($serverMessage)): ?>
                <div style="padding: 1rem; border: 1px solid; margin-bottom: 1rem; text-align: center; color: <?php echo ($msgClass == 'success-msg server-msg') ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo htmlspecialchars($serverMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Changed ID so JS cannot erase it -->
            <div class="sql-events-grid" id="sqlEventsContainer">
                <?php foreach($events as $event): ?>
                    <article class="event-card">
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($event['category']); ?></p>
                        <p><?php echo htmlspecialchars($event['description']); ?></p>
                        
                        <form method="POST" action="events.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <input type="hidden" name="student_id" class="hidden_student_id" value="">
                            <button type="submit" class="btn-register auth-required-btn" style="display:none;">Register for Event</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script src="js/main.js"></script>
    <script>
        // Reveal buttons if logged in
        document.addEventListener("DOMContentLoaded", () => {
            const currentUser = JSON.parse(localStorage.getItem("studenthub_current_user"));
            if (currentUser && currentUser.id) {
                document.querySelectorAll(".hidden_student_id").forEach(input => input.value = currentUser.id);
                document.querySelectorAll(".auth-required-btn").forEach(btn => btn.style.display = "block");
            }
        });
    </script>
</body>
</html>