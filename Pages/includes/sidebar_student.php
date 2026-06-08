<?php
//      Requires these variables to be set BEFORE including:
//      $activePage  (string) – key of the current page, e.g. 'dashboard'
//      $sidebarUser (array)  – must contain 'Studphoto' key
//                              (fetch from student table WHERE UserID = session UserID)
//
//
// ================================================================
//  TEAMMATES — HOW TO ADD YOUR MODULE LINK
// ================================================================
//  1. Find the "Other Modules" <div> near the bottom of this file.
//  2. Replace a placeholder <a> tag with your real link, e.g.:
//       BEFORE:
// //
//       AFTER:
//         <a href="YourPage.php"
//            class="<?= $active === 'your_key' ? 'active' : '' ? >">
//             Your Page Name
//         </a>
//
//  3. In your own PHP page, set $activePage = 'your_key';
//     before the include line.
//
//  4. Keep the sidebar-placeholder class or remove it — up to you.
// ================================================================

$active = $activePage ?? '';
$hasPic = !empty($sidebarUser['Studphoto']);
$picSrc = $hasPic ? '../uploads/' . htmlspecialchars($sidebarUser['Studphoto']) : '';

// Committee is not a separate login role. It is an extra privilege for students
// who have a record in ClubCommitee. This keeps normal student access intact.
$isCommitteeUser = false;
if (isset($link, $_SESSION['UserID'])) {
    $committeeStmt = mysqli_prepare($link, 'SELECT committeeID FROM clubcommitee WHERE userID = ? LIMIT 1');
    if ($committeeStmt) {
        mysqli_stmt_bind_param($committeeStmt, 's', $_SESSION['UserID']);
        mysqli_stmt_execute($committeeStmt);
        mysqli_stmt_store_result($committeeStmt);
        $isCommitteeUser = mysqli_stmt_num_rows($committeeStmt) > 0;
    }
}
?>
<aside class="sidebar">

    <!-- ── Logo + system name ── -->
    <div class="sidebar-header">
        <img src="../img/Logo_UMPSA.png" alt="UMPSA Logo" class="sidebar-logo">
        <span class="sidebar-sys-name">FK CEM System</span>
    </div>

    <!-- ── Logged-in student info ── -->
    <div class="sidebar-user">
        <?php if ($hasPic): ?>
            <img src="<?= $picSrc ?>" alt="Profile picture" class="user-avatar">
        <?php else: ?>
            <div class="user-avatar-default">&#128100;</div>
        <?php endif; ?>
        <p class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></p>
        <p class="user-role"><?= $isCommitteeUser ? 'Student / Committee' : 'Student' ?></p>
    </div>

    <!-- ── Module 1 navigation ── -->
    <nav class="sidebar-nav">
        <a href="studDash.php"       class="<?= $active === 'dashboard'   ? 'active' : '' ?>">Dashboard</a>
        <a href="StudClubDirectory.php" class="<?= $active === 'club_directory' ? 'active' : '' ?>">Club Directory</a>
        <a href="StudManageClub.php" class="<?= $active === 'manage_club' ? 'active' : '' ?>">Manage Club</a>
        <a href="StudJoinClub.php"   class="<?= $active === 'join_club'   ? 'active' : '' ?>">Join club</a>
        <?php if ($isCommitteeUser): ?>
            <a href="committeeDash.php" class="<?= $active === 'committee_dashboard' ? 'active' : '' ?>">Committee Dashboard</a>
        <?php endif; ?>
        <a href="viewProfile.php"    class="<?= $active === 'profile'     ? 'active' : '' ?>">View Profile</a>
        <a href="StudBrowseEvent.php" class="<?= $active === 'browse_event' ? 'active' : '' ?>">Browse Events</a>
        <a href="StudMyEvents.php" class="<?= $active === 'my_events' ? 'active' : '' ?>">My Events</a>

        <?php if ($isCommitteeUser): ?>
            <a href="committeeCreateEvent.php" class="<?= $active === 'create_event' ? 'active' : '' ?>">Create Event</a>
            <a href="committeeManageEvent.php"class="<?= $active === 'manage_event' ? 'active' : '' ?>">Manage Events</a>
        <?php endif; ?>
    </nav>

    <!-- ── Logout ── -->
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-logout">Log Out</a>
    </div>

</aside>