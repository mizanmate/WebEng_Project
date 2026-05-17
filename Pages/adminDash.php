<?php
// ================================================================
//  adminDash.php
//  Admin dashboard — summary statistics and quick overview.
//  Access: admin role only.
// ================================================================

// ── Auth check ────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// ── Database ──────────────────────────────────────────────────
require_once 'DB_connection.php';

// ── Statistics ────────────────────────────────────────────────
$totalStudents = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Student"))[0];
$totalClubs    = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Club"))[0];
$activeClubs   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Club WHERE ClubStatus = 'active'"))[0];
$totalEvents   = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM Event"))[0];

// ── Recent students (last 5 registered) ───────────────────────
$recentStudents = mysqli_query($link,
    "SELECT l.name, s.StudentID, s.Programme
     FROM   Student s
     JOIN   Login   l ON l.UserID = s.UserID
     ORDER  BY s.StudentID DESC
     LIMIT  5"
);

// ── Upcoming events (from Event table — populated by other module) ──
$upcomingEvents = mysqli_query($link,
    "SELECT e.eventTitle, c.ClubName, e.eventDate, e.eventStatus
     FROM   Event e
     JOIN   Club  c ON c.ClubID = e.ClubID
     WHERE  e.eventStatus = 'upcoming'
     ORDER  BY e.eventDate ASC
     LIMIT  5"
);

// ── Page meta ─────────────────────────────────────────────────
$pageTitle  = 'Admin Dashboard'; //used in head for Tab title
$activePage = 'dashboard'; //used in sidebar to highlight current page
?>

<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">

        <h2>Admin Dashboard</h2>

        <!-- ── Stats row ── -->
        <div class="stats-grid">
            <div class="stat-card">
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

</body>
</html>
