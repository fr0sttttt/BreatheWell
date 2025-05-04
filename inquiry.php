<!-- inquiry.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send an Inquiry</title>
  <link rel="stylesheet" href="newstyle.css">
  <style>
        body, html {
            background: #ececec;
            background-attachment: cover;
            max-height: 80vh;
        }

        .inquiry-box {
            margin: 120px auto;
            padding: 0;
            background-color: #fafafa;
            width: 90%;
            height: 400px; /* Fixed height */
            border-radius: 12px;
            overflow-y: auto; /* Enable vertical scrolling */
            box-sizing: border-box;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
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
            color: #161a2d;
        }

        .top-header .app-title {
            color: #161a2d;
        }

        .inq-title {
            background: linear-gradient(to right, #161a2d, #5d65c7);
            padding: 10px;
            color: #fff;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.8);
        }

        form {
          padding: 12px;
        }

    textarea, input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      box-sizing: border-box;
    }

    label {
      font-weight: bold;
      color: #2c3e50;
    }

    button {
      padding: 10px 20px;
      background-color: #007BFF;
      border: none;
      color: white;
      border-radius: 5px;
      cursor: pointer;
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
    <a href="user.php" class="return-button">←</a>
        <div class="left-side">
            <h2 class="app-title">BreatheWell</h2>
        </div>

        <div class="right-side">
            <ul class="navbar-links">
                <li><a href="#">About Us</a></li>
            </ul>
        </div>
    </div> 

    <div class="inquiry-box">
        <h2 class="inq-title">Send Us Your Inquiry</h2>
        <form method="POST" action="send_inquiry.php">
          <label for="email">Your Email:</label>
          <input type="email" name="email" required>
          <br><br><br><br>
          <label for="message">Your Inquiry:</label>
          <textarea name="message" rows="5" required></textarea>
          
          <button type="submit">Send Inquiry</button>
        </form>
    </div>




</body>
</html>
