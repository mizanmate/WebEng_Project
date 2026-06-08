<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$userID = $_SESSION['UserID'];

$eventID = $_GET['id'] ?? '';

if ($eventID === '') {
    header('Location: StudBrowseEvent.php');
    exit();
}

/* Sidebar */
$stmt = mysqli_prepare(
    $link,
    'SELECT Studphoto FROM Student WHERE UserID = ?'
);
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

/* Event details */
$sql = "
SELECT
    e.*,
    c.ClubName,
    COUNT(r.registrationID) AS total_registered

FROM Event e

JOIN Club c
ON c.ClubID = e.ClubID

LEFT JOIN Registration r
ON r.eventID = e.eventID

WHERE e.eventID = ?

GROUP BY e.eventID
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 's', $eventID);
mysqli_stmt_execute($stmt);

$event = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$event) {
    header('Location: StudBrowseEvent.php');
    exit();
}

$remaining =
    $event['maxParticipants']
    - $event['total_registered'];

$pageTitle = 'Event Details';
$activePage = 'events';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
</head>

<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Event Details</h2>

        <?php if(isset($_SESSION['event_msg'])): ?>

        <div class="alert alert-success">
            <?= $_SESSION['event_msg']; ?>
        </div>

        <?php unset($_SESSION['event_msg']); ?>

        <?php endif; ?>

        <div class="card">

            <h3>
                <?= htmlspecialchars($event['eventTitle']) ?>
            </h3>

            <hr style="margin:15px 0;">

            <table>

                <tr>
                    <th style="width:250px;">Club</th>
                    <td>
                        <?= htmlspecialchars($event['ClubName']) ?>
                    </td>
                </tr>

                <tr>
                    <th>Description</th>
                    <td>
                        <?= htmlspecialchars($event['eventDesc']) ?>
                    </td>
                </tr>

                <tr>
                    <th>Date</th>
                    <td>
                        <?= htmlspecialchars($event['eventDate']) ?>
                    </td>
                </tr>

                <tr>
                    <th>Time</th>
                    <td>
                        <?= htmlspecialchars($event['eventTime']) ?>
                    </td>
                </tr>

                <tr>
                    <th>Venue</th>
                    <td>
                        <?= htmlspecialchars($event['eventVenue']) ?>
                    </td>
                </tr>

                <tr>
                    <th>Maximum Participants</th>
                    <td>
                        <?= $event['maxParticipants'] ?>
                    </td>
                </tr>

                <tr>
                    <th>Registered Participants</th>
                    <td>
                        <?= $event['total_registered'] ?>
                    </td>
                </tr>

                <tr>
                    <th>Available Slots</th>
                    <td>
                        <?= $remaining ?>
                    </td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>

                        <?php
                        $badge = match(strtolower($event['eventStatus'])) {
                            'upcoming'  => 'badge-pending',
                            'ongoing'   => 'badge-active',
                            'completed' => 'badge-inactive',
                            default     => 'badge-inactive'
                        };
                        ?>

                        <span class="badge <?= $badge ?>">
                            <?= ucfirst($event['eventStatus']) ?>
                        </span>

                    </td>
                </tr>

            </table>

            <div
                style="
                margin-top:25px;
                display:flex;
                gap:10px;
            ">

                <a
                    href="StudBrowseEvent.php"
                    class="btn btn-secondary">

                    Back

                </a>

                <a
                    href="StudRegisterEvent.php?id=<?= $event['eventID'] ?>"
                    class="btn btn-primary">

                    Register Event

                </a>

            </div>

        </div>

    </main>

</div>

</body>
</html>