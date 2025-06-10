<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$db = new Database();
$conn = $db->connect();


$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;
$user_is_official = ($user_type === 'official');
$user_is_resident = ($user_type === 'resident');

$message = "";
$error = "";


$predefined_types = [
    ['name' => 'Fire', 'urgency' => 'High'],
    ['name' => 'Flood', 'urgency' => 'High'],
    ['name' => 'Medical Emergency', 'urgency' => 'High'],
    ['name' => 'Road Accident', 'urgency' => 'High'],
    ['name' => 'Theft', 'urgency' => 'Medium'],
    ['name' => 'Vandalism', 'urgency' => 'Low'],
    ['name' => 'Missing Person', 'urgency' => 'High'],
    ['name' => 'Domestic Violence', 'urgency' => 'High'],
    ['name' => 'Power Outage', 'urgency' => 'Medium'],
    ['name' => 'Earthquake', 'urgency' => 'High'],
    ['name' => 'Landslide', 'urgency' => 'High'],
    ['name' => 'Public Disturbance', 'urgency' => 'Medium'],
    ['name' => 'Animal Attack', 'urgency' => 'Medium'],
    ['name' => 'Structural Collapse', 'urgency' => 'High'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    if (!$user_is_official && !$user_is_resident) {
        $error = "Only officials and residents can add incident types.";
    } else {
        $type_name = trim($_POST['category_name'] ?? '');
        $urgency = $_POST['priority_level'] ?? '';

        if ($type_name === '') {
            $error = "Incident type name is required.";
        } elseif (!in_array($urgency, ['Low', 'Medium', 'High'])) {
            $error = "Invalid priority level.";
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
            $stmt->execute([$type_name]);

            if ($stmt->fetchColumn() > 0) {
                $error = "Incident type already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (category_name, default_urgency) VALUES (?, ?)");
                if ($stmt->execute([$type_name, $urgency])) {
                    $message = "Incident type added successfully.";
                } else {
                    $error = "Failed to add incident type.";
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $id = $_POST['id'] ?? null;
    $type_name = trim($_POST['category_name'] ?? '');
    $urgency = $_POST['priority_level'] ?? '';

    if (!$id || !is_numeric($id)) {
        $error = "Invalid category ID.";
    } elseif ($type_name === '') {
        $error = "Incident type name is required.";
    } elseif (!in_array($urgency, ['Low', 'Medium', 'High'])) {
        $error = "Invalid priority level.";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET category_name = ?, default_urgency = ? WHERE category_id = ?");
        if ($stmt->execute([$type_name, $urgency, $id])) {
            $message = "Incident type updated successfully.";
        } else {
            $error = "Failed to update incident type.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        $error = "Invalid category ID.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        if ($stmt->execute([$id])) {
            $message = "Incident type deleted successfully.";
        } else {
            $error = "Failed to delete incident type.";
        }
    }
}

$stmt = $conn->query("SELECT category_id, category_name, default_urgency FROM categories ORDER BY category_name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Incident Types</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background-color: #f5f7fa;
  color: #333;
}

.container {
  display: flex;
  flex-direction: column;
  padding: 2rem;
  margin-left: 240px;
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

h2 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 1rem;
}

button,
form button {
  background-color: #2563eb;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  font-weight: 500;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s ease;
}

button:hover,
form button:hover {
  background-color: #1e40af;
}

form {
  max-width: 600px;
}

form .form-label {
  font-weight: 500;
}

input[type="text"],
select {
  width: 100%;
  padding: 0.5rem;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background-color: #fff;
  font-size: 14px;
}

.alert {
  max-width: 600px;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.alert-success {
  background-color: #d1fae5;
  color: #065f46;
  border: 1px solid #10b981;
}

.alert-danger {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #ef4444;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border-radius: 12px;
  overflow: hidden;
}

th, td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
}

th {
  background-color: #1e293b;
  color: #ffffff;
}

td form {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
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

/* Responsive design */
@media (max-width: 768px) {
  .container {
    margin-left: 0;
    padding-top: 4rem;
  }

  .sidebar {
    transform: translateX(-100%);
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .menu-toggle {
    display: flex;
  }

  table {
    font-size: 13px;
  }
}


</style>
</head>


<body class="container py-4">
    
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

<h2>Manage Incident Types</h2>

    <br>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($user_is_official || $user_is_resident): ?>
        <button id="showAddFormBtn" class=" mb-4">Add New Incident Type</button>

        <form id="addForm" method="POST" class="mb-4" style="display:none;">
            <input type="hidden" name="action" value="add">

            <div class="mb-3">
                <label for="category_name" class="form-label">Select Incident Type</label>
                <select name="category_name" id="category_name" class="form-select" required>
                    <option value="">-- Select Incident Type --</option>
                    <?php foreach ($predefined_types as $type): ?>
                        <option value="<?= htmlspecialchars($type['name']) ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="priority_level" class="form-label">Priority Level</label>
                <select name="priority_level" id="priority_level" class="form-select" required>
                    <option value="">-- Select Priority --</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>

            <button type="submit" class="">Add Incident Type</button>
        </form>

        <script>
        document.getElementById('showAddFormBtn').addEventListener('click', function () {
            document.getElementById('addForm').style.display = 'block';
            this.style.display = 'none';
        });

        const typeUrgencyMap = {
            <?php foreach ($predefined_types as $type): ?>
                <?= json_encode($type['name']) ?>: <?= json_encode($type['urgency']) ?>,
            <?php endforeach; ?>
        };

        document.getElementById('category_name').addEventListener('change', function () {
            const urgency = typeUrgencyMap[this.value] || '';
            document.getElementById('priority_level').value = urgency;
        });
        </script>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Incident Type</th>
                <th>Priority Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <form method="POST" class="d-flex gap-2">
                    <td>
                        <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($cat['category_name']) ?>" required>
                    </td>
                    <td>
                        <select name="priority_level" class="form-select" required>
                            <option value="High" <?= $cat['default_urgency'] === 'High' ? 'selected' : '' ?>>High</option>
                            <option value="Medium" <?= $cat['default_urgency'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="Low" <?= $cat['default_urgency'] === 'Low' ? 'selected' : '' ?>>Low</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="id" value="<?= $cat['category_id'] ?>">
                        <button type="submit" name="action" value="update" class="">Update</button>
                        <button type="submit" name="action" value="delete" class="" onclick="return confirm('Delete this incident type?');">Delete</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="<?= $user_is_official ? './officials/official_dashboard.php' : './reportincident.php' ?>" class="btn btn-secondary mt-3">Go to Report Incident</a>
    
    
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
