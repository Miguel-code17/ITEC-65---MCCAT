<?php
/**
 * Security & Validation Utilities
 * Component 5: Code Quality & Security
 */

class SecurityUtils {
    /**
     * Validate and sanitize user input
     */
    public static function sanitizeInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (basic Philippine format)
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
    
    /**
     * Hash password using bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Get CSRF token HTML input
     */
    public static function getCSRFTokenInput() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate decimal input for prices
     */
    public static function validatePrice($price) {
        return is_numeric($price) && $price >= 0 && $price <= 999999.99;
    }
    
    /**
     * Validate quantity input
     */
    public static function validateQuantity($quantity) {
        $qty = (int)$quantity;
        return $qty > 0 && $qty <= 9999;
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }
    
    /**
     * Check rate limiting (simple implementation)
     */
    public static function checkRateLimit($key, $limit = 5, $timeframe = 60) {
        $cache_key = "ratelimit_" . md5($key);
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = ['count' => 0, 'first_request' => time()];
        }
        
        $data = $_SESSION[$cache_key];
        $elapsed = time() - $data['first_request'];
        
        if ($elapsed > $timeframe) {
            $_SESSION[$cache_key] = ['count' => 1, 'first_request' => time()];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            return false;
        }
        
        $_SESSION[$cache_key]['count']++;
        return true;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($conn, $user_id, $event_type, $description, $severity = 'INFO') {
        $query = "INSERT INTO activity_log (user_id, action, description) 
                  VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('iss', $user_id, $event_type, $description);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Session Management
 */
class SessionManager {
    /**
     * Start secure session
     */
    public static function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', 3600); // 1 hour
            
            session_start();
        }
    }
    
    /**
     * Regenerate session ID (for security)
     */
    public static function regenerateSessionID() {
        session_regenerate_id(true);
    }
    
    /**
     * Destroy session
     */
    public static function destroySession() {
        $_SESSION = [];
        session_destroy();
    }
    
    /**
     * Check session timeout
     */
    public static function checkSessionTimeout($timeout = 3600) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::destroySession();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
}

// Security headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' https://cdn.jsdelivr.net; style-src \'self\' https://cdn.jsdelivr.net \'unsafe-inline\'');
}

// Initialize session
SessionManager::startSecureSession();

// Set security headers for all pages
setSecurityHeaders();
?>
