Attendance detection fix
========================

Fixed issue:
- When committee manually creates attendance using Student ID / username, the result did not appear in the Registered Participants table if that student was not already registered for the selected event.

New behavior:
- If the student ID/username exists but is not registered for the event, the system automatically creates a Registration record with status 'Registered'.
- Then it creates/updates the Attendance record.
- The student will appear in the Registered Participants table after submission.
- Points are still calculated from attendance status and volunteer selection.

Files changed:
- Pages/committeeTakeAttendance.php only.

Notes:
- The Student ID / Username must already exist in the Student/Login table.
- The selected event must belong to the committee member's club.
