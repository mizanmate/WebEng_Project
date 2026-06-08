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

/* Committee Club */

$stmt = mysqli_prepare(
    $link,
    "SELECT clubID
     FROM ClubCommitee
     WHERE userID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    's',
    $userID
);

mysqli_stmt_execute($stmt);

$committee =
mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$committee) {
    die('Committee access required.');
}

$clubID = $committee['clubID'];

/* Events */

$events = mysqli_query(
    $link,
    "SELECT

        e.*,

        (
            SELECT COUNT(*)

            FROM Registration r

            WHERE r.eventID = e.eventID

            AND r.registrationStatus='Registered'

        ) AS totalRegistered

     FROM Event e

     WHERE e.ClubID='$clubID'

     ORDER BY e.eventDate DESC"
);

$pageTitle = 'Manage Events';
$activePage = 'committee';

?>

<!DOCTYPE html>
<html lang='en'>

<head>

<?php include 'includes/head.php'; ?>

<style>

.table-card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.btn-create{
    background:#1f3f77;
    color:white;
    padding:10px 18px;
    border-radius:6px;
    text-decoration:none;
}

.status-badge{
    padding:5px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.upcoming{
    background:#fef3c7;
    color:#92400e;
}

.ongoing{
    background:#dbeafe;
    color:#1e40af;
}

.completed{
    background:#d1fae5;
    color:#065f46;
}

.action-btn{
    padding:6px 10px;
    text-decoration:none;
    border-radius:5px;
    margin-right:5px;
}

.edit-btn{
    background:#1f3f77;
    color:white;
}

.delete-btn{
    background:#dc2626;
    color:white;
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

</style>

</head>

<body>

<div class="app">

<?php include 'includes/sidebar_student.php'; ?>

<main class="main-content">

<div class="page-header">

    <h2>Manage Events</h2>

    <a
        href="committeeCreateEvent.php"
        class="btn-create">

        + Create Event

    </a>

</div>

<div class="table-card">

<table>

<thead>

<tr>

    <th>Event</th>
    <th>Date</th>
    <th>Venue</th>
    <th>Participants</th>
    <th>Status</th>
    <th>Action</th>

</tr>

</thead>

<tbody>

<?php while($event = mysqli_fetch_assoc($events)): ?>

<?php

$statusClass = 'upcoming';

if($event['eventStatus']=='ongoing'){
    $statusClass='ongoing';
}

if($event['eventStatus']=='completed'){
    $statusClass='completed';
}

?>

<tr>

<td>

<?= htmlspecialchars($event['eventTitle']) ?>

</td>

<td>

<?= date(
    'd M Y',
    strtotime($event['eventDate'])
) ?>

</td>

<td>

<?= htmlspecialchars($event['eventVenue']) ?>

</td>

<td>

<?= $event['totalRegistered'] ?>

/

<?= $event['maxParticipants'] ?>

</td>

<td>

<span class="status-badge <?= $statusClass ?>">

<?= ucfirst($event['eventStatus']) ?>

</span>

</td>

<td>

<a
href="committeeEditEvent.php?id=<?= $event['eventID'] ?>"
class="action-btn edit-btn">

Edit

</a>

<a
href="committeeDeleteEvent.php?id=<?= $event['eventID'] ?>"
class="action-btn delete-btn"
onclick="return confirm('Delete this event?')">

Delete

</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>