<?php
require_once '../../helpers/auth_check.php';
require_admin();
require_once '../../config/database.php';
require_once '../../helpers/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

// Get product ID
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
} else {
    $data = $_POST;
}

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

// Build update query
$update_fields = [];
$params = ['id' => $product_id];

$fields = ['name', 'description', 'price', 'stock_quantity', 'category_id', 'image_url'];
foreach ($fields as $field) {
    if (isset($data[$field])) {
        if ($field === 'price') {
            $params[$field] = (float)sanitize_input($data[$field]);
        } elseif ($field === 'stock_quantity' || $field === 'category_id') {
            $params[$field] = (int)sanitize_input($data[$field]);
        } else {
            $params[$field] = sanitize_input($data[$field]);
        }
        $update_fields[] = "$field = :$field";
    }
}

if (empty($update_fields)) {
    echo json_encode(["status" => "error", "message" => "No fields to update"]);
    exit();
}

$sql = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = :id";
$stmt = $db->prepare($sql);

if ($stmt->execute($params)) {
    // Get updated product
    $get_sql = "SELECT * FROM products WHERE id = :id";
    $get_stmt = $db->prepare($get_sql);
    $get_stmt->execute(['id' => $product_id]);
    $product = $get_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Product updated successfully",
        "data" => $product
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update product"]);
}
?>