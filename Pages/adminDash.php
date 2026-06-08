<?php
// ================================================================
//  adminDash.php
//  Admin dashboard — summary statistics and Module 2 club overview.
//  Access: admin role only.
// ================================================================

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

// ── Main statistics ───────────────────────────────────────────
$totalStudents = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM student"))[0];
$totalClubs    = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM club"))[0];
$activeClubs   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM club WHERE ClubStatus = 'active'"))[0];
$totalEvents   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM event"))[0];
$totalCommitteeMembers = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(DISTINCT userID) FROM clubcommitee"))[0];
$totalClubInvolvement  = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(DISTINCT userID) FROM clubmembership"))[0];

// ── Recent students ───────────────────────────────────────────
$recentStudents = mysqli_query($link,
    "SELECT l.name, s.StudentID, s.Programme
     FROM student s
     JOIN login   l ON l.UserID = s.UserID
     ORDER  BY s.StudentID DESC
     LIMIT  5"
);

// ── Upcoming events ───────────────────────────────────────────
$upcomingEvents = mysqli_query($link,
    "SELECT e.eventTitle, c.ClubName, e.eventDate, e.eventStatus
     FROM event e
     JOIN club  c ON c.ClubID = e.ClubID
     WHERE  e.eventStatus = 'upcoming' OR e.eventDate >= CURDATE()
     ORDER  BY e.eventDate ASC
     LIMIT  5"
);

// ── Module 2 chart data: distribution of students across clubs ─
$clubDistribution = mysqli_query($link,
    "SELECT c.ClubName, COUNT(DISTINCT cm.userID) AS totalMembers
     FROM club c
     LEFT JOIN clubmembership cm ON cm.clubID = c.ClubID
     GROUP BY c.ClubID, c.ClubName
     ORDER BY totalMembers DESC, c.ClubName ASC
     LIMIT 8"
);
$maxMembersRow = mysqli_fetch_row(mysqli_query($link,
    "SELECT COALESCE(MAX(member_count), 0)
     FROM (
        SELECT COUNT(DISTINCT userID) AS member_count
        FROM clubmembership
        GROUP BY clubID
     ) AS club_counts"
));
$maxMembers = max(1, (int)($maxMembersRow[0] ?? 0));

$clubStatusSummary = mysqli_query($link,
    "SELECT ClubStatus, COUNT(*) AS total
     FROM club
     GROUP BY ClubStatus"
);

$pageTitle  = 'Admin Dashboard';
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>
<div class="app">
    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <h2>Admin Dashboard</h2>

        <div class="quick-actions">
            <a href="adminClubManagement.php" class="btn btn-primary btn-sm">Manage Clubs</a>
            <a href="adminCommitteeManagement.php" class="btn btn-secondary btn-sm">Manage Committees</a>
        </div>

        <!-- ── Stats row ── -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= (int)$totalStudents ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$totalClubs ?></div>
                <div class="stat-label">Total Clubs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$activeClubs ?></div>
                <div class="stat-label">Active Clubs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$totalEvents ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$totalCommitteeMembers ?></div>
                <div class="stat-label">Committee Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$totalClubInvolvement ?></div>
                <div class="stat-label">Students in Clubs</div>
            </div>
        </div>

        <!-- ── Module 2 dashboard charts ── -->
        <div class="dashboard-grid">
            <div class="card">
                <h3>Distribution of Students Across Clubs</h3>
                <?php if ($clubDistribution && mysqli_num_rows($clubDistribution) > 0): ?>
                    <div class="distribution-list">
                        <?php while ($row = mysqli_fetch_assoc($clubDistribution)): ?>
                            <?php $percent = ((int)$row['totalMembers'] / $maxMembers) * 100; ?>
                            <div class="distribution-item">
                                <div class="distribution-label">
                                    <span><?= htmlspecialchars($row['ClubName']) ?></span>
                                    <strong><?= (int)$row['totalMembers'] ?></strong>
                                </div>
                                <div class="distribution-track">
                                    <div class="distribution-bar" style="width: <?= $percent ?>%;"></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="muted-text">No club membership data available yet.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Club Operational Status</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($clubStatusSummary && mysqli_num_rows($clubStatusSummary) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($clubStatusSummary)): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= strtolower($row['ClubStatus']) === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= ucfirst($row['ClubStatus']) ?>
                                    </span>
                                </td>
                                <td><?= (int)$row['total'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align:center; color:#999;">No club status data.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Recently registered students ── -->
        <div class="card">
            <h3>Recently Registered Students</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Programme</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentStudents && mysqli_num_rows($recentStudents) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recentStudents)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['StudentID']) ?></td>
                                <td><?= htmlspecialchars($row['Programme'] ?? '—') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#999;">No students registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Upcoming events ── -->
        <div class="card">
            <h3>Upcoming Events</h3>
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
                    <?php if ($upcomingEvents && mysqli_num_rows($upcomingEvents) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($upcomingEvents)): ?>
                            <tr>
                                <td><?= htmlspecialchars($ev['eventTitle']) ?></td>
                                <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                                <td><?= htmlspecialchars($ev['eventDate']) ?></td>
                                <td><span class="badge badge-pending"><?= ucfirst($ev['eventStatus']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#999;">No upcoming events.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
