<?php
// includes/navigation.php
?>
<nav>
    <a href="index.php"><i class="fas fa-home"></i> Home</a>
    <a href="packages.php"><i class="fas fa-suitcase"></i> Packages</a>
    <?php if (isset($_SESSION['customer_id'])): ?>
        <a href="user/user_dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php else: ?>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
    <!-- Admin link always visible -->
    <a href="admin/login.php" style="background: rgba(255,255,255,0.1);">
        <i class="fas fa-crown"></i> Admin
    </a>
</nav>