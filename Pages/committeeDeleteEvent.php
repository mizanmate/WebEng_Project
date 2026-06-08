<?php

session_start();

if (
    !isset($_SESSION['UserID']) ||
    empty($_SESSION['is_committee'])
) {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$userID = $_SESSION['UserID'];
$eventID = $_GET['id'] ?? '';

if ($eventID == '') {

    header('Location: committeeManageEvent.php');
    exit();
}

/* Verify ownership */

$stmt = mysqli_prepare(
    $link,
    "SELECT e.eventID

     FROM event e

     JOIN clubcommitee cc
     ON cc.clubID = e.ClubID

     WHERE e.eventID = ?
     AND cc.userID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    'ss',
    $eventID,
    $userID
);

mysqli_stmt_execute($stmt);

$result =
mysqli_stmt_get_result($stmt);

if (!mysqli_fetch_assoc($result)) {

    header('Location: committeeManageEvent.php');
    exit();
}

/* Delete Event */

$stmt = mysqli_prepare(
    $link,
    "DELETE FROM event
     WHERE eventID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    's',
    $eventID
);

mysqli_stmt_execute($stmt);

$_SESSION['success_msg'] =
    'Event deleted successfully.';

header('Location: committeeManageEvent.php');
exit();