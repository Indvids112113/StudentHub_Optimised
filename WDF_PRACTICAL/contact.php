<?php
session_start();
require_once 'db.php';

$serverMessage = "";
$msgClass = "";
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isLoggedIn = isset($_SESSION['user_id']);

// 1. Admin Add Contact (Faculty / Student Council)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'create_contact') {
    if (!$isAdmin) {
        $serverMessage = "Unauthorized access.";
        $msgClass = "error-msg";
    } else {
        $name = trim($_POST['name']);
        $role_type = trim($_POST['role_type']);
        $department = trim($_POST['department']);
        $email = trim($_POST['email']);
        $phone = !empty(trim($_POST['phone'])) ? trim($_POST['phone']) : null;

        if (!empty($name) && !empty($role_type) && !empty($department) && !empty($email)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO contacts (name, role_type, department, email, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role_type, $department, $email, $phone]);

                // Audit Log Entry
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
                $log->execute(['Create Contact', "Added $role_type: $name ($department)", $ip]);

                $serverMessage = "$role_type contact added successfully!";
                $msgClass = "success-msg";
            } catch (\PDOException $e) {
                $serverMessage = "Error adding contact: " . $e->getMessage();
                $msgClass = "error-msg";
            }
        } else {
            $serverMessage = "Name, role, department, and email are required.";
            $msgClass = "error-msg";
        }
    }
}

// 2. Admin Delete Contact
if (isset($_GET['delete_contact'])) {
    if (!$isAdmin) {
        $serverMessage = "Unauthorized access.";
        $msgClass = "error-msg";
    } else {
        $contactId = intval($_GET['delete_contact']);
        try {
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->execute([$contactId]);

            // Audit Log Entry
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $log = $pdo->prepare("INSERT INTO audit_logs (action, details, ip_address) VALUES (?, ?, ?)");
            $log->execute(['Delete Contact', "Deleted contact ID: $contactId", $ip]);

            $serverMessage = "Contact deleted successfully.";
            $msgClass = "success-msg";
        } catch (\PDOException $e) {
            $serverMessage = "Error deleting contact: " . $e->getMessage();
            $msgClass = "error-msg";
        }
    }
}

// Case-insensitive Search & Department Filter
$searchName = trim($_GET['search_name'] ?? '');
$filterDept = trim($_GET['filter_dept'] ?? 'All');

$query = "SELECT * FROM contacts WHERE 1=1";
$params = [];

if (!empty($searchName)) {
    // LOWER() provides case-insensitive matching
    $query .= " AND LOWER(name) LIKE LOWER(?)";
    $params[] = "%" . $searchName . "%";
}

if ($filterDept !== 'All' && !empty($filterDept)) {
    $query .= " AND department = ?";
    $params[] = $filterDept;
}

$query .= " ORDER BY role_type ASC, name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();
} catch (\PDOException $e) {
    $contacts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Contact Directory</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .contact-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dept-tag {
            display: inline-block;
            font-size: 0.78rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            font-weight: 600;
        }
        .role-badge-faculty {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        .role-badge-council {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
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
        <p>Faculty & Student Council Directory</p>
    </header>
    
    <nav aria-label="Main Navigation">
        <div class="nav-container">
            <button id="hamburgerBtn" class="hamburger-btn">☰</button>
            <ul id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>

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

    <main style="max-width: 950px; margin: 2rem auto; padding: 1.5rem;">
        <h2>Campus Contacts Directory</h2>

        <?php if (!empty($serverMessage)): ?>
            <div class="<?php echo $msgClass; ?>" style="margin-bottom: 1.5rem; text-align: center; padding: 10px; border-radius: 4px; background: rgba(0,0,0,0.2);">
                <?php echo htmlspecialchars($serverMessage); ?>
            </div>
        <?php endif; ?>

        <!-- ADMIN CONTROLS: ADD NEW CONTACT -->
        <?php if ($isAdmin): ?>
            <section style="margin-bottom: 2.5rem; background: var(--card-bg); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: var(--border-radius);">
                <h3 style="margin-top: 0; color: var(--primary-color);">Admin: Add Faculty / Student Council Member</h3>
                <form method="POST" action="contact.php" style="max-width: 100%;">
                    <input type="hidden" name="action_type" value="create_contact">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Full Name:</label>
                            <input type="text" name="name" required placeholder="e.g. Dr. Rajesh Patel" class="control-input" style="width: 100%;">
                        </div>

                        <div>
                            <label>Role Category:</label>
                            <select name="role_type" required class="control-select" style="width: 100%;">
                                <option value="Faculty">Faculty</option>
                                <option value="Student Council">Student Council</option>
                            </select>
                        </div>

                        <div>
                            <label>Department:</label>
                            <select name="department" required class="control-select" style="width: 100%;">
                                <option value="CSE">CSE</option>
                                <option value="IT">IT</option>
                                <option value="CE">CE</option>
                                <option value="General">General / All</option>
                            </select>
                        </div>

                        <div>
                            <label>Email Address (Required):</label>
                            <input type="email" name="email" required placeholder="name@charusat.edu.in" class="control-input" style="width: 100%;">
                        </div>

                        <div>
                            <label>Phone Number (Optional):</label>
                            <input type="text" name="phone" placeholder="e.g. +91 9876543210" class="control-input" style="width: 100%;">
                        </div>
                    </div>

                    <button type="submit" style="width: auto; padding: 0.5rem 1.8rem;">Add Contact</button>
                </form>
            </section>
        <?php endif; ?>

        <!-- SEARCH AND FILTER CONTROLS -->
        <form method="GET" action="contact.php" class="data-controls">
            <input type="text" name="search_name" placeholder="Search by name (case-insensitive)..." value="<?php echo htmlspecialchars($searchName); ?>" class="control-input">

            <select name="filter_dept" class="control-select">
                <option value="All" <?php if ($filterDept === 'All') echo 'selected'; ?>>All Departments</option>
                <option value="CSE" <?php if ($filterDept === 'CSE') echo 'selected'; ?>>CSE</option>
                <option value="IT" <?php if ($filterDept === 'IT') echo 'selected'; ?>>IT</option>
                <option value="CE" <?php if ($filterDept === 'CE') echo 'selected'; ?>>CE</option>
                <option value="General" <?php if ($filterDept === 'General') echo 'selected'; ?>>General / All</option>
            </select>

            <button type="submit" style="width: auto; padding: 0.5rem 1.5rem;">Filter</button>
            <?php if (!empty($searchName) || $filterDept !== 'All'): ?>
                <a href="contact.php" style="color: var(--primary-color); text-decoration: none; align-self: center; font-size: 0.9rem;">Reset</a>
            <?php endif; ?>
        </form>

        <!-- CONTACT CARDS GRID -->
        <section>
            <?php if (count($contacts) > 0): ?>
                <div class="contact-grid">
                    <?php foreach ($contacts as $c): ?>
                        <article class="contact-card">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 0.5rem;">
                                    <h3 style="margin: 0; color: var(--primary-color); font-size: 1.15rem;"><?php echo htmlspecialchars($c['name']); ?></h3>
                                    <span class="auth-badge <?php echo $c['role_type'] === 'Faculty' ? 'role-badge-faculty' : 'role-badge-council'; ?>">
                                        <?php echo htmlspecialchars($c['role_type']); ?>
                                    </span>
                                </div>

                                <p style="margin: 0.4rem 0;">
                                    <strong>Department:</strong> <span class="dept-tag"><?php echo htmlspecialchars($c['department']); ?></span>
                                </p>

                                <!-- Email Visibility Rule: Logged-in users see the email; Guests are instructed to log in -->
                                <p style="margin: 0.4rem 0;">
                                    <strong>Email:</strong> 
                                    <?php if ($isLoggedIn): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($c['email']); ?>" style="color: #60a5fa; text-decoration: none;"><?php echo htmlspecialchars($c['email']); ?></a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic; font-size: 0.88rem;">🔒 [Log in to view email]</span>
                                    <?php endif; ?>
                                </p>

                                <p style="margin: 0.4rem 0;">
                                    <strong>Phone:</strong> 
                                    <?php if ($isLoggedIn): ?>
                                        <?php echo !empty($c['phone']) ? htmlspecialchars($c['phone']) : '<span style="color: #64748b;">Not provided</span>'; ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic; font-size: 0.88rem;">🔒 [Log in to view phone]</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <?php if ($isAdmin): ?>
                                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); text-align: right;">
                                    <a href="contact.php?delete_contact=<?php echo $c['id']; ?>" onclick="return confirm('Delete this contact record?');" style="color: #ef4444; font-size: 0.85rem; text-decoration: none; font-weight: bold;">
                                        🗑️ Delete Contact
                                    </a>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="margin-top: 2rem;">No contacts found matching your criteria.</div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>Student Hub</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>