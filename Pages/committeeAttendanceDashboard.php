<?php
// ================================================================
// Module 4 — Attendance Dashboard (Committee Member View)
// Detailed dashboard based on the Module 4 presentation model.
// Uses existing Event, Registration, Attendance, PointLog and Student tables.
// ================================================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

// Sidebar profile photo
$photoStmt = mysqli_prepare($link, 'SELECT Studphoto FROM student WHERE UserID = ?');
mysqli_stmt_bind_param($photoStmt, 's', $userID);
mysqli_stmt_execute($photoStmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($photoStmt));

// Committee access and managed club
$committeeStmt = mysqli_prepare($link,
    "SELECT cc.clubID, c.ClubName, cc.commiteePosition
     FROM clubcommitee cc
     JOIN club c ON c.ClubID = cc.clubID
     WHERE cc.userID = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($committeeStmt, 's', $userID);
mysqli_stmt_execute($committeeStmt);
$committee = mysqli_fetch_assoc(mysqli_stmt_get_result($committeeStmt));

if (!$committee) {
    $pageTitle = 'Attendance Dashboard';
    $activePage = 'committee_attendance';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><?php include 'includes/head.php'; ?></head>
    <body>
    <div class="app">
        <?php include 'includes/sidebar_student.php'; ?>
        <main class="main-content">
            <h2>Attendance Dashboard</h2>
            <div class="alert alert-danger">Access denied. Committee members only.</div>
            <a href="studDash.php" class="btn btn-primary">Back to Dashboard</a>
        </main>
    </div>
    </body>
    </html>
    <?php
    exit();
}

$clubID = $committee['clubID'];

function scalar_by_club(mysqli $link, string $sql, string $clubID): int
{
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 's', $clubID);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
    return (int)($row[0] ?? 0);
}

$totalEvents = scalar_by_club($link, 'SELECT COUNT(*) FROM event WHERE ClubID = ?', $clubID);
$upcomingEvents = scalar_by_club($link, "SELECT COUNT(*) FROM event WHERE ClubID = ? AND eventStatus = 'upcoming'", $clubID);
$completedEvents = scalar_by_club($link, "SELECT COUNT(*) FROM event WHERE ClubID = ? AND eventStatus = 'completed'", $clubID);
$totalRegistered = scalar_by_club($link,
    "SELECT COUNT(*)
     FROM registration r
     JOIN event e ON e.eventID = r.eventID
     WHERE e.ClubID = ? AND r.registrationStatus = 'Registered'",
    $clubID
);
$totalMarked = scalar_by_club($link,
    'SELECT COUNT(*) FROM attendance a JOIN event e ON e.eventID = a.eventID WHERE e.ClubID = ?',
    $clubID
);
$presentCount = scalar_by_club($link,
    "SELECT COUNT(*) FROM attendance a JOIN event e ON e.eventID = a.eventID
     WHERE e.ClubID = ? AND a.AttendanceStatus LIKE 'Present%'",
    $clubID
);
$lateCount = scalar_by_club($link,
    "SELECT COUNT(*) FROM attendance a JOIN event e ON e.eventID = a.eventID
     WHERE e.ClubID = ? AND a.AttendanceStatus LIKE 'Late%'",
    $clubID
);
$absentCount = scalar_by_club($link,
    "SELECT COUNT(*) FROM attendance a JOIN event e ON e.eventID = a.eventID
     WHERE e.ClubID = ? AND a.AttendanceStatus LIKE 'Absent%'",
    $clubID
);
$volunteerCount = scalar_by_club($link,
    "SELECT COUNT(*) FROM attendance a JOIN event e ON e.eventID = a.eventID
     WHERE e.ClubID = ? AND a.AttendanceStatus LIKE '%Volunteer%'",
    $clubID
);
$totalPoints = scalar_by_club($link,
    "SELECT COALESCE(SUM(pl.pointsEarn), 0)
     FROM pointlog pl
     JOIN attendance a ON a.attendanceID = pl.attendanceID
     JOIN event e ON e.eventID = a.eventID
     WHERE e.ClubID = ?",
    $clubID
);
$attendanceRate = $totalRegistered > 0 ? min(100, round((($presentCount + $lateCount) / $totalRegistered) * 100, 1)) : 0;

// Event level overview
$eventsStmt = mysqli_prepare($link,
    "SELECT e.eventID, e.eventTitle, e.eventDate, e.eventTime, e.eventVenue, e.eventStatus,
            COUNT(DISTINCT r.registrationID) AS registeredCount,
            COUNT(DISTINCT a.attendanceID) AS markedCount,
            COUNT(DISTINCT CASE WHEN a.AttendanceStatus LIKE 'Present%' THEN a.attendanceID END) AS presentCount,
            COUNT(DISTINCT CASE WHEN a.AttendanceStatus LIKE 'Late%' THEN a.attendanceID END) AS lateCount,
            COUNT(DISTINCT CASE WHEN a.AttendanceStatus LIKE 'Absent%' THEN a.attendanceID END) AS absentCount,
            COUNT(DISTINCT CASE WHEN a.AttendanceStatus LIKE '%Volunteer%' THEN a.attendanceID END) AS volunteerCount,
            COALESCE(SUM(pl.pointsEarn), 0) AS eventPoints
     FROM event e
     LEFT JOIN registration r ON r.eventID = e.eventID AND r.registrationStatus = 'Registered'
     LEFT JOIN attendance a ON a.eventID = e.eventID
     LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
     WHERE e.ClubID = ?
     GROUP BY e.eventID, e.eventTitle, e.eventDate, e.eventTime, e.eventVenue, e.eventStatus
     ORDER BY e.eventDate DESC, e.eventTime DESC"
);
mysqli_stmt_bind_param($eventsStmt, 's', $clubID);
mysqli_stmt_execute($eventsStmt);
$events = mysqli_stmt_get_result($eventsStmt);

// Latest attendance records
$recentStmt = mysqli_prepare($link,
    "SELECT a.checkInTime, a.AttendanceStatus, COALESCE(pl.pointsEarn,0) AS pointsEarn,
            s.StudentID, COALESCE(s.StudentName, l.name) AS StudentName,
            e.eventTitle
     FROM attendance a
     JOIN student s ON s.StudentID = a.studentID
     JOIN login l ON l.UserID = s.UserID
     JOIN event e ON e.eventID = a.eventID
     LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
     WHERE e.ClubID = ?
     ORDER BY a.checkInTime DESC
     LIMIT 8"
);
mysqli_stmt_bind_param($recentStmt, 's', $clubID);
mysqli_stmt_execute($recentStmt);
$recent = mysqli_stmt_get_result($recentStmt);

// Top participants under the committee club
$topStmt = mysqli_prepare($link,
    "SELECT s.StudentID, COALESCE(s.StudentName, l.name) AS StudentName,
            COUNT(DISTINCT a.attendanceID) AS attendanceTotal,
            COALESCE(SUM(pl.pointsEarn),0) AS totalPoints
     FROM attendance a
     JOIN student s ON s.StudentID = a.studentID
     JOIN login l ON l.UserID = s.UserID
     JOIN event e ON e.eventID = a.eventID
     LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
     WHERE e.ClubID = ?
     GROUP BY s.StudentID, StudentName
     ORDER BY totalPoints DESC, attendanceTotal DESC
     LIMIT 5"
);
mysqli_stmt_bind_param($topStmt, 's', $clubID);
mysqli_stmt_execute($topParticipants = $topStmt);
$topParticipants = mysqli_stmt_get_result($topStmt);

$pageTitle = 'Attendance Dashboard';
$activePage = 'committee_attendance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'includes/head.php'; ?>
<style>
.module4-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;gap:15px;}
.module4-title-block h2{margin-bottom:8px;}
.module4-subtitle{color:#6b7280;margin:0;}
.quick-panel{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px;}
.stats-grid-detailed{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-box{background:#fff;padding:20px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);border-top:4px solid #1f3f77;}
.stat-box .label{color:#6b7280;font-size:13px;margin-bottom:8px;}
.stat-box .number{font-size:31px;font-weight:700;color:#1f3f77;line-height:1;}
.stat-box .note{color:#888;font-size:12px;margin-top:8px;}
.dashboard-grid{display:grid;grid-template-columns:2fr 1fr;gap:22px;margin-bottom:24px;}
.card-soft{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:24px;}
.progress-wrap{background:#e5e7eb;border-radius:999px;height:10px;overflow:hidden;min-width:110px;margin-top:6px;}
.progress-fill{height:100%;background:#1f3f77;border-radius:999px;}
.status-badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;}
.upcoming{background:#fef3c7;color:#92400e;}.ongoing{background:#dbeafe;color:#1e40af;}.completed{background:#d1fae5;color:#065f46;}.other{background:#e5e7eb;color:#374151;}
.present{background:#d1fae5;color:#065f46;}.late{background:#fef3c7;color:#92400e;}.absent{background:#fee2e2;color:#991b1b;}.volunteer{background:#e0e7ff;color:#3730a3;}
.compact-table td,.compact-table th{padding:10px;}
.event-action{white-space:nowrap;}
.metric-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;font-size:13px;}
.metric-mini{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:8px;text-align:center;}
.metric-mini strong{display:block;color:#1f3f77;font-size:17px;}
@media(max-width:1100px){.stats-grid-detailed{grid-template-columns:repeat(2,1fr)}.dashboard-grid{grid-template-columns:1fr}.metric-strip{grid-template-columns:repeat(2,1fr)}}
@media(max-width:700px){.stats-grid-detailed{grid-template-columns:1fr}.module4-header{display:block}.quick-panel{margin-top:12px}}
</style>
</head>
<body>
<div class="app">
<?php include 'includes/sidebar_student.php'; ?>
<main class="main-content">
    <div class="module4-header">
        <div class="module4-title-block">
            <h2>Attendance Dashboard</h2>
            <p class="module4-subtitle">
                Committee view for <strong><?= htmlspecialchars($committee['ClubName']) ?></strong>
                · <?= htmlspecialchars($committee['commiteePosition']) ?>
            </p>
        </div>
        <div class="quick-panel">
            <a href="committeeTakeAttendance.php" class="btn btn-primary">Take Attendance</a>
            <a href="StudAttendancePoints.php" class="btn btn-secondary">My Points</a>
        </div>
    </div>

    <div class="stats-grid-detailed">
        <div class="stat-box"><div class="label">Total Club Events</div><div class="number"><?= $totalEvents ?></div><div class="note">All events created by this club</div></div>
        <div class="stat-box"><div class="label">Registered Participants</div><div class="number"><?= $totalRegistered ?></div><div class="note">Total registered records</div></div>
        <div class="stat-box"><div class="label">Attendance Marked</div><div class="number"><?= $totalMarked ?></div><div class="note">Students already checked</div></div>
        <div class="stat-box"><div class="label">Attendance Rate</div><div class="number"><?= $attendanceRate ?>%</div><div class="note">Present + late / registered</div></div>
        <div class="stat-box"><div class="label">Present On Time</div><div class="number"><?= $presentCount ?></div><div class="note">+10 points basic</div></div>
        <div class="stat-box"><div class="label">Late Arrival</div><div class="number"><?= $lateCount ?></div><div class="note">+5 points basic</div></div>
        <div class="stat-box"><div class="label">Absent Without Notice</div><div class="number"><?= $absentCount ?></div><div class="note">-10 points</div></div>
        <div class="stat-box"><div class="label">Volunteer / Helper</div><div class="number"><?= $volunteerCount ?></div><div class="note">Additional +5 points</div></div>
    </div>

    <div class="dashboard-grid">
        <div class="card-soft">
            <h3>Event Attendance Overview</h3>
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Attendance Details</th>
                        <th>Rate</th>
                        <th>Points</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($events && mysqli_num_rows($events) > 0): ?>
                    <?php while ($event = mysqli_fetch_assoc($events)): ?>
                        <?php
                        $registered = (int)$event['registeredCount'];
                        $present = (int)$event['presentCount'];
                        $late = (int)$event['lateCount'];
                        $absent = (int)$event['absentCount'];
                        $volunteer = (int)$event['volunteerCount'];
                        $rate = $registered > 0 ? min(100, round((($present + $late) / $registered) * 100, 1)) : 0;
                        $statusClass = in_array(strtolower($event['eventStatus']), ['upcoming','ongoing','completed'], true) ? strtolower($event['eventStatus']) : 'other';
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($event['eventTitle']) ?></strong><br>
                                <span style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($event['eventVenue']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($event['eventDate']) ?><br><span style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($event['eventTime']) ?></span></td>
                            <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst(htmlspecialchars($event['eventStatus'])) ?></span></td>
                            <td>
                                <div class="metric-strip">
                                    <div class="metric-mini"><strong><?= $registered ?></strong>Registered</div>
                                    <div class="metric-mini"><strong><?= $present ?></strong>Present</div>
                                    <div class="metric-mini"><strong><?= $late ?></strong>Late</div>
                                    <div class="metric-mini"><strong><?= $absent ?></strong>Absent</div>
                                </div>
                                <?php if ($volunteer > 0): ?>
                                    <span class="status-badge volunteer" style="margin-top:8px;">Volunteer: <?= $volunteer ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $rate ?>%
                                <div class="progress-wrap"><div class="progress-fill" style="width:<?= min($rate, 100) ?>%"></div></div>
                            </td>
                            <td><?= (int)$event['eventPoints'] ?></td>
                            <td class="event-action">
                                <a class="btn btn-primary btn-sm" href="committeeTakeAttendance.php?eventID=<?= urlencode($event['eventID']) ?>">Open</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#888;">No event available for this club.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <div class="card-soft">
                <h3>Point Summary</h3>
                <div class="stat-box" style="box-shadow:none;border:1px solid #e5e7eb;margin-bottom:12px;">
                    <div class="label">Total Points Awarded / Deducted</div>
                    <div class="number"><?= $totalPoints ?></div>
                    <div class="note">Calculated from PointLog records</div>
                </div>
                <p style="color:#6b7280;font-size:13px;line-height:1.6;">
                    Present on time = +10, late = +5, absent = -10, volunteer/helper = additional +5.
                </p>
            </div>

            <div class="card-soft">
                <h3>Top Participants</h3>
                <table class="compact-table">
                    <thead><tr><th>Student</th><th>Records</th><th>Points</th></tr></thead>
                    <tbody>
                    <?php if ($topParticipants && mysqli_num_rows($topParticipants) > 0): ?>
                        <?php while ($top = mysqli_fetch_assoc($topParticipants)): ?>
                            <tr>
                                <td><?= htmlspecialchars($top['StudentName']) ?><br><span style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($top['StudentID']) ?></span></td>
                                <td><?= (int)$top['attendanceTotal'] ?></td>
                                <td><strong><?= (int)$top['totalPoints'] ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;color:#888;">No attendance records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card-soft">
        <h3>Recent Attendance Records</h3>
        <table class="compact-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Event</th>
                    <th>Check-In Time</th>
                    <th>Status</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recent && mysqli_num_rows($recent) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                    <?php
                    $statusLower = strtolower($row['AttendanceStatus']);
                    $class = 'other';
                    if (str_starts_with($statusLower, 'present')) $class = 'present';
                    if (str_starts_with($statusLower, 'late')) $class = 'late';
                    if (str_starts_with($statusLower, 'absent')) $class = 'absent';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['StudentName']) ?><br><span style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($row['StudentID']) ?></span></td>
                        <td><?= htmlspecialchars($row['eventTitle']) ?></td>
                        <td><?= htmlspecialchars($row['checkInTime']) ?></td>
                        <td><span class="status-badge <?= $class ?>"><?= htmlspecialchars($row['AttendanceStatus']) ?></span></td>
                        <td><?= (int)$row['pointsEarn'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;color:#888;">No attendance record has been created yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</div>
</body>
</html>
