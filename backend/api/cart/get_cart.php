<?php
require_once '../../helpers/auth_check.php';
require_login();
require_once '../../config/database.php';

$user_id = get_current_user_id();

$sql = "SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = :user_id 
        ORDER BY c.added_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_items = 0;
$total_price = 0;

foreach ($cart_items as &$item) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
    $total_price += $item['subtotal'];
}

echo json_encode([
    "status" => "success",
    "data" => $cart_items,
    "summary" => [
        "total_items" => $total_items,
        "total_price" => $total_price
    ]
]);
?>