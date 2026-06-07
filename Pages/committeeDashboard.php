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

/* Committee Info */
$stmt = mysqli_prepare(
    $link,
    "SELECT
        cc.committeeID,
        cc.commiteePosition,
        c.ClubName,
        c.ClubID

     FROM ClubCommitee cc

     JOIN Club c
     ON cc.clubID = c.ClubID

     WHERE cc.userID = ?"
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

$clubID = $committee['ClubID'];

/* Total Events */
$result =
mysqli_query(
    $link,
    "SELECT COUNT(*)
     FROM Event
     WHERE ClubID='$clubID'"
);

$totalEvents =
mysqli_fetch_row($result)[0];

/* Upcoming Events */
$result =
mysqli_query(
    $link,
    "SELECT COUNT(*)
     FROM Event
     WHERE ClubID='$clubID'
     AND eventStatus='upcoming'"
);

$upcomingEvents =
mysqli_fetch_row($result)[0];

/* Participants */
$result =
mysqli_query(
    $link,
    "SELECT COUNT(*)

     FROM Registration r

     JOIN Event e
     ON r.eventID = e.eventID

     WHERE e.ClubID='$clubID'"
);

$totalParticipants =
mysqli_fetch_row($result)[0];

$pageTitle = 'Committee Dashboard';
$activePage = 'committee';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<?php include 'includes/head.php'; ?>

<style>

.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
    margin-bottom:30px;
}

.stat-card{
    background:#fff;
    border-radius:12px;
    padding:25px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.stat-card h2{
    color:#1f3f77;
    font-size:32px;
    margin-bottom:10px;
}

.action-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.action-card{
    background:#fff;
    border-radius:12px;
    padding:30px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.action-card h3{
    color:#1f3f77;
    margin-bottom:15px;
}

.action-card p{
    color:#666;
    margin-bottom:20px;
}

.action-card a{
    display:inline-block;
    padding:10px 20px;
    background:#1f3f77;
    color:white;
    text-decoration:none;
    border-radius:6px;
}

.committee-info{
    background:white;
    padding:25px;
    border-radius:12px;
    margin-bottom:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

@media(max-width:768px){

.stats-grid,
.action-grid{
    grid-template-columns:1fr;
}

}

</style>

</head>

<body>

<div class="app">

<?php include 'includes/sidebar_student.php'; ?>

<main class="main-content">

<h2>Committee Dashboard</h2>

<div class="committee-info">

    <h3>
        <?= htmlspecialchars($committee['ClubName']) ?>
    </h3>

    <p>
        Position:
        <?= htmlspecialchars($committee['commiteePosition']) ?>
    </p>

</div>

<div class="stats-grid">

    <div class="stat-card">

        <h2><?= $totalEvents ?></h2>

        <p>Total Events</p>

    </div>

    <div class="stat-card">

        <h2><?= $upcomingEvents ?></h2>

        <p>Upcoming Events</p>

    </div>

    <div class="stat-card">

        <h2><?= $totalParticipants ?></h2>

        <p>Total Participants</p>

    </div>

</div>

<div class="action-grid">

    <div class="action-card">

        <h3>Create Event</h3>

        <p>
            Create a new club event.
        </p>

        <a href="committeeCreateEvent.php">

            Open

        </a>

    </div>

    <div class="action-card">

        <h3>Manage Events</h3>

        <p>
            Edit or delete existing events.
        </p>

        <a href="committeeManageEvent.php">

            Open

        </a>

    </div>

    <div class="action-card">

        <h3>Participants</h3>

        <p>
            View registered students.
        </p>

        <a href="committeeParticipants.php">

            Open

        </a>

    </div>

</div>

</main>

</div>

</body>
</html>