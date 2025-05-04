<?php
session_start();
require 'db_connection.php';

$userId = $_SESSION['user_id']; // or your session key
$newMoodId = $_POST['favorite_mood_image_id'];

$stmt = $conn->prepare("UPDATE users SET favorite_mood_image_id = ? WHERE user_id = ?");
$stmt->bind_param("ii", $newMoodId, $userId);
$stmt->execute();

header("Location: profile.php"); // or your profile page
exit();
