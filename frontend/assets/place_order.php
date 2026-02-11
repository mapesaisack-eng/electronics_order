<?php
require_once('../config/database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $data['user_id'];
    $cart_items = $data['cart_items'];
    $shipping_address = $data['shipping_address'];
    $payment_method = $data['payment_method'];
    
    // Calculate total
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Insert into orders table
        $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                     VALUES (:user_id, :total_amount, :shipping_address, :payment_method)";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->execute([
            ':user_id' => $user_id,
            ':total_amount' => $total_amount,
            ':shipping_address' => $shipping_address,
            ':payment_method' => $payment_method
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (:order_id, :product_id, :quantity, :price)";
        $item_stmt = $conn->prepare($item_sql);
        
        foreach ($cart_items as $item) {
            $item_stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
            
            // Update product stock (optional)
            $update_sql = "UPDATE products SET stock = stock - :qty WHERE product_id = :pid";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([':qty' => $item['quantity'], ':pid' => $item['product_id']]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $order_id,
            'total_amount' => $total_amount
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>