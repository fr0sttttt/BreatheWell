<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Breathwell - Create Account</title>
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


        <div class="signup-container">
            <h2>Create Account</h2>

            <form action="save_signup_session.php" method="post">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <div class="name-container">
                    <input type="text" name="firstname" placeholder="First Name" required>
                    <input type="text" name="lastname" placeholder="Last Name" required>
                </div>
                <input type="date" name="birthday" required>
                <button type="submit" class="continue-btn">Continue</button>
            </form>

            <form action="login.php" method="get">
                <center>
                <button type="submit" class="transparent-btn">Already Have An Account?</button>
                </center>
            </form>
        </div>

    </div>
</section>
    

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
          <h2>BreatheWell</h2>
          <p>Find your calm. Discover helpful resources and a community that supports your journey.</p>
        </div>

        <div class="footer-contact">
          <h4>Contact Us</h4>
          <p>Email: <a href="mailto:support@breathewell.com">support@breathewell.com</a></p>
          <p>Phone: +1 (123) 456-7890</p>
        </div>

        <div class="footer-social">
          <h4>Connect With Us</h4>
          <div class="social-icons">
            <a href="#" target="_blank">
              <img src="img_jpg/fb-icon.jpg" alt="Facebook" />
            </a>
            <a href="#" target="_blank">
              <img src="img_jpg/ig-icon.jpg" alt="Instagram" />
            </a>
            <a href="#" target="_blank">
              <img src="img_jpg/twitter-icon.png" alt="Twitter" />
            </a>
          </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2025 <span>BreatheWell</span>. All rights reserved.</p>
    </div>
</footer>


<script>
    let slides = document.querySelectorAll(".logslide");
    let index = 0;

    setInterval(() => {
        slides[index].classList.remove("active");
        index = (index + 1) % slides.length;
        slides[index].classList.add("active");
    }, 3000); // Change every 3 seconds
</script>

</body>
</html>
