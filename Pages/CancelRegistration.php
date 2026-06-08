<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$registrationID = $_GET['id'] ?? '';

if ($registrationID === '') {
    header('Location: StudMyEvents.php');
    exit();
}

/* Check Registration Exists */

$stmt = mysqli_prepare(
    $link,
    "SELECT registrationID
     FROM Registration
     WHERE registrationID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    's',
    $registrationID
);

mysqli_stmt_execute($stmt);

$result =
mysqli_stmt_get_result($stmt);

if (!mysqli_fetch_assoc($result)) {

    $_SESSION['success_msg'] =
        'Registration not found.';

    header('Location: StudMyEvents.php');
    exit();
}

/* Update Status */

$stmt = mysqli_prepare(
    $link,
    "UPDATE Registration
     SET registrationStatus = 'Cancelled'
     WHERE registrationID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    's',
    $registrationID
);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['success_msg'] =
        'Registration cancelled successfully.';

} else {

    $_SESSION['success_msg'] =
        'Failed to cancel registration.';
}

header('Location: StudMyEvents.php');
exit();

