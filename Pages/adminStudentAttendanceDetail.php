<?php
// ================================================================
// Module 4 — View Student Attendance Detail (Admin View)
// Add-on page only. No changes to other modules required.
// ================================================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'DB_connection.php';

$studentID = trim($_GET['studentID'] ?? '');
$student = null;
$history = null;

function recognition_label(int $points): string {
    if ($points >= 80) return 'Outstanding Participant';
    if ($points >= 50) return 'Eligible for Active Student Award / Bonus Points';
    if ($points >= 20) return 'Eligible for Participation Certificate';
    return 'Warning / Reminder to Participate More';
}

if ($studentID !== '') {
    $stmt = mysqli_prepare($link,
        'SELECT s.StudentID, COALESCE(s.StudentName, l.name) AS StudentName, s.Programme, s.StudYear, s.Email, s.Phone, s.totalPoints
         FROM student s
         JOIN login l ON l.UserID = s.UserID
         WHERE s.StudentID = ?
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 's', $studentID);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($student) {
        $histStmt = mysqli_prepare($link,
            "SELECT e.eventTitle, c.ClubName, e.eventDate, e.eventVenue,
                    a.checkInTime, a.AttendanceStatus, COALESCE(pl.pointsEarn,0) AS pointsEarn
             FROM attendance a
             JOIN event e ON e.eventID = a.eventID
             JOIN club c ON c.ClubID = e.ClubID
             LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
             WHERE a.studentID = ?
             ORDER BY a.checkInTime DESC"
        );
        mysqli_stmt_bind_param($histStmt, 's', $studentID);
        mysqli_stmt_execute($histStmt);
        $history = mysqli_stmt_get_result($histStmt);
    }
}

$pageTitle = 'Student Attendance Detail';
$activePage = 'attendance_report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'includes/head.php'; ?>
<style>
.search-card,.detail-card,.history-card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:25px;}
.search-row{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;}.detail-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}
.detail-item{background:#f8fafc;border-radius:10px;padding:16px;}.detail-item strong{display:block;color:#1f3f77;margin-bottom:5px;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
@media(max-width:900px){.search-row,.detail-grid{grid-template-columns:1fr}.page-header{display:block;}}
</style>
</head>
<body>
<div class="app">
<?php include 'includes/sidebar_admin.php'; ?>
<main class="main-content">
    <div class="page-header">
        <h2>View Student Attendance Detail</h2>
        <a href="adminParticipationReport.php" class="btn btn-secondary">Back to Report</a>
    </div>

    <div class="search-card">
        <form method="get" class="search-row">
            <div class="form-group">
                <label>Search Student ID</label>
                <input type="text" name="studentID" placeholder="Example: CA23001" value="<?= htmlspecialchars($studentID) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <?php if ($studentID !== '' && !$student): ?>
        <div class="alert alert-danger">No student found with ID <?= htmlspecialchars($studentID) ?>.</div>
    <?php endif; ?>

    <?php if ($student): ?>
    <div class="detail-card">
        <h3><?= htmlspecialchars($student['StudentName']) ?></h3>
        <div class="detail-grid">
            <div class="detail-item"><strong>Student ID</strong><?= htmlspecialchars($student['StudentID']) ?></div>
            <div class="detail-item"><strong>Programme</strong><?= htmlspecialchars($student['Programme'] ?? '—') ?></div>
            <div class="detail-item"><strong>Total Points</strong><?= (int)$student['totalPoints'] ?></div>
            <div class="detail-item"><strong>Recognition</strong><?= htmlspecialchars(recognition_label((int)$student['totalPoints'])) ?></div>
        </div>
    </div>

    <div class="history-card">
        <h3>Attendance History</h3>
        <table>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Club</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Check-In Time</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($history && mysqli_num_rows($history) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($history)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['eventTitle']) ?></td>
                        <td><?= htmlspecialchars($row['ClubName']) ?></td>
                        <td><?= htmlspecialchars($row['eventDate']) ?></td>
                        <td><?= htmlspecialchars($row['eventVenue']) ?></td>
                        <td><?= htmlspecialchars($row['AttendanceStatus']) ?></td>
                        <td><?= htmlspecialchars($row['checkInTime']) ?></td>
                        <td><?= (int)$row['pointsEarn'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;color:#888;">No attendance record for this student.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
</div>
</body>
</html>
