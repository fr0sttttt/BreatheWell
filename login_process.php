<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if email exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['otp_enabled'] = $user['otp_enabled'];

            // Check if OTP is enabled
            if ($user['otp_enabled']) {
                // Proceed to send OTP
                header('Location: send_otp.php');
            } else {
                // Skip OTP, login directly
                header('Location: user.php');
            }
            exit();
        } else {
            echo "<script>alert('Incorrect password. Please try again.'); window.location.href='login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('No account found with this email.'); window.location.href='login.php';</script>";
        exit();
    }
}
?>
