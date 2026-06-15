Module 4 evt001 QR Fix

What changed:
1. committeeCreateEvent.php now generates event IDs using evt001, evt002, evt003, ... instead of EV0001.
2. The QR code in committeeTakeAttendance.php already uses the selected event ID from the database, so every event will automatically get its own QR code.
3. attendanceQRCheckIn.php now checks eventID case-insensitively, so evt001 / EVT001 will not cause an event-not-found issue because of letter case.

How to use:
1. Copy/replace the files in this ZIP into your project.
2. Create a new event from the committee page. The event ID should become evt001 if no evt events exist, then evt002, evt003 and so on.
3. Go to Take Attendance, select the event and use the QR displayed there.
4. If you are testing using iPhone hotspot, open the system with your laptop hotspot IP, for example:
   http://172.20.10.4/WebEng_Project/Pages/committeeTakeAttendance.php

Important:
If you already created old events such as EV0001 or evt0001, those old IDs will remain in the database. New events will use evt001 and above.
