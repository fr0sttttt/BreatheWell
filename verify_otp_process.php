<?php
session_start();
require 'db_connection.php';

// Check if OTP was submitted
if (!isset($_POST['otp'])) {
    header("Location: verify_otp.php");
    exit();
}

$user_otp = $_POST['otp'];

// Check if OTP matches session OTP
if (isset($_SESSION['otp']) && $user_otp == $_SESSION['otp']) {
    unset($_SESSION['otp']); // Clear OTP from session after successful verification

    if (!isset($_SESSION['email'])) {
        // Email missing from session
        header("Location: login.php");
        exit();
    }

    $email = $_SESSION['email'];

    // Fetch user details and role
    $stmt = $conn->prepare(
        "SELECT users.*, user_roles.role_name 
         FROM users 
         LEFT JOIN user_roles ON users.role_id = user_roles.userrole_id 
         WHERE users.email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['role'] = $user['role_name'];

        // Redirect based on role
        if ($user['role_name'] === 'admin') {
            header("Location: admin.php");
            exit();
        } else {
            header("Location: user.php");
            exit();
        }
    } else {
        // No user found
        $_SESSION['error'] = "User not found. Please try again.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    // OTP is incorrect
    $_SESSION['otp_error'] = "Incorrect OTP. Please try again.";
    header("Location: verify_otp.php");
    exit();
}
?>
