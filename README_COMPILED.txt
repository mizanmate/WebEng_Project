FK Student Club & Event Management System - Compiled Project
============================================================

This compiled folder contains the original uploaded project files plus the Module 4 interface-only PHP pages.
No existing module code was edited. Files with names such as adminDash(1).php were renamed to adminDash.php so the current internal links match correctly.

Folder structure:
- index.php
- createDB_mysqli.php
- README.md
- CSS/style.css
- img/Logo_UMPSA.png
- Pages/*.php
- Pages/includes/head.php
- Pages/includes/sidebar_admin.php
- Pages/includes/sidebar_student.php
- uploads/  (empty folder for uploaded profile photos)
- docs/     (project instruction and proposal document)

Module 4 interface-only pages added:
- Pages/committeeAttendanceDashboard.php
- Pages/committeeTakeAttendance.php
- Pages/StudAttendancePoints.php
- Pages/adminParticipationReport.php
- Pages/adminStudentAttendanceDetail.php

Basic setup:
1. Put this folder in your XAMPP htdocs folder.
2. Start Apache and MySQL.
3. Run createDB_mysqli.php once in the browser.
4. Open index.php or Pages/login.php.

Default accounts are seeded by createDB_mysqli.php if no accounts exist:
- Admin: ADMIN001 / admin123
- Student: CA23001 / student123

Optional navigation:
The Module 4 pages are included as separate pages. To show them in the sidebar, add links manually in Pages/includes/sidebar_admin.php and Pages/includes/sidebar_student.php. This compiled version keeps sidebars unchanged to avoid affecting other modules.
