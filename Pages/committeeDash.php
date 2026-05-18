<?php
// ================================================================
//  committeeDash.php
//  Module 2 — Committee Dashboard.
//  Access: student role only, but user must be assigned in ClubCommitee.
// ================================================================

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

// Sidebar profile photo
$stmt = mysqli_prepare($link, 'SELECT Studphoto FROM Student WHERE UserID = ?');
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Confirm committee access
$accessStmt = mysqli_prepare($link,
    'SELECT COUNT(*) AS total FROM ClubCommitee WHERE userID = ?'
);
mysqli_stmt_bind_param($accessStmt, 's', $userID);
mysqli_stmt_execute($accessStmt);
$accessRow = mysqli_fetch_assoc(mysqli_stmt_get_result($accessStmt));
$isCommittee = ((int)($accessRow['total'] ?? 0)) > 0;

if (!$isCommittee) {
    $pageTitle  = 'Committee Dashboard';
    $activePage = 'committee_dashboard';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><?php include 'includes/head.php'; ?></head>
    <body>
    <div class="app">
        <?php include 'includes/sidebar_student.php'; ?>
        <main class="main-content">
            <h2>Committee Dashboard</h2>
            <div class="alert alert-danger">You are not assigned as a committee member for any club yet.</div>
            <a href="studDash.php" class="btn btn-primary">Back to Student Dashboard</a>
        </main>
    </div>
    </body>
    </html>
    <?php
    exit();
}

function scalar_query(mysqli $link, string $sql, string $userID): int
{
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 's', $userID);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
    return (int)($row[0] ?? 0);
}

$managedClubs = scalar_query($link,
    'SELECT COUNT(DISTINCT clubID) FROM ClubCommitee WHERE userID = ?',
    $userID
);
$totalMembers = scalar_query($link,
    'SELECT COUNT(DISTINCT cm.memberID)
     FROM ClubMembership cm
     JOIN ClubCommitee cc ON cc.clubID = cm.clubID
     WHERE cc.userID = ?',
    $userID
);
$upcomingEvents = scalar_query($link,
    "SELECT COUNT(DISTINCT e.eventID)
     FROM Event e
     JOIN ClubCommitee cc ON cc.clubID = e.ClubID
     WHERE cc.userID = ? AND (e.eventStatus = 'upcoming' OR e.eventDate >= CURDATE())",
    $userID
);
$totalParticipants = scalar_query($link,
    'SELECT COUNT(DISTINCT r.registrationID)
     FROM Registration r
     JOIN Event e ON e.eventID = r.eventID
     JOIN ClubCommitee cc ON cc.clubID = e.ClubID
     WHERE cc.userID = ?',
    $userID
);

$clubsStmt = mysqli_prepare($link,
    "SELECT c.ClubID, c.ClubName, c.ClubStatus, cc.commiteePosition,
            COUNT(DISTINCT cm.memberID) AS totalMembers,
            COUNT(DISTINCT e.eventID) AS totalEvents
     FROM ClubCommitee cc
     JOIN Club c ON c.ClubID = cc.clubID
     LEFT JOIN ClubMembership cm ON cm.clubID = c.ClubID
     LEFT JOIN Event e ON e.ClubID = c.ClubID
     WHERE cc.userID = ?
     GROUP BY c.ClubID, c.ClubName, c.ClubStatus, cc.commiteePosition
     ORDER BY c.ClubName ASC"
);
mysqli_stmt_bind_param($clubsStmt, 's', $userID);
mysqli_stmt_execute($clubsStmt);
$clubsManaged = mysqli_stmt_get_result($clubsStmt);

$eventsStmt = mysqli_prepare($link,
    "SELECT e.eventTitle, c.ClubName, e.eventDate, e.eventTime, e.eventVenue, e.eventStatus,
            COUNT(DISTINCT r.registrationID) AS registrations
     FROM Event e
     JOIN Club c ON c.ClubID = e.ClubID
     JOIN ClubCommitee cc ON cc.clubID = c.ClubID
     LEFT JOIN Registration r ON r.eventID = e.eventID
     WHERE cc.userID = ? AND (e.eventStatus = 'upcoming' OR e.eventDate >= CURDATE())
     GROUP BY e.eventID, e.eventTitle, c.ClubName, e.eventDate, e.eventTime, e.eventVenue, e.eventStatus
     ORDER BY e.eventDate ASC, e.eventTime ASC
     LIMIT 10"
);
mysqli_stmt_bind_param($eventsStmt, 's', $userID);
mysqli_stmt_execute($eventsStmt);
$managedEvents = mysqli_stmt_get_result($eventsStmt);

$participantsStmt = mysqli_prepare($link,
    "SELECT r.registrationDate, r.registrationStatus, e.eventTitle, c.ClubName, s.StudentName, s.StudentID
     FROM Registration r
     JOIN Event e ON e.eventID = r.eventID
     JOIN Club c ON c.ClubID = e.ClubID
     JOIN ClubCommitee cc ON cc.clubID = c.ClubID
     JOIN Student s ON s.StudentID = r.studentID
     WHERE cc.userID = ?
     ORDER BY r.registrationDate DESC
     LIMIT 10"
);
mysqli_stmt_bind_param($participantsStmt, 's', $userID);
mysqli_stmt_execute($participantsStmt);
$participants = mysqli_stmt_get_result($participantsStmt);

$pageTitle  = 'Committee Dashboard';
$activePage = 'committee_dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>
<div class="app">
    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">
        <h2>Committee Dashboard</h2>

        <div class="alert alert-info">
            This dashboard is only shown to students assigned as committee members. Normal students will continue using the Student Dashboard.
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $managedClubs ?></div>
                <div class="stat-label">Managed Clubs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalMembers ?></div>
                <div class="stat-label">Members in My Clubs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $upcomingEvents ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalParticipants ?></div>
                <div class="stat-label">Event Registrations</div>
            </div>
        </div>

        <div class="card">
            <h3>My Committee Roles</h3>
            <table>
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>My Position</th>
                        <th>Members</th>
                        <th>Events</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($clubsManaged && mysqli_num_rows($clubsManaged) > 0): ?>
                    <?php while ($club = mysqli_fetch_assoc($clubsManaged)): ?>
                        <tr>
                            <td><?= htmlspecialchars($club['ClubName']) ?></td>
                            <td><span class="badge badge-pending"><?= htmlspecialchars($club['commiteePosition']) ?></span></td>
                            <td><?= (int)$club['totalMembers'] ?></td>
                            <td><?= (int)$club['totalEvents'] ?></td>
                            <td>
                                <span class="badge <?= strtolower($club['ClubStatus']) === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($club['ClubStatus']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#999;">No committee roles found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Upcoming Events Under My Clubs</h3>
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Club</th>
                        <th>Date / Time</th>
                        <th>Venue</th>
                        <th>Registrations</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($managedEvents && mysqli_num_rows($managedEvents) > 0): ?>
                    <?php while ($ev = mysqli_fetch_assoc($managedEvents)): ?>
                        <tr>
                            <td><?= htmlspecialchars($ev['eventTitle']) ?></td>
                            <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                            <td><?= htmlspecialchars($ev['eventDate']) ?> <?= htmlspecialchars($ev['eventTime']) ?></td>
                            <td><?= htmlspecialchars($ev['eventVenue']) ?></td>
                            <td><?= (int)$ev['registrations'] ?></td>
                            <td><span class="badge badge-pending"><?= ucfirst($ev['eventStatus']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#999;">No upcoming events found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Recent Event Participation Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Club</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($participants && mysqli_num_rows($participants) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($participants)): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['StudentName']) ?></strong><br>
                                <small><?= htmlspecialchars($p['StudentID']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['eventTitle']) ?></td>
                            <td><?= htmlspecialchars($p['ClubName']) ?></td>
                            <td><?= htmlspecialchars($p['registrationDate']) ?></td>
                            <td><span class="badge badge-active"><?= htmlspecialchars($p['registrationStatus']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#999;">No participation records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
