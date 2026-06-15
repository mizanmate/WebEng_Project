Module 4 QR Attendance Updated
==============================

This version keeps the existing project structure and updates only Module 4-related pages.

Updated files:
1. Pages/committeeAttendanceDashboard.php
   - More detailed committee attendance dashboard.
   - Shows total club events, registered participants, attendance marked, attendance rate, present, late, absent, volunteer/helper and total points.
   - Shows event attendance overview, recent attendance records and top participants.

2. Pages/committeeTakeAttendance.php
   - Adds Event QR Code display for the selected event.
   - Adds Create / Update Attendance Record form.
   - Committee can enter Student ID / Username / QR result.
   - Committee chooses whether the student attended: Yes or No.
   - If attended, committee chooses On time or Late.
   - Committee chooses Volunteer/helper: Yes or No.
   - System calculates points automatically:
        Present on time = +10
        Late arrival = +5
        Absent without notice = -10
        Volunteer/helper = additional +5
   - System creates or updates Attendance record.
   - System updates PointLog and recalculates Student.totalPoints.
   - Registered participant table also supports bulk attendance saving.

3. Pages/StudAttendancePoints.php
   - Updated to display combined statuses such as Present + Volunteer and Late + Volunteer correctly.

Important note:
- The QR image uses an online QR image generator, so it needs internet connection to load the QR image.
- If the QR image does not load offline, the QR text code still appears under the image and can be used for demonstration.
- This update does not alter the database structure or other modules.
