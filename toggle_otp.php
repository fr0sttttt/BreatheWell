<?php
session_start();
include 'db_connection.php';

$userId = $_SESSION['user_id'];

if (!$userId) {
    header("Location: login.php");
    exit;
}

// Get current OTP setting
$stmt = $conn->prepare("SELECT otp_enabled FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$currentOtpStatus = $user['otp_enabled'];

// Toggle the setting
$newOtpStatus = $currentOtpStatus ? 0 : 1;

$update = $conn->prepare("UPDATE users SET otp_enabled = ? WHERE user_id = ?");
$update->bind_param("ii", $newOtpStatus, $userId);
$update->execute();

// Store in session (optional for UI use)
$_SESSION['otp_enabled'] = $newOtpStatus;

header("Location: ".$_SERVER['HTTP_REFERER']); // Redirect back
exit;
