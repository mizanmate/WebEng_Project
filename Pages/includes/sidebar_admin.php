<?php
// ================================================================
//  includes/sidebar_admin.php
//  Admin sidebar — included on every admin-facing page.
//
//  Requires this variable to be set BEFORE including:
//      $activePage (string) – key of the current page, e.g. 'dashboard'
//
//  Active-page keys used in this sidebar:
//      'dashboard'     → adminDash.php
//      'register_user' → adminRegisterUser.php
//      'view_users'    → adminViewUser.php
//      'edit_user'     → adminEditUser.php
//
// ================================================================
//  TEAMMATES — HOW TO ADD YOUR ADMIN LINK
// ================================================================
//  If your module needs an admin page, add a nav link inside the
//  <nav class="sidebar-nav"> block below, e.g.:
//
//      <a href="adminYourPage.php"
//         class="<?= $active === 'your_key' ? 'active' : '' ? >">
//          Your Page
//      </a>
//
//  Then set $activePage = 'your_key'; in your admin PHP page.
// ================================================================

$active = $activePage ?? '';
?>
<aside class="sidebar">

    <!-- ── Logo + system name ── -->
    <div class="sidebar-header">
        <img src="../img/Logo_UMPSA.png" alt="UMPSA Logo" class="sidebar-logo">
        <span class="sidebar-sys-name">FK CEM System</span>
    </div>

    <!-- ── Logged-in admin info ── -->
    <div class="sidebar-user">
        <div class="user-avatar-default">&#128100;</div>
        <p class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></p>
        <p class="user-role">Administrator</p>
    </div>

    <!-- ── Module 1 navigation ── -->
    <nav class="sidebar-nav">
        <a href="adminDash.php"         class="<?= $active === 'dashboard'     ? 'active' : '' ?>">Dashboard</a>
        <a href="adminRegisterUser.php" class="<?= $active === 'register_user' ? 'active' : '' ?>">Register User</a>
        <a href="adminViewUser.php"     class="<?= $active === 'view_users'    ? 'active' : '' ?>">View Users</a>
        <a href="adminEditUser.php"     class="<?= $active === 'edit_user'     ? 'active' : '' ?>">Edit User</a>

<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 0601bc1c42d79e127dc0f1f0b317c1ad1963b0d2
        <span class="sidebar-section-label">Module 2</span>
        <a href="adminClubManagement.php" class="<?= $active === 'club_management' ? 'active' : '' ?>">Manage Clubs</a>
        <a href="adminCommitteeManagement.php" class="<?= $active === 'committee_management' ? 'active' : '' ?>">Manage Committees</a>

<<<<<<< HEAD
=======
>>>>>>> 2f56a39d48beca8d7135299cdf5b0c25cb0e7999
=======
>>>>>>> 0601bc1c42d79e127dc0f1f0b317c1ad1963b0d2
        <!-- Teammates: add your admin links below this line -->
    </nav>

    <!-- ── Logout ── -->
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-logout">Log Out</a>
    </div>

</aside>
