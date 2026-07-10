<?php
session_start();
include('connection.php');

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$age = $_POST['age'] ?? 0;

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {

    $check_query = "SELECT email FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
     
        echo "This email address is already registered. Please login or use a different email.";
        header("Refresh: 2; url=register.php");

    } else {
      
        $insert_query = "INSERT INTO users (email, name, password_hash, phone_number, age) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
   
        $insert_stmt->bind_param("ssssi", $email, $name, $hashed_password, $phone, $age);
        
        if ($insert_stmt->execute()) {
          
            $_SESSION['success_message'] = "Registration successful!";
            header("Location: login.php");
            exit(); 
        } else {
            echo "Something went wrong. Please try again.";
        }
        $insert_stmt->close();
    }
    $stmt->close();
    
} catch (Exception $e) { 
  
    die("Database error: " . $e->getMessage());
}

?>