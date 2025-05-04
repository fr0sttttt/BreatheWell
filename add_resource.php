<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $url = $_POST['url'];
    $type = $_POST['type'];

    $stmt = $conn->prepare("INSERT INTO resources (title, url, type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $url, $type);

    if ($stmt->execute()) {
        header("Location: admin.php#resources?added=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
