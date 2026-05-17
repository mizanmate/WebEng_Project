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

// Sidebar
$stmt = mysqli_prepare($link, 'SELECT Studphoto FROM Student WHERE UserID = ?');
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ── Handle leave-club request ──────────────────────────────────────────────
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_club_id'])) {
    $leaveClubID = trim($_POST['leave_club_id']);

    $del = mysqli_prepare($link,
        'DELETE FROM ClubMembership WHERE userID = ? AND clubID = ?'
    );
    mysqli_stmt_bind_param($del, 'ss', $userID, $leaveClubID);
    mysqli_stmt_execute($del);

    if (mysqli_stmt_affected_rows($del) > 0) {
        $success = 'You have left the club.';
    } else {
        $error = 'Could not leave that club.';
    }
}

// ── My clubs ──────────────────────────────────────────────────────────────
$clubStmt = mysqli_prepare($link,
    "SELECT c.ClubID, c.ClubName, c.ClubDesc, c.ClubStatus, cm.RegistrationDate, cm.clubRole
     FROM   Club          c
     JOIN   ClubMembership cm ON cm.clubID = c.ClubID
     WHERE  cm.userID = ?
     ORDER  BY cm.RegistrationDate DESC"
);
mysqli_stmt_bind_param($clubStmt, 's', $userID);
mysqli_stmt_execute($clubStmt);
$myClubs = mysqli_stmt_get_result($clubStmt);

// ── Events from my clubs ───────────────────────────────────────────────────
$evStmt = mysqli_prepare($link,
    "SELECT DISTINCT e.eventTitle, c.ClubName, e.eventDate, e.eventStatus
     FROM   Event         e
     JOIN   Club          c  ON c.ClubID  = e.ClubID
     JOIN   ClubMembership cm ON cm.clubID = e.ClubID
     WHERE  cm.userID = ?
     ORDER  BY e.eventDate ASC"
);
mysqli_stmt_bind_param($evStmt, 's', $userID);
mysqli_stmt_execute($evStmt);
$events = mysqli_stmt_get_result($evStmt);

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'Manage Club';
$activePage = 'manage_club';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Manage Club</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="quick-actions">
            <a href="StudJoinClub.php" class="btn btn-primary btn-sm">+ Join New Club</a>
        </div>

        <!-- ── My clubs table ── -->
        <div class="card">
            <h3>My Clubs</h3>

            <table>
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Description</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($myClubs && mysqli_num_rows($myClubs) > 0): ?>
                        <?php while ($c = mysqli_fetch_assoc($myClubs)): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['ClubName']) ?></td>
                                <td><?= htmlspecialchars($c['ClubDesc'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['clubRole']) ?></td>
                                <td><?= htmlspecialchars($c['RegistrationDate'] ?? '—') ?></td>
                                <td>
                                    <span class="badge <?= strtolower($c['ClubStatus']) === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= ucfirst($c['ClubStatus']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="StudManageClub.php" style="display:inline;">
                                        <input type="hidden" name="leave_club_id" value="<?= htmlspecialchars($c['ClubID']) ?>">
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Leave this club?')">
                                            Leave
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#999;">
                                You have not joined any clubs.
                                <a href="StudJoinClub.php">Join one</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Events from clubs ── -->
        <div class="card">
            <h3>Event List</h3>

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
                                No events found for your clubs.
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
