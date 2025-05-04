<?php
session_start();
require 'db_connection.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$sql = "SELECT firstname, lastname, email, birthday, profile_picture,  cover_photo, contact, gender FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id']; // Assuming user ID is in session

    // Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $profileName = 'uploads/' . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profileName);
        // Update DB
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$profileName, $userId]);
    }

    // Handle cover photo upload
    if (!empty($_FILES['cover_photo']['name'])) {
        $coverName = 'uploads/' . basename($_FILES['cover_photo']['name']);
        move_uploaded_file($_FILES['cover_photo']['tmp_name'], $coverName);
        // Update DB
        $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE user_id = ?");
        $stmt->execute([$coverName, $userId]);
    }

    // Refresh the page
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}


// left-column infos
function calculateAge($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}
$age = $user['birthday'] ? calculateAge($user['birthday']) : 'N/A';

// Fetch top 4 mood factors
$moodFactorsQuery = $conn->prepare("
    SELECT mood_factors, COUNT(*) AS count
    FROM mood_tracking
    WHERE user_id = ?
    GROUP BY mood_factors
    ORDER BY count DESC
    LIMIT 4
");
$moodFactorsQuery->bind_param("i", $userId);
$moodFactorsQuery->execute();
$moodFactorsResult = $moodFactorsQuery->get_result();

$topMoodFactors = [];
while ($row = $moodFactorsResult->fetch_assoc()) {
    $factors = explode(',', $row['mood_factors']);
    foreach ($factors as $factor) {
        $factor = trim($factor);
        if (!isset($topMoodFactors[$factor])) {
            $topMoodFactors[$factor] = 0;
        }
        $topMoodFactors[$factor] += $row['count'];
    }
}
arsort($topMoodFactors);
$topMoodFactors = array_slice($topMoodFactors, 0, 4);

// Fetch top 4–8 selected moods
$moodsQuery = $conn->prepare("
    SELECT selected_moods, COUNT(*) AS count
    FROM mood_tracking
    WHERE user_id = ?
    GROUP BY selected_moods
    ORDER BY count DESC
    LIMIT 20
");
$moodsQuery->bind_param("i", $userId);
$moodsQuery->execute();
$moodsResult = $moodsQuery->get_result();

$topMoods = [];
while ($row = $moodsResult->fetch_assoc()) {
    $moods = explode(',', $row['selected_moods']);
    foreach ($moods as $mood) {
        $mood = trim($mood);
        if (!isset($topMoods[$mood])) {
            $topMoods[$mood] = 0;
        }
        $topMoods[$mood] += $row['count'];
    }
}
arsort($topMoods);
$topMoods = array_slice($topMoods, 0, 6);




?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Profile</title>
  <link rel="stylesheet" href="newstyle.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>


    .view-profile-container {
      max-width: 95%;
      height: 80%;
      margin: 20px auto;
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .view-profile-cover-photo {
      position: relative;
      display: flex;
      width: 100%;
      height: 360px;
      margin: 0.3% 0 auto;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      height: 360px;
      width: 100%;
      z-index: 990;
    }

    .view-profile-cover-photo img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border:  12px solid #ececec;
      box-shadow: 8px 8px 8px rgba(0, 0, 0, 0.3);
    }

    .view-profile-img {
      position: absolute ;
      display: flex;
      margin-left: 30px;
      width: 250px;
      height: 250px;
      top: 180px;
      z-index: 999;
    }
    .view-profile-img img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border:  12px solid #ececec;
      box-shadow: 8px 8px 8px rgba(0, 0, 0, 0.3);
    }
    .profie-edit-btn {
      position: absolute;
      bottom: 0;
      right: 0;
      background: #0077b6;
      color: white;
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      cursor: pointer;
    }
    .info-section {
      margin-top: 0;
      padding-left: 22%;
      padding-bottom: 2%;
      margin-bottom: 0.5%;
      text-align: left;
      box-shadow: 0 4px 12px rgba(0,0,0,0.4);

    }
    .info-section h3 {
      margin-bottom: 8px;
      font-size: 34px;
      color: #03045e;
    }
    .info-item {
      margin-bottom: 8px;
      font-size: 14px;
    }
    .info-label {
      font-weight: bold;
      color: #333;
    }
    input[type="file"] {
      display: none;
    }

    .return-button {
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #0077b6;
        color: white;
        border: none;
        text-decoration: none;
        font-size: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: background-color 0.3s ease;
    }

    .return-button:hover {
        background-color: #023e8a;
    }

    .edit-btn {
        position: absolute;
        margin-top: 75%;
        margin-left: 75%;
        cursor: pointer;
        font-size: 300px;
        color: #555;
        border-radius: 50%;
        background-color: #ececec;
        width: 50px;
        height: 50px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    .cover-edit-btn {
        position: absolute;
        margin-top: 3%;
        margin-left: 75%;
        cursor: pointer;
        font-size: 300px;
        color: #555;
        border-radius: 50%;
        background-color: #ececec;
        width: 50px;
        height: 50px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    .edit-btn .material-icons {
        vertical-align: top;
        font-size: 35px;
        padding-left: 8px;
        padding-top: 8px;
    }

    .cover-edit-btn .material-icons {
        vertical-align: top;
        font-size: 35px;
        padding-left: 8px;
        padding-top: 8px;
    }

    .profile-info-container {
        display: flex;
        gap: 20px;  
        padding: 22px;
        height: 70%;
        max-height: 100%;
        max-width: 100%;
    }

    .profile-boxx {
        margin: 12px;
        padding: 12px;
        border-radius: 5px;
        background-color: #e9f1ff;
        border: 1px solid #8fb0e9;
        justify-content: center;
        align-items: center;
    }

    .profile-left-column {
        width: 40%;
        height: 100%;
        background: #f9f9f9;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    .profile-info-container p,h3 {
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .profile-info-container h3 {
        color: #03045e;
    }

    .profile-middle-column {
        width: 60%;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .profile-right-column {
        width: 20%;
        background: #f9f9f9;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        display: inline-block;
        align-items: center;
        justify-content: center;
    }

    .profile-right-column img{
        width: 100%;
        height: 90%;
        object-fit: contain;
        padding: 0;
    }
  
    .mood-shift-factors {
        height: 45%;
    }

    .frequent-moods {
        height: 55%; 
    }

    .mood-shift-factors,
    .frequent-moods {
        background: #ffffff;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
    }

    .mood-shift-factors div,
    .frequent-moods div {
        font-weight: bold;
        font-size: 14px;
    }


  </style>
</head>
<body>
<div class="top-header">
    <div class="left-side">
        <h2 class="app-title">BreatheWell</h2>
    </div>

    <div class="right-side">
        <button onclick="history.back()">Go Back</button>

    </div>
</div>       

<div>
<form method="POST" enctype="multipart/form-data">
  <!-- Cover Photo -->
  <div class="view-profile-cover-photo" style="background-image: url('<?= htmlspecialchars($user['cover_photo'] ?? 'img/default-cover.png') ?>'); background-size: cover; background-position: center;">
    <label for="coverUpload" class="cover-edit-btn" title="Change Cover" style="right: 20px; top: 20px;">
      <span class="material-icons">edit</span>
    </label>
    <input type="file" id="coverUpload" name="cover_photo" onchange="this.form.submit();">
  </div>

  <!-- Profile Image -->
  <div class="view-profile-img">
    <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'img/default-avatar.png') ?>" alt="Profile Picture">
    <label for="profileUpload" class="edit-btn" title="Change Picture">
      <span class="material-icons">edit</span>
    </label>
    <input type="file" id="profileUpload" name="profile_picture" onchange="this.form.submit();">
  </div>
</form>



  <div class="info-section">
    <h3><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h3>
    <div class="info-item"><span class="info-label">email:</span> <?= htmlspecialchars($user['email']) ?></div>
    <div class="info-item"><span class="info-label">birthday:</span> <?= htmlspecialchars($user['birthday']) ?></div>
  </div>

</div>

<div class="profile-info-container">
  <!-- Left Column -->
  <div class="profile-left-column">
    <h3>User Information</h3>
    <div class="profile-boxx">
    <p><strong>Full Name:</strong> <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></p>
    <hr>
    <p><strong>Birthday:</strong> <?= htmlspecialchars($user['birthday']) ?></p>
    <hr>
    <p><strong>Age:</strong> <?= $age ?></p>
    <hr>
    <p><strong>Gender:</strong> <?= htmlspecialchars($user['gender'] ?? 'N/A') ?></p>
    <hr>
    <p><strong>Contact:</strong> <?= htmlspecialchars($user['contact'] ?? 'N/A') ?></p>
    <hr>

    <!-- Edit button -->
    <button onclick="document.getElementById('editModal').style.display='block'" class="return-button">Edit Personal Information</button>

    </div>
  </div>



  <!-- Right Column -->
  <div class="profile-middle-column">
    <!-- Top Right Section: Positive Mood Shifts -->
    <div class="mood-shift-factors">
        <h3>Factors Linked to Mood Shifts</h3>
        <div class="profile-boxx">
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($topMoodFactors as $factor => $count): ?>
                <div style="flex: 1 1 45%; padding: 10px; color: #fff; background: #4e5d9d; border-radius: 8px; text-align: center;">
                    <?= htmlspecialchars($factor) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>


    <!-- Bottom Right Section: Frequent Positive Thoughts -->
    <div class="frequent-moods">
        <h3>Most Frequent Moods</h3>
        <div class="profile-boxx">
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($topMoods as $mood => $count): ?>
                <div style="flex: 1 1 30%; padding: 10px; color: #fff; background: #4e5d9d; border-radius: 15px; text-align: center;">
                    <?= htmlspecialchars($mood) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

  </div>


                
</div>


<!-- Edit Profile Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.6); z-index: 9999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:40%; border-radius:10px; position:relative;">
    <h2>Edit Your Information</h2>
    <form method="POST" action="update_profile.php">
      <label>First Name:</label>
      <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required><br><br>

      <label>Last Name:</label>
      <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required><br><br>

      <label>Email:</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br><br>

      <label>Birthday:</label>
      <input type="date" name="birthday" value="<?= htmlspecialchars($user['birthday']) ?>"><br><br>

      <label>Gender:</label>
      <select name="gender">
        <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
        <option value="Other" <?= $user['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
      </select><br><br>

      <label>Contact:</label>
      <input type="text" name="contact" value="<?= htmlspecialchars($user['contact']) ?>"><br><br>

      <button type="submit" class="return-button">Save Changes</button>
      <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="return-button" style="background-color:#999;">Cancel</button>
    </form>
  </div>
</div>

<!-- Mood Image Selection Modal -->
<div id="moodImageModal" class="modal" style="display:none; position:fixed; z-index:1000; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6);">
  <div style="background:#fff; padding:20px; max-width:600px; margin:10% auto; border-radius:10px; position:relative;">
    <h3>Select Your Favorite Mood</h3>
    <form method="post" action="update_favorite_mood.php">
      <div style="display: flex; flex-wrap: wrap; gap: 15px;">
        <?php
        $imgQuery = $conn->query("SELECT * FROM mood_images");
        while ($row = $imgQuery->fetch_assoc()):
        ?>
        <label style="cursor:pointer;">
          <input type="radio" name="favorite_mood_image_id" value="<?= $row['id'] ?>" style="display:none;" required>
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['label']) ?>"
               style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:3px solid transparent;"
               onClick="this.parentNode.querySelector('input').checked = true;">
          <div style="text-align:center; font-weight:bold;"><?= htmlspecialchars($row['label']) ?></div>
        </label>
        <?php endwhile; ?>
      </div>
      <br>
      <button type="submit" class="return-button">Save Mood</button>
      <button type="button" class="return-button" style="background:#999;" onclick="document.getElementById('moodImageModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>


</body>
</html>
