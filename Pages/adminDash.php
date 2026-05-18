<?php
// ================================================================
//  adminDash.php
<<<<<<< HEAD
//  Admin dashboard — summary statistics and Module 2 club overview.
//  Access: admin role only.
// ================================================================

session_start();
=======
//  Admin dashboard — summary statistics and quick overview.
//  Access: admin role only.
// ================================================================

// ── Auth check ────────────────────────────────────────────────
session_start();

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

<<<<<<< HEAD
require_once 'DB_connection.php';

// ── Main statistics ───────────────────────────────────────────
=======
// ── Database ──────────────────────────────────────────────────
require_once 'DB_connection.php';

// ── Statistics ────────────────────────────────────────────────
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
$totalStudents = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Student"))[0];
$totalClubs    = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Club"))[0];
$activeClubs   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Club WHERE ClubStatus = 'active'"))[0];
$totalEvents   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Event"))[0];
<<<<<<< HEAD
$totalCommitteeMembers = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(DISTINCT userID) FROM ClubCommitee"))[0];
$totalClubInvolvement  = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(DISTINCT userID) FROM ClubMembership"))[0];

// ── Recent students ───────────────────────────────────────────
=======

// ── Recent students (last 5 registered) ───────────────────────
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
$recentStudents = mysqli_query($link,
    "SELECT l.name, s.StudentID, s.Programme
     FROM   Student s
     JOIN   Login   l ON l.UserID = s.UserID
     ORDER  BY s.StudentID DESC
     LIMIT  5"
);

<<<<<<< HEAD
// ── Upcoming events ───────────────────────────────────────────
=======
// ── Upcoming events (from Event table — populated by other module) ──
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
$upcomingEvents = mysqli_query($link,
    "SELECT e.eventTitle, c.ClubName, e.eventDate, e.eventStatus
     FROM   Event e
     JOIN   Club  c ON c.ClubID = e.ClubID
<<<<<<< HEAD
     WHERE  e.eventStatus = 'upcoming' OR e.eventDate >= CURDATE()
=======
     WHERE  e.eventStatus = 'upcoming'
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
     ORDER  BY e.eventDate ASC
     LIMIT  5"
);

<<<<<<< HEAD
// ── Module 2 chart data: distribution of students across clubs ─
$clubDistribution = mysqli_query($link,
    "SELECT c.ClubName, COUNT(DISTINCT cm.userID) AS totalMembers
     FROM Club c
     LEFT JOIN ClubMembership cm ON cm.clubID = c.ClubID
     GROUP BY c.ClubID, c.ClubName
     ORDER BY totalMembers DESC, c.ClubName ASC
     LIMIT 8"
);
$maxMembersRow = mysqli_fetch_row(mysqli_query($link,
    "SELECT COALESCE(MAX(member_count), 0)
     FROM (
        SELECT COUNT(DISTINCT userID) AS member_count
        FROM ClubMembership
        GROUP BY clubID
     ) AS club_counts"
));
$maxMembers = max(1, (int)($maxMembersRow[0] ?? 0));

$clubStatusSummary = mysqli_query($link,
    "SELECT ClubStatus, COUNT(*) AS total
     FROM Club
     GROUP BY ClubStatus"
);

$pageTitle  = 'Admin Dashboard';
$activePage = 'dashboard';
?>
=======
// ── Page meta ─────────────────────────────────────────────────
$pageTitle  = 'Admin Dashboard'; //used in head for Tab title
$activePage = 'dashboard'; //used in sidebar to highlight current page
?>

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>
<<<<<<< HEAD
<div class="app">
    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <h2>Admin Dashboard</h2>

        <div class="quick-actions">
            <a href="adminClubManagement.php" class="btn btn-primary btn-sm">Manage Clubs</a>
            <a href="adminCommitteeManagement.php" class="btn btn-secondary btn-sm">Manage Committees</a>
        </div>
=======

<div class="app">

    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">

        <h2>Admin Dashboard</h2>
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999

        <!-- ── Stats row ── -->
        <div class="stats-grid">
            <div class="stat-card">
<<<<<<< HEAD
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
=======
                <div class="stat-number"><?= $totalStudents ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalClubs ?></div>
                <div class="stat-label">Total Clubs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $activeClubs ?></div>
                <div class="stat-label">Active Clubs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalEvents ?></div>
                <div class="stat-label">Total Events</div>
            </div>
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
        </div>

        <!-- ── Recently registered students ── -->
        <div class="card">
            <h3>Recently Registered Students</h3>
            <table>
<<<<<<< HEAD
=======

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Programme</th>
                    </tr>
                </thead>
<<<<<<< HEAD
=======

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
                <tbody>
                    <?php if ($recentStudents && mysqli_num_rows($recentStudents) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recentStudents)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['StudentID']) ?></td>
                                <td><?= htmlspecialchars($row['Programme'] ?? '—') ?></td>
                            </tr>
                        <?php endwhile; ?>
<<<<<<< HEAD
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
=======

                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center; color:#999;">
                                No students registered yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>

        <!-- ── Upcoming events (populated by Event module) ── -->
        <div class="card">
            <h3>Upcoming Events</h3>
            <table>

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Club</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
<<<<<<< HEAD
=======

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
                <tbody>
                    <?php if ($upcomingEvents && mysqli_num_rows($upcomingEvents) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($upcomingEvents)): ?>
                            <tr>
                                <td><?= htmlspecialchars($ev['eventTitle']) ?></td>
                                <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                                <td><?= htmlspecialchars($ev['eventDate']) ?></td>
<<<<<<< HEAD
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
=======
                                <td>
                                    <span class="badge badge-pending">
                                        <?= ucfirst($ev['eventStatus']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#999;">
                                No upcoming events.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

        </div>

    </main>
</div>

>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
</body>
</html>
