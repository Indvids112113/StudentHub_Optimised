<?php
// Ensure session is started only if one doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Practical 10: Set inactivity timeout duration (900 seconds = 15 minutes)
$timeout_duration = 900; 

// 1. ADVANCED EXTENSION: Check 'Remember Me' token if session is missing
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    
    try {
        // Find users who have an active remember_token
        $stmt = $pdo->prepare("SELECT id, name, remember_token FROM students WHERE remember_token IS NOT NULL");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            // Verify the plaintext cookie against the hashed database token
            if (password_verify($token, $user['remember_token'])) {
                // Auto-login successful: Regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'student'; 
                $_SESSION['name'] = $user['name'];
                $_SESSION['last_activity'] = time();
                
                // INTERMEDIATE EXTENSION: Update last_login timestamp
                $updateLogin = $pdo->prepare("UPDATE students SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateLogin->execute([$user['id']]);
                break;
            }
        }
    } catch (\PDOException $e) {
        // Silently fail database errors during auto-login
    }
}

// 2. BLOCK UNAUTHORIZED ACCESS: If still no session, kick to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// 3. SESSION TIMEOUT: Check if the user has been inactive for longer than 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Wipe all session data
    session_unset();
    session_destroy();
    
    // Destroy the remember me cookie securely
    setcookie("remember_me", "", time() - 3600, "/"); 
    
    // Redirect with a timeout message
    header("Location: login.php?error=timeout");
    exit;
}

// Update last activity time so the timeout counter resets
$_SESSION['last_activity'] = time();
?>