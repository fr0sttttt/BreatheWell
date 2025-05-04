<?php
include 'db_connection.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: admin.php");
    exit();
}

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM assessments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$assessment = $result->fetch_assoc();
$stmt->close();

if (!$assessment) {
    echo "Assessment not found 💔";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($title)) {
        $stmt = $conn->prepare("UPDATE assessments SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $description, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin.php?updated=1");
        exit();
    } else {
        $error = "Title is required!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Assessment</title>
    <link rel="stylesheet" href="style.css">

<style>
    .edit-container {
  width: 90%;
  max-width: 600px;
  margin: 60px auto;
  background-color: #ffffff;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
}

.edit-container h2 {
  margin-bottom: 20px;
  font-size: 24px;
  color: #2c3e50;
  text-align: center;
}

.edit-container form {
  display: flex;
  flex-direction: column;
}

.edit-container label {
  font-weight: 600;
  margin-bottom: 8px;
  color: #34495e;
}

.edit-container input[type="text"],
.edit-container textarea {
  padding: 10px 15px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 16px;
  resize: none;
}

.edit-container input[type="submit"] {
  background-color: #344882;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.edit-container input[type="submit"]:hover {
  background-color: #2c3e50;
}

</style>    
</head>
<body>
    <div class="container">
        <h2>Edit Assessment </h2>

        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form action="" method="POST">
            <label for="title">Title </label><br>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($assessment['title']); ?>" required><br><br>

            <label for="description">Description </label><br>
            <textarea name="description" id="description" rows="4" cols="50"><?php echo htmlspecialchars($assessment['description']); ?></textarea><br><br>

            <button type="submit">Update Assessment </button>
        </form>

        <br>
        <a href="admin.php#assessment">← Back to Assessments</a>
    </div>
</body>
</html>
