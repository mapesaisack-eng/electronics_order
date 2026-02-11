<?php
require_once '../../config/database.php';
require_once '../../helpers/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

$email = sanitize_input($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email and password are required"]);
    exit();
}

// Find user
$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $db->prepare($sql);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    exit();
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    exit();
}

// Start session
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['email'] = $user['email'];

// Don't send password back
unset($user['password']);

echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "user" => $user
]);
?>