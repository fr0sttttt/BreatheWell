<?php
session_start();

include 'db_connection.php'; // ✅ using centralized DB config

if (!isset($_SESSION['user_id'])) {
    echo "User ID is missing. Please log in again.";
    exit();
}

$user_id = $_SESSION['user_id']; // Use the logged-in user_id

// Use global mysqli connection from db_connection.php
global $conn;

if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Query to get the count of each mood_level for the specific user
$query = "SELECT mood_level, COUNT(mood_level) as frequency FROM mood_tracking WHERE user_id = ? GROUP BY mood_level";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);  // 'i' for integer
$stmt->execute();
$result = $stmt->get_result();

$moodData = [];  // Initialize mood data array

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $moodData[] = [
            'mood_level' => $row['mood_level'],
            'count' => $row['frequency']
        ];
    }
} else {
    echo "No data found for this user.";
}

echo "<script>";
echo "const moodData = " . json_encode($moodData) . ";";
echo "</script>";

$stmt->close();
$conn->close();

$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];

// Current date info
$currentDateTime = date('l, F j, Y h:i A');
$currentMonth = date('F');
?>


<!DOCTYPE html>
<html>
<head>
    <title>My Day</title>
    <link rel="stylesheet" href="newstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

.myday-content {
  display: flex;
  background-color: #fff;
  width: 100%;
  height: 220px;
  justify-content: space-between;
  padding: 20px;
}

.current-date-time {
    font-size: 20px;
    color: #2c3e50;
    padding: 10px;
    display: inline-block;
}

/* Style for the days of the week */
.week-days {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 30px;
    margin-bottom: 30px;
    width: 100%;
}

.day-box {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #2c3e50;
    color: white;
    font-size: 14px;
    font-weight: bold;
    text-align: center;
}

.day-box:hover {
    background-color: #2c3e50;
}

.month {
    color: #2c3e50;
    font-size: 16px;
    font-weight: bold;
    text-align: center;    
}

.week-days-wrapper {
    text-align: center;
    width: 50%;
}

.today-box {
  background-color: #f39c12 !important;
  color: white !important;
  border: 2px solid #ffffffaa;
  transform: scale(1.1);
  transition: 0.3s ease;
}


.mood-checkin-btn {
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
}

.mood-checkin-btn:hover {
    background-color: #3498db;
}

.mood-grid {
    background-color: #fff;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 200px 200px;
    gap: 20px;
    margin: 15px;
    padding: 24px;
    max-width: 100%;
}

.grid-box {
  background-color: #e2eafc;
  display: inline-block;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  padding: 20px;
}

/* Positioning the boxes */
.mood-trends {
  grid-column: 1 / 2;
  grid-row: 1 / 2;
}

.mood-distribution {
  grid-column: 2 / 3;
  grid-row: 1 / 3;
}

.average-mood {
  grid-column: 1 / 2;
  grid-row: 2 / 3;
}

.graph-title {
    color: #2c3e50;
    font-size: 20px;
    font-weight: bold;
    left: 0;
}

#moodTrendChart {
    width: 700px;  
    height: 500px; 
    margin: 12px;
}

#moodDistributionChart {
    width: 100%;
    margin: 0px 60px 0px 60px;
}

#mostFrequentMood {
    font-size: 18px;
    color: #2c3e50; 
    font-weight: bold;
    padding: 10px;
    background-color: #edf2fb; 
    border-radius: 5px;
    border: 1px solid #ddd;
    max-width: 100%;
    margin-top: 20px;
    padding: 24px 5px 24px 5px;
    text-align: center;
}



</style>

</head>
<body>


<!-- Top Navbar -->
<div class="top-navbar">
  <div class="left-side">
    <h2 class="app-title">My Day</h2>
  </div>

  <div class="right-side">
    <ul class="navbar-links">
      <li><a href="user_dashboard.php">Home</a></li>
      <li><a href="user_resources.php">Resources</a></li>
      <li><a href="myday.php" class="active">My Day</a></li>
      <li><a href="user_assessment.php">Assessment</a></li>
      <li><a href="user_library.php">Library</a></li>
      <li><a href="user_hotline.php">Hotlines</a></li>
    </ul>

    <div class="burger-icon" onclick="toggleSidebar()">
      <div></div>
      <div></div>
      <div></div>
    </div>
  </div>

  <div id="dropdownMenu" class="dropdown-menu">
    <div class="profile-info">
      <img src="img/profile-icon.jpg" alt="Profile" class="profile-img">
      <span><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></span>
    </div>
    <ul>
      <li><a href="profile.php">View Profile</a></li>
      <li><a href="#">Change Password</a></li>
      <li><a href="login.php">Logout</a></li>
    </ul>
  </div>
</div>


<!-- END OF TOP NAVBAR -->


<!-- Current Date and Time Display -->
<div class="content">
    <div class="current-date-time">
        <?php echo $currentDateTime; ?>
    </div>
<!-- Weekdays Section -->
     <div class="week-days-wrapper">
        <div class="month"><?php echo $currentMonth; ?></div>
          <div class="week-days">
              <div class="day-box" id="monday">Mon</div>
              <div class="day-box" id="tuesday">Tue</div>
              <div class="day-box" id="wednesday">Wed</div>
              <div class="day-box" id="thursday">Thu</div>
              <div class="day-box" id="friday">Fri</div>
              <div class="day-box" id="saturday">Sat</div>
              <div class="day-box" id="sunday">Sun</div>
          </div>
          <button class="mood-checkin-btn" onclick="location.href='mood_checkin.php'">Mood Check-in</button>
      </div>
</div>

      <div class="mood-grid">
          <div class="grid-box mood-trends">
              <div class="graph-title">
                  Mood Trends
                  <select id="trendToggle">
                    <option value="weekly">This Week</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>
                <canvas id="moodTrendChart"></canvas>
          </div>

        <div class="grid-box mood-distribution">
            <div class="graph-title">  
                Mood Distribution
                <select id="trendToggle">
                  <option value="weekly">This Week</option>
                  <option value="monthly">Monthly</option>
                </select>
            </div> 
            <canvas id="moodDistributionChart"></canvas>
        </div>

        <div class="grid-box average-mood">
            <div class="graph-title">
                Average Mood
                <select id="trendToggle">
                  <option value="weekly">This Week</option>
                  <option value="monthly">Monthly</option>
                </select>
            </div>
            <div id="mostFrequentMood"></div>
        </div>
      </div>





<script src="script.js"></script>
<script>
  // Toggle sidebar dropdown menu
  function toggleSidebar() {
    const dropdownMenu = document.getElementById('dropdownMenu');
    if (dropdownMenu) {
      dropdownMenu.style.display = (dropdownMenu.style.display === 'block') ? 'none' : 'block';
    }
  }

  // Close dropdown when clicking outside
  window.onclick = function (event) {
    const dropdownMenu = document.getElementById('dropdownMenu');
    const burgerIcon = document.querySelector('.burger-icon');

    if (dropdownMenu && burgerIcon && !dropdownMenu.contains(event.target) && !burgerIcon.contains(event.target)) {
      dropdownMenu.style.display = 'none';
    }
  };

  // Display current week dates (Mon–Sun)
  function displayCurrentWeek() {
  const today = new Date();
  const dayOfWeek = today.getDay(); // Sunday is 0, Monday is 1, etc.
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - ((dayOfWeek + 6) % 7)); // Adjust so Monday = 0

  const weekIds = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

  for (let i = 0; i < 7; i++) {
    const currentDay = new Date(startOfWeek);
    currentDay.setDate(startOfWeek.getDate() + i);
    const dateText = currentDay.toISOString().split('T')[0].split('-')[2];

    const dayElement = document.getElementById(weekIds[i]);
    if (dayElement) {
      dayElement.textContent = dateText;

      // Highlight the current day
      if (
        currentDay.getDate() === today.getDate() &&
        currentDay.getMonth() === today.getMonth() &&
        currentDay.getFullYear() === today.getFullYear()
      ) {
        dayElement.classList.add('today-box');
      }
    }
  }
}

  // Run on page load
  displayCurrentWeek();

  // 💜 Mood Data from PHP
  const moodTrendData = <?php echo json_encode($moodData); ?>;

  const moodLabels = ["Gloomy", "Sad", "Meh", "Good", "Joyful"];
  const moodLevelCounts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };

  moodTrendData.forEach(item => {
    if (moodLevelCounts[item.mood_level] !== undefined) {
      moodLevelCounts[item.mood_level] = item.count;
    }
  });

  const moodDataArray = [1, 2, 3, 4, 5].map(level => moodLevelCounts[level]);

  // Mood Trends Bar Chart
  const trendCtx = document.getElementById('moodTrendChart')?.getContext('2d');
  if (trendCtx) {
    new Chart(trendCtx, {
      type: 'bar',
      data: {
        labels: moodLabels,
        datasets: [{
          label: 'Mood Frequency',
          data: moodDataArray,
          backgroundColor: ['#3f2776', '#344882', '#92b06e', '#f3ad67', '#ffff8d'],
          borderRadius: 8
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          title: {
            display: true,
            text: 'Most Frequent Mood Levels',
            color: '#2c3e50',
            font: { size: 16 }
          }
        },
        scales: {
          x: { beginAtZero: true, ticks: { color: '#2c3e50' } },
          y: { ticks: { color: '#2c3e50', autoSkip: false } }
        }
      }
    });
  }

  // Mood Distribution Pie Chart
  const pieCtx = document.getElementById('moodDistributionChart')?.getContext('2d');
  if (pieCtx) {
    new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: moodLabels,
        datasets: [{
          label: 'Mood Distribution',
          data: moodDataArray,
          backgroundColor: ['#3f2776', '#344882', '#92b06e', '#f3ad67', '#ffff8d'],
          borderColor: '#fff',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#2c3e50',
              font: { size: 12 }
            }
          },
          title: {
            display: true,
            text: 'Mood Distribution Pie',
            color: '#2c3e50',
            font: { size: 16 }
          }
        }
      }
    });
  }

  // Average Mood Display
  let totalMoods = 0;
  moodTrendData.forEach(item => totalMoods += item.count);

  let maxCount = 0;
  let mostFrequentMood = null;

  moodTrendData.forEach(item => {
    if (item.count > maxCount) {
      maxCount = item.count;
      mostFrequentMood = item.mood_level;
    }
  });

  const moodPercentage = (maxCount / totalMoods) * 100;
  const moodLabelMap = { 1: "Gloomy", 2: "Sad", 3: "Meh", 4: "Good", 5: "Joyful" };
  const moodText = moodLabelMap[mostFrequentMood];

  const displayEl = document.getElementById('mostFrequentMood');
  if (displayEl && moodText && !isNaN(moodPercentage)) {
    displayEl.innerHTML = `The most frequent mood was "<strong>${moodText}</strong>" with a percentage of <strong>${moodPercentage.toFixed(2)}%</strong> of your total mood entries.`;
  }
</script>



    
    
</body>
</html>

