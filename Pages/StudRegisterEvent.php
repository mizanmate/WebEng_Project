<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$userID  = $_SESSION['UserID'];
$eventID = $_GET['id'] ?? '';

if ($eventID === '') {
    header('Location: StudBrowseEvent.php');
    exit();
}

/* Sidebar */
$stmt = mysqli_prepare(
    $link,
    'SELECT Studphoto FROM student WHERE UserID = ?'
);
mysqli_stmt_bind_param($stmt,'s',$userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

/* Student Details */
$stmt = mysqli_prepare(
    $link,
    "SELECT
        s.StudentID,
        l.name,
        s.Email,
        s.Phone

     FROM student s

     JOIN login l
     ON l.UserID = s.UserID

     WHERE s.UserID = ?"
);

mysqli_stmt_bind_param($stmt,'s',$userID);
mysqli_stmt_execute($stmt);

$student = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

/* Event Details */
$stmt = mysqli_prepare(
    $link,
    "SELECT
        e.*,
        c.ClubName

     FROM event e

     JOIN club c
     ON c.ClubID = e.ClubID

     WHERE e.eventID = ?"
);

mysqli_stmt_bind_param($stmt,'s',$eventID);
mysqli_stmt_execute($stmt);

$event = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$event) {
    header('Location: StudBrowseEvent.php');
    exit();
}

/* Register Event */
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['confirm'])) {

        $error =
            'Please confirm the registration details.';

    } else {

        /* Check existing registration */
        $chk = mysqli_prepare(
            $link,
            "SELECT registrationID
             FROM registration
             WHERE studentID = ?
             AND eventID = ?"
        );

        mysqli_stmt_bind_param(
            $chk,
            'ss',
            $student['StudentID'],
            $eventID
        );

        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {

            $error =
                'You have already registered for this event.';

        } else {

            /* Capacity Check */
            $countQuery =
                mysqli_query(
                    $link,
                    "SELECT COUNT(*)
                     FROM registration
                     WHERE eventID = '$eventID'
                     AND registrationStatus='Registered'"
                );

            $registered =
                mysqli_fetch_row($countQuery)[0];

            $status = 'Registered';

            if (
                $registered >=
                $event['maxParticipants']
            ) {
                $status = 'Waiting List';
            }

            /* Generate ID */
            $count =
                mysqli_fetch_row(
                    mysqli_query(
                        $link,
                        "SELECT COUNT(*) FROM registration"
                    )
                );

            $registrationID =
                'REG' .
                str_pad(
                    $count[0] + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

            $stmt = mysqli_prepare(
                $link,
                "INSERT INTO registration
                (
                    registrationID,
                    studentID,
                    eventID,
                    registrationStatus,
                    registrationDate
                )
                VALUES
                (
                    ?, ?, ?, ?, NOW()
                )"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'ssss',
                $registrationID,
                $student['StudentID'],
                $eventID,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {

                $_SESSION['success_msg'] =
                    ($status === 'Registered')
                    ?
                    'Event registration successful.'
                    :
                    'Event is full. Added to waiting list.';

                header('Location: StudMyEvents.php');
                exit();

            } else {

                $error =
                    'Registration failed.';
            }
        }
    }
}

$pageTitle  = 'Register Event';
$activePage = 'events';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>

    <style>
    .registration-grid{
    display:grid;
    grid-template-columns:1.3fr 1fr;
    gap:35px;
    }

    .form-card{
        width:100%;
        max-width:1300px;
        padding:35px;
    }

    .registration-section{
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:30px;
        height:100%;
    }

    .registration-section h3{
        color:#1f3f77;
        margin-bottom:25px;
    }

    .registration-section textarea{
        width:100%;
        min-height:180px;
        resize:none;
    }

    .confirm-section{
        margin-top:25px;
        padding-top:20px;
        border-top:1px solid #e5e7eb;
    }

    .action-buttons{
        margin-top:25px;
        display:flex;
        gap:12px;
    }

    @media(max-width:992px){

        .registration-grid{
            grid-template-columns:1fr;
        }

    }
    </style>

</head>

<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Event Registration</h2>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="form-card">

        <form method="POST">

            <div class="registration-grid">

                <!-- Event Information -->
                <div class="registration-section">

                    <h3>Event Information</h3>

                    <div class="form-group">
                        <label>Event Name</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($event['eventTitle']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Club</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($event['ClubName']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input
                            type="text"
                            value="<?= date('d M Y', strtotime($event['eventDate'])) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Time</label>
                        <input
                            type="text"
                            value="<?= date('g:i A', strtotime($event['eventTime'])) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Venue</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($event['eventVenue']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Description</label>

                        <textarea
                            rows="5"
                            readonly><?= htmlspecialchars($event['eventDesc']) ?></textarea>

                    </div>

                </div>

                <!-- Student Information -->
                <div class="registration-section">

                    <h3>Student Information</h3>

                    <div class="form-group">
                        <label>Student ID</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($student['StudentID']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Student Name</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($student['name']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($student['Email']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($student['Phone']) ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Registration Status</label>
                        <input
                            type="text"
                            value="Pending Confirmation"
                            readonly>
                    </div>

                    <div class="confirm-section">

                        <label>
                            <input
                                type="checkbox"
                                name="confirm">

                            I confirm that all information provided is correct.
                        </label>

                    </div>

                </div>

            </div>

            <div class="action-buttons">

                <a
                    href="StudEventDetails.php?id=<?= $eventID ?>"
                    class="btn btn-secondary">

                    Back

                </a>

                <button
                    type="submit"
                    class="btn btn-primary">

                    Confirm Registration

                </button>

            </div>

        </form>

    </div>

    </main>

</div>

</body>
</html>