<?php
require_once '../../helpers/auth_check.php';
require_login();
require_once '../../config/database.php';
require_once '../../helpers/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
} else {
    $data = $_POST;
}

$cart_item_id = isset($data['cart_item_id']) ? (int)sanitize_input($data['cart_item_id']) : 0;
$quantity = isset($data['quantity']) ? (int)sanitize_input($data['quantity']) : 1;
$user_id = get_current_user_id();

if ($cart_item_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid cart item ID"]);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(["status" => "error", "message" => "Quantity must be at least 1"]);
    exit();
}

// Check if cart item belongs to user and get product info
$check_sql = "SELECT c.*, p.stock_quantity FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.id = :id AND c.user_id = :user_id";
$check_stmt = $db->prepare($check_sql);
$check_stmt->execute(['id' => $cart_item_id, 'user_id' => $user_id]);
$cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart_item) {
    echo json_encode(["status" => "error", "message" => "Cart item not found"]);
    exit();
}

// Check stock
if ($cart_item['stock_quantity'] < $quantity) {
    echo json_encode([
        "status" => "error", 
        "message" => "Insufficient stock. Available: " . $cart_item['stock_quantity']
    ]);
    exit();
}

// Update quantity
$update_sql = "UPDATE cart SET quantity = :quantity WHERE id = :id";
$update_stmt = $db->prepare($update_sql);

if ($update_stmt->execute(['quantity' => $quantity, 'id' => $cart_item_id])) {
    echo json_encode([
        "status" => "success",
        "message" => "Cart updated successfully"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update cart"]);
}
?>