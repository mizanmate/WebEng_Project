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

$clubID = $committee['clubID'];

/* Event List */

$events = mysqli_query(
    $link,
    "SELECT
        eventID,
        eventTitle

     FROM Event

     WHERE ClubID='$clubID'

     ORDER BY eventDate DESC"
);

$selectedEvent =
$_GET['eventID'] ?? '';

$participants = null;

if ($selectedEvent != '') {

    $participants = mysqli_query(
        $link,
        "SELECT

            s.StudentID,
            s.StudentName,
            s.Email,
            s.Phone,

            r.registrationStatus,
            r.registrationDate

         FROM Registration r

         JOIN Student s
         ON r.studentID = s.StudentID

         WHERE r.eventID='$selectedEvent'

         ORDER BY r.registrationDate ASC"
    );
}

$pageTitle = 'Participants';
$activePage = 'committee';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<?php include 'includes/head.php'; ?>

<style>

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.btn-back{
    background:#1f3f77;
    color:white;
    text-decoration:none;
    padding:10px 18px;
    border-radius:8px;
    font-size:14px;
    font-weight:500;
    transition:.2s;
}

.btn-back:hover{
    background:#16315f;
}

.filter-card{
    background:#fff;
    padding:30px;
    border-radius:12px;
    margin-bottom:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.filter-header h3{
    color:#1f3f77;
    margin-bottom:6px;
    font-size:22px;
    font-weight:600;
}

.filter-header p{
    color:#6b7280;
    margin-bottom:20px;
    font-size:14px;
}

.event-filter{
    max-width:500px;
}

.event-filter select{
    width:100%;
    height:48px;
    padding:0 15px;
    border:1px solid #d1d5db;
    border-radius:8px;
    background:#fff;
    font-size:15px;
    transition:.2s;
}

.event-filter select:focus{
    outline:none;
    border-color:#1f3f77;
    box-shadow:0 0 0 3px rgba(31,63,119,.15);
}

.table-card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f3f77;
    color:white;
    padding:12px;
    text-align:left;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.status{
    padding:5px 12px;
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

.cancelled{
    background:#fee2e2;
    color:#991b1b;
}

</style>

</head>

<body>

<div class="app">

<?php include 'includes/sidebar_student.php'; ?>

<main class="main-content">

<div class="page-header">

    <h2>Event Participants</h2>

    <a href="committeeDashboard.php"
       class="btn-back">

        ← Back to Dashboard

    </a>

</div>

<div class="filter-card">

    <div class="filter-header">

        <h3>Select Event</h3>

        <p>
            Choose an event to view participant registrations.
        </p>

    </div>

    <form method="GET" class="event-filter">

        <select
            name="eventID"
            onchange="this.form.submit()">

            <option value="">
                -- Choose Event --
            </option>

            <?php mysqli_data_seek($events,0); ?>

            <?php while($event = mysqli_fetch_assoc($events)): ?>

            <option
                value="<?= $event['eventID'] ?>"
                <?= $selectedEvent == $event['eventID']
                ? 'selected'
                : '' ?>>

                <?= htmlspecialchars($event['eventTitle']) ?>

            </option>

            <?php endwhile; ?>

        </select>

    </form>

</div>

<?php if($selectedEvent != ''): ?>

<div class="table-card">

<table>

<thead>

<tr>

<th>Student ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Status</th>
<th>Registration Date</th>

</tr>

</thead>

<tbody>

<?php if(mysqli_num_rows($participants) > 0): ?>

<?php while($row = mysqli_fetch_assoc($participants)): ?>

<?php

$class = 'registered';

if (
$row['registrationStatus']
== 'Waiting List'
){
$class='waiting';
}

if (
$row['registrationStatus']
== 'Cancelled'
){
$class='cancelled';
}

?>

<tr>

<td>

<?= htmlspecialchars($row['StudentID']) ?>

</td>

<td>

<?= htmlspecialchars($row['StudentName']) ?>

</td>

<td>

<?= htmlspecialchars($row['Email']) ?>

</td>

<td>

<?= htmlspecialchars($row['Phone']) ?>

</td>

<td>

<span class="status <?= $class ?>">

<?= htmlspecialchars(
$row['registrationStatus']
) ?>

</span>

</td>

<td>

<?= date(
'd M Y',
strtotime(
$row['registrationDate']
)
) ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="6"
style="text-align:center;">

No participants found.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</main>

</div>

</body>

</html>