<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$username = $_SESSION['username'] ?? 'NOT FOUND'; 
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow fixed-top">
    <div class="container-fluid">

        <!-- Logo + Institute Title -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/dashboard/dashboard.php">
            <img src="<?= BASE_URL ?>/images/logo.png" alt="JIPMER Logo" style="height:50px; margin-right: 15px;">
            <div class="d-flex flex-column">
                <span class="fw-bold" style="font-size: 1rem;">
                    JAWAHARLAL INSTITUTE OF POSTGRADUATE MEDICAL EDUCATION AND RESEARCH
                </span>
                <span class="text-muted" style="font-size: 0.85rem;">PUDUCHERRY-6</span>
                <span class="text-muted" style="font-size: 0.75rem;">
                    (An Institution of National Importance under the Ministry of Health & Family Welfare, GOVT OF INDIA)
                </span>
            </div>
        </a>

        <!-- Right Side: Logout -->
        <div class="d-flex align-items-center ms-auto">
            <span class="me-3">Welcome, <strong><?= ucfirst(htmlspecialchars($username)) ?></strong></span>
         
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

    </div>
</nav>