<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

$userID = $_SESSION['UserID'];

/* Get Student Details */
$stmt = mysqli_prepare(
    $link,
    "SELECT *
     FROM student
     WHERE UserID = ?"
);

mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);

$student =
mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

$studentID = $student['StudentID'];

/* Statistics */

$totalRegistered =
mysqli_num_rows(
    mysqli_query(
        $link,
        "SELECT *
         FROM registration
         WHERE studentID='$studentID'
         AND registrationStatus='Registered'"
    )
);

$totalWaiting =
mysqli_num_rows(
    mysqli_query(
        $link,
        "SELECT *
         FROM registration
         WHERE studentID='$studentID'
         AND registrationStatus='Waiting List'"
    )
);

$totalCompleted =
mysqli_num_rows(
    mysqli_query(
        $link,
        "SELECT *
         FROM registration
         WHERE studentID='$studentID'
         AND registrationStatus='Completed'"
    )
);

/* Registration History */

$registrations =
mysqli_query(
    $link,
    "SELECT
        r.registrationID,
        r.registrationStatus,
        r.registrationDate,
        e.eventID,
        e.eventTitle,
        e.eventDate,
        e.eventVenue

     FROM registration r

     JOIN event e
     ON e.eventID = r.eventID

     WHERE r.studentID='$studentID'

     ORDER BY r.registrationDate DESC"
);

$pageTitle = 'My Events';
$activePage = 'myevents';

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
        margin-bottom:25px;
    }

    .stat-card{
        background:#fff;
        padding:25px;
        border-radius:12px;
        text-align:center;
        box-shadow:0 2px 10px rgba(0,0,0,0.08);
    }

    .stat-card h3{
        font-size:32px;
        color:#1f3f77;
        margin:0;
    }

    .stat-card p{
        margin-top:10px;
        color:#666;
    }

    .card{
        background:#fff;
        padding:25px;
        border-radius:12px;
        box-shadow:0 2px 10px rgba(0,0,0,0.08);
    }

    table{
        width:100%;
        border-collapse:collapse;
    }

    th{
        background:#1f3f77;
        color:#fff;
        padding:12px;
        text-align:left;
    }

    td{
        padding:12px;
        border-bottom:1px solid #ddd;
    }

    .badge{
        padding:5px 12px;
        border-radius:20px;
        font-size:12px;
        font-weight:600;
    }

    .badge-active{
        background:#d1fae5;
        color:#065f46;
    }

    .badge-pending{
        background:#fef3c7;
        color:#92400e;
    }

    .badge-primary{
        background:#dbeafe;
        color:#1e40af;
    }

    .badge-danger{
        background:#fee2e2;
        color:#991b1b;
    }

    .btn-sm{
        padding:6px 12px;
        font-size:13px;
    }

    </style>

</head>

<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>My Event Registrations</h2>

        <?php if(isset($_SESSION['success_msg'])): ?>

            <div class="alert alert-success">

                <?= $_SESSION['success_msg']; ?>

            </div>

            <?php unset($_SESSION['success_msg']); ?>

        <?php endif; ?>

        <div class="stats-grid">

            <div class="stat-card">
                <h3><?= $totalRegistered ?></h3>
                <p>Registered Events</p>
            </div>

            <div class="stat-card">
                <h3><?= $totalWaiting ?></h3>
                <p>Waiting List</p>
            </div>

            <div class="stat-card">
                <h3><?= $totalCompleted ?></h3>
                <p>Completed Events</p>
            </div>

        </div>

        <div class="card">

            <h3>Registration History</h3>

            <table>

                <thead>

                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Registered On</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if(mysqli_num_rows($registrations) > 0): ?>

                    <?php while($row = mysqli_fetch_assoc($registrations)): ?>

                        <?php

                        $badge = 'badge-danger';

                        switch($row['registrationStatus']){

                            case 'Registered':
                                $badge = 'badge-active';
                                break;

                            case 'Waiting List':
                                $badge = 'badge-pending';
                                break;

                            case 'Completed':
                                $badge = 'badge-primary';
                                break;

                        }

                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($row['eventTitle']) ?>
                            </td>

                            <td>
                                <?= date(
                                    'd M Y',
                                    strtotime($row['eventDate'])
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['eventVenue']) ?>
                            </td>

                            <td>

                                <span class="badge <?= $badge ?>">

                                    <?= htmlspecialchars(
                                        $row['registrationStatus']
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <?= date(
                                    'd M Y',
                                    strtotime($row['registrationDate'])
                                ) ?>

                            </td>

                            <td>

                                <a
                                    href="StudRegistrationDetails.php?id=<?= $row['registrationID'] ?>"
                                    class="btn btn-primary btn-sm">

                                    View

                                </a>

                                <?php if(
                                $row['registrationStatus'] === 'Registered'
                                ||
                                $row['registrationStatus'] === 'Waiting List'
                                ): ?>

                                    <a
                                        href="CancelRegistration.php?id=<?= $row['registrationID'] ?>"
                                        class="btn btn-secondary btn-sm"
                                        onclick="return confirm('Cancel this registration?')">

                                        Cancel

                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" style="text-align:center;">

                            No registration found.

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
