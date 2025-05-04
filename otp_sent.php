<?php 
session_start();
// Check if the OTP resend success message is set in the session
if (isset($_SESSION['otp_resend_success'])) {
  $otpSuccessMessage = $_SESSION['otp_resend_success'];
  // Unset the message so it doesn't appear again on page reload
  unset($_SESSION['otp_resend_success']);
} else {
  $otpSuccessMessage = '';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OTP Sent - Breathwell</title>
    <link rel="stylesheet" href="newstyle.css">
</head>
<body>

<div class="top-header">
    <div class="left-side">
        <h2 class="app-title">BreatheWell</h2>
    </div>

    <div class="right-side">
    </div>
</div>  


<section>
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
            <h2>OTP Sent</h2>

            <?php if ($otpSuccessMessage): ?>
                <script>
                    alert("<?php echo $otpSuccessMessage; ?>");  // Show the success message as an alert
                </script>
            <?php endif; ?>

            <p>Your OTP has been sent successfully. Please check your email.</p>
            <a href="verify_otp.php" class="continue-btn">Go to OTP verification</a>
        </div>
    </div>
</section>


</body>
</html>
