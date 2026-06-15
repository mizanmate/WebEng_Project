<?php
// ================================================================
// Module 4 — QR Attendance Check-In Page
// This page is opened when a student scans the event QR code.
// It does not require changing the existing login/session modules.
// ================================================================
session_start();
require_once 'DB_connection.php';

function next_code(mysqli $link, string $table, string $column, string $prefix): string
{
    $prefixLen = strlen($prefix) + 1;
    $sql = "SELECT MAX(CAST(SUBSTRING($column, $prefixLen) AS UNSIGNED)) AS max_no
            FROM $table
            WHERE $column LIKE CONCAT(?, '%')";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 's', $prefix);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $next = ((int)($row['max_no'] ?? 0)) + 1;
    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function points_for_status(string $status): int
{
    return match ($status) {
        'Present' => 10,
        'Present + Volunteer' => 15,
        'Late' => 5,
        'Late + Volunteer' => 10,
        default => 0,
    };
}

function status_from_inputs(string $arrival, string $volunteer): string
{
    $status = ($arrival === 'late') ? 'Late' : 'Present';

    if ($volunteer === 'yes') {
        $status .= ' + Volunteer';
    }

    return $status;
}

function find_student_by_identifier(mysqli $link, string $identifier): ?array
{
    $stmt = mysqli_prepare($link,
        "SELECT s.StudentID, s.UserID, COALESCE(s.StudentName, l.name) AS StudentName, s.Email
         FROM student s
         JOIN login l ON l.UserID = s.UserID
         WHERE s.StudentID = ? OR s.UserID = ? OR l.UserID = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'sss', $identifier, $identifier, $identifier);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $student ?: null;
}

function is_registered_for_event(mysqli $link, string $studentID, string $eventID): bool
{
    $stmt = mysqli_prepare($link,
        "SELECT registrationID
         FROM registration
         WHERE studentID = ? AND eventID = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $studentID, $eventID);
    mysqli_stmt_execute($stmt);
    return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function ensure_registration_for_event(mysqli $link, string $studentID, string $eventID): bool
{
    if (is_registered_for_event($link, $studentID, $eventID)) {
        return true;
    }

    $registrationID = next_code($link, 'registration', 'registrationID', 'REG');
    $status = 'Registered';
    $notes = 'QR check-in auto registration';

    $ins = mysqli_prepare($link,
        'INSERT INTO registration (registrationID, studentID, eventID, registrationStatus, notes, registrationDate)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    mysqli_stmt_bind_param($ins, 'sssss', $registrationID, $studentID, $eventID, $status, $notes);
    return mysqli_stmt_execute($ins);
}

function recalc_student_points(mysqli $link, string $studentID): void
{
    $stmt = mysqli_prepare($link, 'SELECT COALESCE(SUM(pointsEarn), 0) FROM pointlog WHERE studentID = ?');
    mysqli_stmt_bind_param($stmt, 's', $studentID);
    mysqli_stmt_execute($stmt);
    $points = (int)mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

    $upd = mysqli_prepare($link, 'UPDATE student SET totalPoints = ? WHERE StudentID = ?');
    mysqli_stmt_bind_param($upd, 'is', $points, $studentID);
    mysqli_stmt_execute($upd);
}

function save_attendance(mysqli $link, string $studentID, string $eventID, string $status): int
{
    $points = points_for_status($status);
    $checkInTime = date('Y-m-d H:i:s');

    $find = mysqli_prepare($link, 'SELECT attendanceID FROM attendance WHERE studentID = ? AND eventID = ? LIMIT 1');
    mysqli_stmt_bind_param($find, 'ss', $studentID, $eventID);
    mysqli_stmt_execute($find);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($find));

    if ($existing) {
        $attendanceID = $existing['attendanceID'];
        $upd = mysqli_prepare($link, 'UPDATE attendance SET checkInTime = ?, AttendanceStatus = ? WHERE attendanceID = ?');
        mysqli_stmt_bind_param($upd, 'sss', $checkInTime, $status, $attendanceID);
        mysqli_stmt_execute($upd);
    } else {
        $attendanceID = next_code($link, 'attendance', 'attendanceID', 'ATT');
        $ins = mysqli_prepare($link,
            'INSERT INTO attendance (attendanceID, studentID, eventID, checkInTime, AttendanceStatus)
             VALUES (?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($ins, 'sssss', $attendanceID, $studentID, $eventID, $checkInTime, $status);
        mysqli_stmt_execute($ins);
    }

    $delPoint = mysqli_prepare($link, 'DELETE FROM pointlog WHERE attendanceID = ?');
    mysqli_stmt_bind_param($delPoint, 's', $attendanceID);
    mysqli_stmt_execute($delPoint);

    $logID = next_code($link, 'pointlog', 'logID', 'PLG');
    $pointIns = mysqli_prepare($link,
        'INSERT INTO pointlog (logID, studentID, attendanceID, pointsEarn, dateAward)
         VALUES (?, ?, ?, ?, NOW())'
    );
    mysqli_stmt_bind_param($pointIns, 'sssi', $logID, $studentID, $attendanceID, $points);
    mysqli_stmt_execute($pointIns);

    recalc_student_points($link, $studentID);
    return $points;
}

$eventID = trim($_GET['eventID'] ?? $_POST['eventID'] ?? '');
$event = null;
$success = '';
$error = '';
$studentName = '';
$points = 0;

if ($eventID !== '') {
    $eventStmt = mysqli_prepare($link,
        "SELECT e.*, c.ClubName
         FROM event e
         JOIN club c ON c.ClubID = e.ClubID
         WHERE LOWER(e.eventID) = LOWER(?)
         LIMIT 1"
    );
    mysqli_stmt_bind_param($eventStmt, 's', $eventID);
    mysqli_stmt_execute($eventStmt);
    $event = mysqli_fetch_assoc(mysqli_stmt_get_result($eventStmt));
}

if (!$event) {
    $error = 'Invalid QR code or event not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $identifier = trim($_POST['student_identifier'] ?? '');
    $arrival = trim($_POST['arrival_status'] ?? 'on_time');
    $volunteer = trim($_POST['volunteer'] ?? 'no');

    if ($identifier === '') {
        $error = 'Please enter your Student ID or username.';
    } else {
        $student = find_student_by_identifier($link, $identifier);

        if (!$student) {
            $error = 'Student ID or username was not found.';
        } elseif (!ensure_registration_for_event($link, $student['StudentID'], $eventID)) {
            $error = 'The system could not create your event registration record.';
        } else {
            $status = status_from_inputs($arrival, $volunteer);
            $points = save_attendance($link, $student['StudentID'], $eventID, $status);
            $studentName = $student['StudentName'];
            $success = 'Check-in successful. Attendance status: ' . $status . '. Points: ' . $points . '.';
        }
    }
}

$pageTitle = 'QR Attendance Check-In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'includes/head.php'; ?>
<style>
body{background:#f0f2f5;}
.qr-checkin-wrap{max-width:780px;margin:40px auto;padding:0 18px;}
.qr-checkin-card{background:#fff;border-radius:14px;box-shadow:0 3px 14px rgba(0,0,0,.10);padding:30px;}
.qr-header{text-align:center;margin-bottom:25px;}
.qr-logo{width:110px;margin-bottom:10px;}
.event-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin-bottom:22px;}
.event-title{color:#1f3f77;margin:0 0 8px;font-size:24px;}
.event-meta{color:#4b5563;line-height:1.7;margin:0;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-grid .full{grid-column:1 / -1;}
.point-guide{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:16px;}
.point-item{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:10px;text-align:center;font-size:13px;}
.point-item strong{display:block;color:#1f3f77;font-size:17px;}
.success-box{background:#d1fae5;color:#065f46;border-radius:10px;padding:18px;margin-bottom:18px;}
.error-box{background:#fee2e2;color:#991b1b;border-radius:10px;padding:18px;margin-bottom:18px;}
@media(max-width:700px){.form-grid,.point-guide{grid-template-columns:1fr}.qr-checkin-card{padding:22px}.event-title{font-size:21px}}
</style>
<script>
function previewQRPoints(){
    var arrival = document.getElementById('arrival_status');
    var volunteer = document.getElementById('volunteer');
    var preview = document.getElementById('point_preview');
    if(!arrival || !volunteer || !preview) return;
    var points = arrival.value === 'late' ? 5 : 10;
    if(volunteer.value === 'yes') points += 5;
    preview.innerText = '+' + points + ' points';
}
window.addEventListener('DOMContentLoaded', previewQRPoints);
</script>
</head>
<body>
<div class="qr-checkin-wrap">
    <div class="qr-checkin-card">
        <div class="qr-header">
            <img src="../img/Logo_UMPSA.png" alt="UMPSA Logo" class="qr-logo">
            <h2>QR Attendance Check-In</h2>
            <p style="color:#6b7280;">FK Club &amp; Event Management System</p>
        </div>

        <?php if ($success): ?>
            <div class="success-box">
                <strong><?= htmlspecialchars($studentName) ?></strong><br>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($event): ?>
            <div class="event-box">
                <h3 class="event-title"><?= htmlspecialchars($event['eventTitle']) ?></h3>
                <p class="event-meta">
                    Club: <?= htmlspecialchars($event['ClubName']) ?><br>
                    Date: <?= htmlspecialchars(date('d M Y', strtotime($event['eventDate']))) ?><br>
                    Time: <?= htmlspecialchars(date('g:i A', strtotime($event['eventTime']))) ?><br>
                    Venue: <?= htmlspecialchars($event['eventVenue']) ?>
                </p>
            </div>

            <form method="post">
                <input type="hidden" name="eventID" value="<?= htmlspecialchars($eventID) ?>">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Student ID / Username</label>
                        <input type="text" name="student_identifier" placeholder="Example: CA23001" required autofocus>
                    </div>

                    <div class="form-group">
                        <label>Arrival</label>
                        <select name="arrival_status" id="arrival_status" onchange="previewQRPoints()" required>
                            <option value="on_time">Present on time</option>
                            <option value="late">Late arrival</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Volunteer / Helper?</label>
                        <select name="volunteer" id="volunteer" onchange="previewQRPoints()" required>
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>
                </div>

                <p style="margin:8px 0 18px;color:#4b5563;">Point preview: <strong id="point_preview">+10 points</strong></p>

                <button type="submit" class="btn btn-primary" style="width:100%;">Submit Check-In</button>
            </form>

            <div class="point-guide">
                <div class="point-item"><strong>+10</strong>Present on time</div>
                <div class="point-item"><strong>+5</strong>Late arrival</div>
                <div class="point-item"><strong>+5</strong>Volunteer/helper bonus</div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
