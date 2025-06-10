<?php

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'resident') {
    header("Location: ../authentication/loginform.php"); 
    exit();
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT fname, lname, purok FROM Residents WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resident) {
    echo "<p style='color:red;'>Resident details not found. Please contact the administrator.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resident Dashboard - Incident Reporting</title>
  <link rel="stylesheet" href="../css/resident_dashboard.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<button class="menu-toggle d-md-none" id="menuToggle">
  <i class="fas fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">
  <div class="logo" style="margin-left: 15px; width: 45%;">
    <img src="../img/logos.png" alt="Logo" style="max-height: 100px;">
  </div>

      <!-- <div class="profile">
        <img src="./img/hehe.png" alt="Profile Pic" class="img-fluid rounded-circle" style="width: 70px; height: 70px; margin-top: 20px;">
        <h3><?php echo htmlspecialchars($resident['fname'] . ' ' . $resident['lname']); ?></h3>
        <span class="badge bg-primary">Resident</span>
      </div> -->

    <nav class="nav">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="documents.php"><i class="fas fa-file-alt"></i> Reports</a>
    <a href="requesthistory.php"><i class="fas fa-history"></i> Incident History</a>
    <a href="account.php"><i class="fas fa-user-cog"></i> Account</a>
    <a href="../manage_incident_types.php"><i class="fas fa-tools"></i> Manage Incidents</a>
  </nav>

  <a href="../authentication/logout.php" class="logout">
    <i class="fas fa-sign-out-alt"></i> Log Out
  </a>
</nav>

    </aside>

    <main class="main">
      <section class="intro">
        <h1>Report and Track Incidents in Barangay Panal</h1>
        <p>Submit and track incidents happening within your purok. Stay updated on your reports and their statuses.</p>
        <a href="../reportincident.php" class="btn btn-primary">Report an Incident</a>
        

      </section>

      <section class="announcement-area">
        <div class="card announcement">
          <h2>Announcement</h2>
          <p>Please be advised that the barangay office may have adjusted hours. Always check before visiting for incident-related queries.</p>
        </div>
        <div class="card days">
          <h2>Incident Reporting Hours</h2>
          <ul>
            <li>Mon–Wed: 7:00 AM – 11:50 AM</li>
            <li>Thu–Fri: No in-person incident reporting</li>
            <li>Online incident reports are accepted daily</li>
          </ul>
        </div>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const sidebar = document.getElementById("sidebar");
  const menuToggle = document.getElementById("menuToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");

  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("open");
    sidebarOverlay.classList.toggle("active");
  });

  sidebarOverlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("active");
  });

  document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove("open");
      sidebarOverlay.classList.remove("active");
    });
  });
</script>

</body>
</html>
