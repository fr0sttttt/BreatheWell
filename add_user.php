<?php
session_start();
require 'db_connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $birthday = $_POST['birthday'];
    $role_id = $_POST['role_id']; // Should match user_roles.userrole_id

    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, birthday, role_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $firstname, $lastname, $email, $password, $birthday, $role_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?message=User+added+successfully");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Add New User</h2>
    <form method="POST">
        <label>First Name:</label>
        <input type="text" name="firstname" required><br>

        <label>Last Name:</label>
        <input type="text" name="lastname" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <label>Password:</label>
        <input type="password" name="password" required><br>

        <label>Birthday:</label>
        <input type="date" name="birthday" required><br>

        <label>Role:</label>
        <select name="role_id" required>
            <?php
            $result = $conn->query("SELECT * FROM user_roles");
            while ($role = $result->fetch_assoc()) {
                echo "<option value='{$role['userrole_id']}'>{$role['role_name']}</option>";
            }
            ?>
        </select><br>

        <button type="submit">Add User</button>
    </form>
</div>
</body>
</html>
