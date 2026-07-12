<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $age   = (int) ($_POST['age'] ?? 0);

    if (empty($name) || empty($email) || empty($password) || empty($phone) || $age < 1) {
        $_SESSION['register_error'] = "All fields are required and age must be valid.";
        header("Location: register.php");
        exit();
    }

    $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['register_error'] = "This email is already registered. Please login.";
        $check->close();
        header("Location: register.php");
        exit();
    }
    $check->close();

    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $role = 'member'; 

    $insert = $conn->prepare("INSERT INTO users (email, name, password_hash, phone_number, age, role) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssis", $email, $name, $hashed, $phone, $age, $role);

    if ($insert->execute()) {
        $_SESSION['success_message'] = "Registration successful! Please login.";
        $insert->close();
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['register_error'] = "Registration failed. Please try again later.";
        $insert->close();
        header("Location: register.php");
        exit();
    }
}

header("Location: register.php");
exit();
?>