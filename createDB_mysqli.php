<?php
// ─────────────────────────────────────────────────────────
//  FK Club & Event Management — Database Setup
//  Run once at: http://localhost/WebEng_Project/createDB_mysqli.php
// ─────────────────────────────────────────────────────────

$link = mysqli_connect('localhost', 'root', '');
if (!$link) die('Connection failed: ' . mysqli_connect_error());

mysqli_query($link, 'CREATE DATABASE IF NOT EXISTS fkcluabeventandmanagement')
    or die(mysqli_error($link));
mysqli_select_db($link, 'web_project');
echo "Database ready.<br>";

// ── Tables (all 11 from ERD) ────────────────────────────────────────────────

$tables = [

    // 1. Login — parent table for all users
    "CREATE TABLE IF NOT EXISTS Login (
        UserID   VARCHAR(20)  NOT NULL PRIMARY KEY,
        name     VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role     VARCHAR(20)  NOT NULL
    )",

    // 2. Admin — admin profile (Module 1)
    "CREATE TABLE IF NOT EXISTS Admin (
        UserID     VARCHAR(20)  NOT NULL PRIMARY KEY,
        department VARCHAR(100) NOT NULL DEFAULT 'Administration',
        FOREIGN KEY (UserID) REFERENCES Login(UserID) ON DELETE CASCADE
    )",

    // 3. Student — student profile (Module 1)
    //    Studphoto stored as filename in uploads/ (VARCHAR) not raw BLOB,
    //    StudYear stored as text e.g. 'Year 2' (VARCHAR) not DATE,
    //    Phone stored as text to preserve leading zeros (VARCHAR) not INT.
    "CREATE TABLE IF NOT EXISTS Student (
        StudentID   VARCHAR(20)  NOT NULL PRIMARY KEY,
        UserID      VARCHAR(20),
        StudentName VARCHAR(100),
        Programme   VARCHAR(100) NOT NULL DEFAULT '',
        StudYear    VARCHAR(10)  NOT NULL DEFAULT '',
        Email       VARCHAR(100),
        Studphoto   VARCHAR(255),
        Phone       VARCHAR(20),
        status      VARCHAR(20),
        totalPoints INT          NOT NULL DEFAULT 0,
        FOREIGN KEY (UserID) REFERENCES Login(UserID) ON DELETE CASCADE
    )",

    // 4. Club — club master data (Module 1)
    "CREATE TABLE IF NOT EXISTS Club (
        ClubID      VARCHAR(20) NOT NULL PRIMARY KEY,
        ClubName    VARCHAR(50) NOT NULL,
        ClubDesc    TEXT,
        ClubCreated DATE        NOT NULL,
        AdvisorName VARCHAR(50) NOT NULL DEFAULT 'TBA',
        ClubStatus  VARCHAR(50) NOT NULL DEFAULT 'active'
    )",

    // 5. ClubMembership — student ↔ club relationship (Module 1)
    "CREATE TABLE IF NOT EXISTS ClubMembership (
        memberID         VARCHAR(20) NOT NULL PRIMARY KEY,
        userID           VARCHAR(20),
        clubID           VARCHAR(20),
        RegistrationDate DATE        NOT NULL,
        clubRole         VARCHAR(50) NOT NULL DEFAULT 'Member',
        UNIQUE KEY unique_membership (userID, clubID),
        FOREIGN KEY (userID) REFERENCES Login(UserID) ON DELETE CASCADE,
        FOREIGN KEY (clubID) REFERENCES Club(ClubID)  ON DELETE CASCADE
    )",

    // 6. ClubCommitee — admin assigns committee positions directly
    "CREATE TABLE IF NOT EXISTS ClubCommitee (
        committeeID        VARCHAR(20) NOT NULL PRIMARY KEY,
        userID             VARCHAR(20),
        clubID             VARCHAR(20),
        commiteePosition   VARCHAR(50) NOT NULL,
        CommiteeAssignDate DATE        NOT NULL,
        UNIQUE KEY unique_committee (userID, clubID),
        FOREIGN KEY (userID) REFERENCES Login(UserID) ON DELETE CASCADE,
        FOREIGN KEY (clubID) REFERENCES Club(ClubID)  ON DELETE CASCADE
    )",

    // 7. Event — club events (other module)
    "CREATE TABLE IF NOT EXISTS Event (
        eventID         VARCHAR(20)  NOT NULL PRIMARY KEY,
        ClubID          VARCHAR(20),
        eventTitle      VARCHAR(100) NOT NULL,
        eventDesc       TEXT         NOT NULL,
        eventDate       DATE         NOT NULL,
        eventTime       TIME         NOT NULL,
        eventVenue      VARCHAR(100) NOT NULL,
        maxParticipants INT          NOT NULL DEFAULT 0,
        eventStatus     VARCHAR(20)  NOT NULL DEFAULT 'upcoming',
        created_At      DATETIME     NOT NULL,
        FOREIGN KEY (ClubID) REFERENCES Club(ClubID) ON DELETE CASCADE
    )",

    // 8. Registration — event registration (other module)
    "CREATE TABLE IF NOT EXISTS Registration (
        registrationID     VARCHAR(20)  NOT NULL PRIMARY KEY,
        studentID          VARCHAR(20),
        eventID            VARCHAR(20),
        registrationStatus VARCHAR(20)  NOT NULL,
        notes              VARCHAR(200),
        registrationDate   DATETIME     NOT NULL,
        FOREIGN KEY (studentID) REFERENCES Student(StudentID) ON DELETE CASCADE,
        FOREIGN KEY (eventID)   REFERENCES Event(eventID)     ON DELETE CASCADE
    )",

    // 9. WaitingList — event waiting list (other module)
    "CREATE TABLE IF NOT EXISTS WaitingList (
        WaitingListID  VARCHAR(20) NOT NULL PRIMARY KEY,
        registrationID VARCHAR(20),
        position       INT         NOT NULL,
        addedDate      DATETIME    NOT NULL,
        FOREIGN KEY (registrationID) REFERENCES Registration(registrationID) ON DELETE CASCADE
    )",

    // 10. Attendance — event attendance (other module)
    "CREATE TABLE IF NOT EXISTS Attendance (
        attendanceID     VARCHAR(20) NOT NULL PRIMARY KEY,
        studentID        VARCHAR(20),
        eventID          VARCHAR(20),
        checkInTime      DATETIME    NOT NULL,
        AttendanceStatus VARCHAR(20) NOT NULL,
        FOREIGN KEY (studentID) REFERENCES Student(StudentID) ON DELETE CASCADE,
        FOREIGN KEY (eventID)   REFERENCES Event(eventID)     ON DELETE CASCADE
    )",

    // 11. PointLog — points awarded per attendance (other module)
    "CREATE TABLE IF NOT EXISTS PointLog (
        logID        VARCHAR(20) NOT NULL PRIMARY KEY,
        studentID    VARCHAR(20),
        attendanceID VARCHAR(20),
        pointsEarn   INT         NOT NULL DEFAULT 0,
        dateAward    DATETIME    NOT NULL,
        FOREIGN KEY (studentID)    REFERENCES Student(StudentID)   ON DELETE CASCADE,
        FOREIGN KEY (attendanceID) REFERENCES Attendance(attendanceID) ON DELETE CASCADE
    )",
];

foreach ($tables as $sql) {
    if (mysqli_query($link, $sql)) {
        echo "Table OK.<br>";
    } else {
        echo 'Error: ' . mysqli_error($link) . '<br>';
    }
}

// ── Seed admin account ──────────────────────────────────────────────────────
$check = mysqli_query($link, "SELECT UserID FROM Login WHERE role = 'admin' LIMIT 1");
if (mysqli_num_rows($check) === 0) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($link,
        "INSERT INTO Login (UserID, name, password, role)
         VALUES ('ADMIN001', 'Administrator', '$pass', 'admin')"
    );
    mysqli_query($link,
        "INSERT INTO Admin (UserID, department)
         VALUES ('ADMIN001', 'Administration')"
    );
    echo "Admin seeded — User ID: <strong>ADMIN001</strong>, password: <strong>admin123</strong><br>";
}

// ── Seed sample student account ─────────────────────────────────────────────
$checkStud = mysqli_query($link, "SELECT UserID FROM Login WHERE role = 'student' LIMIT 1");
if (mysqli_num_rows($checkStud) === 0) {
    $pass2 = password_hash('student123', PASSWORD_DEFAULT);
    mysqli_query($link,
        "INSERT INTO Login (UserID, name, password, role)
         VALUES ('CA23001', 'Sample Student', '$pass2', 'student')"
    );
    mysqli_query($link,
        "INSERT INTO Student (StudentID, UserID, StudentName, Programme, StudYear, Email, status, totalPoints)
         VALUES ('CA23001', 'CA23001', 'Sample Student', 'Computer Science', 'Year 1', 'student@umpsa.edu.my', 'active', 0)"
    );
    echo "Sample student seeded — User ID: <strong>CA23001</strong>, password: <strong>student123</strong><br>";
}

// ── Seed sample clubs ───────────────────────────────────────────────────────
$check2 = mysqli_query($link, 'SELECT ClubID FROM Club LIMIT 1');
if (mysqli_num_rows($check2) === 0) {
    $today = date('Y-m-d');
    $clubs = [
        ['CLB001', 'Computer Science Club', 'A club for CS enthusiasts and coders.',       'TBA'],
        ['CLB002', 'Robotics Club',         'Explore robotics, IoT, and automation.',       'TBA'],
        ['CLB003', 'Debate Club',           'Sharpen your public speaking and reasoning.', 'TBA'],
    ];
    foreach ($clubs as [$id, $name, $desc, $advisor]) {
        $id     = mysqli_real_escape_string($link, $id);
        $name   = mysqli_real_escape_string($link, $name);
        $desc   = mysqli_real_escape_string($link, $desc);
        $advisor = mysqli_real_escape_string($link, $advisor);
        mysqli_query($link,
            "INSERT INTO Club (ClubID, ClubName, ClubDesc, ClubCreated, AdvisorName, ClubStatus)
             VALUES ('$id', '$name', '$desc', '$today', '$advisor', 'active')"
        );
    }
    echo 'Sample clubs seeded.<br>';
}

mysqli_close($link);
echo '<br><strong>Setup complete!</strong> ';
echo '<a href="Pages/login.php">Go to Login &rarr;</a>';
?>
