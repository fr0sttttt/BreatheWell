<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];

    $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $role_id, $user_id);

    if ($stmt->execute()) {
        // ✅ Redirect back to the admin dashboard with a success message
        header("Location: admin_dashboard.php?message=User+updated+successfully");
        exit();
    } else {
        // ❌ If something goes wrong
        echo "Update failed: " . $stmt->error;
    }
}
?>
