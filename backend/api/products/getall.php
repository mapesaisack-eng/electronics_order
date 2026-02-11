<?php
require_once '../../config/database.php';
require_once '../../helpers/sanitize.php';

$page = isset($_GET['page']) ? (int)sanitize_input($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)sanitize_input($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

$category_id = isset($_GET['category_id']) ? (int)sanitize_input($_GET['category_id']) : null;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float)sanitize_input($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) ? (float)sanitize_input($_GET['max_price']) : null;
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) 
         ? strtoupper(sanitize_input($_GET['order'])) 
         : 'DESC';

// Build query
$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

// Apply filters
if ($category_id) {
    $sql .= " AND p.category_id = :category_id";
    $params['category_id'] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = "%$search%";
}

if ($min_price !== null) {
    $sql .= " AND p.price >= :min_price";
    $params['min_price'] = $min_price;
}

if ($max_price !== null) {
    $sql .= " AND p.price <= :max_price";
    $params['max_price'] = $max_price;
}

// Add sorting
$allowed_sorts = ['name', 'price', 'created_at', 'stock_quantity'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
$sql .= " ORDER BY p.$sort $order";

// Add pagination
$sql .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $limit;
$params['offset'] = $offset;

// Count total products for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
$count_params = [];

if ($category_id) {
    $count_sql .= " AND p.category_id = :category_id";
    $count_params['category_id'] = $category_id;
}

if (!empty($search)) {
    $count_sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $count_params['search'] = "%$search%";
}

$count_stmt = $db->prepare($count_sql);
foreach ($count_params as $key => $value) {
    $count_stmt->bindValue(":$key", $value);
}
$count_stmt->execute();
$total_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
$total_products = $total_result['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === 'limit' || $key === 'offset') {
        $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(":$key", $value);
    }
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating for each product
foreach ($products as &$product) {
    $rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                   FROM reviews WHERE product_id = :product_id";
    $rating_stmt = $db->prepare($rating_sql);
    $rating_stmt->execute(['product_id' => $product['id']]);
    $rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
    
    $product['avg_rating'] = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
    $product['review_count'] = $rating_data['review_count'];
}

echo json_encode([
    "status" => "success",
    "data" => $products,
    "pagination" => [
        "current_page" => $page,
        "total_pages" => $total_pages,
        "total_products" => $total_products,
        "products_per_page" => $limit
    ]
]);
?>