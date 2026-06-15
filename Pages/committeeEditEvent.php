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
$eventID = $_GET['id'] ?? '';

$photoStmt = mysqli_prepare($link, 'SELECT Studphoto FROM student WHERE UserID = ?');
mysqli_stmt_bind_param($photoStmt, 's', $userID);
mysqli_stmt_execute($photoStmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($photoStmt));

if ($eventID == '') {
    header('Location: committeeManageEvent.php');
    exit();
}

/* Verify committee owns this event */

$stmt = mysqli_prepare(
    $link,
    "SELECT
        e.*,
        c.ClubName

     FROM event e

     JOIN club c
     ON e.ClubID = c.ClubID

     JOIN clubcommitee cc
     ON cc.clubID = e.ClubID

     WHERE e.eventID = ?
     AND cc.userID = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    'ss',
    $eventID,
    $userID
);

mysqli_stmt_execute($stmt);

$event =
mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$event) {

    header('Location: committeeManageEvent.php');
    exit();
}

$success = '';
$error = '';

/* Update event */

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

        $stmt = mysqli_prepare(
            $link,
            "UPDATE event

             SET

             eventTitle=?,
             eventDesc=?,
             eventDate=?,
             eventTime=?,
             eventVenue=?,
             maxParticipants=?,
             eventStatus=?

             WHERE eventID=?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'sssssiss',
            $eventTitle,
            $eventDesc,
            $eventDate,
            $eventTime,
            $eventVenue,
            $maxParticipants,
            $eventStatus,
            $eventID
        );

        if (mysqli_stmt_execute($stmt)) {

            $success =
                'Event updated successfully.';

            /* Refresh data */

            $stmt = mysqli_prepare(
                $link,
                "SELECT
                    e.*,
                    c.ClubName

                 FROM event e

                 JOIN club c
                 ON e.ClubID = c.ClubID

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

        } else {

            $error =
                'Failed to update event.';
        }
    }
}

$pageTitle = 'Edit Event';
$activePage = 'committee';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<?php include 'includes/head.php'; ?>

<style>

.edit-card{
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

.alert-success{
    background:#d1fae5;
    color:#065f46;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
}

.alert-danger{
    background:#fee2e2;
    color:#991b1b;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
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

<h2>Edit Event</h2>

<?php if($success): ?>

<div class="alert-success">

    <?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>

<?php if($error): ?>

<div class="alert-danger">

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<div class="edit-card">

<form method="POST">

<div class="form-group">

<label>Club</label>

<input
type="text"
value="<?= htmlspecialchars($event['ClubName']) ?>"
readonly>

</div>

<div class="form-group">

<label>Event Title</label>

<input
type="text"
name="eventTitle"
value="<?= htmlspecialchars($event['eventTitle']) ?>"
required>

</div>

<div class="form-group">

<label>Description</label>

<textarea
name="eventDesc"
rows="6"
required><?= htmlspecialchars($event['eventDesc']) ?></textarea>

</div>

<div class="form-row">

<div class="form-group">

<label>Event Date</label>

<input
type="date"
name="eventDate"
value="<?= $event['eventDate'] ?>"
required>

</div>

<div class="form-group">

<label>Event Time</label>

<input
type="time"
name="eventTime"
value="<?= $event['eventTime'] ?>"
required>

</div>

</div>

<div class="form-row">

<div class="form-group">

<label>Venue</label>

<input
type="text"
name="eventVenue"
value="<?= htmlspecialchars($event['eventVenue']) ?>"
required>

</div>

<div class="form-group">

<label>Maximum Participants</label>

<input
type="number"
name="maxParticipants"
min="1"
value="<?= $event['maxParticipants'] ?>"
required>

</div>

</div>

<div class="form-group">

<label>Status</label>

<select name="eventStatus">

<option
value="upcoming"
<?= $event['eventStatus']=='upcoming' ? 'selected' : '' ?>>

Upcoming

</option>

<option
value="ongoing"
<?= $event['eventStatus']=='ongoing' ? 'selected' : '' ?>>

Ongoing

</option>

<option
value="completed"
<?= $event['eventStatus']=='completed' ? 'selected' : '' ?>>

Completed

</option>

</select>

</div>

<div class="btn-group">

<a
href="committeeManageEvent.php"
class="btn btn-secondary">

Back

</a>

<button
type="submit"
class="btn btn-primary">

Update event

</button>

</div>

</form>

</div>

</main>

</div>

</body>

</html>