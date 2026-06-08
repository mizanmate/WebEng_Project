<?php

session_start();

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$eventID = $_GET['id'] ?? '';

if ($eventID == '') {

    header('Location: adminEventList.php');
    exit();
}

/* Event Details */

$stmt = mysqli_prepare(
    $link,
    "SELECT

        e.*,
        c.ClubName,
        c.AdvisorName

     FROM Event e

     JOIN Club c
     ON c.ClubID = e.ClubID

     WHERE e.eventID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    's',
    $eventID
);

mysqli_stmt_execute($stmt);

$event =
mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$event) {

    header('Location: adminEventList.php');
    exit();
}

/* Statistics */

$totalRegistered =
mysqli_fetch_row(
    mysqli_query(
        $link,
        "SELECT COUNT(*)

         FROM Registration

         WHERE eventID='$eventID'

         AND registrationStatus='Registered'"
    )
)[0];

$totalWaiting =
mysqli_fetch_row(
    mysqli_query(
        $link,
        "SELECT COUNT(*)

         FROM Registration

         WHERE eventID='$eventID'

         AND registrationStatus='Waiting List'"
    )
)[0];

/* Participants */

$participants = mysqli_query(
    $link,
    "SELECT

        s.StudentID,
        s.StudentName,
        s.Email,

        r.registrationStatus,
        r.registrationDate

     FROM Registration r

     JOIN Student s
     ON s.StudentID = r.studentID

     WHERE r.eventID='$eventID'

     ORDER BY r.registrationDate ASC"
);

$pageTitle = 'Event Details';
$activePage = 'events';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<?php include 'includes/head.php'; ?>

<style>

.info-card,
.table-card,
.stats-card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    margin-bottom:25px;
}

.stat-number{
    font-size:32px;
    font-weight:bold;
    color:#1f3f77;
}

.detail-table{
    width:100%;
}

.detail-table td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.detail-table td:first-child{
    width:220px;
    font-weight:600;
}

.status{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
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

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f3f77;
    color:white;
    padding:12px;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

</style>

</head>

<body>

<div class="app">

<?php include 'includes/sidebar_admin.php'; ?>

<main class="main-content">

<div class="page-header">

    <h2>Event Details</h2>

    <a href="adminEventList.php"
       class="btn btn-secondary">

        Back

    </a>

</div>

<div class="info-card">

<table class="detail-table">

<tr>
<td>Event Name</td>
<td><?= htmlspecialchars($event['eventTitle']) ?></td>
</tr>

<tr>
<td>Club</td>
<td><?= htmlspecialchars($event['ClubName']) ?></td>
</tr>

<tr>
<td>Advisor</td>
<td><?= htmlspecialchars($event['AdvisorName']) ?></td>
</tr>

<tr>
<td>Description</td>
<td><?= htmlspecialchars($event['eventDesc']) ?></td>
</tr>

<tr>
<td>Date</td>
<td><?= date('d M Y', strtotime($event['eventDate'])) ?></td>
</tr>

<tr>
<td>Time</td>
<td><?= date('g:i A', strtotime($event['eventTime'])) ?></td>
</tr>

<tr>
<td>Venue</td>
<td><?= htmlspecialchars($event['eventVenue']) ?></td>
</tr>

<tr>
<td>Maximum Participants</td>
<td><?= $event['maxParticipants'] ?></td>
</tr>

<tr>
<td>Status</td>
<td><?= ucfirst($event['eventStatus']) ?></td>
</tr>

</table>

</div>

<div class="stats-grid">

<div class="stats-card">

<div class="stat-number">

<?= $totalRegistered ?>

</div>

<p>Registered Participants</p>

</div>

<div class="stats-card">

<div class="stat-number">

<?= $totalWaiting ?>

</div>

<p>Waiting List</p>

</div>

</div>

<div class="table-card">

<h3>Participant List</h3>

<table>

<thead>

<tr>

<th>Student ID</th>
<th>Name</th>
<th>Email</th>
<th>Status</th>
<th>Registration Date</th>

</tr>

</thead>

<tbody>

<?php if(mysqli_num_rows($participants) > 0): ?>

<?php while($row = mysqli_fetch_assoc($participants)): ?>

<?php
$class =
$row['registrationStatus'] == 'Waiting List'
? 'waiting'
: 'registered';
?>

<tr>

<td><?= htmlspecialchars($row['StudentID']) ?></td>

<td><?= htmlspecialchars($row['StudentName']) ?></td>

<td><?= htmlspecialchars($row['Email']) ?></td>

<td>

<span class="status <?= $class ?>">

<?= htmlspecialchars($row['registrationStatus']) ?>

</span>

</td>

<td>

<?= date(
'd M Y',
strtotime($row['registrationDate'])
) ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="5"
style="text-align:center;">

No registrations found.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>