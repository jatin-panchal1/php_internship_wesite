<?php
session_start();
require_once 'connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$role  = $_SESSION['user_role'] ?? 'member';

$message = '';
$message_type = '';

// --- Handle profile picture upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2 MB

        if (!in_array($file['type'], $allowed_types)) {
            $message = "Only JPG, PNG, and GIF images are allowed.";
            $message_type = 'danger';
        } elseif ($file['size'] > $max_size) {
            $message = "File size must be under 2 MB.";
            $message_type = 'danger';
        } else {
            // Ensure uploads directory exists
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old picture if exists
                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();
                $old = $res->fetch_assoc();
                if ($old && $old['profile_picture'] && file_exists($upload_dir . $old['profile_picture'])) {
                    unlink($upload_dir . $old['profile_picture']);
                }
                $stmt->close();

                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE email = ?");
                $stmt->bind_param("ss", $new_filename, $email);
                if ($stmt->execute()) {
                    $message = "Profile picture updated successfully.";
                    $message_type = 'success';
                } else {
                    $message = "Database update failed.";
                    $message_type = 'danger';
                }
                $stmt->close();
            } else {
                $message = "Failed to move uploaded file.";
                $message_type = 'danger';
            }
        }
    } else {
        $message = "Please select a file to upload.";
        $message_type = 'danger';
    }

    $_SESSION['profile_message'] = $message;
    $_SESSION['profile_message_type'] = $message_type;
    header("Location: home.php");
    exit();
}

// --- Handle password change ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current     = $_POST['current_password'] ?? '';
    $new         = $_POST['new_password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $message = "All password fields are required.";
        $message_type = 'danger';
    } elseif ($new !== $confirm) {
        $message = "New password and confirmation do not match.";
        $message_type = 'danger';
    } elseif (strlen($new) < 6) {
        $message = "New password must be at least 6 characters.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($current, $user['password_hash'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $email);
            if ($stmt->execute()) {
                $message = "Password changed successfully.";
                $message_type = 'success';
            } else {
                $message = "Error changing password: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Current password is incorrect.";
            $message_type = 'danger';
        }
    }

    $_SESSION['profile_message'] = $message;
    $_SESSION['profile_message_type'] = $message_type;
    header("Location: home.php");
    exit();
}

// --- Display session messages ---
if (isset($_SESSION['profile_message'])) {
    $message = $_SESSION['profile_message'];
    $message_type = $_SESSION['profile_message_type'];
    unset($_SESSION['profile_message'], $_SESSION['profile_message_type']);
}

// Fetch current user data (including profile_picture)
$user = null;
$stmt = $conn->prepare("SELECT email, name, phone_number, age, role, created_at, profile_picture FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Profile picture path
$profile_pic_path = '';
if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/uploads/' . $user['profile_picture'])) {
    $profile_pic_path = 'uploads/' . $user['profile_picture'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f7f8fa;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1d1d1f;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e9eaee;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .navbar-brand {
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .main-container {
            flex: 1;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .card {
            border: 1px solid #e9eaee;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
            background: #ffffff;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e9eaee;
            font-weight: 600;
            padding: 1.2rem 1.5rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e9eaee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #86868b;
            margin: 0 auto 1rem;
            overflow: hidden;
            object-fit: cover;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-primary {
            background: #1d1d1f;
            border-color: #1d1d1f;
        }
        .btn-primary:hover {
            background: #2c2c2e;
            border-color: #2c2c2e;
        }
        .btn-outline-secondary {
            color: #1d1d1f;
            border-color: #dcdde3;
        }
        .btn-outline-secondary:hover {
            background: #f1f1f3;
        }
        .footer {
            background: #ffffff;
            border-top: 1px solid #e9eaee;
            padding: 1.2rem 0;
            color: #86868b;
            font-size: 0.85rem;
            text-align: center;
        }
        .badge-role {
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 30px;
        }
        .badge-superadmin { background: #c0392b; color: #fff; }
        .badge-admin      { background: #2980b9; color: #fff; }
        .badge-member     { background: #7f8c8d; color: #fff; }
        .form-control:focus {
            border-color: #a4a7b3;
            box-shadow: 0 0 0 3px rgba(120, 125, 140, 0.1);
        }
        .modal-content {
            border-radius: 14px;
            border: 1px solid #e9eaee;
        }
        .form-control[disabled], .form-control:disabled {
            background: #f1f1f3;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-user-circle me-2" style="color: #1d1d1f;"></i>My Profile
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 text-secondary" style="font-size:0.9rem;">
                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($email); ?>
                <span class="badge-role 
                    <?php echo $role === 'superadmin' ? 'badge-superadmin' : ($role === 'admin' ? 'badge-admin' : 'badge-member'); ?>
                    ms-1">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container main-container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-id-card me-2"></i>Your Information</span>
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#passwordModal">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="profile-avatar mx-auto">
                            <?php if (!empty($profile_pic_path)): ?>
                                <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <!-- Upload form -->
                        <form method="post" enctype="multipart/form-data" class="mt-2">
                            <input type="hidden" name="action" value="upload_picture">
                            <div class="input-group input-group-sm">
                                <input type="file" class="form-control" name="profile_picture" accept="image/*" required>
                                <button class="btn btn-primary btn-sm" type="submit">Upload</button>
                            </div>
                            <small class="text-muted">Max 2 MB, allowed: JPG, PNG, GIF</small>
                        </form>
                    </div>
                    <hr>

                    <!-- Profile Information – read‑only -->
                    <form>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone_number']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" value="<?php echo (int) $user['age']; ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="created_at" class="form-label">Member Since</label>
                            <input type="text" class="form-control" id="created_at" value="<?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?>" disabled>
                        </div>
                    </form>

                    <div class="alert alert-secondary mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i> Profile information is read‑only. To update your details, contact an administrator.
                    </div>
                </div>
            </div>

            <!-- Dynamic Back to Dashboard link -->
            <div class="text-center mt-3">
                <?php
                $back_url = 'home.php';
                if ($role === 'admin') $back_url = 'admin.php';
                elseif ($role === 'superadmin') $back_url = 'superadmin.php';
                ?>
                <a href="<?php echo $back_url; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        &copy; <?php echo date('Y'); ?> Your Company. All rights reserved.
    </div>
</footer>

<!-- Password Change Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="change_password">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>