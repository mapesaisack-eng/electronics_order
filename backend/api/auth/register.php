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

$username = sanitize_input($data['username'] ?? '');
$email = sanitize_input($data['email'] ?? '');
$password = $data['password'] ?? '';

// Validation
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit();
}

if (!validate_email($email)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit();
}

if (!validate_password($password)) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters with uppercase, lowercase and number"]);
    exit();
}

// Check if user exists
$check_sql = "SELECT id FROM users WHERE email = :email OR username = :username";
$check_stmt = $db->prepare($check_sql);
$check_stmt->execute(['email' => $email, 'username' => $username]);

if ($check_stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "Username or email already exists"]);
    exit();
}

// Hash password and insert
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'customer'; // Default role

$sql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
$stmt = $db->prepare($sql);

if ($stmt->execute([
    'username' => $username,
    'email' => $email,
    'password' => $hashed_password,
    'role' => $role
])) {
    $user_id = $db->lastInsertId();
    
    // Start session
    session_start();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['user_role'] = $role;
    $_SESSION['email'] = $email;
    
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful",
        "user" => [
            "id" => $user_id,
            "username" => $username,
            "email" => $email,
            "role" => $role
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Registration failed"]);
}
?>