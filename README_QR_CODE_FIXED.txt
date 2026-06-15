QR CODE FIX FOR MODULE 4
========================

Problem fixed:
The previous QR code contained custom text only, so iPhone Camera may not open any page after scanning.

Fix applied:
1. committeeTakeAttendance.php now generates a real web URL inside the QR code.
2. New page added: Pages/attendanceQRCheckIn.php
3. When students scan the QR code, it opens the QR Attendance Check-In page.
4. Students can enter Student ID / username, choose arrival status and volunteer/helper status.
5. The system saves registration if needed, attendance and point log automatically.

Important for XAMPP/localhost:
If the QR code URL starts with localhost or 127.0.0.1, iPhone cannot open it because localhost means the iPhone itself.
Open the system using the laptop IP address instead, for example:
http://192.168.x.x/WebEng_Project/Pages/committeeTakeAttendance.php

Then the QR code will use the laptop IP address and your iPhone can open it while connected to the same Wi-Fi.
