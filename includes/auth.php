<?php
// includes/auth.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

/**
 * Helper: check role and optionally exit with no access
 * @param int|array $roles role id or array of role ids allowed
 */
function require_role($roles) {
    $current = $_SESSION['role'] ?? null;
    if (is_array($roles)) {
        if (!in_array($current, $roles)) {
            header('Location: /no_access.php'); exit;
        }
    } else {
        if ($current != $roles) {
            header('Location: /no_access.php'); exit;
        }
    }
}
