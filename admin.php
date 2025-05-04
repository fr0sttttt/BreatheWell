<?php
session_start();

require 'db_connection.php';

// Fetch all users with role name
$usersResult = $conn->query("SELECT users.user_id, users.firstname, users.lastname, user_roles.role_name 
                             FROM users 
                             LEFT JOIN user_roles ON users.role_id = user_roles.userrole_id");

if (!isset($_SESSION['firstname']) || !isset($_SESSION['lastname'])) {
  echo "Name information is missing. Please log in again.";
  exit();
}

// Function to extract YouTube URL from a given string (like title)
function getYouTubeLink($title) {
  if (preg_match('/https:\/\/(www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $title, $matches)) {
      return $matches[0]; // Full URL to YouTube video
  }
  return null; // If no YouTube link found
}

// Handle Add
if (isset($_POST['add'])) {
    if (!empty($_POST['title']) && !empty($_POST['type']) && !empty($_POST['url'])) {
        $title = $_POST['title'];
        $type = $_POST['type'];
        $url = $_POST['url'];

        $stmt = $conn->prepare("INSERT INTO resources (title, type, url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $type, $url);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "<script>alert('Please fill in Title, URL, and Type.');</script>";
    }
}


// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $type = $_POST['type']; // changed from 'category'
    $stmt = $conn->prepare("UPDATE resources SET title = ?, type = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $type, $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all
$result = $conn->query("SELECT * FROM resources");
$resources = $result->fetch_all(MYSQLI_ASSOC);

// Handle Add Announcement
if (isset($_POST['add_announcement'])) {
  $title = $_POST['announcement_title'];
  $message = $_POST['announcement_message'];
  $stmt = $conn->prepare("INSERT INTO announcements (title, message) VALUES (?, ?)");
  $stmt->bind_param("ss", $title, $message);
  $stmt->execute();
  $stmt->close();
}

// Handle Delete Announcement
if (isset($_GET['delete_announcement'])) {
  $id = $_GET['delete_announcement'];
  $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Handle Edit Announcement
if (isset($_POST['edit_announcement'])) {
  $id = $_POST['announcement_id'];
  $title = $_POST['announcement_title'];
  $message = $_POST['announcement_message'];
  $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ? WHERE id = ?");
  $stmt->bind_param("ssi", $title, $message, $id);
  $stmt->execute();
  $stmt->close();
}

// Fetch all announcements
$announcementsResult = $conn->query("SELECT * FROM announcements ORDER BY date_posted DESC");

// Count total users and group by role
$roleCountsResult = $conn->query("SELECT user_roles.role_name, COUNT(*) as count 
                                  FROM users 
                                  LEFT JOIN user_roles ON users.role_id = user_roles.userrole_id 
                                  GROUP BY role_name");

$roleCounts = [];
while ($row = $roleCountsResult->fetch_assoc()) {
    $roleCounts[] = $row;
}

// Fetch assessments (use existing $conn from db_connection.php)
$assessments = [];
$assessment_query = "SELECT * FROM assessments ORDER BY created_at DESC";
$assessment_result = $conn->query($assessment_query);
if ($assessment_result && $assessment_result->num_rows > 0) {
  while ($row = $assessment_result->fetch_assoc()) {
    $assessments[] = $row;
  }
}

// Fetch hotlines
$categories = $conn->query("SELECT id, name FROM hotline_categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$hotlines = $conn->query("SELECT h.*, c.name AS category_name FROM hotlines h JOIN hotline_categories c ON h.category_id = c.id ORDER BY c.name, h.name")->fetch_all(MYSQLI_ASSOC);

$hotlines_sql = "SELECT h.id, h.name, h.number, h.description, hc.name AS category
                 FROM hotlines h
                 LEFT JOIN hotline_categories hc ON h.category_id = hc.id
                 ORDER BY h.id DESC";

$result = $conn->query($hotlines_sql);

if (!$result) {
    die("Error fetching hotlines: " . $conn->error);
}

$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$profileImg = $_SESSION['profile_picture'] ?? 'img/default-avatar.png'; 

//originals 

if (isset($_POST['add_original'])) {
  $title = $_POST['title'];
  $url = $_POST['url'];
  $image = $_FILES['image'];

  if ($image['error'] === UPLOAD_ERR_OK) {
      $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
      $newName = uniqid() . '.' . $ext;
      $destination = 'uploads/' . $newName;
      move_uploaded_file($image['tmp_name'], $destination);

      $stmt = $conn->prepare("INSERT INTO originals (title, image_url, url) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $title, $destination, $url);
      $stmt->execute();
  }   



  if (isset($_POST['delete_original'])) {
      $id = $_POST['original_id'];
      $stmt = $conn->prepare("DELETE FROM originals WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
  }
}

// Fetch originals
$originals = $conn->query("SELECT * FROM originals ORDER BY id DESC");

// Handle edit form submission
if (isset($_POST['edit_original'])) {
$id = $_POST['original_id'];
$newTitle = $_POST['edit_title'];
$newImage = $_FILES['edit_image'];

if ($newImage['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($newImage['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . '.' . $ext;
    $destination = 'uploads/' . $newName;
    move_uploaded_file($newImage['tmp_name'], $destination);

    $stmt = $conn->prepare("UPDATE originals SET title = ?, image_url = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newTitle, $destination, $id);
} else {
    $stmt = $conn->prepare("UPDATE originals SET title = ? WHERE id = ?");
    $stmt->bind_param("si", $newTitle, $id);
}
$stmt->execute();
header("Location: " . $_SERVER['PHP_SELF']);
exit();
}

$userCountQuery = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$userCount = $userCountQuery->fetch_assoc()['total_users'];


?>

<!DOCTYPE html>
<html>
<head>
    <title>BreatheWell</title>
    <link rel="stylesheet" href="newstyle.css">
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
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
      <li><a href="#dashboard">Dashboard</a></li>
      <li><a href="#resources">Resources</a></li>
      <li><a href="#announcement">Announcement</a></li>
      <li><a href="#assessment">Assessment</a></li>
      <li><a href="#hotline">Hotlines</a></li>
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

<section id="dashboard">
    <div class="dashboard-box">
      <div class="box-left">
        <h2>User Role Distribution</h2>
        <canvas id="userChart"></canvas>
      </div>

      <div class="box-right">
        <h2>Total Users</h2>
        <p class="user-count"><?= htmlspecialchars($userCount) ?></p>
      </div>
    </div>
    <!-- User Count Graph Section -->




    <!-- User Management Section -->

    <div class="user-crud-section">
        <h2 style="color: #03045e;">Users</h2>
        <button onclick="document.getElementById('addUserModal').style.display='block'" class="btn">Add New User</button>

          <!-- Modal Overlay -->
          <div id="addUserModal" class="modal">
              <div class="modal-content">
                  <span onclick="document.getElementById('addUserModal').style.display='none'" class="close">&times;</span>
                    <h2>Add New User</h2>
                    <form method="POST">
                        <input type="hidden" name="add_user" value="1">
                        <label>First Name:</label>
                        <input type="text" name="firstname" required><br>

                        <label>Last Name:</label>
                        <input type="text" name="lastname" required><br>

                        <label>Email:</label>
                        <input type="email" name="email" required><br>

                        <label>Password:</label>
                        <input type="password" name="password" required><br>

                        <label>Birthday:</label>
                        <input type="date" name="birthday" required><br>

                        <label>Role:</label>
                        <select name="role_id" required>
                            <?php
                            $result = $conn->query("SELECT * FROM user_roles");
                            while ($role = $result->fetch_assoc()) {
                                echo "<option value='{$role['userrole_id']}'>{$role['role_name']}</option>";
                            }
                            ?>
                        </select><br><br>

                        <button type="submit">Add User</button>
                    </form>
                </div>
            </div>
            <div class="user-table-wrapper">
              <table border="1">
                <tr>
                  <th>User ID</th>
                  <th>Firstname</th>
                  <th>Lastname</th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
                <?php while ($row = $usersResult->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['user_id'] ?></td>
                  <td><?= htmlspecialchars($row['firstname']) ?></td>
                  <td><?= htmlspecialchars($row['lastname']) ?></td>
                  <td><?= htmlspecialchars($row['role_name']) ?></td>
                  <td>
                    <a href="edit_user.php?id=<?= $row['user_id'] ?>">Edit</a> |
                    <a href="delete_user.php?user_id=<?= $row['user_id'] ?>" 
                      onclick="return confirm('Are you sure you want to delete this user?');">
                      Delete
                    </a>            
                  </td>
                </tr>
                <?php endwhile; ?>
              </table>
            </div>
    </div>
</section>

<section id="resources">
    <!-- Resource Management Section -->

    <div class="user-crud-section">
      <h2>Resource Management</h2>
        <button id="openAddResourceBtn" style="background-color: #446592; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">
            Add New Resource
        </button>

                <!-- Overlay Form (Hidden at first) -->
        <div id="addResourceOverlay" class="overlay">
            <div class="overlay-content">
                <h2>Add New Resource</h2>
                <form action="add_resource.php" method="post" class="resource-form">
                    <input type="text" id="title" name="title" placeholder="Input Title" required>
                    <input type="url" id="url" name="url" placeholder="Input URL" required>
                    <select id="type" name="type" required>
                        <option value="">Select type</option>
                        <option value="Video">Video</option>
                        <option value="Podcast">Podcast</option>
                        <option value="Article">Article</option>
                    </select>
                    <div style="margin-top: 20px;">
                        <button type="submit" style="background-color: #446592; color:white; padding:8px 16px; border:none; border-radius:5px;">Add Resource</button>
                        <button type="button" id="closeAddResourceBtn" style="background-color: #ccc; color:black; padding:8px 16px; border:none; border-radius:5px; margin-left:10px;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="resource-table-wrapper">
          <table class="admin-resource-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Link</th>
                <th>Edit</th>
                <th>Delete</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($resources as $res): ?>
              <tr>
                <td><?= htmlspecialchars($res['title']) ?></td>
                <td><?= htmlspecialchars($res['type']) ?></td>
                <td>
                  <a href="<?= htmlspecialchars($res['url']) ?>" target="_blank" style="color:#00b4d8; text-decoration:underline;">
                    View
                  </a>
                </td>
                <td>
                  <form method="POST" style="display:flex; flex-direction:column; gap:5px;">
                    <input type="hidden" name="id" value="<?= $res['id'] ?>">
                    <input type="text" name="title" value="<?= htmlspecialchars($res['title']) ?>" required>
                    <select name="type" required>
                      <option value="Podcast" <?= $res['type'] == 'Podcast' ? 'selected' : '' ?>>Podcast</option>
                      <option value="Video" <?= $res['type'] == 'Video' ? 'selected' : '' ?>>Video</option>
                      <option value="Article" <?= $res['type'] == 'Article' ? 'selected' : '' ?>>Article</option>
                    </select>
                    <button type="submit" name="edit" style="background-color:#00a896; color:white;">Update</button>
                  </form>
                </td>
                <td>
                  <a href="?delete=<?= $res['id'] ?>" 
                    onclick="return confirm('Are you sure you want to delete this resource?');" 
                    style="color:#fff; font-weight:bold;">
                    Delete
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

    </div>






</section>

<section id="originals">

  <h2 style="text-align:center;">Manage Originals</h2>

  <div class="original-form">
      <form action="" method="POST" enctype="multipart/form-data">
          <input type="text" name="title" placeholder="Original title" required>
          <input type="text" name="url" placeholder="External URL (optional)">
          <input type="file" name="image" accept="image/*" required>
          <button type="submit" name="add_original">Add Original</button>
      </form>
  </div>

  <div class="originals-grid-wrapper">
    <div class="originals-grid">
      <?php while ($row = $originals->fetch_assoc()): ?>
        <div class="original-card">
          <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Original Image">
          <h4><?= htmlspecialchars($row['title']) ?></h4>

          <form method="POST" class="delete-form">
            <input type="hidden" name="original_id" value="<?= $row['id'] ?>">
            <button type="submit" name="delete_original">Delete</button>
          </form>

          <button type="button" onclick="toggleEditForm(<?= $row['id'] ?>)">Edit</button>

          <form method="POST" enctype="multipart/form-data" class="edit-form" id="edit-form-<?= $row['id'] ?>" style="display: none;">
            <input type="hidden" name="original_id" value="<?= $row['id'] ?>">
            <input type="text" name="edit_title" value="<?= htmlspecialchars($row['title']) ?>" required>
            <input type="text" name="edit_url" value="<?= htmlspecialchars($row['url']) ?>" placeholder="External URL">
            <input type="file" name="edit_image" accept="image/*">
            <button type="submit" name="edit_original">Save Changes</button>
          </form>
        </div>
      <?php endwhile; ?>
    </div>
  </div>


</section>
<!-- Announcement Management Section -->

<section id="announcement">
<h2 style="margin: 20px 12px;">Announcement Management</h2>

<div class="user-crud-section">
    <h2 style="color: #03045e;">Announcements</h2>

    <button onclick="document.getElementById('addAnnouncementModal').style.display='block'" class="btn">Add New Announcement</button>

    <!-- Modal for Adding Announcement -->
    <div id="addAnnouncementModal" class="modal">
        <div class="modal-content">
            <span onclick="document.getElementById('addAnnouncementModal').style.display='none'" class="close">&times;</span>
            <h2>Add New Announcement</h2>
            <form method="POST">
                <input type="hidden" name="add_announcement" value="1">
                <input type="text" name="announcement_title" placeholder="Announcement Title" required>
                <textarea name="announcement_message" placeholder="Announcement Message" rows="4" style="width:100%; padding:10px;" required></textarea>
                <br>
                <button type="submit">Post Announcement</button>
            </form>
        </div>
    </div>

    <div class="announcement-table-wrapper">
      <table border="1" class="announcement-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Message</th>
            <th>Date Posted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $announcementsResult->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['message']) ?></td>
            <td><?= $row['date_posted'] ?></td>
            <td>
              <form method="POST" style="display:inline-block;">
                <input type="hidden" name="announcement_id" value="<?= $row['id'] ?>">
                <input type="text" name="announcement_title" value="<?= htmlspecialchars($row['title']) ?>" required>
                <input type="text" name="announcement_message" value="<?= htmlspecialchars($row['message']) ?>" required>
                <button type="submit" name="edit_announcement">Edit</button>
              </form>
              <a href="?delete_announcement=<?= $row['id'] ?>" onclick="return confirm('Delete this announcement?');">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

</div>

</section>

<section id="assessment">

<div class="user-crud-section">
<h1>Assessments</h1>
  <div class="assessment-table-wrapper">
    <table class="assessment-table">
      <thead>
        <tr>
          <th>Topic</th>
          <th>Description</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assessments as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['topic']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>
              <a href="edit_ass.php?id=<?= $row['id'] ?>">Edit</a> |
              <a href="delete_ass.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<!-- Add Assessment Form -->
<form action="submit_assessment.php" method="POST">
  <label for="topic">Select the Topic of the Question:</label>
  <select id="topic" name="topic" required>
    <option value="physical">Physical</option>
    <option value="mental">Mental</option>
    <option value="social">Social</option>
    <option value="financial">Financial</option>
    <option value="educational">Educational</option>
    <option value="occupational">Occupational</option>
  </select>

  <br><br>

  <label for="description">Question Description:</label>
  <textarea id="description" name="description" rows="4" required></textarea>

  <br><br>

  <button type="submit">Add Assessment</button>
</form>
</div>


</section>


<section id="hotline">
  <h2 style="margin: 20px 12px;">Hotline Management</h2>
  <div class="user-crud-section">
    <h2 style="color: #03045e;">Hotlines</h2>

    <!-- Add Hotline Button -->
    <button id="openAddHotlineBtn" style="background-color: #446592; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">
        Add New Hotline
    </button>

    <div id="addHotlineOverlay" class="overlay">
        <div class="overlay-content">
            <h2>Add New Hotline</h2>
            <form action="add_hotline.php" method="POST" class="resource-form">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="number" placeholder="Number" required>
                <textarea name="description" placeholder="Description"></textarea>
                <select name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="margin-top: 20px;">
                    <button type="submit" style="background-color: #446592; color:white; padding:8px 16px; border:none; border-radius:5px;">Add Hotline</button>
                    <button type="button" id="closeAddHotlineBtn" style="background-color: #ccc; color:black; padding:8px 16px; border:none; border-radius:5px; margin-left:10px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Adding Hotline -->
    <div class="hotlines-table-wrapper">
      <table class="hotlines-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Number</th>
            <th>Description</th>
            <th>Category</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hotlines as $hotline): ?>
            <tr>
              <td><?= htmlspecialchars($hotline['name']) ?></td>
              <td><?= htmlspecialchars($hotline['number']) ?></td>
              <td><?= htmlspecialchars($hotline['description']) ?></td>
              <td><?= htmlspecialchars($hotline['category_name']) ?></td>
              <td>
                <button onclick='editHotline(<?= json_encode($hotline) ?>)'>Edit</button>
                <a href="delete_hotline.php?id=<?= $hotline['id'] ?>" onclick="return confirm('Delete this hotline?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>


<!-- Add Hotline Modal -->
<div id="addHotlineModal" class="modal">
  <form action="add_hotline.php" method="POST">
    <h3>Add Hotline</h3>
    <input type="text" name="name" placeholder="Name" required>
    <input type="text" name="number" placeholder="Number" required>
    <textarea name="description" placeholder="Description"></textarea>
    <select name="category_id" required>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Save</button>
    <button type="button" onclick="document.getElementById('addHotlineModal').classList.remove('show')">Cancel</button>
  </form>
</div>

<!-- Edit Hotline Modal -->
<div id="editHotlineModal" class="modal">
  <form id="editForm" action="edit_hotline.php" method="POST">
    <h3>Edit Hotline</h3>
    <input type="hidden" name="id" id="edit-id">
    <input type="text" name="name" id="edit-name" required>
    <input type="text" name="number" id="edit-number" required>
    <textarea name="description" id="edit-description"></textarea>
    <select name="category_id" id="edit-category" required>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Update</button>
    <button type="button" onclick="document.getElementById('editHotlineModal').classList.remove('show')">Cancel</button>
  </form>
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
function toggleProfileDropdown() {
    const profileDropdown = document.getElementById('profileDropdown');
    profileDropdown.style.display = (profileDropdown.style.display === 'block') ? 'none' : 'block';
}

function toggleRoleDropdown() {
    const roleDropdown = document.getElementById('roleDropdown');
    roleDropdown.style.display = (roleDropdown.style.display === 'block') ? 'none' : 'block';
}

// Close the dropdowns if clicked outside
window.onclick = function(event) {
    const profileDropdown = document.getElementById('profileDropdown');
    const roleDropdown = document.getElementById('roleDropdown');
    const profileIcon = document.querySelector('.material-icons[onclick="toggleProfileDropdown()"]');
    const roleIcon = document.querySelector('.material-icons[onclick="toggleRoleDropdown()"]');
    
    if (!profileDropdown.contains(event.target) && !profileIcon.contains(event.target)) {
        profileDropdown.style.display = 'none';
    }
    
    if (!roleDropdown.contains(event.target) && !roleIcon.contains(event.target)) {
        roleDropdown.style.display = 'none';
    }
}

  document.addEventListener("DOMContentLoaded", function () {
    const burger = document.querySelector('.burger-icon');
    const dropdown = document.querySelector('.dropdown-menu');

    burger.addEventListener('click', () => {
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    // Optional: Click outside to close
    document.addEventListener('click', (e) => {
      if (!burger.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  });

  const ctx = document.getElementById('userChart').getContext('2d');
const userChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($roleCounts, 'role_name')) ?>,
        datasets: [{
            label: 'Number of Users',
            data: <?= json_encode(array_column($roleCounts, 'count')) ?>,
            backgroundColor: '#2c3e50',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});


//resource overlay

const openBtn = document.getElementById('openAddResourceBtn');
const overlay = document.getElementById('addResourceOverlay');
const closeBtn = document.getElementById('closeAddResourceBtn');

openBtn.addEventListener('click', () => {
    overlay.classList.add('show');
});

closeBtn.addEventListener('click', () => {
    overlay.classList.remove('show');
});

// Optional: Close overlay if user clicks outside the form
overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
        overlay.classList.remove('show');
    }
});

// Resource overlay
document.getElementById('openAddResourceBtn').addEventListener('click', function() {
  document.getElementById('addResourceOverlay').classList.add('show');
});

document.getElementById('closeAddResourceBtn').addEventListener('click', function() {
  document.getElementById('addResourceOverlay').classList.remove('show');
});

// Announcement overlay
document.getElementById('openAddAnnouncementBtn').addEventListener('click', function() {
  document.getElementById('addAnnouncementOverlay').classList.add('show');
});

document.getElementById('closeAddAnnouncementBtn').addEventListener('click', function() {
  document.getElementById('addAnnouncementOverlay').classList.remove('show');
});

//hotline

          // Show Delete Modal
          function showDeleteModal(hotlineId) {
            const modal = document.getElementById('deleteModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            modal.classList.add('show');
            
            // Set the delete button to confirm deletion of this hotline
            confirmDeleteBtn.onclick = function() {
                window.location.href = 'delete_hotlines.php?id=' + hotlineId;
            };
        }

        // Close Delete Modal
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('show');
        }

        // Close the modal if clicked outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeDeleteModal();
            }
        }


function closeAddHotlineModal() {
  document.getElementById('addHotlineModal').classList.remove('show');
}

function editHotline(data) {
  document.getElementById('edit-id').value = data.id;
  document.getElementById('edit-name').value = data.name;
  document.getElementById('edit-number').value = data.number;
  document.getElementById('edit-description').value = data.description;
  document.getElementById('edit-category').value = data.category_id;
  document.getElementById('editHotlineModal').classList.add('show');
}

// Open the overlay for adding a hotline
document.getElementById('openAddHotlineBtn').addEventListener('click', function () {
    document.getElementById('addHotlineOverlay').classList.add('show');
});

// Close the overlay when clicking the Cancel button
document.getElementById('closeAddHotlineBtn').addEventListener('click', function () {
    document.getElementById('addHotlineOverlay').classList.remove('show');
});


</script>
    <script src="script.js"></script>

    
    
</body>
</html>

