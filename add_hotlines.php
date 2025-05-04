<?php
include 'db_connection.php';

$success = $error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $number = trim($_POST['number']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);

    if ($name && $number && $category_id) {
        $stmt = $conn->prepare("INSERT INTO hotlines (name, number, description, category_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $number, $description, $category_id);
        if ($stmt->execute()) {
            $success = "Hotline added successfully!";
        } else {
            $error = "Failed to add hotline.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch categories for dropdown
$category_query = $conn->query("SELECT id, name FROM hotline_categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Hotline</title>
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
    <h2>Add Hotline</h2>

    <?php if ($success): ?>
        <div class="message success"><?= $success; ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="name">Hotline Name*</label>
        <input type="text" id="name" name="name" required>

        <label for="number">Phone Number*</label>
        <input type="text" id="number" name="number" required>

        <label for="category_id">Category*</label>
        <select name="category_id" id="category_id" required>
            <option value="">-- Select Category --</option>
            <?php while ($cat = $category_query->fetch_assoc()): ?>
                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
            <?php endwhile; ?>
        </select>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"></textarea>

        <button type="submit">Add Hotline</button>
    </form>

    <a class="back-link" href="admin_hotline.php">&larr; Back to Hotline List</a>
</div>

</body>
</html>
