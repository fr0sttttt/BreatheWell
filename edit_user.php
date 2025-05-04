<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Edit User Role</h2>
    <form action="update_user.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">

        <label>First Name:</label>
        <input type="text" value="<?php echo $user['firstname']; ?>" disabled><br>

        <label>Last Name:</label>
        <input type="text" value="<?php echo $user['lastname']; ?>" disabled><br>

        <label>Role:</label>
        <select name="role_id">
            <?php
            $roles = $conn->query("SELECT * FROM user_roles");
            while ($role = $roles->fetch_assoc()) {
                $selected = ($role['userrole_id'] == $user['role_id']) ? 'selected' : '';
                echo "<option value='{$role['userrole_id']}' $selected>{$role['role_name']}</option>";
            }
            ?>
        </select><br>

        <button type="submit">Update Role</button>
    </form>
</div>
</body>
</html>
