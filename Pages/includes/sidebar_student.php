<?php
// ================================================================
//  includes/sidebar_student.php
//  Student sidebar — included on every student-facing page.
//
//  Required before include:
//      $activePage  (string) current page key
//      $sidebarUser (array)  must contain Studphoto key
// ================================================================

$active = $activePage ?? '';
$hasPic = !empty($sidebarUser['Studphoto']);
$picSrc = $hasPic ? '../uploads/' . htmlspecialchars($sidebarUser['Studphoto']) : '';

// Committee is not a separate login role. It is an extra privilege for students
// who have a record in ClubCommitee.
$isCommitteeUser = false;
if (isset($link, $_SESSION['UserID'])) {
    $committeeStmt = mysqli_prepare($link, 'SELECT committeeID FROM clubcommitee WHERE userID = ? LIMIT 1');
    if ($committeeStmt) {
        mysqli_stmt_bind_param($committeeStmt, 's', $_SESSION['UserID']);
        mysqli_stmt_execute($committeeStmt);
        mysqli_stmt_store_result($committeeStmt);
        $isCommitteeUser = mysqli_stmt_num_rows($committeeStmt) > 0;
        mysqli_stmt_close($committeeStmt);
    }
}
?>
<aside class="sidebar">

    <div class="sidebar-header">
        <img src="../img/Logo_UMPSA.png" alt="UMPSA Logo" class="sidebar-logo">
        <span class="sidebar-sys-name">FK CEM System</span>
    </div>

    <div class="sidebar-user">
        <?php if ($hasPic): ?>
            <img src="<?= $picSrc ?>" alt="Profile picture" class="user-avatar">
        <?php else: ?>
            <div class="user-avatar-default">&#128100;</div>
        <?php endif; ?>
        <p class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Student') ?></p>
        <p class="user-role"><?= $isCommitteeUser ? 'Student / Committee' : 'Student' ?></p>
    </div>

    <!-- ── Navigation ── -->
    <nav class="sidebar-nav">
        <a href="studDash.php"    class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="viewProfile.php" class="<?= $active === 'profile'   ? 'active' : '' ?>">View Profile</a>

        <span class="sidebar-section-label">Module 2</span>
        <a href="StudClubDirectory.php" class="<?= $active === 'club_directory' ? 'active' : '' ?>">Club Directory</a>
        <a href="StudManageClub.php"    class="<?= $active === 'manage_club'    ? 'active' : '' ?>">Manage Club</a>
        <a href="StudJoinClub.php"      class="<?= $active === 'join_club'      ? 'active' : '' ?>">Join Club</a>
        <?php if ($isCommitteeUser): ?>
            <a href="committeeDash.php" class="<?= $active === 'committee_dashboard' ? 'active' : '' ?>">Committee Dashboard</a>
        <?php endif; ?>

        <span class="sidebar-section-label">Module 3</span>
        <a href="StudBrowseEvent.php" class="<?= $active === 'browse_event' ? 'active' : '' ?>">Browse Events</a>
        <a href="StudMyEvents.php"    class="<?= $active === 'my_events'    ? 'active' : '' ?>">My Events</a>
        <?php if ($isCommitteeUser): ?>
            <a href="committeeCreateEvent.php" class="<?= $active === 'create_event' ? 'active' : '' ?>">Create Event</a>
            <a href="committeeManageEvent.php" class="<?= $active === 'manage_event' ? 'active' : '' ?>">Manage Events</a>
        <?php endif; ?>

        <span class="sidebar-section-label">Module 4</span>
        <?php if ($isCommitteeUser): ?>
            <a href="committeeAttendanceDashboard.php" class="<?= $active === 'committee_attendance' ? 'active' : '' ?>">Attendance Dashboard</a>
            <a href="committeeTakeAttendance.php"      class="<?= in_array($active, ['take_attendance', 'committee_attendance'], true) ? 'active' : '' ?>">Take Attendance</a>
        <?php endif; ?>
        <a href="StudAttendancePoints.php" class="<?= $active === 'attendance_points' ? 'active' : '' ?>">My Attendance &amp; Points</a>
    </nav>

    <!-- ── Logout ── -->
    <div class="sidebar-footer">
        <a href="login.php?logout=1" class="sidebar-logout">Log Out</a>
    </div>

</aside>
