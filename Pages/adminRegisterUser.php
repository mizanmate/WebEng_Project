<?php
// ================================================================
//  adminRegisterUser.php
//  Admin page — register a new student or admin account.
//  Creates a record in Login + Student (or Admin) tables.
// ================================================================

// ── Auth check ────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// ── Database ──────────────────────────────────────────────────
require_once 'DB_connection.php';

// ── Handle form submission ────────────────────────────────────
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read form fields
    $userID    = trim($_POST['user_id']   ?? '');
    $name      = trim($_POST['name']      ?? '');
    $password  = trim($_POST['password']  ?? '');
    $role      = trim($_POST['role']      ?? 'student');
    $programme = trim($_POST['programme'] ?? '');
    $year      = trim($_POST['year']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');

    // Validate required fields
    if (!$userID || !$name || !$password) {
        $error = 'User ID, Name, and Password are required.';

    } else {
        // Check for duplicate User ID
        $dup = mysqli_prepare($link, 'SELECT UserID FROM login WHERE UserID = ?');
        mysqli_stmt_bind_param($dup, 's', $userID);
        mysqli_stmt_execute($dup);
        mysqli_stmt_store_result($dup);

        if (mysqli_stmt_num_rows($dup) > 0) {
            $error = 'User ID already exists.';

        } else {
            // Insert into login table
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins    = mysqli_prepare($link,
                'INSERT INTO login (UserID, name, password, role) VALUES (?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($ins, 'ssss', $userID, $name, $hashed, $role);

            if (mysqli_stmt_execute($ins)) {

                // Insert into role-specific table
                if ($role === 'student') {
                    $ins2 = mysqli_prepare($link,
                        'INSERT INTO student
                            (StudentID, UserID, StudentName, Programme, StudYear, Email, Phone)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param($ins2, 'sssssss',
                        $userID, $userID, $name, $programme, $year, $email, $phone
                    );
                    mysqli_stmt_execute($ins2);

                } else {
                    $dept = 'Administration';
                    $ins2 = mysqli_prepare($link,
                        'INSERT INTO admin (UserID, department) VALUES (?, ?)'
                    );
                    mysqli_stmt_bind_param($ins2, 'ss', $userID, $dept);
                    mysqli_stmt_execute($ins2);
                }

                $success = "User \"{$name}\" (ID: {$userID}) registered successfully.";

            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// ── Page meta ─────────────────────────────────────────────────
$pageTitle  = 'Register User — Admin';
$activePage = 'register_user';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">

        <h2>Register User</h2>

        <!-- ── Quick links ── -->
        <div class="quick-actions">
            <a href="adminViewUser.php" class="btn btn-secondary btn-sm">View Users</a>
            <a href="adminEditUser.php" class="btn btn-secondary btn-sm">Edit User</a>
        </div>

        <!-- ── Alerts ── -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ── Registration form ── -->
        <div class="form-card">
            <form action="adminRegisterUser.php" method="post">

                <div class="form-group">
                    <label for="user_id">
                        User ID
                        <small style="color:#999;">(matric / staff number)</small>
                    </label>
                    <input type="text"
                           id="user_id"
                           name="user_id"
                           placeholder="e.g. CA23055"
                           value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           placeholder="e.g. Ahmad bin Ali"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Login password"
                           required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- Student-specific fields (ignored for admin role) -->
                <div class="form-group">
                    <label for="programme">
                        Programme
                        <small style="color:#999;">(student)</small>
                    </label>
                    <input type="text"
                           id="programme"
                           name="programme"
                           placeholder="e.g. Computer Science"
                           value="<?= htmlspecialchars($_POST['programme'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="year">
                        Year of Study
                        <small style="color:#999;">(student)</small>
                    </label>
                    <input type="text"
                           id="year"
                           name="year"
                           placeholder="e.g. Year 2"
                           value="<?= htmlspecialchars($_POST['year'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           placeholder="e.g. student@umpsa.edu.my"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="phone">
                        Phone
                        <small style="color:#999;">(student)</small>
                    </label>
                    <input type="text"
                           id="phone"
                           name="phone"
                           placeholder="e.g. 011-12345678"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="btn-group">
                    <button type="reset"  class="btn btn-secondary">Clear</button>
                    <button type="submit" class="btn btn-primary">Register User</button>
                </div>

            </form>
        </div>

    </main>
</div>

</body>
</html>
