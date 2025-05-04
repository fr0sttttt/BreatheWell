<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mood Check-In</title>
    <link rel="stylesheet" href="your-styles.css">
    <style>
        .step { display: none; }
        .step.active { display: block; }
        .mood-box, .factor-box {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            cursor: pointer;
        }
        .selected { background-color: #b3e5fc; }
        .error { color: red; font-size: 14px; margin-top: 8px; }
        textarea { width: 100%; height: 100px; border-radius: 8px; padding: 10px; }
    </style>
</head>
<body>

<div class="container">
    <form id="moodForm" action="mood_checkin.php" method="POST">
        <!-- Step 1: Mood Slider -->
        <div id="step1" class="step active">
            <h2>How are you feeling today?</h2>
            <input type="range" name="mood_level" min="1" max="10" value="5">
            <p>Mood Level: <span id="moodValue">5</span></p>
            <button type="button" onclick="nextStep(1)">Continue</button>
        </div>

        <!-- Step 2: Select Mood -->
        <div id="step2" class="step">
            <h2>Select your mood</h2>
            <div id="moodOptions">
                <?php
                $moods = ['Happy', 'Sad', 'Anxious', 'Excited', 'Angry', 'Calm'];
                foreach ($moods as $mood) {
                    echo "<div class='mood-box' onclick='toggleSelect(this, \"mood\")'>$mood</div>";
                }
                ?>
            </div>
            <input type="hidden" name="selected_moods" id="selectedMoods">
            <div id="moodError" class="error" style="display:none;">Please select at least one mood.</div>
            <button type="button" onclick="nextStep(2)">Continue</button>
        </div>

        <!-- Step 3: Mood Factors -->
        <div id="step3" class="step">
            <h2>What influenced your mood?</h2>
            <div id="factorOptions">
                <?php
                $factors = ['Work', 'Family', 'Health', 'Sleep', 'Weather', 'Social'];
                foreach ($factors as $factor) {
                    echo "<div class='factor-box' onclick='toggleSelect(this, \"factor\")'>$factor</div>";
                }
                ?>
            </div>
            <input type="hidden" name="mood_factors" id="selectedFactors">
            <div id="factorError" class="error" style="display:none;">Please select at least one factor.</div>
            <button type="button" onclick="nextStep(3)">Continue</button>
        </div>

        <!-- Step 4: Reflection -->
        <div id="step4" class="step">
            <h2>Write your reflection</h2>
            <textarea name="reflection" id="reflection" placeholder="Type your thoughts here..."></textarea>
            <div id="reflectionError" class="error" style="display:none;">Please write a reflection.</div>
            <button type="submit">Save</button>
        </div>
    </form>
</div>

<script>
    const moodValue = document.querySelector('input[type="range"]');
    const moodDisplay = document.getElementById('moodValue');
    moodValue.addEventListener('input', () => {
        moodDisplay.textContent = moodValue.value;
    });

    function toggleSelect(element, type) {
        element.classList.toggle('selected');
    }

    function getSelectedValues(type) {
        const selected = document.querySelectorAll(`.${type}-box.selected`);
        return Array.from(selected).map(el => el.textContent);
    }

    function nextStep(current) {
        if (current === 1) {
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.add('active');
        } else if (current === 2) {
            const moods = getSelectedValues("mood");
            if (moods.length === 0) {
                document.getElementById("moodError").style.display = "block";
                return;
            }
            document.getElementById("moodError").style.display = "none";
            document.getElementById("selectedMoods").value = moods.join(",");

            document.getElementById('step2').classList.remove('active');
            document.getElementById('step3').classList.add('active');
        } else if (current === 3) {
            const factors = getSelectedValues("factor");
            if (factors.length === 0) {
                document.getElementById("factorError").style.display = "block";
                return;
            }
            document.getElementById("factorError").style.display = "none";
            document.getElementById("selectedFactors").value = factors.join(",");

            document.getElementById('step3').classList.remove('active');
            document.getElementById('step4').classList.add('active');
        }
    }

    document.getElementById('moodForm').addEventListener('submit', function(e) {
        const reflection = document.getElementById('reflection').value.trim();
        if (reflection === '') {
            document.getElementById('reflectionError').style.display = "block";
            e.preventDefault();
        } else {
            document.getElementById('reflectionError').style.display = "none";
        }
    });
</script>

</body>
</html>
