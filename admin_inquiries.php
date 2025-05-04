<?php
include 'db_connection.php';

$inquiries = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Inquiry</title>
    <link rel="stylesheet" href="newstyle.css">
    <style>
        body, html {
            background: #ececec;
            background-attachment: cover;
            max-height: 80vh;
        }
        .top-header {
            position: fixed;
            top: 0;
            width: 100%;
            height: 40px;
            background: linear-gradient(to right, #e7e7e7, #fff);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            margin-bottom: 0;
            z-index: 1000;
        }

        .top-header .navbar-links li a {
            color: #354297;
        }

        .top-header .app-title {
            color: #354297;
        }

        .inquiry-box {
            margin: 60px auto;
            padding: 0;
            background-color: #fafafa;
            width: 90%;
            height: 600px; /* Fixed height */
            border-radius: 12px;
            overflow-y: auto; /* Enable vertical scrolling */
            box-sizing: border-box;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
        }

        .inquiry-card {
            background-color: #fff;
            border-left: 4px solid #3498db;
            padding: 12px 16px; /* Reduced padding */
            margin: 12px 10px auto;      /* Reduced margin */
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        .inquiry-card p {
            margin: 10px 0;
            line-height: 0.8;
        }

        .inquiry-card strong {
            color: #2c3e50;
        }

        .inq-title {
            background: linear-gradient(to right, #161a2d, #5d65c7);
            padding: 10px;
            color: #fff;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.8);
        }

        form {
            margin-top: 15px;
        }

        textarea {
            width: 100%;
            padding: 8px 10px; /* Reduced padding */
            resize: vertical;
            font-size: 13px;    /* Slightly smaller font */
            font-family: inherit;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 10px;
            min-height: 60px;   /* Reduced height */
        }

        button[type="submit"] {
            background-color: #3498db;
            color: white;
            padding: 10px 18px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #2980b9;
        }

        .return-button {
            position: absolute;
            display: inline-block;
            margin: 0;
            text-decoration: none;
            background-color: #ecf0f1;
            color: #2c3e50;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }

        .return-button:hover {
            background-color: #d0d7de;
        }
    </style>
</head>
<body>
    <div class="top-header">
        <a href="admin.php" class="return-button">←</a>
            <div class="left-side">
            <div style="display: flex; align-items: center; gap: 8px;">
            <img src="img/logo.png" alt="Logo" class="app-logo">
            <h2 class="app-title">BreatheWell</h2>
        </div>
        </div>

        <div class="right-side">
            <ul class="navbar-links">
                <li><a href="#">About Us</a></li>
            </ul>
        </div>
    </div> 

    

    <div class="inquiry-box">
        <h2 class="inq-title">User Inquiries</h2>
        <?php while ($row = $inquiries->fetch_assoc()): ?>
        <div class="inquiry-card">
            <p><strong>From:</strong> <?= htmlspecialchars($row['user_email']) ?></p>
            <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>

            <?php if ($row['response']): ?>
            <p><strong>Response:</strong> <?= nl2br(htmlspecialchars($row['response'])) ?></p>
            <form method="POST" action="delete_inquiry.php" style="margin-top: 10px;">
                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                <button type="submit" onclick="return confirm('Are you sure you want to delete this inquiry?');" style="background-color: #e74c3c; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer;">Delete</button>
            </form>

            <?php else: ?>
            <form method="POST" action="send_response.php">
                <input type="hidden" name="inquiry_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="user_email" value="<?= htmlspecialchars($row['user_email']) ?>">
                <textarea name="response" required placeholder="Write your response..."></textarea>
                <button type="submit">Send Response</button>
            </form>
            <?php endif; ?>
        </div>    
        <?php endwhile; ?>
    </div>

</body>
</html>

