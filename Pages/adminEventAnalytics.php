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

/* ===========================
   EVENT STATISTICS
=========================== */

$totalEvents = mysqli_fetch_row(
    mysqli_query(
        $link,
        "SELECT COUNT(*) FROM event"
    )
)[0];

$totalRegistrations = mysqli_fetch_row(
    mysqli_query(
        $link,
        "SELECT COUNT(*) FROM registration"
    )
)[0];

$totalUpcoming = mysqli_fetch_row(
    mysqli_query(
        $link,
        "SELECT COUNT(*)
         FROM event
         WHERE eventStatus='upcoming'"
    )
)[0];

$totalCompleted = mysqli_fetch_row(
    mysqli_query(
        $link,
        "SELECT COUNT(*)
         FROM event
         WHERE eventStatus='completed'"
    )
)[0];

/* ===========================
   POPULAR EVENTS
=========================== */

$popularEvents = mysqli_query(
    $link,
    "SELECT

        e.eventTitle,

        COUNT(r.registrationID)
        AS totalParticipants

     FROM event e

     LEFT JOIN registration r
     ON r.eventID = e.eventID

     GROUP BY e.eventID

     ORDER BY totalParticipants DESC

     LIMIT 5"
);

/* ===========================
   EVENTS BY CLUB
=========================== */

$eventsByClub = mysqli_query(
    $link,
    "SELECT

        c.ClubName,

        COUNT(e.eventID)
        AS totalEvents

     FROM club c

     LEFT JOIN event e
     ON e.ClubID = c.ClubID

     GROUP BY c.ClubID

     ORDER BY totalEvents DESC"
);

/* ===========================
   REGISTRATION STATUS
=========================== */

$registrationStatus = mysqli_query(
    $link,
    "SELECT

        registrationStatus,

        COUNT(*) AS total

     FROM registration

     GROUP BY registrationStatus"
);

$pageTitle = 'Event Analytics';
$activePage = 'analytics';

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

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:25px;
}

.stat-card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.stat-number{
    font-size:34px;
    font-weight:bold;
    color:#1f3f77;
    margin-bottom:8px;
}

.stat-label{
    color:#666;
}

.analytics-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:25px;
}

.card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.card h3{
    margin-top:0;
    color:#1f3f77;
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

.progress-bar{
    width:100%;
    background:#e5e7eb;
    border-radius:10px;
    overflow:hidden;
    height:10px;
}

.progress-fill{
    background:#1f3f77;
    height:100%;
}

@media(max-width:900px){

    .stats-grid{
        grid-template-columns:1fr 1fr;
    }

    .analytics-grid{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>

<div class="app">

<?php include 'includes/sidebar_admin.php'; ?>

<main class="main-content">

<div class="page-header">

    <h2>Event Analytics</h2>

    <a href="adminDash.php"
       class="btn btn-secondary">

        Back Dashboard

    </a>

</div>

<!-- Statistics -->

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-number">
            <?= $totalEvents ?>
        </div>
        <div class="stat-label">
            Total Events
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-number">
            <?= $totalRegistrations ?>
        </div>
        <div class="stat-label">
            Registrations
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-number">
            <?= $totalUpcoming ?>
        </div>
        <div class="stat-label">
            Upcoming Events
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-number">
            <?= $totalCompleted ?>
        </div>
        <div class="stat-label">
            Completed Events
        </div>
    </div>

</div>

<div class="analytics-grid">

    <!-- Popular Events -->

    <div class="card">

        <h3>Most Popular Events</h3>

        <table>

            <thead>

            <tr>
                <th>Event</th>
                <th>Participants</th>
            </tr>

            </thead>

            <tbody>

            <?php while($row = mysqli_fetch_assoc($popularEvents)): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($row['eventTitle']) ?>
                </td>

                <td>
                    <?= $row['totalParticipants'] ?>
                </td>

            </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

    <!-- Registration Status -->

    <div class="card">

        <h3>Registration Status Summary</h3>

        <table>

            <thead>

            <tr>
                <th>Status</th>
                <th>Total</th>
            </tr>

            </thead>

            <tbody>

            <?php while($row = mysqli_fetch_assoc($registrationStatus)): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($row['registrationStatus']) ?>
                </td>

                <td>
                    <?= $row['total'] ?>
                </td>

            </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

<br>

<div class="card">

    <h3>Events Organized by Club</h3>

    <table>

        <thead>

        <tr>
            <th>Club Name</th>
            <th>Total Events</th>
        </tr>

        </thead>

        <tbody>

        <?php while($row = mysqli_fetch_assoc($eventsByClub)): ?>

        <tr>

            <td>
                <?= htmlspecialchars($row['ClubName']) ?>
            </td>

            <td>
                <?= $row['totalEvents'] ?>
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