<?php
require_once '../../helpers/auth_check.php';
require_login();
require_once '../../config/database.php';

$user_id = get_current_user_id();

$sql = "SELECT o.*, 
               COUNT(oi.id) as items_count,
               GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = :user_id
        GROUP BY o.id
        ORDER BY o.order_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order items for each order
foreach ($orders as &$order) {
    $items_sql = "SELECT oi.*, p.name, p.image_url 
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id";
    $items_stmt = $db->prepare($items_sql);
    $items_stmt->execute(['order_id' => $order['id']]);
    $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    "status" => "success",
    "data" => $orders
]);
?>