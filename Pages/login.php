<?php
// ================================================================
//  login.php
//  Login page — the entry point for all users.
//  Posts credentials to Login_action.php for authentication.
// ================================================================

session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — FK CEM System</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body class="login-page">

<div class="login-box">

    <!-- ── Logo / heading ── -->
    <div class="login-logo">
        <img src="../img/Logo_UMPSA.png" alt="UMPSA Logo" class="login-logo-img">
        <h1>FK CEM System</h1>
        <p>Club &amp; Event Management</p>
    </div>

    <!-- ── Session alerts (set by Login_action.php) ── -->
    <?php if (isset($_SESSION['ERRMSG_ARR'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['ERRMSG_ARR']) ?>
        </div>
        <?php unset($_SESSION['ERRMSG_ARR']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_msg']) ?>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- ── Login form ── -->
    <form action="Login_action.php" method="post">

        <div class="form-group">
            <label for="username">User ID</label>
            <input type="text"
                   id="username"
                   name="username"
                   placeholder="Enter your matric / staff ID"
                   required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="Enter password"
                   required>
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="">-- Select Role --</option>
                <option value="admin">Admin</option>
                <option value="student">Student</option>
            </select>
        </div>

        <div class="btn-group" style="margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">Login</button>
        </div>

    </form>

</div>

</body>
</html>
