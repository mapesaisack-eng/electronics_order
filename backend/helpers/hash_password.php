<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        echo json_encode([
            "status" => "error",
            "message" => "Authentication required"
        ]);
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        echo json_encode([
            "status" => "error",
            "message" => "Admin access required"
        ]);
        exit();
    }
}

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
?>