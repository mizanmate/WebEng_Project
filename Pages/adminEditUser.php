<?php
// ── Auth ──────────────────────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// ── DB ────────────────────────────────────────────────────────────────────
require_once 'DB_connection.php';

// ── Handle POST actions ────────────────────────────────────────────────────
$success = '';
$error   = '';
$search  = trim($_POST['search_id'] ?? $_GET['search_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetID = trim($_POST['target_userid'] ?? '');
    $action   = $_POST['action'] ?? '';

    // ── Delete user ──
    if ($action === 'delete' && $targetID !== '') {
        $del = mysqli_prepare($link, "DELETE FROM login WHERE UserID = ? AND role != 'admin'");
        mysqli_stmt_bind_param($del, 's', $targetID);
        mysqli_stmt_execute($del);

        if (mysqli_stmt_affected_rows($del) > 0) {
            $success = 'User deleted successfully.';
            $search  = '';
        } else {
            $error = 'Could not delete user (admin accounts cannot be deleted this way).';
        }
    }

    // ── Update student details ──
    if ($action === 'update' && $targetID !== '') {
        $name      = trim($_POST['name']      ?? '');
        $programme = trim($_POST['programme'] ?? '');
        $year      = trim($_POST['year']      ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');

        if (!$name) {
            $error = 'Name cannot be empty.';
        } else {
            $picFilename = null;
            if (isset($_FILES['Studphoto']) && $_FILES['Studphoto']['error'] === UPLOAD_ERR_OK) {
                $ext     = strtolower(pathinfo($_FILES['Studphoto']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $picFilename = 'user_' . $targetID . '_' . time() . '.' . $ext;
                    $uploadDir   = '../uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    if (!move_uploaded_file($_FILES['Studphoto']['tmp_name'], $uploadDir . $picFilename)) {
                        $picFilename = null;
                        $error = 'Image upload failed.';
                    }
                } else {
                    $error = 'Only JPG, PNG, GIF, WEBP files are allowed.';
                }
            }

            if (!$error) {
                $updLogin = mysqli_prepare($link, 'UPDATE login SET name = ? WHERE UserID = ?');
                mysqli_stmt_bind_param($updLogin, 'ss', $name, $targetID);
                mysqli_stmt_execute($updLogin);

                if ($picFilename) {
                    $updStud = mysqli_prepare($link,
                        'UPDATE student SET Programme=?, StudYear=?, Email=?, Phone=?, Studphoto=?
                         WHERE UserID=?'
                    );
                    mysqli_stmt_bind_param($updStud, 'ssssss',
                        $programme, $year, $email, $phone, $picFilename, $targetID
                    );
                } else {
                    $updStud = mysqli_prepare($link,
                        'UPDATE student SET Programme=?, StudYear=?, Email=?, Phone=? WHERE UserID=?'
                    );
                    mysqli_stmt_bind_param($updStud, 'sssss',
                        $programme, $year, $email, $phone, $targetID
                    );
                }
                mysqli_stmt_execute($updStud)
                    ? $success = 'User updated successfully.'
                    : $error   = 'Update failed. Please try again.';
            }
        }
    }

    // ── Assign committee position ──
    if ($action === 'assign_committee' && $targetID !== '') {
        $clubID   = trim($_POST['comm_club_id']  ?? '');
        $position = trim($_POST['comm_position'] ?? '');

        if (!$clubID || !$position) {
            $error = 'Please select both a club and a position.';
        } else {
            $today = date('Y-m-d');

            // Upsert into clubcommitee
            $chk = mysqli_prepare($link,
                'SELECT committeeID FROM clubcommitee WHERE userID = ? AND clubID = ?'
            );
            mysqli_stmt_bind_param($chk, 'ss', $targetID, $clubID);
            mysqli_stmt_execute($chk);
            $chkRes = mysqli_stmt_get_result($chk);

            if ($chkRes && mysqli_num_rows($chkRes) > 0) {
                $existing = mysqli_fetch_assoc($chkRes);
                $upd = mysqli_prepare($link,
                    'UPDATE clubcommitee SET commiteePosition=?, CommiteeAssignDate=?
                     WHERE committeeID=?'
                );
                mysqli_stmt_bind_param($upd, 'sss', $position, $today, $existing['committeeID']);
                mysqli_stmt_execute($upd);
            } else {
                $cntRow = mysqli_fetch_row(mysqli_query($link, 'SELECT COUNT(*) FROM clubcommitee'));
                $commID = 'COM' . str_pad((int)$cntRow[0] + 1, 4, '0', STR_PAD_LEFT);
                $ins    = mysqli_prepare($link,
                    'INSERT INTO clubcommitee
                        (committeeID, userID, clubID, commiteePosition, CommiteeAssignDate)
                     VALUES (?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param($ins, 'sssss', $commID, $targetID, $clubID, $position, $today);
                mysqli_stmt_execute($ins);
            }

            // Promote clubRole in ClubMembership
            $updMem = mysqli_prepare($link,
                "UPDATE clubmembership SET clubRole = 'Committee' WHERE userID = ? AND clubID = ?"
            );
            mysqli_stmt_bind_param($updMem, 'ss', $targetID, $clubID);
            mysqli_stmt_execute($updMem);

            $success = "Committee position \"$position\" assigned.";
        }
    }

    // ── Revoke committee position ──
    if ($action === 'revoke_committee' && $targetID !== '') {
        $clubID = trim($_POST['revoke_club_id'] ?? '');

        $del = mysqli_prepare($link,
            'DELETE FROM clubcommitee WHERE userID = ? AND clubID = ?'
        );
        mysqli_stmt_bind_param($del, 'ss', $targetID, $clubID);
        mysqli_stmt_execute($del);

        $updMem = mysqli_prepare($link,
            "UPDATE clubmembership SET clubRole = 'Member' WHERE userID = ? AND clubID = ?"
        );
        mysqli_stmt_bind_param($updMem, 'ss', $targetID, $clubID);
        mysqli_stmt_execute($updMem);

        $success = 'Committee status revoked.';
    }
}

// ── Fetch user for form ────────────────────────────────────────────────────
$found       = null;
$notFound    = false;
$memberships = [];

if ($search !== '') {
    $q = mysqli_prepare($link,
        'SELECT l.name, s.StudentID, s.UserID, s.Programme, s.StudYear,
                s.Email, s.Phone, s.Studphoto
         FROM student s
         JOIN login   l ON l.UserID = s.UserID
         WHERE  s.StudentID = ?'
    );
    mysqli_stmt_bind_param($q, 's', $search);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);

    if ($res && mysqli_num_rows($res) === 1) {
        $found = mysqli_fetch_assoc($res);

        // Load club memberships + current committee info
        $memStmt = mysqli_prepare($link,
            "SELECT c.ClubID, c.ClubName, cm.clubRole,
                    cc.commiteePosition, cc.CommiteeAssignDate
             FROM clubmembership cm
             JOIN club         c  ON c.ClubID  = cm.clubID
             LEFT   JOIN clubcommitee cc
                    ON cc.userID = cm.userID AND cc.clubID = cm.clubID
             WHERE  cm.userID = ?
             ORDER  BY c.ClubName ASC"
        );
        mysqli_stmt_bind_param($memStmt, 's', $found['UserID']);
        mysqli_stmt_execute($memStmt);
        $memRes = mysqli_stmt_get_result($memStmt);
        while ($row = mysqli_fetch_assoc($memRes)) {
            $memberships[] = $row;
        }
    } else {
        $notFound = true;
    }
}

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'Edit User — Admin';
$activePage = 'edit_user';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">

        <h2>Edit User</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ── Search bar ── -->
        <form action="adminEditUser.php" method="get">
            <div class="search-bar">
                <input type="text"
                       name="search_id"
                       placeholder="Enter Student ID to edit"
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <?php if ($notFound): ?>
            <div class="alert alert-danger">
                No student found with ID: <strong><?= htmlspecialchars($search) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($found): ?>

            <!-- ══ Student details form ══ -->
            <div class="form-card" style="max-width:560px;">
                <form action="adminEditUser.php"
                      method="post"
                      enctype="multipart/form-data">

                    <input type="hidden" name="target_userid" value="<?= htmlspecialchars($found['UserID']) ?>">
                    <input type="hidden" name="search_id"     value="<?= htmlspecialchars($found['StudentID']) ?>">

                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="avatar-preview">
                            <?php if (!empty($found['Studphoto'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($found['Studphoto']) ?>"
                                     alt="Current profile picture">
                            <?php else: ?>
                                <div class="avatar-empty">&#128100;</div>
                            <?php endif; ?>
                            <input type="file" name="Studphoto" accept="image/*">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Student ID (read-only)</label>
                        <input type="text" value="<?= htmlspecialchars($found['StudentID']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name"
                               value="<?= htmlspecialchars($found['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="programme">Programme</label>
                        <input type="text" id="programme" name="programme"
                               value="<?= htmlspecialchars($found['Programme'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="year">Year of Study</label>
                        <input type="text" id="year" name="year"
                               value="<?= htmlspecialchars($found['StudYear'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($found['Email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone"
                               value="<?= htmlspecialchars($found['Phone'] ?? '') ?>">
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="action" value="update"
                                class="btn btn-primary">Save Changes</button>
                        <button type="submit" name="action" value="delete"
                                class="btn btn-danger"
                                onclick="return confirm('Permanently delete this user?')">
                            Delete User
                        </button>
                    </div>

                </form>
            </div>

            <!-- ══ Club roles & committee assignment ══ -->
            <?php if (count($memberships) > 0): ?>
            <div class="card" style="margin-top:28px; max-width:700px;">
                <h3>Club Roles &amp; Committee Assignment</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Club</th>
                            <th>Current Role</th>
                            <th>Position</th>
                            <th>Assigned Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberships as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['ClubName']) ?></td>
                            <td>
                                <span class="badge <?= $m['clubRole'] === 'Committee' ? 'badge-active' : 'badge-pending' ?>">
                                    <?= htmlspecialchars($m['clubRole']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($m['commiteePosition'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($m['CommiteeAssignDate'] ?? '—') ?></td>
                            <td>
                                <?php if ($m['clubRole'] === 'Committee'): ?>
                                <form method="post" action="adminEditUser.php">
                                    <input type="hidden" name="target_userid" value="<?= htmlspecialchars($found['UserID']) ?>">
                                    <input type="hidden" name="search_id"     value="<?= htmlspecialchars($found['StudentID']) ?>">
                                    <input type="hidden" name="revoke_club_id" value="<?= htmlspecialchars($m['ClubID']) ?>">
                                    <button type="submit" name="action" value="revoke_committee"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Revoke committee status for this club?')">
                                        Revoke
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Assign form -->
                <div style="margin-top:22px; padding-top:18px; border-top:1px solid #eef0f5;">
                    <h3>Assign Committee Position</h3>
                    <form method="post" action="adminEditUser.php">
                        <input type="hidden" name="target_userid" value="<?= htmlspecialchars($found['UserID']) ?>">
                        <input type="hidden" name="search_id"     value="<?= htmlspecialchars($found['StudentID']) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="comm_club_id">Club</label>
                                <select id="comm_club_id" name="comm_club_id" required>
                                    <option value="">-- Select club --</option>
                                    <?php foreach ($memberships as $m): ?>
                                        <option value="<?= htmlspecialchars($m['ClubID']) ?>">
                                            <?= htmlspecialchars($m['ClubName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="comm_position">Position</label>
                                <select id="comm_position" name="comm_position" required>
                                    <option value="">-- Select position --</option>
                                    <option value="President">President</option>
                                    <option value="Vice President">Vice President</option>
                                    <option value="Secretary">Secretary</option>
                                    <option value="Treasurer">Treasurer</option>
                                    <option value="Event Manager">Event Manager</option>
                                    <option value="Public Relations">Public Relations</option>
                                </select>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="action" value="assign_committee"
                                    class="btn btn-success">
                                Assign Position
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info" style="max-width:560px; margin-top:16px;">
                This student has not joined any clubs yet. Committee assignment is only available for club members.
            </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>
</div>

</body>
</html>
