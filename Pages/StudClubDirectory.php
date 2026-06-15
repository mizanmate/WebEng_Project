<?php
// ================================================================
//  StudClubDirectory.php
//  Module 2 — View club list and details for students.
// ================================================================

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

// Sidebar profile photo
$stmt = mysqli_prepare($link, 'SELECT Studphoto FROM student WHERE UserID = ?');
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$sidebarUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$search = trim($_GET['search'] ?? '');
$whereSql = "WHERE c.ClubStatus = 'active'";
$params = [];
$types = '';

if ($search !== '') {
    $whereSql .= " AND (c.ClubName LIKE ? OR c.ClubDesc LIKE ? OR c.AdvisorName LIKE ?)";
    $keyword = '%' . $search . '%';
    $params = [$keyword, $keyword, $keyword];
    $types = 'sss';
}

$sql = "SELECT c.ClubID, c.ClubName, c.ClubDesc, c.AdvisorName, c.ClubCreated, c.ClubStatus,
               COUNT(DISTINCT cm.memberID) AS totalMembers,
               MAX(CASE WHEN mycm.userID IS NOT NULL THEN 1 ELSE 0 END) AS alreadyJoined
        FROM club c
        LEFT JOIN clubmembership cm ON cm.clubID = c.ClubID
        LEFT JOIN clubmembership mycm ON mycm.clubID = c.ClubID AND mycm.userID = ?
        $whereSql
        GROUP BY c.ClubID, c.ClubName, c.ClubDesc, c.AdvisorName, c.ClubCreated, c.ClubStatus
        ORDER BY c.ClubName ASC";

$allTypes = 's' . $types;
$allParams = array_merge([$userID], $params);
$clubStmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($clubStmt, $allTypes, ...$allParams);
mysqli_stmt_execute($clubStmt);
$clubs = mysqli_stmt_get_result($clubStmt);

function fetch_committee(mysqli $link, string $clubID): array
{
    $stmt = mysqli_prepare($link,
        "SELECT l.name, cc.commiteePosition
         FROM clubcommitee cc
         JOIN login l ON l.UserID = cc.userID
         WHERE cc.clubID = ?
         ORDER BY FIELD(cc.commiteePosition, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Committee Member'), l.name ASC"
    );
    mysqli_stmt_bind_param($stmt, 's', $clubID);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function fetch_events(mysqli $link, string $clubID, string $operator): array
{
    $today = date('Y-m-d');
    $stmt = mysqli_prepare($link,
        "SELECT eventTitle, eventDate, eventVenue, eventStatus
         FROM event
         WHERE ClubID = ? AND eventDate $operator ?
         ORDER BY eventDate " . ($operator === '>=' ? 'ASC' : 'DESC') . "
         LIMIT 5"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $clubID, $today);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

$pageTitle  = 'Club Directory';
$activePage = 'club_directory';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>
<div class="app">
    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">
        <h2>Club Directory</h2>

        <form method="get" action="StudClubDirectory.php">
            <div class="search-bar">
                <input type="text" name="search" placeholder="Search club name, description or advisor"
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="StudClubDirectory.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($clubs && mysqli_num_rows($clubs) > 0): ?>
            <?php while ($club = mysqli_fetch_assoc($clubs)): ?>
                <?php
                    $committee = fetch_committee($link, $club['ClubID']);
                    $upcoming  = fetch_events($link, $club['ClubID'], '>=');
                    $past      = fetch_events($link, $club['ClubID'], '<');
                ?>
                <div class="card club-directory-card">
                    <div class="card-header-row">
                        <div>
                            <h3><?= htmlspecialchars($club['ClubName']) ?></h3>
                            <p class="muted-text"><?= htmlspecialchars($club['ClubDesc'] ?: 'No description available.') ?></p>
                        </div>
                        <div class="table-actions">
                            <span class="badge badge-active">Active</span>
                            <?php if ((int)$club['alreadyJoined'] === 1): ?>
                                <span class="badge badge-pending">Joined</span>
                            <?php else: ?>
                                <a href="StudJoinClub.php" class="btn btn-primary btn-sm">Join club</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div>
                            <strong>Advisor</strong><br>
                            <?= htmlspecialchars($club['AdvisorName']) ?>
                        </div>
                        <div>
                            <strong>Total Members</strong><br>
                            <?= (int)$club['totalMembers'] ?> student(s)
                        </div>
                        <div>
                            <strong>Created</strong><br>
                            <?= htmlspecialchars($club['ClubCreated']) ?>
                        </div>
                    </div>

                    <div class="detail-grid detail-grid-three">
                        <div>
                            <strong>Committee Members</strong>
                            <?php if ($committee): ?>
                                <ul class="compact-list">
                                    <?php foreach ($committee as $cm): ?>
                                        <li><?= htmlspecialchars($cm['commiteePosition']) ?> — <?= htmlspecialchars($cm['name']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="muted-text">No committee assigned yet.</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong>Upcoming Events</strong>
                            <?php if ($upcoming): ?>
                                <ul class="compact-list">
                                    <?php foreach ($upcoming as $ev): ?>
                                        <li><?= htmlspecialchars($ev['eventTitle']) ?> — <?= htmlspecialchars($ev['eventDate']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="muted-text">No upcoming events.</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong>Past Events</strong>
                            <?php if ($past): ?>
                                <ul class="compact-list">
                                    <?php foreach ($past as $ev): ?>
                                        <li><?= htmlspecialchars($ev['eventTitle']) ?> — <?= htmlspecialchars($ev['eventDate']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="muted-text">No past events.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card">
                <p style="text-align:center; color:#999;">No active clubs found.</p>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
