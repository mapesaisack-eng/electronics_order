<?php
require_once('../config/database.php');

$sql = "SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Orders</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-pending { color: orange; }
        .status-completed { color: green; }
        .status-cancelled { color: red; }
    </style>
</head>
<body>
    <h1>All Orders</h1>
    
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                <td>
                    <?php echo htmlspecialchars($order['username']); ?><br>
                    <small><?php echo htmlspecialchars($order['email']); ?></small>
                </td>
                <td><?php echo $order['order_date']; ?></td>
                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                <td class="status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </td>
                <td><?php echo ucfirst($order['payment_method']); ?></td>
                <td>
                    <a href="order_details.php?order_id=<?php echo $order['order_id']; ?>">
                        View Details
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>