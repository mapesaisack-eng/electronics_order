<?php
require_once '../../helpers/auth_check.php';
require_admin();
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

$name = sanitize_input($data['name'] ?? '');
$description = sanitize_input($data['description'] ?? '');
$price = isset($data['price']) ? (float)sanitize_input($data['price']) : 0;
$stock_quantity = isset($data['stock_quantity']) ? (int)sanitize_input($data['stock_quantity']) : 0;
$category_id = isset($data['category_id']) ? (int)sanitize_input($data['category_id']) : null;
$image_url = sanitize_input($data['image_url'] ?? 'default_product.jpg');

// Validation
$errors = [];
if (empty($name)) $errors[] = "Product name is required";
if ($price <= 0) $errors[] = "Price must be greater than 0";
if ($stock_quantity < 0) $errors[] = "Stock quantity cannot be negative";

if (!empty($errors)) {
    echo json_encode(["status" => "error", "message" => implode(", ", $errors)]);
    exit();
}

// Check if category exists if provided
if ($category_id) {
    $check_category = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $check_category->execute([$category_id]);
    if ($check_category->rowCount() === 0) {
        $category_id = null;
    }
}

$sql = "INSERT INTO products (name, description, price, stock_quantity, category_id, image_url) 
        VALUES (:name, :description, :price, :stock_quantity, :category_id, :image_url)";
$stmt = $db->prepare($sql);

if ($stmt->execute([
    'name' => $name,
    'description' => $description,
    'price' => $price,
    'stock_quantity' => $stock_quantity,
    'category_id' => $category_id,
    'image_url' => $image_url
])) {
    $product_id = $db->lastInsertId();
    
    // Get the created product
    $get_sql = "SELECT * FROM products WHERE id = :id";
    $get_stmt = $db->prepare($get_sql);
    $get_stmt->execute(['id' => $product_id]);
    $product = $get_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Product created successfully",
        "data" => $product
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create product"]);
}
?>