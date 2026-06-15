<?php
// ================================================================
// Module 4 — Participation Report (Admin View)
// Add-on page only. No change to existing admin dashboard/modules.
// ================================================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'DB_connection.php';

$clubFilter = trim($_GET['clubID'] ?? '');
$eventFilter = trim($_GET['eventID'] ?? '');

$where = 'WHERE 1=1';
if ($clubFilter !== '') {
    $clubSafe = mysqli_real_escape_string($link, $clubFilter);
    $where .= " AND c.ClubID = '$clubSafe'";
}
if ($eventFilter !== '') {
    $eventSafe = mysqli_real_escape_string($link, $eventFilter);
    $where .= " AND e.eventID = '$eventSafe'";
}

$totalEvents = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM event"))[0];
$totalAttendance = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM attendance"))[0];
$totalStudentsWithPoints = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM student WHERE totalPoints > 0"))[0];
$presentCount = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(*) FROM attendance WHERE AttendanceStatus IN ('Present','Present on time','Volunteer','Late')"))[0];
$attendanceRate = $totalAttendance > 0 ? round(((int)$presentCount / (int)$totalAttendance) * 100, 1) : 0;

$clubs = mysqli_query($link, 'SELECT ClubID, ClubName FROM club ORDER BY ClubName ASC');
$events = mysqli_query($link, 'SELECT eventID, eventTitle, eventDate FROM event ORDER BY eventDate DESC');

$eventSummary = mysqli_query($link,
    "SELECT e.eventID, e.eventTitle, c.ClubName, e.eventDate,
            COUNT(DISTINCT r.registrationID) AS registeredCount,
            COUNT(DISTINCT a.attendanceID) AS attendanceCount,
            SUM(CASE WHEN a.AttendanceStatus IN ('Present','Present on time','Volunteer','Late') THEN 1 ELSE 0 END) AS presentCount
     FROM event e
     JOIN club c ON c.ClubID = e.ClubID
     LEFT JOIN registration r ON r.eventID = e.eventID AND r.registrationStatus = 'Registered'
     LEFT JOIN attendance a ON a.eventID = e.eventID
     $where
     GROUP BY e.eventID, e.eventTitle, c.ClubName, e.eventDate
     ORDER BY e.eventDate DESC"
);

$topStudents = mysqli_query($link,
    "SELECT s.StudentID, COALESCE(s.StudentName, l.name) AS StudentName, s.totalPoints,
            COUNT(DISTINCT a.eventID) AS eventsJoined
     FROM student s
     JOIN login l ON l.UserID = s.UserID
     LEFT JOIN attendance a ON a.studentID = s.StudentID
     GROUP BY s.StudentID, StudentName, s.totalPoints
     ORDER BY s.totalPoints DESC, eventsJoined DESC
     LIMIT 10"
);

$clubRates = mysqli_query($link,
    "SELECT c.ClubName,
            COUNT(DISTINCT a.attendanceID) AS attendanceCount,
            SUM(CASE WHEN a.AttendanceStatus IN ('Present','Present on time','Volunteer','Late') THEN 1 ELSE 0 END) AS presentCount
     FROM club c
     LEFT JOIN event e ON e.ClubID = c.ClubID
     LEFT JOIN attendance a ON a.eventID = e.eventID
     GROUP BY c.ClubID, c.ClubName
     ORDER BY attendanceCount DESC"
);

$pageTitle = 'Participation Report';
$activePage = 'attendance_report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'includes/head.php'; ?>
<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.filter-card,.report-card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:25px;}
.filter-row{display:grid;grid-template-columns:1fr 1fr auto;gap:15px;align-items:end;}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:25px;}.stat-card{text-align:center;}
.stat-card h3{font-size:32px;color:#1f3f77;margin:0;}.stat-card p{color:#666;margin:8px 0 0;}
.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:25px;}
.progress-wrap{background:#e5e7eb;border-radius:999px;height:10px;overflow:hidden;}.progress-fill{height:100%;background:#1f3f77;}
@media(max-width:900px){.stats-grid,.report-grid,.filter-row{grid-template-columns:1fr}.page-header{display:block;}}
</style>
</head>
<body>
<div class="app">
<?php include 'includes/sidebar_admin.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h2>Participation Report</h2>
        <a href="adminStudentAttendanceDetail.php" class="btn btn-primary">View Student Detail</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h3><?= (int)$totalEvents ?></h3><p>Total Events</p></div>
        <div class="stat-card"><h3><?= (int)$totalAttendance ?></h3><p>Total Participation</p></div>
        <div class="stat-card"><h3><?= $attendanceRate ?>%</h3><p>Attendance Rate</p></div>
        <div class="stat-card"><h3><?= (int)$totalStudentsWithPoints ?></h3><p>Students With Points</p></div>
    </div>

    <div class="filter-card">
        <form method="get" class="filter-row">
            <div class="form-group">
                <label>Club</label>
                <select name="clubID">
                    <option value="">All Clubs</option>
                    <?php while ($club = mysqli_fetch_assoc($clubs)): ?>
                        <option value="<?= htmlspecialchars($club['ClubID']) ?>" <?= $clubFilter === $club['ClubID'] ? 'selected' : '' ?>><?= htmlspecialchars($club['ClubName']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Event</label>
                <select name="eventID">
                    <option value="">All Events</option>
                    <?php while ($event = mysqli_fetch_assoc($events)): ?>
                        <option value="<?= htmlspecialchars($event['eventID']) ?>" <?= $eventFilter === $event['eventID'] ? 'selected' : '' ?>><?= htmlspecialchars($event['eventTitle']) ?> — <?= htmlspecialchars($event['eventDate']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div class="report-card">
        <h3>Event Statistics</h3>
        <table>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Club</th>
                    <th>Date</th>
                    <th>Registered</th>
                    <th>Attendance</th>
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($eventSummary && mysqli_num_rows($eventSummary) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($eventSummary)): ?>
                    <?php
                    $registered = (int)$row['registeredCount'];
                    $present = (int)$row['presentCount'];
                    $rate = $registered > 0 ? round(($present / $registered) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['eventTitle']) ?></td>
                        <td><?= htmlspecialchars($row['ClubName']) ?></td>
                        <td><?= htmlspecialchars($row['eventDate']) ?></td>
                        <td><?= $registered ?></td>
                        <td><?= (int)$row['attendanceCount'] ?></td>
                        <td><?= $rate ?>%</td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;color:#888;">No report data available.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="report-grid">
        <div class="report-card">
            <h3>Most Active Students</h3>
            <table>
                <thead><tr><th>Student</th><th>Total Points</th><th>Events</th></tr></thead>
                <tbody>
                <?php while ($student = mysqli_fetch_assoc($topStudents)): ?>
                    <tr>
                        <td><a href="adminStudentAttendanceDetail.php?studentID=<?= urlencode($student['StudentID']) ?>"><?= htmlspecialchars($student['StudentName']) ?></a></td>
                        <td><?= (int)$student['totalPoints'] ?></td>
                        <td><?= (int)$student['eventsJoined'] ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="report-card">
            <h3>Attendance Rate by Club</h3>
            <table>
                <thead><tr><th>Club</th><th>Attendance</th><th>Rate</th></tr></thead>
                <tbody>
                <?php while ($club = mysqli_fetch_assoc($clubRates)): ?>
                    <?php
                    $att = (int)$club['attendanceCount'];
                    $present = (int)$club['presentCount'];
                    $rate = $att > 0 ? round(($present / $att) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($club['ClubName']) ?></td>
                        <td><?= $att ?></td>
                        <td>
                            <div><?= $rate ?>%</div>
                            <div class="progress-wrap"><div class="progress-fill" style="width:<?= min($rate,100) ?>%"></div></div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
</body>
</html>
