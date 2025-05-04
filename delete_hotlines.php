<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prepare and delete hotline
    $stmt = $conn->prepare("DELETE FROM hotlines WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: admin_hotline.php?message=Hotline+deleted+successfully");
        exit();
    } else {
        echo "Error deleting hotline.";
    }

    $stmt->close();
} else {
    echo "Invalid hotline ID.";
}
?>
