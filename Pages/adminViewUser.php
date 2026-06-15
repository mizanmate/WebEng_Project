<?php
// ── Auth ──────────────────────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// ── DB ────────────────────────────────────────────────────────────────────
require_once 'DB_connection.php';

// ── Search ────────────────────────────────────────────────────────────────
$search   = trim($_GET['search_id'] ?? '');
$found    = null;
$notFound = false;

if ($search !== '') {
    $q = mysqli_prepare($link,
        "SELECT l.name, s.StudentID, s.Programme, s.StudYear, s.Email, s.Phone, s.totalPoints,
                s.status,
                GROUP_CONCAT(c.ClubName ORDER BY c.ClubName SEPARATOR ', ') AS clubs
         FROM student       s
         JOIN login         l  ON l.UserID  = s.UserID
         LEFT JOIN clubmembership cm ON cm.userID = s.UserID
         LEFT JOIN club          c  ON c.ClubID  = cm.clubID
         WHERE  s.StudentID = ?
         GROUP  BY s.StudentID"
    );
    mysqli_stmt_bind_param($q, 's', $search);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);

    if ($res && mysqli_num_rows($res) === 1) {
        $found = mysqli_fetch_assoc($res);
    } else {
        $notFound = true;
    }
}

// ── Full student list ──────────────────────────────────────────────────────
$listQuery = "SELECT l.name, s.StudentID, s.Programme, s.StudYear, s.Email,
                     s.status, s.totalPoints,
                     COUNT(DISTINCT cm.clubID) AS clubCount
              FROM student s
              JOIN login l ON l.UserID = s.UserID
              LEFT JOIN clubmembership cm ON cm.userID = s.UserID
              GROUP BY s.StudentID, l.name, s.Programme, s.StudYear, s.Email, s.status, s.totalPoints
              ORDER BY l.name ASC";
$allStudents = mysqli_query($link, $listQuery);

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'View User — Admin';
$activePage = 'view_users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>

<div class="app">

    <?php include 'includes/sidebar_admin.php'; ?>

    <main class="main-content">

        <h2>View User</h2>

        <!-- ── Search bar ── -->
        <form action="adminViewUser.php" method="get">
            <div class="search-bar">
                <input type="text"
                       name="search_id"
                       placeholder="Enter Student ID to search"
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <?php if ($notFound): ?>
            <div class="alert alert-danger">
                No student found with ID: <strong><?= htmlspecialchars($search) ?></strong>
            </div>
        <?php endif; ?>

        <!-- ── Single search result detail card ── -->
        <?php if ($found): ?>
            <div class="card">
                <h3>Student Details</h3>
                <table>
                    <tbody>
                        <tr>
                            <td style="width:180px; font-weight:600; color:#555;">Name</td>
                            <td><?= htmlspecialchars($found['name']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Student ID</td>
                            <td><?= htmlspecialchars($found['StudentID']) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Programme</td>
                            <td><?= htmlspecialchars($found['Programme'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Year of Study</td>
                            <td><?= htmlspecialchars($found['StudYear'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Email</td>
                            <td><?= htmlspecialchars($found['Email'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Phone</td>
                            <td><?= htmlspecialchars($found['Phone'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Total Points</td>
                            <td><?= (int)$found['totalPoints'] ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Status</td>
                            <td>
                                <span class="badge <?= strtolower($found['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($found['status'] ?? 'active') ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#555;">Registered Clubs</td>
                            <td><?= htmlspecialchars($found['clubs'] ?? 'None') ?></td>
                        </tr>
                    </tbody>
                </table>
                <div class="btn-group">
                    <a href="adminEditUser.php?search_id=<?= urlencode($found['StudentID']) ?>"
                       class="btn btn-primary btn-sm">Edit This User</a>
                    <a href="adminViewUser.php" class="btn btn-secondary btn-sm">Clear Search</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── All students table ── -->
        <div class="card">
            <h3><?= $search !== '' ? 'All Students' : 'All Registered Students' ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Programme</th>
                        <th>Year</th>
                        <th>Email</th>
                        <th>Clubs</th>
                        <th>Points</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($allStudents && mysqli_num_rows($allStudents) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($allStudents)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['StudentID']) ?></td>
                            <td><?= htmlspecialchars($row['Programme'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($row['StudYear'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($row['Email'] ?: '—') ?></td>
                            <td><?= (int)$row['clubCount'] ?></td>
                            <td><?= (int)$row['totalPoints'] ?></td>
                            <td>
                                <span class="badge <?= strtolower($row['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($row['status'] ?? 'active') ?>
                                </span>
                            </td>
                            <td>
                                <a href="adminViewUser.php?search_id=<?= urlencode($row['StudentID']) ?>"
                                   class="btn btn-secondary btn-sm">View</a>
                                <a href="adminEditUser.php?search_id=<?= urlencode($row['StudentID']) ?>"
                                   class="btn btn-primary btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; color:#999;">No students registered yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

</body>
</html>
