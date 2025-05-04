<?php
session_start();
require 'db_connection.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit;
}

$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$birthday = $_POST['birthday'];
$gender = $_POST['gender'];
$contact = $_POST['contact'];

$sql = "UPDATE users SET firstname=?, lastname=?, email=?, birthday=?, gender=?, contact=? WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", $firstname, $lastname, $email, $birthday, $gender, $contact, $userId);
$stmt->execute();

header("Location: profile.php");
exit;
