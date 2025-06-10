<?php
require_once '../includes/db.php';

class UserLogin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];

                $role = $this->getUserRole($user['user_id']);

                if ($role !== 'Unknown') {
                    $_SESSION['user_type'] = strtolower($role); 

                    switch ($role) {
                        case 'Resident':
                            header("Location: ../residents/resident_dashboard.php");
                            break;
                        case 'Official':
                            header("Location: ../officials/official_dashboard.php");
                            break;
                        case 'Tanod':
                            header("Location: ../tanods/tanod_dashboard.php");
                            break;
                    }
                    exit();
                } else {
                    return "User role not found.";
                }
            } else {
                return "Incorrect password.";
            }
        } else {
            return "Username not found.";
        }
    }

    private function getUserRole($user_id) {
        $stmt = $this->conn->prepare("SELECT resident_id FROM Residents WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            return 'Resident';
        }

        $stmt = $this->conn->prepare("SELECT official_id FROM Barangay_Officials WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            return 'Official';
        }

        $stmt = $this->conn->prepare("SELECT tanod_id FROM Tanods WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            return 'Tanod';
        }

        return 'Unknown';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $database = new Database();
    $db = $database->connect();
    $login = new UserLogin($db);

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $result = $login->login($username, $password);

    if ($result !== null && $result !== true) {
        header("Location: loginform.php?error=" . urlencode($result));
        exit();
    }
}
?>
