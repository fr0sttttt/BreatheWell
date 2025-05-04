<?php
include 'db_connection.php';

$success = $error = "";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch categories
$category_query = $conn->query("SELECT id, name FROM hotline_categories ORDER BY name ASC");

// Fetch hotline data to edit
$stmt = $conn->prepare("SELECT * FROM hotlines WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$hotline = $result->fetch_assoc();
$stmt->close();

if (!$hotline) {
    die("Hotline not found.");
}

// Handle form update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $number = trim($_POST['number']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);

    if ($name && $number && $category_id) {
        $update_stmt = $conn->prepare("UPDATE hotlines SET name=?, number=?, description=?, category_id=? WHERE id=?");
        $update_stmt->bind_param("sssii", $name, $number, $description, $category_id, $id);

        if ($update_stmt->execute()) {
            $success = "Hotline updated successfully!";
        } else {
            $error = "Failed to update hotline.";
        }

        $update_stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Hotline</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .hotline-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: 600;
            color: #555;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
        }

        textarea {
            resize: vertical;
        }

        button {
            margin-top: 20px;
            padding: 12px 20px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        a.back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        a.back-link:hover {
            text-decoration: underline;
        }
    </style>

</head>
<body>

<div class="hotline-container">
    <h2>Edit Hotline</h2>

    <?php if ($success): ?>
        <div class="message success"><?= $success; ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Hotline Name*</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($hotline['name']) ?>" required>

        <label for="number">Phone Number*</label>
        <input type="text" id="number" name="number" value="<?= htmlspecialchars($hotline['number']) ?>" required>

        <label for="category_id">Category*</label>
        <select name="category_id" id="category_id" required>
            <option value="">-- Select Category --</option>
            <?php while ($cat = $category_query->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $hotline['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"><?= htmlspecialchars($hotline['description']) ?></textarea>

        <button type="submit">Update Hotline</button>
    </form>

    <a class="back-link" href="admin_hotline.php">&larr; Back to Hotline List</a>
</div>

</body>
</html>