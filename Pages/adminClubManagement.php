<?php
// ================================================================
//  adminClubManagement.php
//  Module 2 — Manage club information.
//  Admin can create, update, delete, activate/deactivate and view clubs.
// ================================================================

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';

function next_prefixed_id(mysqli $link, string $table, string $column, string $prefix, int $pad = 3): string
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
$editClub = null;

// ── Delete club ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $clubID = trim($_POST['club_id'] ?? '');

    if ($clubID === '') {
        $error = 'Invalid club selected.';
    } else {
        $del = mysqli_prepare($link, 'DELETE FROM Club WHERE ClubID = ?');
        mysqli_stmt_bind_param($del, 's', $clubID);

        if (mysqli_stmt_execute($del) && mysqli_stmt_affected_rows($del) > 0) {
            $success = 'Club deleted successfully.';
        } else {
            $error = 'Unable to delete club. Please try again.';
        }
    }
}

// ── Create / Update club ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create', 'update'], true)) {
    $action      = $_POST['action'];
    $clubID      = trim($_POST['club_id'] ?? '');
    $clubName    = trim($_POST['club_name'] ?? '');
    $clubDesc    = trim($_POST['club_desc'] ?? '');
    $advisorName = trim($_POST['advisor_name'] ?? '');
    $clubStatus  = trim($_POST['club_status'] ?? 'active');

    if ($clubName === '' || $advisorName === '') {
        $error = 'Club name and advisor name are required.';
    } elseif (!in_array($clubStatus, ['active', 'inactive'], true)) {
        $error = 'Invalid club status.';
    } else {
        if ($action === 'create') {
            $newID = next_prefixed_id($link, 'Club', 'ClubID', 'CLB', 3);
            $today = date('Y-m-d');

            $ins = mysqli_prepare($link,
                'INSERT INTO Club (ClubID, ClubName, ClubDesc, ClubCreated, AdvisorName, ClubStatus)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($ins, 'ssssss', $newID, $clubName, $clubDesc, $today, $advisorName, $clubStatus);

            if (mysqli_stmt_execute($ins)) {
                $success = 'Club created successfully.';
            } else {
                $error = 'Unable to create club. Club name may already exist or database error occurred.';
            }
        } else {
            if ($clubID === '') {
                $error = 'Invalid club selected.';
            } else {
                $upd = mysqli_prepare($link,
                    'UPDATE Club
                     SET ClubName = ?, ClubDesc = ?, AdvisorName = ?, ClubStatus = ?
                     WHERE ClubID = ?'
                );
                mysqli_stmt_bind_param($upd, 'sssss', $clubName, $clubDesc, $advisorName, $clubStatus, $clubID);

                if (mysqli_stmt_execute($upd)) {
                    $success = 'Club updated successfully.';
                } else {
                    $error = 'Unable to update club. Please try again.';
                }
            }
        }
    }
}

// ── Load club for editing ─────────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $editID = trim($_GET['edit']);
    $stmt = mysqli_prepare($link, 'SELECT * FROM Club WHERE ClubID = ?');
    mysqli_stmt_bind_param($stmt, 's', $editID);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $editClub = $res ? mysqli_fetch_assoc($res) : null;
}

// ── Club list summary ─────────────────────────────────────────────────────
$clubs = mysqli_query($link,
    "SELECT c.ClubID, c.ClubName, c.ClubDesc, c.ClubCreated, c.AdvisorName, c.ClubStatus,
            COUNT(DISTINCT cm.memberID) AS totalMembers,
            COUNT(DISTINCT cc.committeeID) AS totalCommittees
     FROM Club c
     LEFT JOIN ClubMembership cm ON cm.clubID = c.ClubID
     LEFT JOIN ClubCommitee cc ON cc.clubID = c.ClubID
     GROUP BY c.ClubID, c.ClubName, c.ClubDesc, c.ClubCreated, c.AdvisorName, c.ClubStatus
     ORDER BY c.ClubName ASC"
);

$pageTitle  = 'Manage Clubs — Admin';
$activePage = 'club_management';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>
<div class="app">
    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <h2>Manage Club Information</h2>

        <div class="quick-actions">
            <a href="adminCommitteeManagement.php" class="btn btn-secondary btn-sm">Manage Committees</a>
            <a href="adminDash.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="form-card form-card-wide">
            <h3><?= $editClub ? 'Update Club' : 'Create New Club' ?></h3>
            <form method="post" action="adminClubManagement.php">
                <input type="hidden" name="action" value="<?= $editClub ? 'update' : 'create' ?>">
                <input type="hidden" name="club_id" value="<?= htmlspecialchars($editClub['ClubID'] ?? '') ?>">

                <?php if ($editClub): ?>
                    <div class="form-group">
                        <label>Club ID</label>
                        <input type="text" value="<?= htmlspecialchars($editClub['ClubID']) ?>" readonly>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="club_name">Club Name</label>
                        <input type="text" id="club_name" name="club_name" placeholder="e.g. Computer Science Club"
                               value="<?= htmlspecialchars($editClub['ClubName'] ?? ($_POST['club_name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="advisor_name">Advisor Name</label>
                        <input type="text" id="advisor_name" name="advisor_name" placeholder="e.g. Dr. Ahmad"
                               value="<?= htmlspecialchars($editClub['AdvisorName'] ?? ($_POST['advisor_name'] ?? '')) ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top:18px;">
                    <label for="club_desc">Description</label>
                    <textarea id="club_desc" name="club_desc" rows="4" placeholder="Briefly describe the club activities and purpose."><?= htmlspecialchars($editClub['ClubDesc'] ?? ($_POST['club_desc'] ?? '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="club_status">Operational Status</label>
                    <?php $currentStatus = $editClub['ClubStatus'] ?? ($_POST['club_status'] ?? 'active'); ?>
                    <select id="club_status" name="club_status">
                        <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active — visible to students</option>
                        <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive — hidden from students</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?= $editClub ? 'Update Club' : 'Create Club' ?></button>
                    <?php if ($editClub): ?>
                        <a href="adminClubManagement.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-secondary">Clear</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Club List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Advisor</th>
                        <th>Members</th>
                        <th>Committees</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($clubs && mysqli_num_rows($clubs) > 0): ?>
                    <?php while ($club = mysqli_fetch_assoc($clubs)): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($club['ClubName']) ?></strong><br>
                                <small><?= htmlspecialchars($club['ClubDesc'] ?: 'No description') ?></small>
                            </td>
                            <td><?= htmlspecialchars($club['AdvisorName']) ?></td>
                            <td><?= (int)$club['totalMembers'] ?></td>
                            <td><?= (int)$club['totalCommittees'] ?></td>
                            <td>
                                <span class="badge <?= strtolower($club['ClubStatus']) === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($club['ClubStatus']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($club['ClubCreated']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="adminClubManagement.php?edit=<?= urlencode($club['ClubID']) ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <form method="post" action="adminClubManagement.php" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="club_id" value="<?= htmlspecialchars($club['ClubID']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Delete this club? Related membership, committee and event records may also be removed.')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#999;">No clubs found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
