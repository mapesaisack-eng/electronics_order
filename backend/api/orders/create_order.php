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

$shipping_address = sanitize_input($data['shipping_address'] ?? '');
$user_id = get_current_user_id();

if (empty($shipping_address)) {
    echo json_encode(["status" => "error", "message" => "Shipping address is required"]);
    exit();
}

// Get cart items
$cart_sql = "SELECT c.product_id, c.quantity, p.price, p.name, p.stock_quantity 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = :user_id";
$cart_stmt = $db->prepare($cart_sql);
$cart_stmt->execute(['user_id' => $user_id]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    echo json_encode(["status" => "error", "message" => "Your cart is empty"]);
    exit();
}

// Check stock and calculate total
$total_amount = 0;
$order_items = [];

foreach ($cart_items as $item) {
    if ($item['stock_quantity'] < $item['quantity']) {
        echo json_encode([
            "status" => "error", 
            "message" => "Insufficient stock for: " . $item['name'] . ". Available: " . $item['stock_quantity']
        ]);
        exit();
    }
    
    $subtotal = $item['price'] * $item['quantity'];
    $total_amount += $subtotal;
    
    $order_items[] = [
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'price' => $item['price']
    ];
}

try {
    $db->beginTransaction();
    
    // Create order
    $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address) 
                  VALUES (:user_id, :total_amount, :shipping_address)";
    $order_stmt = $db->prepare($order_sql);
    $order_stmt->execute([
        'user_id' => $user_id,
        'total_amount' => $total_amount,
        'shipping_address' => $shipping_address
    ]);
    
    $order_id = $db->lastInsertId();
    
    // Add order items and update stock
    foreach ($order_items as $item) {
        // Add to order_items
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                     VALUES (:order_id, :product_id, :quantity, :price)";
        $item_stmt = $db->prepare($item_sql);
        $item_stmt->execute([
            'order_id' => $order_id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ]);
        
        // Update product stock
        $update_stock_sql = "UPDATE products 
                             SET stock_quantity = stock_quantity - :quantity 
                             WHERE id = :product_id";
        $update_stmt = $db->prepare($update_stock_sql);
        $update_stmt->execute([
            'quantity' => $item['quantity'],
            'product_id' => $item['product_id']
        ]);
    }
    
    // Clear cart
    $clear_cart_sql = "DELETE FROM cart WHERE user_id = :user_id";
    $clear_stmt = $db->prepare($clear_cart_sql);
    $clear_stmt->execute(['user_id' => $user_id]);
    
    $db->commit();
    
    echo json_encode([
        "status" => "success",
        "message" => "Order created successfully",
        "order_id" => $order_id,
        "total_amount" => $total_amount
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        "status" => "error",
        "message" => "Order failed: " . $e->getMessage()
    ]);
}
?>