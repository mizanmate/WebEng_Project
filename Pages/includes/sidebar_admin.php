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
        <p class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Administrator') ?></p>
        <p class="user-role">Administrator</p>
    </div>

    <!-- ── Navigation ── -->
    <nav class="sidebar-nav">
        <a href="adminDash.php"         class="<?= $active === 'dashboard'     ? 'active' : '' ?>">Dashboard</a>
        <a href="adminRegisterUser.php" class="<?= $active === 'register_user' ? 'active' : '' ?>">Register User</a>
        <a href="adminViewUser.php"     class="<?= $active === 'view_users'    ? 'active' : '' ?>">View Users</a>

        <span class="sidebar-section-label">Module 2</span>
        <a href="adminClubManagement.php"      class="<?= $active === 'club_management'       ? 'active' : '' ?>">Manage Clubs</a>
        <a href="adminCommitteeManagement.php" class="<?= $active === 'committee_management'  ? 'active' : '' ?>">Manage Committees</a>

        <span class="sidebar-section-label">Module 3</span>
        <a href="adminEventList.php"      class="<?= in_array($active, ['events', 'event_management'], true) ? 'active' : '' ?>">Event Management</a>
        <a href="adminEventAnalytics.php" class="<?= $active === 'analytics'         ? 'active' : '' ?>">Event Analytics</a>

        <span class="sidebar-section-label">Module 4</span>
        <a href="adminParticipationReport.php" class="<?= in_array($active, ['attendance_report', 'participation_report'], true) ? 'active' : '' ?>">Participation Report</a>
    </nav>

    <!-- ── Logout ── -->
    <div class="sidebar-footer">
        <a href="login.php?logout=1" class="sidebar-logout">Log Out</a>
    </div>

</aside>
