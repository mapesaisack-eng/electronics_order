<?php
require_once '../../helpers/auth_check.php';
require_admin();
require_once '../../config/database.php';
require_once '../../helpers/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Product ID is required"]);
    exit();
}

$product_id = (int)sanitize_input($data['id']);

// Check if product exists
$check_sql = "SELECT id FROM products WHERE id = :id";
$check_stmt = $db->prepare($check_sql);
$check_stmt->execute(['id' => $product_id]);

if ($check_stmt->rowCount() === 0) {
    echo json_encode(["status" => "error", "message" => "Product not found"]);
    exit();
}

// Delete product (cascade will handle order_items and reviews)
$sql = "DELETE FROM products WHERE id = :id";
$stmt = $db->prepare($sql);

if ($stmt->execute(['id' => $product_id])) {
    echo json_encode([
        "status" => "success",
        "message" => "Product deleted successfully"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to delete product"]);
}
?>