FIXED VERSION - Module 4 Visible in Sidebar

This version keeps the existing project structure and Module 4 interface-only pages.

Main fix:
- Added Module 4 navigation links into the existing sidebar files so the pages can appear from the system menu.

Admin login will show:
- Participation Report -> adminParticipationReport.php

Student / Committee login will show:
- Attendance Dashboard -> committeeAttendanceDashboard.php
- Take Attendance -> committeeTakeAttendance.php
- My Attendance & Points -> StudAttendancePoints.php

Student login will show:
- My Attendance & Points -> StudAttendancePoints.php

Important testing notes:
1. Run createDB_mysqli.php first if the database is not created yet.
2. Login as admin to see the admin Module 4 report.
3. Login as a student who is assigned in ClubCommitee to see the committee attendance pages.
4. If you login as a normal student, committee attendance pages will not show because the user is not a committee member.
5. If tables show empty data, create clubs, assign committee, create events, register students, then test attendance.
