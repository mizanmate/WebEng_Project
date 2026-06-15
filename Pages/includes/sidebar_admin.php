<?php
// ================================================================
//  includes/sidebar_admin.php
//  Admin sidebar — included on every admin-facing page.
//
//  Required before include:
//      $activePage (string) current page key
// ================================================================

$active = $activePage ?? '';
?>
<aside class="sidebar">

    <div class="sidebar-header">
        <img src="../img/Logo_UMPSA.png" alt="UMPSA Logo" class="sidebar-logo">
        <span class="sidebar-sys-name">FK CEM System</span>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar-default">&#128100;</div>
        <p class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?></p>
        <p class="user-role">Administrator</p>
    </div>

    <nav class="sidebar-nav">
        <a href="adminDash.php" class="<?php echo ($active === 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
        <a href="adminRegisterUser.php" class="<?php echo ($active === 'register_user') ? 'active' : ''; ?>">Register User</a>
        <a href="adminViewUser.php" class="<?php echo ($active === 'view_users') ? 'active' : ''; ?>">View Users</a>

        <span class="sidebar-section-label">Module 2</span>
        <a href="adminClubManagement.php" class="<?php echo ($active === 'club_management') ? 'active' : ''; ?>">Manage Clubs</a>
        <a href="adminCommitteeManagement.php" class="<?php echo ($active === 'committee_management') ? 'active' : ''; ?>">Manage Committees</a>

        <span class="sidebar-section-label">Module 3</span>
        <a href="adminEventList.php" class="<?php echo ($active === 'events' || $active === 'event_management') ? 'active' : ''; ?>">Event Management</a>
        <a href="adminEventAnalytics.php" class="<?php echo ($active === 'analytics') ? 'active' : ''; ?>">Event Analytics</a>

        <a href="adminParticipationReport.php" class="<?php echo ($active === 'attendance_report' || $active === 'participation_report') ? 'active' : ''; ?>">Participation Report</a>
    </nav>

    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-logout">Log Out</a>
    </div>

</aside>
