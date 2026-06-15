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
            <img src="<?php echo $picSrc; ?>" alt="Profile picture" class="user-avatar">
        <?php else: ?>
            <div class="user-avatar-default">&#128100;</div>
        <?php endif; ?>

        <p class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></p>
        <p class="user-role"><?php echo $isCommitteeUser ? 'Student / Committee' : 'Student'; ?></p>
    </div>

    <nav class="sidebar-nav">
        <a href="studDash.php" class="<?php echo ($active === 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
        <a href="StudClubDirectory.php" class="<?php echo ($active === 'club_directory') ? 'active' : ''; ?>">Club Directory</a>
        <a href="StudManageClub.php" class="<?php echo ($active === 'manage_club') ? 'active' : ''; ?>">Manage Club</a>
        <a href="StudJoinClub.php" class="<?php echo ($active === 'join_club') ? 'active' : ''; ?>">Join club</a>

        <?php if ($isCommitteeUser): ?>
            <a href="committeeDash.php" class="<?php echo ($active === 'committee_dashboard') ? 'active' : ''; ?>">Committee Dashboard</a>
        <?php endif; ?>

        <a href="viewProfile.php" class="<?php echo ($active === 'profile') ? 'active' : ''; ?>">View Profile</a>
        <a href="StudBrowseEvent.php" class="<?php echo ($active === 'browse_event' || $active === 'events') ? 'active' : ''; ?>">Browse Events</a>
        <a href="StudMyEvents.php" class="<?php echo ($active === 'my_events' || $active === 'myevents') ? 'active' : ''; ?>">My Events</a>

        <?php if ($isCommitteeUser): ?>
            <span class="sidebar-section-label">Committee</span>
            <a href="committeeCreateEvent.php" class="<?php echo ($active === 'create_event' || $active === 'committee_create') ? 'active' : ''; ?>">Create Event</a>
            <a href="committeeManageEvent.php" class="<?php echo ($active === 'manage_event' || $active === 'committee') ? 'active' : ''; ?>">Manage Events</a>
            <a href="committeeAttendanceDashboard.php" class="<?php echo ($active === 'committee_attendance') ? 'active' : ''; ?>">Attendance Dashboard</a>
            <a href="committeeTakeAttendance.php" class="<?php echo ($active === 'take_attendance') ? 'active' : ''; ?>">Take Attendance</a>
        <?php endif; ?>

        <a href="StudAttendancePoints.php" class="<?php echo ($active === 'attendance_points') ? 'active' : ''; ?>">My Attendance &amp; Points</a>
    </nav>

    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-logout">Log Out</a>
    </div>

</aside>
