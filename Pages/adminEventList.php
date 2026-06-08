<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "

SELECT

e.eventID,
e.eventTitle,
e.eventDate,
e.eventVenue,
e.eventStatus,

c.ClubName,

(
    SELECT COUNT(*)
    FROM Registration r
    WHERE r.eventID = e.eventID
) AS totalParticipants

FROM Event e

JOIN Club c
ON c.ClubID = e.ClubID

WHERE 1=1

";

if ($search !== '') {

    $searchSafe =
        mysqli_real_escape_string(
            $link,
            $search
        );

    $sql .= "
        AND (
            e.eventTitle LIKE '%$searchSafe%'
            OR c.ClubName LIKE '%$searchSafe%'
        )
    ";
}

if ($status !== '') {

    $statusSafe =
        mysqli_real_escape_string(
            $link,
            $status
        );

    $sql .= "
        AND e.eventStatus='$statusSafe'
    ";
}

$sql .= "
ORDER BY e.eventDate DESC
";

$events = mysqli_query($link, $sql);

$pageTitle = 'Event Management';
$activePage = 'events';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<?php include 'includes/head.php'; ?>

<style>

.filter-card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    margin-bottom:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.filter-row{
    display:flex;
    gap:15px;
    align-items:center;
}

.filter-row input,
.filter-row select{
    height:45px;
    padding:0 12px;
}

.table-card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.status{
    padding:6px 12px;
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

    <h2>Event Management</h2>

    <a href="adminDash.php"
       class="btn btn-secondary">

        Back Dashboard

    </a>

</div>

<div class="filter-card">

<form method="GET">

<div class="filter-row">

<input
type="text"
name="search"
placeholder="Search event..."
value="<?= htmlspecialchars($search) ?>">

<select name="status">

<option value="">
All Status
</option>

<option
value="upcoming"
<?= $status=='upcoming'?'selected':'' ?>>
Upcoming
</option>

<option
value="ongoing"
<?= $status=='ongoing'?'selected':'' ?>>
Ongoing
</option>

<option
value="completed"
<?= $status=='completed'?'selected':'' ?>>
Completed
</option>

</select>

<button
type="submit"
class="btn btn-primary">

Search

</button>

</div>

</form>

</div>

<div class="table-card">

<table>

<thead>

<tr>

<th>Event Name</th>
<th>Club</th>
<th>Date</th>
<th>Venue</th>
<th>Participants</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if(mysqli_num_rows($events) > 0): ?>

<?php while($event = mysqli_fetch_assoc($events)): ?>

<?php

$class='upcoming';

if($event['eventStatus']=='ongoing'){
$class='ongoing';
}

if($event['eventStatus']=='completed'){
$class='completed';
}

?>

<tr>

<td>

<?= htmlspecialchars($event['eventTitle']) ?>

</td>

<td>

<?= htmlspecialchars($event['ClubName']) ?>

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

<?= $event['totalParticipants'] ?>

</td>

<td>

<span class="status <?= $class ?>">

<?= ucfirst($event['eventStatus']) ?>

</span>

</td>

<td>

<a
href="adminEventDetails.php?id=<?= $event['eventID'] ?>"
class="btn btn-primary btn-sm">

View

</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="7"
style="text-align:center;">

No events found.

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