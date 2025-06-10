<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: authentication/loginform.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

if (!$conn) {
    die("Database connection failed.");
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$reporter_id = null;

switch ($userType) {
    case 'resident':
        $stmt = $conn->prepare("SELECT resident_id FROM Residents WHERE user_id = ?");
        break;
    case 'official':
        $stmt = $conn->prepare("SELECT official_id FROM Barangay_Officials WHERE user_id = ?");
        break;
    case 'tanod':
        $stmt = $conn->prepare("SELECT tanod_id FROM Tanods WHERE user_id = ?");
        break;
    default:
        die("Invalid user type.");
}

if ($stmt && $stmt->execute([$userId])) {
    $reporter_id = $stmt->fetchColumn();
} else {
    die("Failed to fetch reporter ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'] ?? null;
    $purok = $_POST['purok'] ?? null;
    $landmark = $_POST['landmark'] ?? null;
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $details = $_POST['details'] ?? null;
    $urgency_level = $_POST['urgency'] ?? null;  
    $priority_level = null;

$stmt->execute([$reporter_id, $category_id, $purok, $landmark, $latitude, $longitude, $details, $urgency_level, $priority_level]);


    if (!empty($_POST['victim_name'])) {
        foreach ($_POST['victim_name'] as $i => $name) {
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO incident_victims (incident_id, name, age, contact_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $incident_id,
                    $name,
                    $_POST['victim_age'][$i] ?? null,
                    $_POST['victim_contact'][$i] ?? null
                ]);
            }
        }
    }

    if (!empty($_POST['perpetrator_name'])) {
        foreach ($_POST['perpetrator_name'] as $i => $name) {
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO incident_perpetrators (incident_id, name, age, contact_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $incident_id,
                    $name,
                    $_POST['perpetrator_age'][$i] ?? null,
                    $_POST['perpetrator_contact'][$i] ?? null
                ]);
            }
        }
    }

    if (!empty($_POST['witness_name'])) {
        foreach ($_POST['witness_name'] as $i => $name) {
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO incident_witnesses (incident_id, name, contact_number) VALUES (?, ?, ?)");
                $stmt->execute([
                    $incident_id,
                    $name,
                    $_POST['witness_contact'][$i] ?? null
                ]);
            }
        }
    }

    if (!empty($_FILES['evidence']['name'][0])) {
        foreach ($_FILES['evidence']['name'] as $i => $filename) {
            if ($_FILES['evidence']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['evidence']['tmp_name'][$i];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $safeName = uniqid('evidence_', true) . '.' . $ext;
                $targetPath = "uploads/" . $safeName;

                $fileType = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : ($ext === 'mp4' ? 'video' : null);
                if (!$fileType) {
                    continue;
                }

                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $stmt = $conn->prepare("INSERT INTO incident_evidence (incident_id, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$incident_id, $targetPath, $fileType, $userId]);
                }
            }
        }
    }

    echo "<p style='color:green;'>Incident reported successfully! <em>(Tagalog: Matagumpay na nai-report ang insidente!)</em></p>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>

        body {
        margin: 0;
        background-color: #f5f7fa;
        color: #333;
        }
        
        .sidebar {
        width: 240px;
        background: #1e293b;
        color: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 1.5rem;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15);
        z-index: 999;
        transition: transform 0.3s ease;
        }

        .sidebar .logo img {
        max-height: 70px;
        margin-bottom: 1rem;
        }

        .sidebar .nav {
        width: 100%;
        display: flex;
        flex-direction: column;
        padding: 0;
        }

        .sidebar .nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.75rem 1.5rem;
        color: #cbd5e1;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 14px;
        }

        .sidebar .nav a i {
        font-size: 16px;
        width: 20px;
        }

        .sidebar .nav a:hover,
        .sidebar .nav a:focus {
        background: #0f172a;
        color: #ffffff;
        }

        .logout {
        margin-top: auto;
        padding: 0.75rem 1.5rem;
        width: 100%;
        color: #ef4444;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        border-top: 1px solid #334155;
        transition: background 0.3s;
        font-size: 14px;
        }

        .logout i {
        font-size: 16px;
        }

        .logout:hover {
        background: #dc2626;
        color: #fff;
        }

        .main-content {
            margin-left: 400px;
            padding: 2rem;
            width: 100%;
            max-width: 960px;
        }

        form {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }

        h2, h3 {
            color: #003366;
            margin-top: 30px;
            border-left: 5px solid #0066cc;
            padding-left: 10px;
            font-size: 18px;
            font-weight: bold;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #444;
        }

        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fafafa;
        }

        textarea[name="details"] {
            font-size: 12px;
            min-height: 100px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #bbb;
            border-radius: 6px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            line-height: 1.5;
            color: #333;
        }

        textarea[name="details"]::placeholder {
            color: #888;
            font-style: italic;
        }

        textarea:focus,
        input:focus,
        select:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 5px rgba(0,102,204,0.2);
            font-size: 14px;
        }

        button {
            background-color: #0066cc;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 10px;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #004999;
        }

        button[type="button"] {
            background-color: #888;
        }

        button[type="button"]:hover {
            background-color: #666;
        }

        #map {
            height: 350px;
            margin-bottom: 20px;
            border: 2px solid #ccc;
            border-radius: 8px;
        }

        .victim-fields,
        .perpetrator-fields,
        .witness-fields {
            background-color: #f0f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 5px solid #007acc;
            font-size: 12px;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            }

            .sidebar-overlay.active {
            display: block;
            }

 
            .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            background: #1e293b;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 18px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            }

        @media (max-width: 768px) {
             .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }


    </style>
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

<div class="main-content">



<h2>Report an Incident <em>(I-report ang Insidente)</em></h2>

<form method="POST" enctype="multipart/form-data">
    <label>Category: <em>(Kategorya)</em></label>
    <select name="category_id" id="category" class="form-select" required onchange="setUrgencyLevel()">
        <option value="" disabled selected>Select a Category</option>
        <?php
       $stmt = $conn->query("SELECT category_id, category_name, default_urgency FROM categories ORDER BY category_name ASC");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$row['category_id']}' data-urgency='{$row['default_urgency']}'>
      {$row['category_name']} ({$row['default_urgency']})</option>";

        }
        ?>
    </select>

    <label>Urgency Level: <em>(Antas ng Dali)</em></label>
    <select name="urgency" id="urgency" class="form-select" >
        <option value="">Select urgency</option>
        <option value="Low">Low</option>
        <option value="Medium">Medium</option>
        <option value="High">High</option>
    </select>
</form>
<script>
function setUrgencyLevel() {
    var categorySelect = document.getElementById("category");
    var selectedOption = categorySelect.options[categorySelect.selectedIndex];
    var urgency = selectedOption.getAttribute("data-urgency");
    document.getElementById("urgency").value = urgency;
}
</script>



    <label>Purok: <em>(Purok)</em></label>
    <select name="purok" required>
        <?php for ($i = 1; $i <= 7; $i++): ?>
            <option value="Purok <?= $i ?>">Purok <?= $i ?></option>
        <?php endfor; ?>
    </select>

    <label>Landmark (optional): <em>(Landmark (opsyonal))</em></label>
    <input type="text" name="landmark">

    <label>Select Location on Map: <em>(Piliin ang Lokasyon sa Mapa)</em></label>
    <div id="map"></div>

    <label>Latitude: <em>(Latitude)</em></label>
    <input type="text" name="latitude" id="latitude" required readonly>

    <label>Longitude: <em>(Longitude)</em></label>
    <input type="text" name="longitude" id="longitude" required readonly>

    <h3>Victims (optional): <em>(Mga Biktima)</em></h3>
    <div id="victims">
        <div class="victim-fields">
            <label>Victim Name: <em>(Pangalan ng Biktima)</em></label>
            <input type="text" name="victim_name[]">
            <label>Victim Age: <em>(Edad ng Biktima)</em></label>
            <input type="text" name="victim_age[]">
            <label>Victim Contact: <em>(Kontak ng Biktima)</em></label>
            <input type="text" name="victim_contact[]">
        </div>
    </div>
    <button type="button" onclick="addVictim()">Add More Victim</button>

    <h3>Perpetrators (optional): <em>(Mga Salarin)</em></h3>
    <div id="perpetrators">
        <div class="perpetrator-fields">
            <label>Perpetrator Name: <em>(Pangalan ng Salarin)</em></label>
            <input type="text" name="perpetrator_name[]">
            <label>Perpetrator Age: <em>(Edad ng Salarin)</em></label>
            <input type="text" name="perpetrator_age[]">
            <label>Perpetrator Contact: <em>(Kontak ng Salarin)</em></label>
            <input type="text" name="perpetrator_contact[]">
        </div>
    </div>
    <button type="button" onclick="addPerpetrator()">Add More Perpetrator</button>

    <h3>Witnesses (optional): <em>(Mga Magsusaksi)</em></h3>
    <div id="witnesses">
        <div class="witness-fields">
            <label>Witness Name: <em>(Pangalan ng Magsusaksi)</em></label>
            <input type="text" name="witness_name[]">
            <label>Witness Contact: <em>(Kontak ng Magsusaksi)</em></label>
            <input type="text" name="witness_contact[]">
        </div>
    </div>
    <button type="button" onclick="addWitness()">Add More Witness</button>

    <h3>Incident Details: <em>(Detalye ng Insidente)</em></h3>
    <textarea name="details" rows="4" required></textarea>
    
   
  <button onclick="window.location.href='manage_incident_types.php'">Manage Incident Types</button>

   


    <h3>Upload Evidence (optional): <em>(Mag-upload ng Ebidensya)</em></h3>
    <input type="file" name="evidence[]" accept="image/*,video/*" multiple>

    <button type="submit">Submit Report</button>

    <button type="button" onclick="cancelReport()">Cancel <em>(Kanselahin)</em></button>
</form>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
        const map = L.map('map').setView([13.3549, 123.7204], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: ''
    }).addTo(map);

    var marker;

    map.on('click', function(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng).addTo(map);
        document.getElementById("latitude").value = e.latlng.lat;
        document.getElementById("longitude").value = e.latlng.lng;
    });

    function addVictim() {
        var newField = document.createElement('div');
        newField.classList.add('victim-fields');
        newField.innerHTML = `
            <label>Victim Name: <em>(Pangalan ng Biktima)</em></label>
            <input type="text" name="victim_name[]">
            <label>Victim Age: <em>(Edad ng Biktima)</em></label>
            <input type="text" name="victim_age[]">
            <label>Victim Contact: <em>(Kontak ng Biktima)</em></label>
            <input type="text" name="victim_contact[]">
        `;
        document.getElementById('victims').appendChild(newField);
    }

    function addPerpetrator() {
        var newField = document.createElement('div');
        newField.classList.add('perpetrator-fields');
        newField.innerHTML = `
            <label>Perpetrator Name: <em>(Pangalan ng Salarin)</em></label>
            <input type="text" name="perpetrator_name[]">
            <label>Perpetrator Age: <em>(Edad ng Salarin)</em></label>
            <input type="text" name="perpetrator_age[]">
            <label>Perpetrator Contact: <em>(Kontak ng Salarin)</em></label>
            <input type="text" name="perpetrator_contact[]">
        `;
        document.getElementById('perpetrators').appendChild(newField);
    }

    function addWitness() {
        var newField = document.createElement('div');
        newField.classList.add('witness-fields');
        newField.innerHTML = `
            <label>Witness Name: <em>(Pangalan ng Magsusaksi)</em></label>
            <input type="text" name="witness_name[]">
            <label>Witness Contact: <em>(Kontak ng Magsusaksi)</em></label>
            <input type="text" name="witness_contact[]">
        `;
        document.getElementById('witnesses').appendChild(newField);
    }
</script>

<script>
    function cancelReport() {
        <?php
            $userType = $_SESSION['user_type'] ?? '';
            if ($userType == 'resident') {
                $redirectPage = './residents/resident_dashboard.php';
            } elseif ($userType == 'official') {
                $redirectPage = './officials/official_dashboard.php';
            } elseif ($userType == 'tanod') {
                $redirectPage = './tanods/tanod_dashboard.php';
            } else {
                $redirectPage = 'index.php'; 
            }
        ?>
        window.location.href = "<?php echo $redirectPage; ?>";
    }
</script> 


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
