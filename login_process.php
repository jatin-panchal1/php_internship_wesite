<?php
session_start();
require_once 'connection.php';

// If already logged in, redirect to the correct dashboard based on role
if (isset($_SESSION['user_email'])) {
    $role = $_SESSION['user_role'] ?? 'member';
    switch ($role) {
        case 'superadmin':
            header("Location: superadmin_home.php");
            break;
        case 'admin':
            header("Location: admin_home.php");
            break;
        default:
            header("Location: home.php");
            break;
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in both email and password.";
        header("Location: login.php");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT email, password_hash, role FROM users WHERE email = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = $user['role'];
            unset($_SESSION['login_error']);

            // Redirect based on role
            switch ($user['role']) {
                case 'superadmin':
                    header("Location: superadmin.php");
                    break;
                case 'admin':
                    header("Location: admin.php");
                    break;
                default:
                    header("Location: home.php");
                    break;
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Incorrect email or password.";
            header("Location: login.php");
            exit();
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = "A system error occurred. Please try again later.";
        header("Location: login.php");
        exit();
    }
}
// If not POST, redirect to login
header("Location: login.php");
exit();
?>