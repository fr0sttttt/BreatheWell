<?php
// Include the database connection
include 'db_connection.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $topic = $_POST['topic'];
    $description = $_POST['description'];

    // Prepare the SQL query to insert the assessment into the database
    $query = "INSERT INTO assessments (topic, description, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        // Bind the parameters and execute the query
        $stmt->bind_param('ss', $topic, $description);
        if ($stmt->execute()) {
            // Redirect after successful insertion
            header('Location: admin_assessment.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
