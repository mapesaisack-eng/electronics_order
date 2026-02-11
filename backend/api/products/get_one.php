<?php
require_once '../../config/database.php';
require_once '../../helpers/sanitize.php';

if (!isset($_GET['id'])) {
    echo json_encode(["status" => "error", "message" => "Product ID is required"]);
    exit();
}

$product_id = (int)sanitize_input($_GET['id']);

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id";
$stmt = $db->prepare($sql);
$stmt->execute(['id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(["status" => "error", "message" => "Product not found"]);
    exit();
}

// Get reviews for this product
$reviews_sql = "SELECT r.*, u.username FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = :product_id 
                ORDER BY r.created_at DESC";
$reviews_stmt = $db->prepare($reviews_sql);
$reviews_stmt->execute(['product_id' => $product_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
               FROM reviews WHERE product_id = :product_id";
$rating_stmt = $db->prepare($rating_sql);
$rating_stmt->execute(['product_id' => $product_id]);
$rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);

$product['reviews'] = $reviews;
$product['avg_rating'] = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$product['review_count'] = $rating_data['review_count'];

echo json_encode([
    "status" => "success",
    "data" => $product
]);
?>