Module 4 Interface-Only Add-On
================================

This version follows only the Module 4 presentation model interfaces from the proposal:

1. Attendance dashboards (committee member view)
   File: Pages/committeeAttendanceDashboard.php

2. Take attendance page (committee member view)
   File: Pages/committeeTakeAttendance.php

3. My attendance & points (student view)
   File: Pages/StudAttendancePoints.php

4. Participation report (admin view)
   File: Pages/adminParticipationReport.php

5. View student attendance detail (admin view)
   File: Pages/adminStudentAttendanceDetail.php

IMPORTANT:
- No existing files are overwritten.
- No existing module is modified.
- No database structure is changed.
- Copy only the new PHP files into your existing Pages folder.
- Open the pages directly in the browser, or add sidebar links manually only if your group agrees.

Optional sidebar links only if you want menu access:

Admin sidebar:
<a href="adminParticipationReport.php" class="<?= $active === 'attendance_report' ? 'active' : '' ?>">Participation Report</a>

Student sidebar:
<a href="StudAttendancePoints.php" class="<?= $active === 'attendance_points' ? 'active' : '' ?>">My Attendance & Points</a>

Committee section:
<a href="committeeAttendanceDashboard.php" class="<?= $active === 'committee_attendance' ? 'active' : '' ?>">Attendance Dashboard</a>

Testing flow:
1. Login as committee member.
2. Open committeeAttendanceDashboard.php.
3. Select an event in committeeTakeAttendance.php.
4. Mark registered students as Present, Late, Absent or Volunteer.
5. Login as student and open StudAttendancePoints.php.
6. Login as admin and open adminParticipationReport.php.
