<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("config/db.php"); // db.php already has session_start()

// Safely get POST variables
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo "Please enter username and password!";
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE Username = ? AND Password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['username'] = $user['Username'];
        echo "success";
    } else {
        echo "Invalid username or password!";
    }
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
}
