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

// Sidebar
$stmt = mysqli_prepare($link, 'SELECT Studphoto FROM Student WHERE UserID = ?');
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ── Handle join request ────────────────────────────────────────────────────
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubID = trim($_POST['club_id'] ?? '');

    if ($clubID === '') {
        $error = 'Please select a club.';
    } else {
        // Check already a member
        $chk = mysqli_prepare($link,
            'SELECT memberID FROM ClubMembership WHERE userID = ? AND clubID = ?'
        );
        mysqli_stmt_bind_param($chk, 'ss', $userID, $clubID);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = 'You are already a member of this club.';
        } else {
            // Generate memberID
            $cntRow   = mysqli_fetch_row(mysqli_query($link, 'SELECT COUNT(*) FROM ClubMembership'));
            $memberID = 'MBR' . str_pad((int)$cntRow[0] + 1, 4, '0', STR_PAD_LEFT);
            $today    = date('Y-m-d');

            $ins = mysqli_prepare($link,
                'INSERT INTO ClubMembership (memberID, userID, clubID, RegistrationDate, clubRole)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $clubRole = 'Member';
            mysqli_stmt_bind_param($ins, 'sssss', $memberID, $userID, $clubID, $today, $clubRole);

            if (mysqli_stmt_execute($ins)) {
                $success = 'You have successfully joined the club!';
            } else {
                $error = 'Could not join the club. Please try again.';
            }
        }
    }
}

// ── Available clubs dropdown ───────────────────────────────────────────────
$optStmt = mysqli_prepare($link,
    "SELECT ClubID, ClubName FROM Club WHERE ClubStatus = 'active' ORDER BY ClubName ASC"
);
mysqli_stmt_execute($optStmt);
$clubOptions = mysqli_stmt_get_result($optStmt);

// ── All active clubs with member count ────────────────────────────────────
$allStmt = mysqli_prepare($link,
    "SELECT c.ClubID, c.ClubName, c.ClubDesc, c.ClubStatus,
            COUNT(cm.memberID) AS members
     FROM   Club          c
     LEFT   JOIN ClubMembership cm ON cm.clubID = c.ClubID
     WHERE  c.ClubStatus = 'active'
     GROUP  BY c.ClubID
     ORDER  BY c.ClubName ASC"
);
mysqli_stmt_execute($allStmt);
$allClubs = mysqli_stmt_get_result($allStmt);

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'Join Club';
$activePage = 'join_club';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Join Club</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ── Join form ── -->
        <div class="form-card">
            <form action="StudJoinClub.php" method="post">

                <div class="form-group">
                    <label for="club_id">Select Club</label>
                    <select id="club_id" name="club_id" required>
                        <option value="">-- Select a club --</option>
                        <?php while ($c = mysqli_fetch_assoc($clubOptions)): ?>
                            <option value="<?= htmlspecialchars($c['ClubID']) ?>">
                                <?= htmlspecialchars($c['ClubName']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="reset"  class="btn btn-secondary">Clear</button>
                    <button type="submit" class="btn btn-primary">Join Club</button>
                </div>

            </form>
        </div>

        <!-- ── Available clubs info table ── -->
        <div class="card" style="margin-top:24px;">
            <h3>Available Clubs</h3>

            <table>
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Description</th>
                        <th>Members</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allClubs && mysqli_num_rows($allClubs) > 0): ?>
                        <?php while ($c = mysqli_fetch_assoc($allClubs)): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['ClubName']) ?></td>
                                <td><?= htmlspecialchars($c['ClubDesc'] ?? '—') ?></td>
                                <td><?= (int)$c['members'] ?></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#999;">
                                No active clubs available.
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
