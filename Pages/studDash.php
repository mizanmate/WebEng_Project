<?php
// ── Auth ──────────────────────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

// ── DB ────────────────────────────────────────────────────────────────────
require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

// Sidebar (Studphoto)
$stmt = mysqli_prepare($link, 'SELECT Studphoto FROM student WHERE UserID = ?');
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Clubs the student has joined
$myClubs = mysqli_prepare($link,
    "SELECT c.ClubName, c.ClubStatus
     FROM club          c
     JOIN clubmembership cm ON cm.clubID = c.ClubID
     WHERE  cm.userID = ?"
);
mysqli_stmt_bind_param($myClubs, 's', $userID);
mysqli_stmt_execute($myClubs);
$myClubsResult = mysqli_stmt_get_result($myClubs);

// Events from those clubs
$evStmt = mysqli_prepare($link,
    "SELECT DISTINCT e.eventTitle, c.ClubName, e.eventDate, e.eventStatus
     FROM event         e
     JOIN club          c  ON c.ClubID  = e.ClubID
     JOIN clubmembership cm ON cm.clubID = e.ClubID
     WHERE  cm.userID = ?
     ORDER  BY e.eventDate ASC"
);
mysqli_stmt_bind_param($evStmt, 's', $userID);
mysqli_stmt_execute($evStmt);
$events = mysqli_stmt_get_result($evStmt);

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'Student Dashboard';
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Student Dashboard</h2>

        <!-- ── My clubs ── -->
        <div class="card">
            <h3>My Clubs</h3>

            <?php if ($myClubsResult && mysqli_num_rows($myClubsResult) > 0): ?>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
                    <?php while ($cl = mysqli_fetch_assoc($myClubsResult)): ?>
                        <span class="badge <?= strtolower($cl['ClubStatus']) === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= htmlspecialchars($cl['ClubName']) ?>
                        </span>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:#999; margin-top:8px;">
                    You have not joined any clubs yet.
                    <a href="StudJoinClub.php">Join a club</a>.
                </p>
            <?php endif; ?>
        </div>

        <!-- ── Club events ── -->
        <div class="card">
            <h3>Club Events</h3>

            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Club</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events && mysqli_num_rows($events) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($events)): ?>
                            <?php
                            $badge = match(strtolower($ev['eventStatus'])) {
                                'upcoming'  => 'badge-pending',
                                'ongoing'   => 'badge-active',
                                'completed' => 'badge-inactive',
                                default     => 'badge-inactive',
                            };
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($ev['eventTitle']) ?></td>
                                <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                                <td><?= htmlspecialchars($ev['eventDate']) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($ev['eventStatus']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#999;">
                                No events from your clubs yet.
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
