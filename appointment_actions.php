<?php
session_start();
require_once 'connection.php';

// Must be logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$role  = $_SESSION['user_role'] ?? 'member';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: home.php");
    exit();
}

$action = $_POST['action'] ?? '';
$message = '';
$message_type = '';

// --- BOOK: Members create a new appointment ---
if ($action === 'book') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date        = $_POST['appointment_date'] ?? '';
    $time        = $_POST['appointment_time'] ?? '';

    if (empty($title) || empty($date) || empty($time)) {
        $message = "Title, date, and time are required.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (user_email, title, description, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $title, $description, $date, $time);
        if ($stmt->execute()) {
            $message = "Appointment booked successfully.";
            $message_type = 'success';
        } else {
            $message = "Error booking appointment: " . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// --- CANCEL: Members cancel their own pending appointment ---
elseif ($action === 'cancel') {
    $id = (int) ($_POST['appointment_id'] ?? 0);
    if ($id < 1) {
        $message = "Invalid appointment.";
        $message_type = 'danger';
    } else {
        // Only allow cancelling own pending appointments
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND user_email = ? AND status = 'pending'");
        $stmt->bind_param("is", $id, $email);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Appointment cancelled.";
            $message_type = 'success';
        } else {
            $message = "Could not cancel. Only pending appointments you own can be cancelled.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// --- UPDATE STATUS: Admin/Superadmin approve, reject, complete ---
elseif ($action === 'update_status') {
    if ($role !== 'admin' && $role !== 'superadmin') {
        $message = "Unauthorized.";
        $message_type = 'danger';
    } else {
        $id      = (int) ($_POST['appointment_id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        $remarks = trim($_POST['admin_remarks'] ?? '');

        $allowed = ['approved', 'rejected', 'completed'];
        if ($id < 1 || !in_array($status, $allowed)) {
            $message = "Invalid request.";
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE appointments SET status = ?, admin_remarks = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $remarks, $id);
            if ($stmt->execute()) {
                $message = "Appointment " . $status . " successfully.";
                $message_type = 'success';
            } else {
                $message = "Error updating appointment: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// --- DELETE: Superadmin only ---
elseif ($action === 'delete_appointment') {
    if ($role !== 'superadmin') {
        $message = "Unauthorized.";
        $message_type = 'danger';
    } else {
        $id = (int) ($_POST['appointment_id'] ?? 0);
        if ($id < 1) {
            $message = "Invalid appointment.";
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Appointment deleted.";
                $message_type = 'success';
            } else {
                $message = "Error deleting appointment: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

else {
    $message = "Unknown action.";
    $message_type = 'danger';
}

// Flash message and redirect back
$_SESSION['appt_message'] = $message;
$_SESSION['appt_message_type'] = $message_type;

// Whitelist allowed redirect targets to prevent open redirect
$allowed_redirects = ['home.php', 'admin.php', 'superadmin.php'];
$redirect = $_POST['redirect'] ?? '';
if (!in_array($redirect, $allowed_redirects)) {
    $redirect = ($role === 'superadmin') ? 'superadmin.php' : (($role === 'admin') ? 'admin.php' : 'home.php');
}
header("Location: " . $redirect);
exit();
?>
