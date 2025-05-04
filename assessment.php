<?php 
session_start();

// Check required session variables
if (!isset($_SESSION['firstname']) || !isset($_SESSION['lastname']) || !isset($_SESSION['user_id'])) {
  echo "User session is incomplete. Please log in again.";
  exit();
}

$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$user_id = $_SESSION['user_id'];

// Connect to the database
$conn = new mysqli("localhost", "root", "", "sample_db");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
  foreach ($_POST['answers'] as $assessment_id => $rating) {
    // Fetch the topic for this assessment
    $stmt = $conn->prepare("SELECT topic FROM assessments WHERE id = ?");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $stmt->bind_result($topic);
    $stmt->fetch();
    $stmt->close();

    // Insert the answer with the topic
    $stmt = $conn->prepare("INSERT INTO assessment_answers (user_id, assessment_id, rating, topic) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $assessment_id, $rating, $topic);
    $stmt->execute();
    $stmt->close();
  }

  // Redirect back to prevent form resubmission
  header("Location: user.php#assessment");
  exit();
}

// Fetch announcements
$announcements = [];
$result = $conn->query("SELECT * FROM announcements");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
  }
}

// Fetch assessments (questions)
$assessments = [];
$result = $conn->query("SELECT * FROM assessments ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $assessments[] = $row;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="newstyle.css">
    <style>
    body {
      background: linear-gradient(to bottom, #161a2d 0%, #4e5d9d 50%, #7faaff 100%);
      background-attachment: fixed;
    }

    .assessment-container {
        margin: 40px auto;
        width: 80%;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .assessment-container h2 {
        font-size: 24px;
        color: #2c3e50;
    }

    .assessment-container ul {
        list-style-type: none;
        padding: 0;
    }

    .assessment-container li {
        padding: 12px;
        margin: 10px 0;
        background-color: #f9f9f9;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .assessment-container li h3 {
        font-size: 20px;
        margin: 0;
        color: #34495e;
    }

    .assessment-container li p {
        font-size: 16px;
        color: #7f8c8d;
    }

    .assessment-container input,
    .assessment-container textarea {
        width: 100%;
        padding: 10px;
        margin-top: 8px;
        margin-bottom: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 16px;
    }

    .assessment-container button {
        padding: 12px 24px;
        background-color: #f39c12;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
    }

    .assessment-container button:hover {
        background-color: #e67e22;
    }

    .rating-labels {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }

    .rating-labels input {
        width: auto;
    }
    </style>
</head>
<body>

<!-- Assessments Section -->
<button type="button" onclick="handleReturn()">Return</button>

<div class="assessment-container">
  <h2>Your Assessments</h2>
  <form method="POST">
    <ul>
      <?php foreach ($assessments as $assessment): ?>
        <li>
          <h3><?php echo htmlspecialchars($assessment['title']); ?></h3>
          <p><?php echo htmlspecialchars($assessment['description']); ?></p>
          <p><strong>Created at:</strong> <?php echo htmlspecialchars($assessment['created_at']); ?></p>

          <!-- Rating Input (1-5) -->
          <label>Your Answer:</label>
          <div class="rating-labels">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <label>
                <input type="radio" name="answers[<?php echo $assessment['id']; ?>]" value="<?php echo $i; ?>" required>
                <?php echo $i; ?>
              </label>
            <?php endfor; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <button type="submit">Submit Answers</button>
  </form>
</div>

<script>
function toggleSidebar() {
  const dropdownMenu = document.getElementById('dropdownMenu');
  dropdownMenu.style.display = (dropdownMenu.style.display === 'block') ? 'none' : 'block';
}

window.onclick = function(event) {
  const dropdownMenu = document.getElementById('dropdownMenu');
  const burgerIcon = document.querySelector('.burger-icon');
  if (!dropdownMenu.contains(event.target) && !burgerIcon.contains(event.target)) {
    dropdownMenu.style.display = 'none';
  }
}

function handleReturn() {
  // Check if any radio button is selected
  const answered = document.querySelector('input[type="radio"]:checked');

  if (answered) {
    if (confirm("You have started answering the assessment. Do you want to leave without saving?")) {
      // User confirmed to leave
      window.location.href = "user_assessment.php";
    } else {
      // User wants to stay
      return;
    }
  } else {
    // No answers selected, safe to leave
    window.location.href = "user_assessment.php";
  }
}
</script>

<script src="script.js"></script>
</body>
</html>
