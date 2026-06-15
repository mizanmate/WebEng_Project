Module 4 QR text removed update

Changed file:
- Pages/committeeTakeAttendance.php

Changes:
1. Removed the visible localhost/iPhone warning text under the QR code.
2. Removed the visible QR generator warning text.
3. Kept the QR link display only.
4. If the attendance page is opened using localhost, the QR URL will use 172.20.10.4 automatically for local hotspot demo.

If your hotspot IP changes, edit Pages/committeeTakeAttendance.php and replace 172.20.10.4 with your new IPv4 address.
