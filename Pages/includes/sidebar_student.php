<?php
//      Requires these variables to be set BEFORE including:
//      $activePage  (string) – key of the current page, e.g. 'dashboard'
//      $sidebarUser (array)  – must contain 'Studphoto' key
//                              (fetch from Student table WHERE UserID = session UserID)
//
//
// ================================================================
//  TEAMMATES — HOW TO ADD YOUR MODULE LINK
// ================================================================
//  1. Find the "Other Modules" <div> near the bottom of this file.
//  2. Replace a placeholder <a> tag with your real link, e.g.:
//       BEFORE:
//         <a href="#" class="sidebar-placeholder">Module 2 (coming soon)</a>
//
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
        <p class="user-role">Student</p>
    </div>

    <!-- ── Module 1 navigation ── -->
    <nav class="sidebar-nav">
        <a href="studDash.php"       class="<?= $active === 'dashboard'   ? 'active' : '' ?>">Dashboard</a>
        <a href="StudManageClub.php" class="<?= $active === 'manage_club' ? 'active' : '' ?>">Manage Club</a>
        <a href="StudJoinClub.php"   class="<?= $active === 'join_club'   ? 'active' : '' ?>">Join Club</a>
        <a href="viewProfile.php"    class="<?= $active === 'profile'     ? 'active' : '' ?>">View Profile</a>
    </nav>

    <!-- ── Other Modules (teammates: add your links here) ── -->
    <div class="sidebar-modules">
        <span class="sidebar-section-label">Other Modules</span>

        <!-- Replace href="#" and class with your real page and active key -->
        <a href="#" class="sidebar-placeholder">Module 2 (coming soon)</a>
        <a href="#" class="sidebar-placeholder">Module 3 (coming soon)</a>
    </div>

    <!-- ── Logout ── -->
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-logout">Log Out</a>
    </div>

</aside>
