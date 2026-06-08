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

// Fetch student + login data (also used as sidebar source)
$stmt = mysqli_prepare($link,
    'SELECT l.name, s.StudentID, s.Programme, s.StudYear,
            s.Email, s.Phone, s.Studphoto, s.totalPoints
     FROM student s
     JOIN login   l ON l.UserID = s.UserID
     WHERE  s.UserID = ?'
);
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$sidebarUser = $user; // sidebar reads 'Studphoto' from this

// Clubs the student has joined
$clubStmt = mysqli_prepare($link,
    "SELECT c.ClubName
     FROM club          c
     JOIN clubmembership cm ON cm.clubID = c.ClubID
     WHERE  cm.userID = ?
     ORDER  BY c.ClubName ASC"
);
mysqli_stmt_bind_param($clubStmt, 's', $userID);
mysqli_stmt_execute($clubStmt);
$clubRows = mysqli_stmt_get_result($clubStmt);
$clubList = [];
while ($c = mysqli_fetch_assoc($clubRows)) {
    $clubList[] = $c['ClubName'];
}

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'View Profile';
$activePage = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>View Profile</h2>

        <div class="quick-actions">
            <a href="StudEditProfile.php" class="btn btn-primary btn-sm">Edit Profile</a>
        </div>

        <div class="form-card">

            <?php if (!empty($user['Studphoto'])): ?>
                <div style="margin-bottom:20px;">
                    <img src="../uploads/<?= htmlspecialchars($user['Studphoto']) ?>"
                         alt="Profile picture"
                         style="width:96px; height:96px; border-radius:50%; object-fit:cover;
                                border:3px solid #d0d7e4;">
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" value="<?= htmlspecialchars($user['name'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Student ID</label>
                <input type="text" value="<?= htmlspecialchars($user['StudentID'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Programme</label>
                <input type="text" value="<?= htmlspecialchars($user['Programme'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Year of Study</label>
                <input type="text" value="<?= htmlspecialchars($user['StudYear'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" value="<?= htmlspecialchars($user['Email'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" value="<?= htmlspecialchars($user['Phone'] ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Total Points</label>
                <input type="text" value="<?= (int)($user['totalPoints'] ?? 0) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Registered Clubs</label>
                <input type="text"
                       value="<?= htmlspecialchars(count($clubList) > 0 ? implode(', ', $clubList) : 'None') ?>"
                       readonly>
            </div>

        </div>
    </main>
</div>

</body>
</html>
