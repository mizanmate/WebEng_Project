<?php
// ================================================================
// Module 4 — Take Attendance Page (Committee Member View)
// Features added:
// 1. Event QR code display
// 2. Create attendance by Student ID / Username / QR result
// 3. Attendance yes/no selection
// 4. Volunteer/helper selection
// 5. Automatic point calculation and Student.totalPoints update
// ================================================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

$photoStmt = mysqli_prepare($link, 'SELECT Studphoto FROM student WHERE UserID = ?');
mysqli_stmt_bind_param($photoStmt, 's', $userID);
mysqli_stmt_execute($photoStmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($photoStmt));

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
    die('Access denied. Committee members only.');
}

$clubID = $committee['clubID'];

function points_for_status(string $status): int
{
    return match ($status) {
        'Present' => 10,
        'Present + Volunteer' => 15,
        'Late' => 5,
        'Late + Volunteer' => 10,
        'Absent' => -10,
        default => 0,
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'Present' => 'Present on time',
        'Present + Volunteer' => 'Present on time + Volunteer/helper',
        'Late' => 'Late arrival',
        'Late + Volunteer' => 'Late arrival + Volunteer/helper',
        'Absent' => 'Absent without notice',
        default => 'Not Marked',
    };
}

function status_from_inputs(string $attended, string $arrival, string $volunteer): string
{
    if ($attended === 'no') {
        return 'Absent';
    }

    $status = ($arrival === 'late') ? 'Late' : 'Present';

    if ($volunteer === 'yes') {
        $status .= ' + Volunteer';
    }

    return $status;
}

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
    $notes = 'Auto added by attendance module';

    $ins = mysqli_prepare($link,
        'INSERT INTO registration (registrationID, studentID, eventID, registrationStatus, notes, registrationDate)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    mysqli_stmt_bind_param($ins, 'sssss', $registrationID, $studentID, $eventID, $status, $notes);
    return mysqli_stmt_execute($ins);
}

function current_pages_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // For phone QR scanning during local XAMPP demo, do not use localhost.
    // The user's current hotspot/laptop IP is used instead.
    if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)) {
        $host = '172.20.10.4';
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/Pages'));
    $scriptDir = rtrim($scriptDir, '/');
    return $scheme . '://' . $host . $scriptDir;
}

function save_attendance(mysqli $link, string $studentID, string $eventID, string $status, string $checkInTime): void
{
    $points = points_for_status($status);

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
}

$selectedEvent = trim($_GET['eventID'] ?? $_POST['eventID'] ?? '');
$success = '';
$error = '';
$notice = '';

// Create one attendance record by ID / username / QR result.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_attendance') {
    $identifier = trim($_POST['student_identifier'] ?? '');
    $attended = trim($_POST['attended'] ?? 'yes');
    $arrival = trim($_POST['arrival_status'] ?? 'on_time');
    $volunteer = trim($_POST['volunteer'] ?? 'no');
    $manualTime = trim($_POST['manual_check_in_time'] ?? '');

    if ($selectedEvent === '' || $identifier === '') {
        $error = 'Please select an event and enter Student ID / Username / QR result.';
    } else {
        $student = find_student_by_identifier($link, $identifier);

        if (!$student) {
            $error = 'Student ID or username was not found.';
        } else {
            $status = status_from_inputs($attended, $arrival, $volunteer);
            $checkInTime = $manualTime !== '' ? date('Y-m-d H:i:s', strtotime($manualTime)) : date('Y-m-d H:i:s');

            $alreadyRegistered = is_registered_for_event($link, $student['StudentID'], $selectedEvent);

            if (!$alreadyRegistered) {
                if (!ensure_registration_for_event($link, $student['StudentID'], $selectedEvent)) {
                    $error = 'Student found, but the system could not add this student into the event registered participant list.';
                } else {
                    $notice = 'Student was not previously registered for this event, so the system automatically added the student into Registered Participants.';
                }
            }

            if (!$error) {
                save_attendance($link, $student['StudentID'], $selectedEvent, $status, $checkInTime);
                $success = 'Attendance created/updated for ' . $student['StudentName'] . ' (' . $student['StudentID'] . '). Points: ' . points_for_status($status) . '.';
            }
        }
    }
}

// Manual table save for registered participants.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_attendance') {
    $studentIDs = $_POST['student_id'] ?? [];
    $statuses = $_POST['attendance_status'] ?? [];
    $times = $_POST['check_in_time'] ?? [];

    foreach ($studentIDs as $i => $studentID) {
        $studentID = trim($studentID);
        $status = trim($statuses[$i] ?? '');
        $time = trim($times[$i] ?? '');

        if ($studentID !== '' && $status !== '') {
            $checkIn = $time !== '' ? date('Y-m-d H:i:s', strtotime($time)) : date('Y-m-d H:i:s');
            save_attendance($link, $studentID, $selectedEvent, $status, $checkIn);
        }
    }

    $success = 'Attendance table has been saved.';
}

$eventList = mysqli_prepare($link,
    'SELECT eventID, eventTitle, eventDate, eventTime, eventVenue, eventStatus
     FROM event
     WHERE ClubID = ?
     ORDER BY eventDate DESC, eventTime DESC'
);
mysqli_stmt_bind_param($eventList, 's', $clubID);
mysqli_stmt_execute($eventList);
$eventOptions = mysqli_stmt_get_result($eventList);

$participants = null;
$eventInfo = null;
$eventStats = [
    'registered' => 0,
    'marked' => 0,
    'present' => 0,
    'late' => 0,
    'absent' => 0,
    'volunteer' => 0,
    'points' => 0,
    'rate' => 0,
];

if ($selectedEvent !== '') {
    $eventStmt = mysqli_prepare($link,
        'SELECT * FROM event WHERE eventID = ? AND ClubID = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($eventStmt, 'ss', $selectedEvent, $clubID);
    mysqli_stmt_execute($eventStmt);
    $eventInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($eventStmt));

    if ($eventInfo) {
        $participantsStmt = mysqli_prepare($link,
            "SELECT s.StudentID, COALESCE(s.StudentName, l.name) AS StudentName, s.Email,
                    a.attendanceID, a.checkInTime, a.AttendanceStatus,
                    COALESCE(pl.pointsEarn, 0) AS pointsEarn
             FROM registration r
             JOIN student s ON s.StudentID = r.studentID
             JOIN login l ON l.UserID = s.UserID
             LEFT JOIN attendance a ON a.studentID = s.StudentID AND a.eventID = r.eventID
             LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
             WHERE r.eventID = ? AND r.registrationStatus = 'Registered'
             ORDER BY COALESCE(s.StudentName, l.name) ASC"
        );
        mysqli_stmt_bind_param($participantsStmt, 's', $selectedEvent);
        mysqli_stmt_execute($participantsStmt);
        $participants = mysqli_stmt_get_result($participantsStmt);

        $statStmt = mysqli_prepare($link,
            "SELECT
                (SELECT COUNT(*) FROM registration WHERE eventID = ? AND registrationStatus = 'Registered') AS registered,
                COUNT(DISTINCT a.attendanceID) AS marked,
                SUM(CASE WHEN a.AttendanceStatus LIKE 'Present%' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN a.AttendanceStatus LIKE 'Late%' THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN a.AttendanceStatus LIKE 'Absent%' THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN a.AttendanceStatus LIKE '%Volunteer%' THEN 1 ELSE 0 END) AS volunteer,
                COALESCE(SUM(pl.pointsEarn),0) AS points
             FROM attendance a
             LEFT JOIN pointlog pl ON pl.attendanceID = a.attendanceID
             WHERE a.eventID = ?"
        );
        mysqli_stmt_bind_param($statStmt, 'ss', $selectedEvent, $selectedEvent);
        mysqli_stmt_execute($statStmt);
        $statRow = mysqli_fetch_assoc(mysqli_stmt_get_result($statStmt));
        if ($statRow) {
            $eventStats['registered'] = (int)$statRow['registered'];
            $eventStats['marked'] = (int)$statRow['marked'];
            $eventStats['present'] = (int)$statRow['present'];
            $eventStats['late'] = (int)$statRow['late'];
            $eventStats['absent'] = (int)$statRow['absent'];
            $eventStats['volunteer'] = (int)$statRow['volunteer'];
            $eventStats['points'] = (int)$statRow['points'];
            $eventStats['rate'] = $eventStats['registered'] > 0
                ? round((($eventStats['present'] + $eventStats['late']) / $eventStats['registered']) * 100, 1)
                : 0;
        }
    }
}

$pageTitle = 'Take Attendance';
$activePage = 'take_attendance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'includes/head.php'; ?>
<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;gap:14px;}
.filter-card,.table-card,.attendance-create-card,.qr-card,.event-info-card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:25px;}
.form-inline{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;}
.attendance-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:25px;}
.create-grid{display:grid;grid-template-columns:1.2fr .8fr .8fr .8fr 1fr auto;gap:12px;align-items:end;}
.qr-wrap{display:flex;gap:20px;align-items:center;flex-wrap:wrap;}
.qr-img{width:190px;height:190px;border:10px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.18);background:#fff;}
.qr-code-text{font-family:Consolas,monospace;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:10px;word-break:break-all;}
.status-select{min-width:190px;}
.small-muted{color:#6b7280;font-size:13px;line-height:1.6;}
.stats-mini{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px;}
.stat-mini{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px;text-align:center;}
.stat-mini strong{display:block;color:#1f3f77;font-size:23px;}
.status-badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;}
.present{background:#d1fae5;color:#065f46;}.late{background:#fef3c7;color:#92400e;}.absent{background:#fee2e2;color:#991b1b;}.volunteer{background:#e0e7ff;color:#3730a3;}.other{background:#e5e7eb;color:#374151;}
.point-guide{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:12px;}
.point-item{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:10px;text-align:center;font-size:13px;}
.point-item strong{display:block;color:#1f3f77;font-size:17px;}
@media(max-width:1150px){.create-grid{grid-template-columns:1fr 1fr}.attendance-grid{grid-template-columns:1fr}.stats-mini{grid-template-columns:repeat(2,1fr)}.point-guide{grid-template-columns:repeat(2,1fr)}}
@media(max-width:700px){.form-inline,.create-grid{grid-template-columns:1fr}.page-header{display:block}.stats-mini,.point-guide{grid-template-columns:1fr}}
</style>
<script>
function updateAttendanceFields(){
    var attended = document.getElementById('attended');
    var arrival = document.getElementById('arrival_status');
    var volunteer = document.getElementById('volunteer');
    var preview = document.getElementById('point_preview');
    if(!attended || !arrival || !volunteer || !preview) return;

    if(attended.value === 'no'){
        arrival.disabled = true;
        volunteer.disabled = true;
        preview.innerText = '-10 points';
        return;
    }

    arrival.disabled = false;
    volunteer.disabled = false;
    var points = arrival.value === 'late' ? 5 : 10;
    if(volunteer.value === 'yes') points += 5;
    preview.innerText = '+' + points + ' points';
}
window.addEventListener('DOMContentLoaded', updateAttendanceFields);
</script>
</head>
<body>
<div class="app">
<?php include 'includes/sidebar_student.php'; ?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h2>Take Attendance</h2>
            <p class="small-muted">Committee attendance recording for <?= htmlspecialchars($committee['ClubName']) ?>.</p>
        </div>
        <a href="committeeAttendanceDashboard.php" class="btn btn-secondary">Back to Attendance Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="alert alert-warning"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="filter-card">
        <form method="get" class="form-inline">
            <div class="form-group">
                <label>Select Event</label>
                <select name="eventID" required>
                    <option value="">-- Choose Event --</option>
                    <?php while ($event = mysqli_fetch_assoc($eventOptions)): ?>
                        <option value="<?= htmlspecialchars($event['eventID']) ?>" <?= $selectedEvent === $event['eventID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['eventTitle']) ?> — <?= htmlspecialchars($event['eventDate']) ?> <?= htmlspecialchars($event['eventTime']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Open Attendance Page</button>
        </form>
    </div>

    <?php if ($eventInfo): ?>
        <?php
            $qrCheckInUrl = current_pages_base_url() . '/attendanceQRCheckIn.php?eventID=' . rawurlencode($eventInfo['eventID']);
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($qrCheckInUrl);
        ?>
        <div class="attendance-grid">
            <div class="qr-card">
                <h3>Event QR Code</h3>
                <div class="qr-wrap">
                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="Attendance QR Code" class="qr-img">
                    <div>
                        <p class="small-muted">Scan this QR code to open the attendance check-in page.</p>
                        <div class="qr-code-text"><?= htmlspecialchars($qrCheckInUrl) ?></div>
                    </div>
                </div>
            </div>

            <div class="event-info-card">
                <h3><?= htmlspecialchars($eventInfo['eventTitle']) ?></h3>
                <p class="small-muted">
                    <?= htmlspecialchars($eventInfo['eventDate']) ?> · <?= htmlspecialchars($eventInfo['eventTime']) ?><br>
                    Venue: <?= htmlspecialchars($eventInfo['eventVenue']) ?><br>
                    Status: <?= htmlspecialchars(ucfirst($eventInfo['eventStatus'])) ?>
                </p>
                <div class="stats-mini">
                    <div class="stat-mini"><strong><?= $eventStats['registered'] ?></strong>Registered</div>
                    <div class="stat-mini"><strong><?= $eventStats['marked'] ?></strong>Marked</div>
                    <div class="stat-mini"><strong><?= $eventStats['rate'] ?>%</strong>Rate</div>
                    <div class="stat-mini"><strong><?= $eventStats['points'] ?></strong>Points</div>
                </div>
            </div>
        </div>

        <div class="attendance-create-card">
            <h3>Create / Update Attendance Record</h3>
            <p class="small-muted">Enter the student's ID or username, then choose whether the student attended and whether they were a volunteer/helper.</p>
            <form method="post" class="create-grid">
                <input type="hidden" name="action" value="create_attendance">
                <input type="hidden" name="eventID" value="<?= htmlspecialchars($selectedEvent) ?>">

                <div class="form-group">
                    <label>Student ID / Username / QR Result</label>
                    <input type="text" name="student_identifier" placeholder="Example: CA23001" required autofocus>
                </div>

                <div class="form-group">
                    <label>Attend?</label>
                    <select name="attended" id="attended" onchange="updateAttendanceFields()" required>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Arrival</label>
                    <select name="arrival_status" id="arrival_status" onchange="updateAttendanceFields()">
                        <option value="on_time">On time</option>
                        <option value="late">Late</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Volunteer?</label>
                    <select name="volunteer" id="volunteer" onchange="updateAttendanceFields()">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Check-In Time</label>
                    <input type="datetime-local" name="manual_check_in_time" value="<?= date('Y-m-d\TH:i') ?>">
                </div>

                <button type="submit" class="btn btn-primary">Create Attendance</button>
            </form>
            <p class="small-muted" style="margin-top:10px;">Point preview: <strong id="point_preview">+10 points</strong></p>
            <div class="point-guide">
                <div class="point-item"><strong>+10</strong>Present on time</div>
                <div class="point-item"><strong>+5</strong>Late arrival</div>
                <div class="point-item"><strong>-10</strong>Absent without notice</div>
                <div class="point-item"><strong>+5</strong>Volunteer/helper bonus</div>
            </div>
        </div>

        <div class="table-card">
            <h3>Registered Participants</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_attendance">
                <input type="hidden" name="eventID" value="<?= htmlspecialchars($selectedEvent) ?>">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Check-In Time</th>
                            <th>Attendance Status</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($participants && mysqli_num_rows($participants) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($participants)): ?>
                            <?php
                            $currentStatus = $row['AttendanceStatus'] ?? '';
                            $statusLower = strtolower($currentStatus);
                            $badgeClass = 'other';
                            if (str_starts_with($statusLower, 'present')) $badgeClass = 'present';
                            if (str_starts_with($statusLower, 'late')) $badgeClass = 'late';
                            if (str_starts_with($statusLower, 'absent')) $badgeClass = 'absent';
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($row['StudentID']) ?>
                                    <input type="hidden" name="student_id[]" value="<?= htmlspecialchars($row['StudentID']) ?>">
                                </td>
                                <td><?= htmlspecialchars($row['StudentName']) ?></td>
                                <td><?= htmlspecialchars($row['Email'] ?? '—') ?></td>
                                <td>
                                    <input type="datetime-local" name="check_in_time[]"
                                           value="<?= $row['checkInTime'] ? date('Y-m-d\TH:i', strtotime($row['checkInTime'])) : '' ?>">
                                </td>
                                <td>
                                    <select name="attendance_status[]" class="status-select">
                                        <option value="">-- Not Marked --</option>
                                        <option value="Present" <?= $currentStatus === 'Present' ? 'selected' : '' ?>>Present on time (+10)</option>
                                        <option value="Present + Volunteer" <?= $currentStatus === 'Present + Volunteer' ? 'selected' : '' ?>>Present + Volunteer (+15)</option>
                                        <option value="Late" <?= $currentStatus === 'Late' ? 'selected' : '' ?>>Late arrival (+5)</option>
                                        <option value="Late + Volunteer" <?= $currentStatus === 'Late + Volunteer' ? 'selected' : '' ?>>Late + Volunteer (+10)</option>
                                        <option value="Absent" <?= $currentStatus === 'Absent' ? 'selected' : '' ?>>Absent without notice (-10)</option>
                                    </select>
                                    <?php if ($currentStatus !== ''): ?>
                                        <br><span class="status-badge <?= $badgeClass ?>" style="margin-top:6px;"><?= htmlspecialchars(status_label($currentStatus)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$row['pointsEarn'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;color:#888;">No registered participant for this event. You can still create attendance using the form above.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="btn-group" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Save Registered Participant Attendance</button>
                    <a href="committeeAttendanceDashboard.php" class="btn btn-secondary">Dashboard</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>
</div>
</body>
</html>
