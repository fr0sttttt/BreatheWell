<?php 
session_start();
$email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - BreathWell</title>
    <link rel="stylesheet" href="newstyle.css">
</head>
<body>
    <div class="page-container">
        <div class="login-container">
            <h2>Reset Password</h2>

            <?php if (isset($_SESSION['error'])) {
                echo '<p style="color:red;">' . $_SESSION['error'] . '</p>';
                unset($_SESSION['error']);
            } ?>

            <form action="send_otp.php" method="POST">
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" class="continue-btn">Send OTP</button>
            </form>
        </div>
    </div>
</body>
</html>
