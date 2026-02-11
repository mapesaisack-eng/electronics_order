<?php
require_once __DIR__ . '/../config/database.php';

class AuthMiddleware {
    
    // Check if user is authenticated via session
    public static function authenticate() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            sendError('Authentication required', 401);
        }
        
        return $_SESSION['user_id'];
    }
    
    // Check if user is admin
    public static function requireAdmin() {
        $userId = self::authenticate();
        
        $db = getDB();
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || $user['role'] !== 'admin') {
            sendError('Admin access required', 403);
        }
        
        return $userId;
    }
    
    // Get current user info
    public static function getCurrentUser() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
    
    // Check if user owns a resource
    public static function checkResourceOwnership($resourceType, $resourceId) {
        $userId = self::authenticate();
        $currentUser = self::getCurrentUser();
        
        // Admin can access any resource
        if ($currentUser['role'] === 'admin') {
            return true;
        }
        
        $db = getDB();
        
        switch ($resourceType) {
            case 'order':
                $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
                break;
            case 'review':
                $stmt = $db->prepare("SELECT user_id FROM reviews WHERE id = ?");
                break;
            case 'cart':
                $stmt = $db->prepare("SELECT user_id FROM cart WHERE id = ?");
                break;
            default:
                sendError('Invalid resource type', 400);
        }
        
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();
        
        if (!$resource) {
            sendError('Resource not found', 404);
        }
        
        if ($resource['user_id'] != $userId) {
            sendError('Access denied to this resource', 403);
        }
        
        return true;
    }
    
    // Rate limiting middleware
    public static function rateLimit($requestsPerMinute = 60) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit_$ip";
        
        session_start();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'timestamp' => time()
            ];
        } else {
            $data = $_SESSION[$key];
            
            // Reset counter if a minute has passed
            if (time() - $data['timestamp'] > 60) {
                $_SESSION[$key] = [
                    'count' => 1,
                    'timestamp' => time()
                ];
            } else {
                $data['count']++;
                
                if ($data['count'] > $requestsPerMinute) {
                    sendError('Too many requests. Please try again later.', 429);
                }
                
                $_SESSION[$key] = $data;
            }
        }
        
        return true;
    }
    
    // CSRF protection middleware
    public static function verifyCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
            $_SERVER['REQUEST_METHOD'] === 'PUT' || 
            $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                    ($_POST['csrf_token'] ?? '');
            
            session_start();
            
            if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
                sendError('Invalid CSRF token', 403);
            }
        }
        
        return true;