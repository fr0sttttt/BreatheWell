<?php
session_start();
include 'db_connection.php'; // adjust this path as needed

// Ensure the user is logged in and user_id is available
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$userId = $_SESSION['user_id'];
$currentDateTime = date('Y-m-d H:i:s');

// Get data from POST
$moodLevel = $_POST['moodLevel'] ?? null;
$selectedMoods = $_POST['moods'] ?? [];
$moodsStr = implode(", ", $selectedMoods);
$factors = $_POST['factors'] ?? '';
$note = $_POST['note'] ?? '';

// Save to database
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "sample_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO mood_tracking (user_id, mood_level, selected_moods, mood_factors, note, timestamp) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $userId, $moodLevel, $moodsStr, $factors, $note, $currentDateTime);

    if ($stmt->execute()) {
        // Redirect or show success
        header("Location: user.php#myday"); // or a success page
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mood Checkin</title>
    <link rel="stylesheet" href="newstyle.css">

<style>  

section {
    width: 100%;
    height: 100vh;
}

/* Mood Slider*/

.slider-container {
  width: 60%;
  height: 70%;
  margin: 30px auto;
  text-align: center;
  background: #fff;
  padding: 40px;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 8px 16px rgb(0, 0, 0);

}

.custom-slider {
  -webkit-appearance: none;
  width: 100%;
  height: 10px;
  background: #000;
  border-radius: 12px;
  outline: none;
  margin-top: 30px;
  background-color:rgb(21, 20, 29);
  margin-bottom: 30px;
}

.custom-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #2c3e50;
  cursor: pointer;
  transition: background 0.3s ease;
}

.custom-slider::-webkit-slider-thumb:hover {
  background: #2980b9;
}

.slider-icons {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.slider-icons img {
    width: 70px;
    height: 70px;
    object-fit: contain;
}

.slider-labels {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #333;
  font-weight: bold;
}

.current-date-time {
    font-size: 20px;
    color: #000;
    margin-top: 0;
    width: 100%;
    text-align: center;
    font-weight: bold;
}

.slider-title {
    text-align: center;
    color: #000;
    padding: 12px;
}

/* General grid container setup */
.grid-container {
    display: grid;
    grid-template-columns: repeat(5, 1fr);  /* Creates 5 equal-width columns */
    grid-template-rows: repeat(5, 60px);    /* Creates 5 equal-height rows */
    gap: 10px;                             /* Adds space between grid boxes */
    padding: 20px;
    justify-content: center;
    background-color: #fff;
    margin: 20px;
    border-radius: 12px;
}

/* Styling for the grid items */
.grid-box {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #2c3e50;
    color: white;
    font-size: 16px;
    font-weight: bold;
    border-radius: 8px;
    padding: 10px;
    box-sizing: border-box;  /* Ensures padding and borders are included in the box's total width/height */
    text-align: center;
    cursor: pointer;
    transition: background-color 0.3s;
}

/* Hide the checkbox, but keep it functional */
.grid-box input[type="checkbox"] {
    opacity: 0; /* Makes the checkbox invisible */
    position: absolute; /* Remove from visual layout */
    width: 0;  /* Ensure it's not taking space */
    height: 0; /* Ensure it's not visible */
}

/* Change color on hover */
.grid-box:hover {
    background-color: #2980b9;
}

/* Style for the selected (checked) grid-box */
.grid-box.selected {
    background-color: #2980b9;  /* Green color when selected */
}


/* Optional: Add responsive behavior */
@media (max-width: 768px) {
    .grid-container {
        grid-template-columns: repeat(3, 1fr);  /* 3 columns on smaller screens */
    }
}

@media (max-width: 480px) {
    .grid-container {
        grid-template-columns: 1fr;  /* Single column on very small screens */
    }
}

.mood-factor-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 24px;
    background-color: #2c3e50;
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
    justify-content: space-between;
}

.mood-factor-btn:hover {
    background-color: #3498db;
}

.button-holder {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    margin-top: 0;
    padding-bottom: 20px;
    background: #fff;
}

.notepadbg {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh; /* Full height of the screen */
    background-color: #f4f4f4; /* Optional background */
    padding: 20px;
}

.notepad-container {
    width: 95%;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

textarea {
    width: 100%;
    height: 460px;
    font-size: 16px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    resize: vertical;
}

button {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 24px;
    background-color: #2c3e50;
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
    justify-content: space-between;
}

button:hover {
    background-color: #2980b9;
}


</style>

</head>
<body>


<!-- Top Navbar -->

<div class="top-header">
    <div class="left-side">
        <h2 class="app-title">BreatheWell</h2>
    </div>

    <div class="right-side">
        <ul class="navbar-links">
            <li><a href="#">About Us</a></li>
        </ul>
    </div>
</div>  

<!-- END OF TOP NAVBAR -->
<form method="POST">
    <section id="slider">
        <div class="slider-container">
            <div class="current-date-time">
                <?php echo $currentDateTime; ?>
            </div>
            <div class="slider-title">
                <h2 style="font-size: 20px;">Describe Your Mood</h2>
            </div>
            <div class="slider-icons">
                <img src="img/gloomy.png" alt="Gloomy">
                <img src="img/sad.png" alt="Sad">
                <img src="img/meh.png" alt="Meh">
                <img src="img/good.png" alt="Good">
                <img src="img/joyful.png" alt="Joyful">
            </div>
            <input type="range" min="1" max="5" value="3" class="custom-slider" id="answerSlider" name="moodLevel">

            <div class="slider-labels">
                <span>Gloomy</span>
                <span>Sad</span>
                <span>Meh</span>
                <span>Good</span>
                <span>Joyful</span>
            </div>
        </div>

    </section>

    <section id="selectmoods">
        <div>
            <h3 style="text-align:center; color: #161a2d; margin-bottom: 10px; font-size: 24px;">Select Your Moods</h3>
            <div class="grid-container">
                <?php
                $moods = ["Amused", "Happy", "Pride", "Relief", "Joy", "Serenity", "Gratitude", "Calm", "Rage", "Disturb", 
                        "Disgust", "Angry", "Envy", "Jealous", "Disappointed", "Fear", "Stress", "Numb", "Panic", "Tired", 
                        "Regret", "Depressed", "Shame", "Guilt", "Indifference"];
                $moodColors = [
                "Amused" => "#f39c12",
                "Happy" => "#f1c40f",
                "Pride" => "#8e44ad",
                "Relief" => "#2ecc71",
                "Joy" => "#e67e22",
                "Serenity" => "#3498db",
                "Gratitude" => "#1abc9c",
                "Calm" => "#16a085",
                "Rage" => "#c0392b",
                "Disturb" => "#e74c3c",
                ];
                 
                foreach ($moods as $mood) {
                    echo "<div class='grid-box'>
                            <input type='checkbox' name='moods[]' value='$mood'> $mood
                        </div>";
                }
                //foreach ($moods as $mood) {
                //$color = $moodColors[$mood] ?? "#2c3e50"; // default fallback color
                //echo "<div class='grid-box' style='background-color: $color;'>
                //<input type='checkbox' name='moods[]' value='$mood'> $mood
                //</div>";
                //}
                        
                ?>
            </div>
        </div> 
    </section>

    <section id="mfactors">
        <div>
            <h3 style="text-align:center; color: #161a2d; margin-bottom: 10px; font-size: 24px;">What Affected Your Mood?</h3>
            <div class="grid-container">

                <?php
                $factors = [
                    "Sleep", "Work", "Movies", "Hobbies", "Friends",
                    "Financial", "Weather", "Books", "Temperature", "Learning",
                    "Music", "Social Media", "Family", "Spiritual", "Pets",
                    "Health", "News", "Travel", "Milestones", "Games"
                ];
                foreach ($factors as $factor) {
                    echo "<div class='grid-box'>
                            <input type='checkbox' name='factors[]' value='$factor'> $factor
                        </div>";
                }
                ?>
            </div>    
        </div>

        <input type="hidden" name="factors" id="selected-factors">
    </section>

    <section id="notepad">
        <div class="notepadbg">
            <div class="notepad-container">
                <form method="post">
                    <h2>Reflection</h2>
                    <textarea name="note" placeholder="Type your thoughts here..."><?php echo htmlspecialchars($note); ?></textarea>
                    <br>
                    <div class="button-holder">
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

</form>


<script src="script.js"></script>
<script>
    document.getElementById('answerSlider').addEventListener('input', function() {
  console.log('Selected Level:', this.value);
});

    // Handle clicks on grid boxes to toggle selection
    const gridBoxes = document.querySelectorAll('.grid-box');
    
    gridBoxes.forEach(box => {
        box.addEventListener('click', () => {
            const checkbox = box.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked; // Toggle checkbox state
            
            // Toggle visual selection (change background color)
            box.classList.toggle('selected', checkbox.checked);
        });
    });

    document.querySelector("form").addEventListener("submit", function () {
    const selectedFactors = Array.from(document.querySelectorAll('.grid-box input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value)
        .join(', ');

    document.getElementById('selected-factors').value = selectedFactors;
});
</script>

    
    
</body>
</html>

