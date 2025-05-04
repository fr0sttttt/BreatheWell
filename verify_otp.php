<?php
session_start();

// Check if email and OTP exist in session
if (!isset($_SESSION['email']) || !isset($_SESSION['otp'])) {
    header("Location: signup.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP - Breathwell</title>
    <link rel="stylesheet" href="newstyle.css">
</head>
<body>

<header class="main-header">
    <div class="header-left">
        <h1>Breathwell</h1>
    </div>
</header>

<div class="page-container">

    <div class="image-side">
        <div class="slideshow">
            <img src="img/anxiety.png" class="logslide active">
            <img src="img/calm.png" class="logslide">
            <img src="img/confused.png" class="logslide">
            <img src="img/anger.png" class="logslide">
            <img src="img/hapeh.png" class="logslide">
            <img src="img/okay.png" class="logslide">
            <img src="img/hurt.png" class="logslide">
            <img src="img/haphap.png" class="logslide">
            <img src="img/longing.png" class="logslide">
            <img src="img/happeh.png" class="logslide">
            <img src="img/sadge.png" class="logslide">
        </div>
    </div>

    <div class="login-container">
        <h2>Verify OTP</h2>

        <!-- Show error message if OTP is incorrect -->
        <?php
        if (isset($_SESSION['otp_error'])) {
            echo '<p style="color: red;">' . $_SESSION['otp_error'] . '</p>';
            unset($_SESSION['otp_error']);
        }
        ?>

        <form action="verify_otp_process.php" method="post">
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <button type="submit" class="continue-btn">Verify</button>
        </form>

        <p id="timer-text" style="margin-top: 10px;">Resend OTP in <span id="countdown">30</span> seconds</p>
        <form id="resend-form" action="resend_otp.php" method="post" style="display: none;">
            <button type="submit" class="continue-btn">Resend OTP</button>
        </form>
    </div>

</div>

<script>
    let countdown = 30;
    const countdownElement = document.getElementById("countdown");
    const timerText = document.getElementById("timer-text");
    const resendForm = document.getElementById("resend-form");

    const interval = setInterval(() => {
        countdown--;
        countdownElement.textContent = countdown;

        if (countdown <= 0) {
            clearInterval(interval);
            timerText.style.display = "none";
            resendForm.style.display = "block";
        }
    }, 1000);
</script>

</body>
</html>
