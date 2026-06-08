<?php

session_start();

if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$userID = $_SESSION['UserID'];

/* Verify committee member */
$stmt = mysqli_prepare(
    $link,
    "SELECT
        cc.clubID,
        c.ClubName,
        cc.commiteePosition

     FROM clubcommitee cc

     JOIN club c
     ON c.ClubID = cc.clubID

     WHERE cc.userID = ?

     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);

$committee =
mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$committee) {

    die('Access Denied. Committee members only.');

}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eventTitle = trim($_POST['eventTitle']);
    $eventDesc = trim($_POST['eventDesc']);
    $eventDate = $_POST['eventDate'];
    $eventTime = $_POST['eventTime'];
    $eventVenue = trim($_POST['eventVenue']);
    $maxParticipants = (int)$_POST['maxParticipants'];
    $eventStatus = $_POST['eventStatus'];

    if (
        $eventTitle === '' ||
        $eventDesc === '' ||
        $eventVenue === ''
    ) {

        $error = 'Please fill in all required fields.';

    } else {

        $count =
        mysqli_fetch_row(
            mysqli_query(
                $link,
                "SELECT COUNT(*) FROM event"
            )
        );

        $eventID =
            'EV' .
            str_pad(
                $count[0] + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

        $stmt = mysqli_prepare(
            $link,
            "INSERT INTO event
            (
                eventID,
                ClubID,
                eventTitle,
                eventDesc,
                eventDate,
                eventTime,
                eventVenue,
                maxParticipants,
                eventStatus,
                created_At
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'sssssssis',
            $eventID,
            $committee['clubID'],
            $eventTitle,
            $eventDesc,
            $eventDate,
            $eventTime,
            $eventVenue,
            $maxParticipants,
            $eventStatus
        );

        if (mysqli_stmt_execute($stmt)) {

            $success =
                'Event created successfully.';

        } else {

            $error =
                'Failed to create event.';
        }
    }
}

$pageTitle = 'Create Event';
$activePage = 'committee_create';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php include 'includes/head.php'; ?>

    <style>

    .create-event-card{
        background:#fff;
        padding:30px;
        border-radius:12px;
        box-shadow:0 2px 10px rgba(0,0,0,.08);
        max-width:1000px;
    }

    .form-row{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:20px;
    }

    .btn-group{
        margin-top:25px;
        display:flex;
        gap:10px;
    }

    textarea{
        width:100%;
        resize:none;
    }

    @media(max-width:768px){

        .form-row{
            grid-template-columns:1fr;
        }

    }

    </style>

</head>

<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Create Event</h2>

        <?php if($success): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

        <?php if($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <div class="create-event-card">

            <form method="POST">

                <div class="form-group">

                    <label>Club</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($committee['ClubName']) ?>"
                        readonly>

                </div>

                <div class="form-group">

                    <label>Committee Position</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($committee['commiteePosition']) ?>"
                        readonly>

                </div>

                <div class="form-group">

                    <label>Event Title</label>

                    <input
                        type="text"
                        name="eventTitle"
                        required>

                </div>

                <div class="form-group">

                    <label>Description</label>

                    <textarea
                        name="eventDesc"
                        rows="6"
                        required></textarea>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Event Date</label>

                        <input
                            type="date"
                            name="eventDate"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Event Time</label>

                        <input
                            type="time"
                            name="eventTime"
                            required>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Venue</label>

                        <input
                            type="text"
                            name="eventVenue"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Maximum Participants</label>

                        <input
                            type="number"
                            name="maxParticipants"
                            min="1"
                            required>

                    </div>

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="eventStatus">

                        <option value="upcoming">
                            Upcoming
                        </option>

                        <option value="ongoing">
                            Ongoing
                        </option>

                        <option value="completed">
                            Completed
                        </option>

                    </select>

                </div>

                <div class="btn-group">

                    <a
                        href="committeeDashboard.php"
                        class="btn btn-secondary">

                        Back

                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Create Event

                    </button>

                </div>

            </form>

        </div>

    </main>

</div>

</body>
</html>
