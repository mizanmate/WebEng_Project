<?php
// ================================================================
// Module 4 — My Attendance & Points (Student View)
// Add-on page only. It does not modify other modules.
// ================================================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}
require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

$stmt = mysqli_prepare($link,
    'SELECT l.name, s.StudentID, s.Programme, s.StudYear, s.Email, s.Phone, s.Studphoto, s.totalPoints
     FROM student s
     JOIN login l ON l.UserID = s.UserID
     WHERE s.UserID = ?'
);
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$sidebarUser = $student;
$studentID = $student['StudentID'] ?? '';
$totalPoints = (int)($student['totalPoints'] ?? 0);

function recognition_label(int $points): string {
    if ($points >= 80) return 'Outstanding Participant';
    if ($points >= 50) return 'Active Student Award / Bonus Points';
    if ($points >= 20) return 'Eligible for Participation Certificate';
    return 'Warning / Reminder to Participate More';
}

function attendance_label(string $status): string {
    return match ($status) {
        'Present' => 'Present on time',
        'Present + Volunteer' => 'Present on time + Volunteer/helper',
        'Late' => 'Late arrival',
        'Late + Volunteer' => 'Late arrival + Volunteer/helper',
        'Absent' => 'Absent without notice',
        default => $status,
    };
}

function attendance_class(string $status): string {
    $lower = strtolower($status);
    if (str_starts_with($lower, 'present')) return 'present';
    if (str_starts_with($lower, 'late')) return 'late';
    if (str_starts_with($lower, 'absent')) return 'absent';
    if (str_contains($lower, 'volunteer')) return 'volunteer';
    return 'other';
}

$rankStmt = mysqli_prepare($link,
    'SELECT COUNT(*) + 1 AS ranking FROM student WHERE totalPoints > ?'
);
mysqli_stmt_bind_param($rankStmt, 'i', $totalPoints);
mysqli_stmt_execute($rankStmt);
$ranking = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($rankStmt))['ranking'];

$historyStmt = mysqli_prepare($link,
    "SELECT e.eventTitle, c.ClubName, e.eventDate, e.eventVenue,
            a.checkInTime, a.AttendanceStatus, COALESCE(pl.pointsEarn,0) AS pointsEarn
     FROM attendance a
     JOIN event e ON e.eventID = a.eventID
     JOIN club c ON c.ClubID = e.ClubID
     LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
     WHERE a.studentID = ?
     ORDER BY a.checkInTime DESC"
);
mysqli_stmt_bind_param($historyStmt, 's', $studentID);
mysqli_stmt_execute($historyStmt);
$history = mysqli_stmt_get_result($historyStmt);

$pageTitle = 'My Attendance & Points';
$activePage = 'attendance_points';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'includes/head.php'; ?>
<style>
.profile-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:25px;}
.summary-card{background:#fff;padding:24px;border-radius:12px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.08);border-top:4px solid #1f3f77;}
.summary-card h3{font-size:32px;margin:0;color:#1f3f77;}.summary-card p{color:#666;margin:8px 0 0;}
.history-card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);}
.status-badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;}
.present{background:#d1fae5;color:#065f46;}.late{background:#fef3c7;color:#92400e;}.absent{background:#fee2e2;color:#991b1b;}.volunteer{background:#e0e7ff;color:#3730a3;}.other{background:#e5e7eb;color:#374151;}
.recognition-box{background:#fff;padding:22px;border-left:5px solid #1f3f77;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:25px;}
@media(max-width:900px){.profile-summary{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<div class="app">
<?php include 'includes/sidebar_student.php'; ?>
<main class="main-content">
    <h2>My Attendance &amp; Points</h2>

    <div class="profile-summary">
        <div class="summary-card"><h3><?= (int)$totalPoints ?></h3><p>Total Points</p></div>
        <div class="summary-card"><h3>#<?= $ranking ?></h3><p>Ranking</p></div>
        <div class="summary-card"><h3><?= mysqli_num_rows($history) ?></h3><p>Events Attended</p></div>
        <div class="summary-card"><h3><?= htmlspecialchars($student['StudentID'] ?? '') ?></h3><p>Student ID</p></div>
    </div>

    <div class="recognition-box">
        <h3>Recognition Level</h3>
        <p><?= htmlspecialchars(recognition_label($totalPoints)) ?></p>
    </div>

    <div class="history-card">
        <h3>Participation History</h3>
        <table>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Club</th>
                    <th>Date</th>
                    <th>Check-In Time</th>
                    <th>Status</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($history && mysqli_num_rows($history) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($history)): ?>
                    <?php $class = attendance_class($row['AttendanceStatus']); ?>
                    <tr>
                        <td><?= htmlspecialchars($row['eventTitle']) ?></td>
                        <td><?= htmlspecialchars($row['ClubName']) ?></td>
                        <td><?= htmlspecialchars($row['eventDate']) ?></td>
                        <td><?= htmlspecialchars($row['checkInTime']) ?></td>
                        <td><span class="status-badge <?= $class ?>"><?= htmlspecialchars(attendance_label($row['AttendanceStatus'])) ?></span></td>
                        <td><?= (int)$row['pointsEarn'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;color:#888;">No attendance record yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</div>
</body>
</html>
