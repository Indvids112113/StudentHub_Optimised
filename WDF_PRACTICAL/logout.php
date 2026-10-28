<?php
session_start();
require_once 'db.php';

// Clear Remember Me token from database if user is logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare("UPDATE students SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear the Remember Me cookie
setcookie("remember_me", "", time() - 3600, "/");

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>