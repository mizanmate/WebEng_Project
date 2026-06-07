<?php
// ── Auth ───────────────────────────────────────────────────────────────
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

// ── Database ───────────────────────────────────────────────────────────
require_once 'DB_connection.php';

$userID = $_SESSION['UserID'];

// Sidebar profile picture
$stmt = mysqli_prepare($link,
    'SELECT Studphoto FROM Student WHERE UserID = ?'
);
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ── Filters ────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$club   = trim($_GET['club'] ?? '');
$status = trim($_GET['status'] ?? '');

// ── Club dropdown ──────────────────────────────────────────────────────
$clubOptions = mysqli_query(
    $link,
    "SELECT ClubID, ClubName
     FROM Club
     ORDER BY ClubName ASC"
);

// ── Event Query ────────────────────────────────────────────────────────
$sql = "
SELECT
    e.eventID,
    e.eventTitle,
    e.eventDate,
    e.eventVenue,
    e.eventStatus,
    e.maxParticipants,
    c.ClubName,
    COUNT(r.registrationID) AS total_registered

FROM Event e

JOIN Club c
ON c.ClubID = e.ClubID

LEFT JOIN Registration r
ON r.eventID = e.eventID

WHERE 1=1
";

$params = [];
$types  = '';

if ($search !== '') {
    $sql .= " AND e.eventTitle LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($club !== '') {
    $sql .= " AND e.ClubID = ?";
    $params[] = $club;
    $types .= 's';
}

if ($status !== '') {
    $sql .= " AND e.eventStatus = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= "
GROUP BY e.eventID
ORDER BY e.eventDate ASC
";

$stmt = mysqli_prepare($link, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$events = mysqli_stmt_get_result($stmt);

// ── Page meta ──────────────────────────────────────────────────────────
$pageTitle  = 'Browse Events';
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

        <h2>Browse Events</h2>

        <!-- Search Card -->
        <div class="form-card">

            <form method="GET">

                <div class="form-group">
                    <label>Search Event</label>
                    <input
                        type="text"
                        name="search"
                        placeholder="Enter event name"
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="form-group">
                    <label>Club</label>

                    <select name="club">

                        <option value="">All Clubs</option>

                        <?php while ($c = mysqli_fetch_assoc($clubOptions)): ?>

                            <option
                                value="<?= htmlspecialchars($c['ClubID']) ?>"
                                <?= $club === $c['ClubID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($c['ClubName']) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div class="form-group">
                    <label>Status</label>

                    <select name="status">

                        <option value="">All Status</option>

                        <option value="upcoming"
                            <?= $status === 'upcoming' ? 'selected' : '' ?>>
                            Upcoming
                        </option>

                        <option value="ongoing"
                            <?= $status === 'ongoing' ? 'selected' : '' ?>>
                            Ongoing
                        </option>

                        <option value="completed"
                            <?= $status === 'completed' ? 'selected' : '' ?>>
                            Completed
                        </option>

                    </select>

                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                    <a href="StudBrowseEvent.php"
                       class="btn btn-secondary">
                        Clear
                    </a>
                </div>

            </form>

        </div>

        <!-- Event Table -->
        <div class="card" style="margin-top:30px;">
            
            <h3>Available Events</h3>

            <table>

                <thead>

                    <tr>
                        <th>Event Name</th>
                        <th>Club</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Slots</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($events && mysqli_num_rows($events) > 0): ?>

                    <?php while ($event = mysqli_fetch_assoc($events)): ?>

                        <?php

                        $badge = match(strtolower($event['eventStatus'])) {
                            'upcoming'  => 'badge-pending',
                            'ongoing'   => 'badge-active',
                            'completed' => 'badge-inactive',
                            default     => 'badge-inactive'
                        };

                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($event['eventTitle']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($event['ClubName']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($event['eventDate']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($event['eventVenue']) ?>
                            </td>

                            <td>
                                <?= (int)$event['total_registered'] ?>
                                /
                                <?= (int)$event['maxParticipants'] ?>
                            </td>

                            <td>
                                <span class="badge <?= $badge ?>">
                                    <?= ucfirst($event['eventStatus']) ?>
                                </span>
                            </td>

                            <td>

                                <a
                                    href="StudEventDetails.php?id=<?= urlencode($event['eventID']) ?>"
                                    class="btn btn-primary btn-sm">

                                    View

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7"
                            style="text-align:center;color:#999;">

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