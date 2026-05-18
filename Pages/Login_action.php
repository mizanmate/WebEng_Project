<?php


// start session
session_start();

// ── Read & validate form fields ────────────────────────────────
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role']     ?? '');

if ($username === '' || $password === '' || $role === '') {
    $_SESSION['ERRMSG_ARR'] = 'Please fill in all fields.';
    header('Location: login.php');
    exit();
}

// ── Query the Login table ──────────────────────────────────────
require_once 'DB_connection.php';

$stmt = mysqli_prepare($link,
    'SELECT UserID, name, password FROM Login WHERE UserID = ? AND role = ?'
);
mysqli_stmt_bind_param($stmt, 'ss', $username, $role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ── Verify password and start session ─────────────────────────
if ($result && mysqli_num_rows($result) === 1) {

    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {

        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['name']   = $user['name'];
        $_SESSION['role']   = $role;

        // For students, also store StudentID
        if ($role === 'student') {
            $s = mysqli_prepare($link,
                'SELECT StudentID FROM Student WHERE UserID = ?'
            );
            mysqli_stmt_bind_param($s, 's', $user['UserID']);
            mysqli_stmt_execute($s);
            $sres = mysqli_stmt_get_result($s);

            if ($srow = mysqli_fetch_assoc($sres)) {
                $_SESSION['StudentID'] = $srow['StudentID'];
            }
<<<<<<< HEAD

            // Committee members are still students, but they receive an extra dashboard link.
            $c = mysqli_prepare($link,
                'SELECT committeeID FROM ClubCommitee WHERE userID = ? LIMIT 1'
            );
            mysqli_stmt_bind_param($c, 's', $user['UserID']);
            mysqli_stmt_execute($c);
            mysqli_stmt_store_result($c);
            $_SESSION['is_committee'] = mysqli_stmt_num_rows($c) > 0;
=======
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
        }

        session_write_close();
        header('Location: ' . ($role === 'admin' ? 'adminDash.php' : 'studDash.php'));
        exit();
    }
}

// ── Authentication failed ──────────────────────────────────────
$_SESSION['ERRMSG_ARR'] = 'Invalid User ID, password, or role.';
//  On failure — redirects back to login.php
header('Location: login.php');
exit();
