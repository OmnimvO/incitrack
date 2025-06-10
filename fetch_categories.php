<?php
require_once __DIR__ . '/includes/db.php';

$db = new Database();
$conn = $db->connect();

$stmt = $conn->query("SELECT category_id, category_name, priority_level FROM categories ORDER BY category_name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($categories);
