<?php
require_once '../../helpers/auth_check.php';
require_login();
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

$product_id = isset($data['product_id']) ? (int)sanitize_input($data['product_id']) : 0;
$quantity = isset($data['quantity']) ? (int)sanitize_input($data['quantity']) : 1;
$user_id = get_current_user_id();

if ($product_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid product ID"]);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(["status" => "error", "message" => "Quantity must be at least 1"]);
    exit();
}

// Check if product exists and has stock
$product_sql = "SELECT id, stock_quantity FROM products WHERE id = :id";
$product_stmt = $db->prepare($product_sql);
$product_stmt->execute(['id' => $product_id]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(["status" => "error", "message" => "Product not found"]);
    exit();
}

// Check stock
if ($product['stock_quantity'] < $quantity) {
    echo json_encode([
        "status" => "error", 
        "message" => "Insufficient stock. Available: " . $product['stock_quantity']
    ]);
    exit();
}

// Check if item already in cart
$check_sql = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
$check_stmt = $db->prepare($check_sql);
$check_stmt->execute(['user_id' => $user_id, 'product_id' => $product_id]);
$existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_item) {
    // Update quantity
    $new_quantity = $existing_item['quantity'] + $quantity;
    
    // Check stock again with new quantity
    if ($product['stock_quantity'] < $new_quantity) {
        echo json_encode([
            "status" => "error", 
            "message" => "Cannot add more. Maximum available: " . $product['stock_quantity']
        ]);
        exit();
    }
    
    $update_sql = "UPDATE cart SET quantity = :quantity WHERE id = :id";
    $update_stmt = $db->prepare($update_sql);
    $success = $update_stmt->execute([
        'quantity' => $new_quantity,
        'id' => $existing_item['id']
    ]);
    
    $message = "Cart updated";
} else {
    // Add new item
    $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
    $insert_stmt = $db->prepare($insert_sql);
    $success = $insert_stmt->execute([
        'user_id' => $user_id,
        'product_id' => $product_id,
        'quantity' => $quantity
    ]);
    
    $message = "Item added to cart";
}

if ($success) {
    // Get updated cart count
    $count_sql = "SELECT SUM(quantity) as total_items FROM cart WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute(['user_id' => $user_id]);
    $count_data = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => $message,
        "cart_count" => $count_data['total_items'] ?? 0
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update cart"]);
}
?>