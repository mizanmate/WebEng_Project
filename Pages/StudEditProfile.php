<?php
// ── Auth ──────────────────────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

// ── DB ────────────────────────────────────────────────────────────────────
require_once 'DB_connection.php';
$userID = $_SESSION['UserID'];

// ── Handle form submission ─────────────────────────────────────────────────
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']         ?? '');
    $programme = trim($_POST['programme']    ?? '');
    $year      = trim($_POST['year']         ?? '');
    $email     = trim($_POST['email']        ?? '');
    $phone     = trim($_POST['phone']        ?? '');
    $newPass   = trim($_POST['new_password'] ?? '');

    if (!$name) {
        $error = 'Name cannot be empty.';
    } else {
        // Handle Studphoto upload
        $picFilename = null;
        if (isset($_FILES['Studphoto']) && $_FILES['Studphoto']['error'] === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($_FILES['Studphoto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed)) {
                $picFilename = 'user_' . $userID . '_' . time() . '.' . $ext;
                $uploadDir   = '../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (!move_uploaded_file($_FILES['Studphoto']['tmp_name'], $uploadDir . $picFilename)) {
                    $picFilename = null;
                    $error = 'Image upload failed.';
                }
            } else {
                $error = 'Only JPG, PNG, GIF, WEBP files are allowed.';
            }
        }

        if (!$error) {
            // Update login name (and optionally password)
            if ($newPass !== '') {
                $hashed   = password_hash($newPass, PASSWORD_DEFAULT);
                $updLogin = mysqli_prepare($link,
                    'UPDATE login SET name = ?, password = ? WHERE UserID = ?'
                );
                mysqli_stmt_bind_param($updLogin, 'sss', $name, $hashed, $userID);
            } else {
                $updLogin = mysqli_prepare($link,
                    'UPDATE login SET name = ? WHERE UserID = ?'
                );
                mysqli_stmt_bind_param($updLogin, 'ss', $name, $userID);
            }
            mysqli_stmt_execute($updLogin);

            // Update student record (with or without new photo)
            if ($picFilename) {
                $updStud = mysqli_prepare($link,
                    'UPDATE student SET Programme=?, StudYear=?, Email=?, Phone=?, Studphoto=?
                     WHERE UserID=?'
                );
                mysqli_stmt_bind_param($updStud, 'ssssss',
                    $programme, $year, $email, $phone, $picFilename, $userID
                );
            } else {
                $updStud = mysqli_prepare($link,
                    'UPDATE student SET Programme=?, StudYear=?, Email=?, Phone=? WHERE UserID=?'
                );
                mysqli_stmt_bind_param($updStud, 'sssss',
                    $programme, $year, $email, $phone, $userID
                );
            }

            if (mysqli_stmt_execute($updStud)) {
                $_SESSION['name'] = $name;
                $success = 'Profile updated successfully.';
            } else {
                $error = 'Update failed. Please try again.';
            }
        }
    }
}

// Refresh user record after possible update
$stmt = mysqli_prepare($link,
    'SELECT l.name, s.StudentID, s.Programme, s.StudYear,
            s.Email, s.Phone, s.Studphoto
     FROM student s
     JOIN login   l ON l.UserID = s.UserID
     WHERE  s.UserID = ?'
);
mysqli_stmt_bind_param($stmt, 's', $userID);
mysqli_stmt_execute($stmt);
$user        = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$sidebarUser = $user;

// ── Page meta ─────────────────────────────────────────────────────────────
$pageTitle  = 'Edit Profile';
$activePage = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'includes/head.php'; ?></head>
<body>

<div class="app">

    <?php include 'includes/sidebar_student.php'; ?>

    <main class="main-content">

        <h2>Edit Profile</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form action="StudEditProfile.php" method="post" enctype="multipart/form-data">

                <!-- Studphoto upload with current picture preview -->
                <div class="form-group">
                    <label>Profile Picture</label>
                    <div class="avatar-preview">
                        <?php if (!empty($user['Studphoto'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($user['Studphoto']) ?>"
                                 alt="Current profile picture">
                        <?php else: ?>
                            <div class="avatar-empty">&#128100;</div>
                        <?php endif; ?>
                        <input type="file" name="Studphoto" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label>Student ID (read-only)</label>
                    <input type="text" value="<?= htmlspecialchars($user['StudentID'] ?? '') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="programme">Programme</label>
                    <input type="text"
                           id="programme"
                           name="programme"
                           value="<?= htmlspecialchars($user['Programme'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="year">Year of Study</label>
                    <input type="text"
                           id="year"
                           name="year"
                           value="<?= htmlspecialchars($user['StudYear'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="<?= htmlspecialchars($user['Email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text"
                           id="phone"
                           name="phone"
                           value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="new_password">
                        New Password
                        <small style="color:#999; font-weight:400;">(leave blank to keep current)</small>
                    </label>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Enter new password">
                </div>

                <div class="btn-group">
                    <a href="viewProfile.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>

            </form>
        </div>

    </main>
</div>

</body>
</html>
