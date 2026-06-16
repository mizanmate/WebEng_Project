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
$clubChartLabels = [];
$clubChartCounts = [];
while ($row = mysqli_fetch_assoc($clubDistribution)) {
    $clubChartLabels[] = $row['ClubName'];
    $clubChartCounts[] = (int)$row['totalMembers'];
}

$clubStatusSummary = mysqli_query($link,
    "SELECT ClubStatus, COUNT(*) AS total
     FROM club
     GROUP BY ClubStatus"
);
$statusChartLabels = [];
$statusChartCounts = [];
$statusChartColors = [];
$sColorMap = ['active' => '#27ae60', 'inactive' => '#e74c3c', 'suspended' => '#f39c12'];
while ($row = mysqli_fetch_assoc($clubStatusSummary)) {
    $statusChartLabels[] = ucfirst($row['ClubStatus']);
    $statusChartCounts[] = (int)$row['total'];
    $statusChartColors[] = $sColorMap[strtolower($row['ClubStatus'])] ?? '#95a5a6';
}

// ── Chart data: events by status ─────────────────────────────
$evStatusRes = mysqli_query($link,
    "SELECT eventStatus, COUNT(*) AS total FROM event
     GROUP BY eventStatus
     ORDER BY FIELD(eventStatus, 'upcoming', 'ongoing', 'completed')"
);
$evChartLabels = [];
$evChartCounts = [];
$evChartColors = [];
$evColorMap = ['upcoming' => '#f39c12', 'ongoing' => '#27ae60', 'completed' => '#1a3c6e'];
while ($row = mysqli_fetch_assoc($evStatusRes)) {
    $evChartLabels[] = ucfirst($row['eventStatus']);
    $evChartCounts[] = (int)$row['total'];
    $evChartColors[] = $evColorMap[strtolower($row['eventStatus'])] ?? '#95a5a6';
}

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

        <!-- ── dashboard charts ── -->
        <div class="dashboard-grid">
            <div class="card">
                <h3>Distribution of Students Across Clubs</h3>
                <?php if (!empty($clubChartLabels)): ?>
                    <div class="chart-bar-wrap">
                        <canvas id="clubMemberChart"></canvas>
                    </div>
                <?php else: ?>
                    <p class="muted-text">No club membership data available yet.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Club Operational Status</h3>
                <?php if (!empty($statusChartLabels)): ?>
                    <div class="chart-doughnut-wrap">
                        <canvas id="clubStatusChart"></canvas>
                    </div>
                <?php else: ?>
                    <p class="muted-text">No club status data.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Events by status chart ── -->
        <div class="card">
            <h3>Events by Status</h3>
            <?php if (!empty($evChartLabels)): ?>
                <div class="chart-event-wrap">
                    <canvas id="eventStatusChart"></canvas>
                </div>
            <?php else: ?>
                <p class="muted-text">No event data available yet.</p>
            <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($clubChartLabels)): ?>
new Chart(document.getElementById('clubMemberChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($clubChartLabels) ?>,
        datasets: [{
            label: 'Members',
            data: <?= json_encode($clubChartCounts) ?>,
            backgroundColor: '#1a3c6e',
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php endif; ?>

<?php if (!empty($statusChartLabels)): ?>
new Chart(document.getElementById('clubStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusChartLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusChartCounts) ?>,
            backgroundColor: <?= json_encode($statusChartColors) ?>,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
<?php endif; ?>

<?php if (!empty($evChartLabels)): ?>
new Chart(document.getElementById('eventStatusChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($evChartLabels) ?>,
        datasets: [{
            label: 'Events',
            data: <?= json_encode($evChartCounts) ?>,
            backgroundColor: <?= json_encode($evChartColors) ?>,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php endif; ?>
</script>

</body>
</html>
