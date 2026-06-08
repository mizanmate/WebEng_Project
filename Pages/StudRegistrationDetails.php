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

$stmt = mysqli_prepare(
    $link,
    "SELECT
        r.registrationID,
        r.registrationStatus,
        r.registrationDate,

        e.eventID,
        e.eventTitle,
        e.eventDesc,
        e.eventDate,
        e.eventTime,
        e.eventVenue,

        c.ClubName

     FROM registration r

     JOIN event e
     ON r.eventID = e.eventID

     JOIN club c
     ON e.ClubID = c.ClubID

     WHERE r.registrationID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    's',
    $registrationID
);

mysqli_stmt_execute($stmt);

$registration =
mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$registration) {
    header('Location: StudMyEvents.php');
    exit();
}

$pageTitle = 'Registration Details';
$activePage = 'myevents';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<?php include 'includes/head.php'; ?>

<style>

.detail-card{
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.detail-table{
    width:100%;
    border-collapse:collapse;
}

.detail-table td{
    padding:15px;
    border-bottom:1px solid #eee;
}

.detail-table td:first-child{
    width:250px;
    font-weight:600;
    color:#1f3f77;
}

.status-badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
}

.registered{
    background:#d1fae5;
    color:#065f46;
}

.waiting{
    background:#fef3c7;
    color:#92400e;
}

.cancelled{
    background:#fee2e2;
    color:#991b1b;
}

.completed{
    background:#dbeafe;
    color:#1e40af;
}

.action-buttons{
    margin-top:25px;
    display:flex;
    gap:10px;
}

</style>

</head>

<body>

<div class="app">

<?php include 'includes/sidebar_student.php'; ?>

<main class="main-content">

<h2>Registration Details</h2>

<div class="detail-card">

<table class="detail-table">

<tr>
<td>Registration ID</td>
<td><?= htmlspecialchars($registration['registrationID']) ?></td>
</tr>

<tr>
<td>Event Name</td>
<td><?= htmlspecialchars($registration['eventTitle']) ?></td>
</tr>

<tr>
<td>Club</td>
<td><?= htmlspecialchars($registration['ClubName']) ?></td>
</tr>

<tr>
<td>Description</td>
<td><?= htmlspecialchars($registration['eventDesc']) ?></td>
</tr>

<tr>
<td>Date</td>
<td><?= date('d M Y', strtotime($registration['eventDate'])) ?></td>
</tr>

<tr>
<td>Time</td>
<td><?= date('g:i A', strtotime($registration['eventTime'])) ?></td>
</tr>

<tr>
<td>Venue</td>
<td><?= htmlspecialchars($registration['eventVenue']) ?></td>
</tr>

<tr>
<td>Status</td>
<td>

<?php

$class = 'registered';

if ($registration['registrationStatus'] === 'Waiting List') {
    $class = 'waiting';
}

if ($registration['registrationStatus'] === 'Cancelled') {
    $class = 'cancelled';
}

if ($registration['registrationStatus'] === 'Completed') {
    $class = 'completed';
}

?>

<span class="status-badge <?= $class ?>">

<?= htmlspecialchars($registration['registrationStatus']) ?>

</span>

</td>
</tr>

<tr>
<td>Registration Date</td>
<td><?= date('d M Y', strtotime($registration['registrationDate'])) ?></td>
</tr>

</table>

<div class="action-buttons">

<a href="StudMyEvents.php"
   class="btn btn-secondary">

    Back

</a>

<?php if(
$registration['registrationStatus'] !== 'Cancelled'
): ?>

<a href="CancelRegistration.php?id=<?= $registration['registrationID'] ?>"
   class="btn btn-primary"
   onclick="return confirm('Cancel this registration?')">

    Cancel Registration

</a>

<?php endif; ?>

</div>

</div>

</main>

</div>

</body>
</html>
