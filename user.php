<?php
session_start();

include 'db_connection.php'; 


if (!isset($_SESSION['firstname']) || !isset($_SESSION['lastname'])) {
  echo "Name information is missing. Please log in again.";
  exit();
}

$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$profileImg = $_SESSION['profile_picture'] ?? 'img/default-avatar.png'; 

$conn = new mysqli("localhost", "root", "", "sample_db");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$announcements = [];
$result = $conn->query("SELECT * FROM announcements");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
  }
}

// Functions

if (!function_exists('formatLink')) {
    function formatLink($link) {
        $link = trim($link);
        $link = preg_replace('/^http:\/\//i', 'https://', $link);

        if (preg_match('/^https?:\/\/youtu\.be\/([^\?&]+)/', $link, $matches)) {
            return 'https://youtu.be/' . $matches[1];
        }

        if (preg_match('/^https?:\/\/(www\.)?youtube\.com/', $link)) {
            return preg_replace('/^http:\/\//i', 'https://', $link);
        }

        if (!preg_match('/^https?:\/\//', $link)) {
            return 'https://' . ltrim($link, '/');
        }

        return $link;
    }
}

function getYouTubeThumbnail($url) {
    if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $matches) || 
        preg_match('/youtube\.com.*[?&]v=([^&]+)/', $url, $matches)) {
        return 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
    }
    return null;
}

function getSpotifyThumbnail($url) {
    $embedUrl = "https://open.spotify.com/oembed?url=" . urlencode($url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $embedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // Avoid being blocked

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response !== false) {
        $data = json_decode($response, true);
        return $data['thumbnail_url'] ?? null;
    }

    return null;
}

// Fetch resources from database
$result = $conn->query("SELECT * FROM resources");
$resources = $result->fetch_all(MYSQLI_ASSOC);

// Group by type
$groupedResources = [];
foreach ($resources as $res) {
    $groupedResources[$res['type']][] = $res;
}

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

// Current date info
$currentDateTime = date('l, F j, Y h:i A');
$currentMonth = date('F');

// Fetch assessments (questions) along with their topics
$assessments = [];
$result = $conn->query("SELECT * FROM assessments ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $assessments[] = $row;
  }
}

$userId = $_SESSION['user_id']; // Make sure you're storing this in session

$categoryAverages = [];
$weeklyData = [];
$monthlyData = [];

// Fetch answer frequencies for each assessment
$sql = "SELECT assessment_id, rating, COUNT(*) as frequency, DATE(submitted_at) as date 
        FROM assessment_answers 
        WHERE user_id = ? 
        GROUP BY assessment_id, rating, DATE(submitted_at) 
        ORDER BY assessment_id, date";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $qid = $row['assessment_id'];
    $answer = $row['rating'];
    $freq = $row['frequency'];
    $date = $row['date'];

    $topicResult = $conn->query("SELECT topic FROM assessments WHERE id = $qid");
    $topic = $topicResult->fetch_assoc()['topic'];

    if (!isset($categoryAverages[$topic])) {
        $categoryAverages[$topic] = ['total' => 0, 'count' => 0];
    }

    $categoryAverages[$topic]['total'] += $answer * $freq;
    $categoryAverages[$topic]['count'] += $freq;

    // Calculate weekly and monthly data
    $weekKey = date("W-Y", strtotime($date)); // Week and Year
    $monthKey = date("M-Y", strtotime($date)); // Month and Year

    if (!isset($weeklyData[$weekKey])) {
        $weeklyData[$weekKey] = [];
    }
    if (!isset($monthlyData[$monthKey])) {
        $monthlyData[$monthKey] = [];
    }

    if (!isset($weeklyData[$weekKey][$topic])) {
        $weeklyData[$weekKey][$topic] = [];
    }
    if (!isset($monthlyData[$monthKey][$topic])) {
        $monthlyData[$monthKey][$topic] = [];
    }

    if (!isset($weeklyData[$weekKey][$topic][$answer])) {
        $weeklyData[$weekKey][$topic][$answer] = 0;
    }
    if (!isset($monthlyData[$monthKey][$topic][$answer])) {
        $monthlyData[$monthKey][$topic][$answer] = 0;
    }

    $weeklyData[$weekKey][$topic][$answer] += $freq;
    $monthlyData[$monthKey][$topic][$answer] += $freq;
}

// Calculate average for each category
$categoryAvgData = [];
foreach ($categoryAverages as $topic => $data) {
    $average = $data['total'] / $data['count'];
    $categoryAvgData[$topic] = $average;
}

// Find the category with the highest average
arsort($categoryAvgData);
$topCategories = array_keys($categoryAvgData);

$highestAvgCategory = $topCategories[0] ?? 'N/A';
$secondHighestCategory = $topCategories[1] ?? 'N/A';
$thirdHighestCategory = $topCategories[2] ?? 'N/A';

$categoryMessages = [
  'physical' => 'Stay active and maintain healthy habits for physical well-being.',
  'mental' => 'Practice mindfulness and manage stress to support mental health.',
  'social' => 'Engage in positive social interactions and build strong relationships.',
  'financial' => 'Create a budget and build financial stability for peace of mind.',
  'educational' => 'Continue learning and expanding your knowledge and skills.',
  'occupational' => 'Strive for work-life balance and career satisfaction.',
];



//library

// Handle diary form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['diary_entry'])) {
    $entry = $_POST['diary_entry'];
    $stmt = $conn->prepare("INSERT INTO personal_diary (user_id, entry) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $entry);
    $stmt->execute();
    $stmt->close();
}

// Handle diary entry deletion
if (isset($_GET['delete_entry_id'])) {
    $entryId = $_GET['delete_entry_id'];
    $stmt = $conn->prepare("DELETE FROM personal_diary WHERE user_id = ? AND id = ?");
    $stmt->bind_param("ii", $userId, $entryId);
    $stmt->execute();
    $stmt->close();
    header("Location: user_library.php");  // Redirect to avoid resubmission on refresh
    exit();
}

// Fetch mood notes
$moodNotes = [];
$result = $conn->query("SELECT note, timestamp FROM mood_tracking WHERE user_id = $userId AND note IS NOT NULL ORDER BY timestamp DESC");
while ($row = $result->fetch_assoc()) {
    $moodNotes[] = $row;
}

// Fetch personal diary entries
$diaryEntries = [];
$result = $conn->query("SELECT id, entry, timestamp FROM personal_diary WHERE user_id = $userId ORDER BY timestamp DESC");
while ($row = $result->fetch_assoc()) {
    $diaryEntries[] = $row;
}

//hotlines

// Fetch all categories
$category_query = $conn->query("SELECT id, name FROM hotline_categories ORDER BY name ASC");

$categories = [];
while ($cat = $category_query->fetch_assoc()) {
    $categories[$cat['id']] = $cat['name'];
}

// Fetch all hotlines
$hotlines_query = $conn->query("SELECT * FROM hotlines ORDER BY category_id, name");
$hotlines = [];
while ($hotline = $hotlines_query->fetch_assoc()) {
    $hotlines[$hotline['category_id']][] = $hotline;
}

//originals
$originals = $conn->query("SELECT * FROM originals LIMIT 15");

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>BreatheWell</title>
    <link rel="stylesheet" href="newstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      html {
        scroll-behavior: smooth;
      }

      .library-bg {
        background-color: #ececec;
        background-image:  linear-gradient(rgba(68,76,247,0.3) 2px, transparent 2px), linear-gradient(90deg, rgba(68,76,247,0.3) 2px, transparent 2px), linear-gradient(rgba(68,76,247,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(68,76,247,0.5) 1px,rgba(229, 229, 247, 0) 1px);
        background-size: 300px 300px, 300px 300px, 100px 100px, 100px 100px;
        background-position: -2px -2px, -2px -2px, -1px -1px, -1px -1px;
      }

      .library-content {
        position: relative;
        z-index: 1;
      }

      .resource-bg {
        /*background: linear-gradient(to right, #d4d4d4, #ffffff);
        background: linear-gradient(135deg,rgba(68, 76, 247, 0.19) 25%, transparent 25%) -25px 0/ 50px 50px, linear-gradient(225deg,rgba(68, 77, 247, 0.4) 25%, transparent 25%) -25px 0/ 50px 50px, linear-gradient(315deg,rgba(68, 77, 247, 0.19) 25%, transparent 25%) 0px 0/ 50px 50px, linear-gradient(45deg,rgba(68, 77, 247, 0.4) 25%,rgba(229, 229, 247, 0) 25%) 0px 0/ 50px 50px;
        */
        background-color: #023e8a; 
      }



    </style>
</head>

<body>
<!-- New Header Above Navbar -->
<div class="top-header">
    <div class="left-side">
      <div style="display: flex; align-items: center; gap: 8px;">
          <img src="img/logo white.png" alt="Logo" class="app-logo">
          <h2 class="app-title">BreatheWell</h2>
      </div>
    </div>

    <div class="right-side">
        <ul class="navbar-links">
            <li><a href="#">About Us</a></li>
        </ul>
    </div>
</div>    

<!-- Top Navbar -->
<div class="top-navbar">
  <div class="left-side">
    <ul class="navbar-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#resources">Resources</a></li>
      <li><a href="#myday">My Day</a></li>
      <li><a href="#assessment">Assessment</a></li>
      <li><a href="#library">Library</a></li>
      <li><a href="#hotlines">Hotlines</a></li>
    </ul>
  </div>

  <div class="right-side">
    <div class="icon-group">

        <!-- Help icon stays -->
        <a href="inquiry.php" title="Ask a Question">
            <span class="material-icons">help_outline</span>
        </a>

        <!-- Existing profile icon and dropdown -->

        <a href="profile.php" title="View Profile">
            <span class="material-icons">person</span>
        </a>

        <div class="dropdown">
            <span class="material-icons dropdown-toggle" onclick="toggleSettingsDropdown()" title="Settings">settings</span>

            <div id="settingsDropdown" class="dropdown-menu">
                <ul>
                  <li>
                    <a href="toggle_otp.php">
                      <?= ($_SESSION['otp_enabled'] ?? 1) ? "Turn off OTP" : "Enable OTP" ?>
                    </a>
                  </li>
                  <li><a href="change_pass.php">Change Password</a></li>
                  <li><a href="login.php">Logout</a></li>
                </ul>
            </div>
        </div>

    </div>
  </div>

</div>
<!-- END OF TOP NAVBAR -->

<!-- SECTIONS -->

<!-- Home Section -->
<section id="home" style="padding-bottom: 0;">
  <div class="welcome-beks">
      <div class="beks">
        <img src="img/logo.png" alt="logo-blue">
      </div>
      <h1>Welcome to BreatheWell</h1>
      <p>A Safe Space for Your Mind and Emotions</p>
  </div>

  <div class="blu-divider">
  </div>

    <!-- Announcements Section -->
    <div class="announcements" style="padding: 60px 20px 60px 20px;">
      <h3 style="padding-bottom: 0; color: #354297; font-size: 40px; text-align: center;">Latest Announcements</h3>
      <div class="recommended">
        <div class="slides">
            <div class="slide">
                <img src="img/brokkk.png" alt="Image 1">
            </div>
            <div class="slide">
                <img src="img/brokkk2.png" alt="Image 2">
            </div>
            <div class="slide">
                <img src="img/brokkk.png" alt="Image 3">
            </div>
            <div class="slide">
                <img src="img/brokkk2.png" alt="Image 4">
            </div>
        </div>
      </div>
      <?php if (!empty($announcements)): ?>
          <ul style="list-style: none; padding: 0; gap: 10px;">
          <?php foreach ($announcements as $a): ?>
              <li style="background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.8);">
              <h4 style="margin: 0 0 10px; color: #2c3e50; font-size: 35px;"><?= htmlspecialchars($a['title']) ?></h4>
              <p style="margin: 0; color: #555;"><?= htmlspecialchars($a['message']) ?></p>
              </li>
          <?php endforeach; ?>
          </ul>
      <?php else: ?>
          <p>No announcements yet.</p>
      <?php endif; ?>
    </div>
    <div class="announcement-cta">
        <h3 style="font-style: oblique; text-align: center; color: #2c3e50;">Take a breath. Check in with yourself. We're here to support you.</h3>
          <div class="ann-cta-buttons">
            <a href="user.php#myday" class="ann-cta-button">Start a Mood Check-In</a>
            <a href="user.php#resources" class="ann-cta-button">Explore Mental Health Resources</a>
            <a href="user.php#hotlines" class="ann-cta-button">Explore Our Hotlines</a>
          </div>
    </div>
    <div class="blu-divider2">
    </div>
</section>

<!-- Resources Section -->
<section id="resources" class="resource-bg">
  <h1 style="color: #ffc918; text-align: center; font-size: 70px;  font-style: oblique;">May is Mental Health<br>Awareness Month</h1>
  <h2 style= "text-align: center; color: #ececec;">Your mental well-being matters — this month and every month.</h2>
  <p style="text-align: center; color: #ececec; padding: 30px 200px 10px 200px; font-weight: 300;">At BreatheWell, we believe in creating a safe space where you can explore, 
    learn, and grow. Mental Health Awareness Month is a time to raise understanding, 
    reduce stigma, and empower you to take positive steps toward your emotional 
    wellness.
    <br><br><br>
    Browse our carefully curated resources — from coping strategies and self-care 
    tips to professional support networks — designed to guide you through your 
    journey with care and compassion.
    <br><br><br>
    <span style="font-weight: bold;">Take the first step toward clarity, calm, and connection.</span>
  </p>
  <div class="featured">
    <h1 style="text-align: center; padding: 20px; font-size: 24px; color: #252525;">Mental Health Month Toolkit</h1>
        <div class="featured-rsc">
          <div class="featr-box">
            <h3 style="color: #252525;">Box Breathing Visual Tool</h3>
            <a href="https://xhalr.com/">
            <img src="img/calm.png" alt="calm">
            </a>
          </div>
          <div class="featr-box">
            <h3 style="color: #252525;">Calm's Breathing Bubble Tool</h3>
            <a href="https://www.youtube.com/watch?v=nmFUDkj1Aq0">
            <img src="img/calm.png" alt="calm">
            </a>
          </div>
        </div>
       
  </div>
  <div class="resources-section">
      <?php
          $defaultThumbnails = [
              'Podcast' => 'img/podcast-icon.png',
              'Video' => 'img/video-icon.png',
              'Article' => 'img/article-icon.png'
          ];
          $index = 0;

          foreach ($groupedResources as $type => $items):
              $sliderId = 'slider-' . $index;
      ?>
        <div class="resources-category">
          <h3><?= htmlspecialchars($type) ?>s</h3>
          
          <!-- Slider wrapper with buttons -->
          <div class="slider-wrapper">
            <button class="slider-btn left" onclick="scrollSlider('<?= $sliderId ?>', -1)">❮</button>
            
            <div class="resource-slider" id="<?= $sliderId ?>">
              <?php foreach ($items as $res):
                  $rawLink = isset($res['url']) ? trim($res['url']) : '';
                  $link = !empty($rawLink) ? formatLink($rawLink) : '#';
                  $thumbnail = '';

                  if ($link !== '#' && filter_var($link, FILTER_VALIDATE_URL)) {
                    if (preg_match('/youtube\.com|youtu\.be/', $link)) {
                      $thumbnail = getYouTubeThumbnail($link);
                    } elseif (preg_match('/spotify\.com/', $link)) {
                      $thumbnail = getSpotifyThumbnail($link);
                    }
                  }

                  if (!$thumbnail) {
                    $thumbnail = 'img/default.png';
                  }

                  $cleanTitle = preg_replace('/https?:\/\/[^\s]+/', '', $res['title']);
              ?>
                <a href="<?= htmlspecialchars($link) ?>" target="_blank">
                  <div class="resource-card" style="background-image: url('<?= htmlspecialchars($thumbnail) ?>');">
                    <div class="play-icon">▶</div>
                    <p><?= htmlspecialchars(trim($cleanTitle)) ?><br><small>(<?= htmlspecialchars($res['type']) ?>)</small></p>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
            
            <button class="slider-btn right" onclick="scrollSlider('<?= $sliderId ?>', 1)">❯</button>
          </div>
        </div>
      <?php $index++; endforeach; ?>
  </div>

</section>
<div class="blu-divider2">
</div>
  <section id="originals">
    <div class="originals-container">
      <?php while ($row = $originals->fetch_assoc()): ?>
        <div class="original-item">
          <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank">
              <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Original Image">
          </a>
          <h4><?= htmlspecialchars($row['title']) ?></h4>
        </div>
      <?php endwhile; ?>
    </div>
  </section>
<div class="blu-divider2">
</div>
<!-- My Day Section -->
<section id="myday">

  <div class="myday-content">
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
            <center>
            <canvas id="moodDistributionChart"></canvas>
            </center>
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

</section>

<!-- Assessment Section -->
<section id="assessment" class="resource-bg">
    <div class="assessment-container">
        <!-- Pie Chart on Left -->
        <div class="assessment-baks">
            <h3 style="color: #03045e;">Categories Affecting Your Mental Well-Being</h3>
            <canvas id="categoryChart" ></canvas>
                <!-- Start Assessment Button -->
            <div class="assessment-cta">
                <p>Ready to take the assessment? Click below to start.</p>
                <a href="assessment.php" class="btn-assessment">Start Assessment</a>
            </div>
        </div>

        <!-- Top Rating Categories Boxes on Right -->
        <div class="top-rating-boxes">
          <?php foreach ([$highestAvgCategory, $secondHighestCategory, $thirdHighestCategory] as $category): ?> 
              <a href="recommendations.php?category=<?= urlencode($category) ?>" class="top-rating-box-link" style="text-decoration: none; color: inherit;">
                  <div class="top-rating-box">
                      <h3><?= htmlspecialchars($category) ?? 'No Data' ;?> </h3>
                      <p><?= $categoryMessages[$category] ?? 'No Data' ?></p>
                      
                  </div>
              </a>
          <?php endforeach; ?>
      </div>

    </div>
</section>

<!-- Library Section -->
<section id="library" class="library-bg">
  <div class="library-content">

  <div class="library-wrapper">
    <!-- Left: Mood Notes -->
    <div class="library-left">
      <h3 style="color: #03045e;">Mood Notes</h3>
      <?php foreach ($moodNotes as $note): ?>
        <div class="note-card">
          <h4><?php echo date("F j, Y g:i A", strtotime($note['timestamp'])); ?></h4>
          <p><?php echo htmlspecialchars($note['note']); ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Diary + Entry -->
    <div class="library-right">
          <h3 style="color: #03045e;">Personal Diary</h3>

          <div class="notepad-container">
              <a href="add_diary_entry.php" class="add-entry-button">Add a Diary Entry</a>
          </div>

          <?php foreach ($diaryEntries as $entry): ?>
          <div class="note-card">
              <h4><?php echo date("F j, Y g:i A", strtotime($entry['timestamp'])); ?></h4>
              <p><?php echo htmlspecialchars($entry['entry']); ?></p>
              <a href="?delete_entry_id=<?php echo $entry['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this entry?');">x</a>
          </div>
          <?php endforeach; ?>

    </div>
  </div>
</section>

<!-- Hotlines Section -->
<section id="hotlines" style="background-color: #fff;">
    <div class="hotline-container">
        <h2 class="section-title" style="color: #03045e;">Hotlines</h2>

        <?php if (empty($hotlines)): ?>
            <p>No hotlines available.</p>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <?php foreach ($categories as $category_id => $category_name): ?>
                    <?php if (isset($hotlines[$category_id])): ?>
                        <div style="flex: 1; min-width: 280px; background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 15px;">
                            <h3><?= htmlspecialchars($category_name); ?></h3>
                            <?php foreach ($hotlines[$category_id] as $hotline): ?>
                                <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                                    <strong><?= htmlspecialchars($hotline['name']); ?></strong><br>
                                    <span><?= htmlspecialchars($hotline['number']); ?></span><br>
                                    <small><?= htmlspecialchars($hotline['description']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
              <img src="img/fb-icon.png" alt="Facebook" />
            </a>
            <a href="#" target="_blank">
              <img src="img/insta-icon.png" alt="Instagram" />
            </a>
            <a href="#" target="_blank">
              <img src="img/x-icon.png" alt="Twitter" />
            </a>
          </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2025 <span>BreatheWell</span>. All rights reserved.</p>
    </div>
</footer>

<!-- END SECTIONS -->

<script>

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
}

function toggleSettingsDropdown() {
    const settingsDropdown = document.getElementById("settingsDropdown");
    const isVisible = settingsDropdown.classList.contains("show");
    closeAllDropdowns();
    if (!isVisible) {
        settingsDropdown.classList.add("show");
    }
}

function toggleProfileDropdown() {
    const profileDropdown = document.getElementById("profileDropdown");
    const isVisible = profileDropdown.classList.contains("show");
    closeAllDropdowns();
    if (!isVisible) {
        profileDropdown.classList.add("show");
    }
}

// Close dropdowns when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-toggle')) {
        closeAllDropdowns();
    }
});



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

//assessment chart

const categoryChartCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryChartCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($categoryAvgData)); ?>,
            datasets: [{
                label: 'Average Ratings per Category',
                data: <?php echo json_encode(array_values($categoryAvgData)); ?>,
                backgroundColor: ['#3498db', '#e74c3c', '#f39c12', '#2ecc71', '#9b59b6'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
        },
    });

    document.getElementById('dataSelect').addEventListener('change', function() {
        var selectedValue = this.value;
        // Handle logic for switching between weekly/monthly data
        console.log("Data view switched to:", selectedValue);
    });
</script>
<script src="script.js"></script>

</body>
</html>
