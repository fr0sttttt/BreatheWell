<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['entry']) && $user_id) {
    $entry = trim($_POST['entry']);
    $stmt = $conn->prepare("INSERT INTO personal_diary (user_id, entry, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $entry);
    $stmt->execute();
    header("Location: user_library.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Diary Entry</title>
  <link rel="stylesheet" href="style.css">
  <style>

    .entry-form {
      margin: 60px auto;
      max-width: 600px;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .entry-form h2 {
      margin-bottom: 15px;
      color: #2c3e50;
    }

    .entry-form textarea {
      width: 100%;
      height: 150px;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      resize: vertical;
      font-size: 15px;
    }

    .entry-form button {
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .entry-form button:hover {
      background-color: #2980b9;
    }

    .return-button {
      margin-top: 0px;
      display: inline-block;
      text-decoration: none;
      background-color: #3498db;
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }

    .return-button:hover {
      background-color: #2980b9;
    }
    
  </style>
</head>
<body>
    <!-- Return Button -->
    <a href="user_library.php" class="return-button">←</a>

    <div class="entry-form">
        <h2>Write a New Diary Entry</h2>
        <form method="POST">
        <textarea name="entry" placeholder="Write your thoughts..."></textarea>
        <br>
        <button type="submit">Save Entry</button>
    </form>

  </div>
</body>
</html>
