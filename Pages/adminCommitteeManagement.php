<?php
// ================================================================
//  adminCommitteeManagement.php
//  Module 2 — Manage club committees.
//  Admin can assign, update and remove committee roles.
// ================================================================

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

function next_id(mysqli $link, string $table, string $column, string $prefix, int $pad = 4): string
{
    $prefixLen = strlen($prefix) + 1;
    $sql = "SELECT MAX(CAST(SUBSTRING($column, $prefixLen) AS UNSIGNED)) AS max_no FROM $table WHERE $column LIKE CONCAT(?, '%')";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 's', $prefix);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $next = ((int)($row['max_no'] ?? 0)) + 1;
    return $prefix . str_pad((string)$next, $pad, '0', STR_PAD_LEFT);
}

$success = '';
$error   = '';
$positions = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Committee Member'];

// ── Assign or update committee role ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
    $userID   = trim($_POST['user_id'] ?? '');
    $clubID   = trim($_POST['club_id'] ?? '');
    $position = trim($_POST['position'] ?? '');

    if ($userID === '' || $clubID === '' || $position === '') {
        $error = 'Please select student, club and committee position.';
    } elseif (!in_array($position, $positions, true)) {
        $error = 'Invalid committee position.';
    } else {
        // Ensure the student is also a club member. Committee is an extra privilege on top of student membership.
        $chkMember = mysqli_prepare($link, 'SELECT memberID FROM clubmembership WHERE userID = ? AND clubID = ?');
        mysqli_stmt_bind_param($chkMember, 'ss', $userID, $clubID);
        mysqli_stmt_execute($chkMember);
        mysqli_stmt_store_result($chkMember);

        if (mysqli_stmt_num_rows($chkMember) === 0) {
            $memberID = next_id($link, 'clubmembership', 'memberID', 'MBR', 4);
            $today = date('Y-m-d');
            $insMember = mysqli_prepare($link,
                'INSERT INTO clubmembership (memberID, userID, clubID, RegistrationDate, clubRole)
                 VALUES (?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($insMember, 'sssss', $memberID, $userID, $clubID, $today, $position);
            mysqli_stmt_execute($insMember);
        } else {
            $updMember = mysqli_prepare($link, 'UPDATE clubmembership SET clubRole = ? WHERE userID = ? AND clubID = ?');
            mysqli_stmt_bind_param($updMember, 'sss', $position, $userID, $clubID);
            mysqli_stmt_execute($updMember);
        }

        // Insert or update committee table.
        $chkCommittee = mysqli_prepare($link, 'SELECT committeeID FROM clubcommitee WHERE userID = ? AND clubID = ?');
        mysqli_stmt_bind_param($chkCommittee, 'ss', $userID, $clubID);
        mysqli_stmt_execute($chkCommittee);
        $res = mysqli_stmt_get_result($chkCommittee);
        $existing = $res ? mysqli_fetch_assoc($res) : null;
        $today = date('Y-m-d');

        if ($existing) {
            $upd = mysqli_prepare($link,
                'UPDATE clubcommitee
                 SET commiteePosition = ?, CommiteeAssignDate = ?
                 WHERE committeeID = ?'
            );
            mysqli_stmt_bind_param($upd, 'sss', $position, $today, $existing['committeeID']);
            if (mysqli_stmt_execute($upd)) {
                $success = 'Committee role updated successfully.';
            } else {
                $error = 'Unable to update committee role.';
            }
        } else {
            $committeeID = next_id($link, 'clubcommitee', 'committeeID', 'CMT', 4);
            $ins = mysqli_prepare($link,
                'INSERT INTO clubcommitee (committeeID, userID, clubID, commiteePosition, CommiteeAssignDate)
                 VALUES (?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($ins, 'sssss', $committeeID, $userID, $clubID, $position, $today);
            if (mysqli_stmt_execute($ins)) {
                $success = 'Committee member assigned successfully.';
            } else {
                $error = 'Unable to assign committee member.';
            }
        }
    }
}

// ── Remove committee role ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    $committeeID = trim($_POST['committee_id'] ?? '');
    $userID      = trim($_POST['user_id'] ?? '');
    $clubID      = trim($_POST['club_id'] ?? '');

    if ($committeeID === '') {
        $error = 'Invalid committee record.';
    } else {
        $del = mysqli_prepare($link, 'DELETE FROM clubcommitee WHERE committeeID = ?');
        mysqli_stmt_bind_param($del, 's', $committeeID);

        if (mysqli_stmt_execute($del) && mysqli_stmt_affected_rows($del) > 0) {
            if ($userID !== '' && $clubID !== '') {
                $role = 'Member';
                $updMember = mysqli_prepare($link, 'UPDATE clubmembership SET clubRole = ? WHERE userID = ? AND clubID = ?');
                mysqli_stmt_bind_param($updMember, 'sss', $role, $userID, $clubID);
                mysqli_stmt_execute($updMember);
            }
            $success = 'Committee role removed successfully.';
        } else {
            $error = 'Unable to remove committee role.';
        }
    }
}

// ── Dropdown options ──────────────────────────────────────────────────────
$students = mysqli_query($link,
    "SELECT s.UserID, s.StudentID, l.name
     FROM student s
     JOIN login l ON l.UserID = s.UserID
     WHERE COALESCE(s.status, 'active') <> 'inactive'
     ORDER BY l.name ASC"
);

$clubs = mysqli_query($link,
    "SELECT ClubID, ClubName, ClubStatus
     FROM club
     ORDER BY ClubName ASC"
);

// ── Current committee list ────────────────────────────────────────────────
$committees = mysqli_query($link,
    "SELECT cc.committeeID, cc.userID, cc.clubID, cc.commiteePosition, cc.CommiteeAssignDate,
            l.name, s.StudentID, c.ClubName, c.ClubStatus
     FROM clubcommitee cc
     JOIN login l ON l.UserID = cc.userID
     JOIN student s ON s.UserID = cc.userID
     JOIN club c ON c.ClubID = cc.clubID
     ORDER BY c.ClubName ASC,
              FIELD(cc.commiteePosition, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Committee Member'),
              l.name ASC"
);

$pageTitle  = 'Manage Committees — Admin';
$activePage = 'committee_management';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>
<div class="app">
    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <h2>Manage Club Committees</h2>

        <div class="quick-actions">
            <a href="adminClubManagement.php" class="btn btn-secondary btn-sm">Manage Clubs</a>
            <a href="adminDash.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="form-card form-card-wide">
            <h3>Assign / Update Committee Role</h3>
            <form method="post" action="adminCommitteeManagement.php">
                <input type="hidden" name="action" value="assign">

                <div class="form-row">
                    <div class="form-group">
                        <label for="user_id">Student</label>
                        <select id="user_id" name="user_id" required>
                            <option value="">-- Select student --</option>
                            <?php if ($students): ?>
                                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                                    <option value="<?= htmlspecialchars($student['UserID']) ?>">
                                        <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['StudentID']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="club_id">Club</label>
                        <select id="club_id" name="club_id" required>
                            <option value="">-- Select club --</option>
                            <?php if ($clubs): ?>
                                <?php while ($club = mysqli_fetch_assoc($clubs)): ?>
                                    <option value="<?= htmlspecialchars($club['ClubID']) ?>">
                                        <?= htmlspecialchars($club['ClubName']) ?><?= strtolower($club['ClubStatus']) === 'inactive' ? ' (inactive)' : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:18px;">
                    <label for="position">Committee Position</label>
                    <select id="position" name="position" required>
                        <option value="">-- Select position --</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?= htmlspecialchars($position) ?>"><?= htmlspecialchars($position) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alert alert-info">
                    Assigning a committee role also adds the student as a club member if they are not already in that club.
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Save Committee Role</button>
                    <button type="reset" class="btn btn-secondary">Clear</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Current Committee Members</h3>
            <table>
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Student</th>
                        <th>Position</th>
                        <th>Assigned Date</th>
                        <th>Club Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($committees && mysqli_num_rows($committees) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($committees)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['ClubName']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                <small><?= htmlspecialchars($row['StudentID']) ?></small>
                            </td>
                            <td><span class="badge badge-pending"><?= htmlspecialchars($row['commiteePosition']) ?></span></td>
                            <td><?= htmlspecialchars($row['CommiteeAssignDate']) ?></td>
                            <td>
                                <span class="badge <?= strtolower($row['ClubStatus']) === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($row['ClubStatus']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="adminCommitteeManagement.php" style="display:inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="committee_id" value="<?= htmlspecialchars($row['committeeID']) ?>">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['userID']) ?>">
                                    <input type="hidden" name="club_id" value="<?= htmlspecialchars($row['clubID']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Remove this student from committee? Membership will remain as normal member.')">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#999;">No committee members assigned yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
