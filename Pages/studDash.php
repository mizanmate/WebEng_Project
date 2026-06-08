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

// ── Stats: clubs joined count ─────────────────────────────────────────────
$clubCountStmt = mysqli_prepare($link, 'SELECT COUNT(*) FROM clubmembership WHERE userID = ?');
mysqli_stmt_bind_param($clubCountStmt, 's', $userID);
mysqli_stmt_execute($clubCountStmt);
$clubsJoined = (int)mysqli_fetch_row(mysqli_stmt_get_result($clubCountStmt))[0];

// ── Chart data: event status breakdown from student's clubs ───────────────
$evStatusStmt = mysqli_prepare($link,
    "SELECT e.eventStatus, COUNT(DISTINCT e.eventID) AS total
     FROM event e
     JOIN clubmembership cm ON cm.clubID = e.ClubID
     WHERE cm.userID = ?
     GROUP BY e.eventStatus
     ORDER BY FIELD(e.eventStatus, 'upcoming', 'ongoing', 'completed')"
);
mysqli_stmt_bind_param($evStatusStmt, 's', $userID);
mysqli_stmt_execute($evStatusStmt);
$evStatusResult = mysqli_stmt_get_result($evStatusStmt);

$evChartLabels = [];
$evChartCounts = [];
$evChartColors = [];
$evColorMap = ['upcoming' => '#f39c12', 'ongoing' => '#27ae60', 'completed' => '#1a3c6e'];
$totalEventsCount  = 0;
$upcomingEvCount   = 0;
while ($row = mysqli_fetch_assoc($evStatusResult)) {
    $evChartLabels[] = ucfirst($row['eventStatus']);
    $evChartCounts[] = (int)$row['total'];
    $evChartColors[] = $evColorMap[strtolower($row['eventStatus'])] ?? '#95a5a6';
    $totalEventsCount += (int)$row['total'];
    if (strtolower($row['eventStatus']) === 'upcoming') {
        $upcomingEvCount = (int)$row['total'];
    }
}

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

        <!-- ── Stats row ── -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $clubsJoined ?></div>
                <div class="stat-label">Clubs Joined</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalEventsCount ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $upcomingEvCount ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>
        </div>

        <!-- ── Event status chart ── -->
        <?php if (!empty($evChartLabels)): ?>
        <div class="card">
            <h3>My Events by Status</h3>
            <div class="chart-doughnut-wrap">
                <canvas id="evStatusChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

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

<?php if (!empty($evChartLabels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('evStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($evChartLabels) ?>,
        datasets: [{
            data: <?= json_encode($evChartCounts) ?>,
            backgroundColor: <?= json_encode($evChartColors) ?>,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
<?php endif; ?>

</body>
</html>
