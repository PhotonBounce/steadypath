<?php
// Photon Bounce SaaS — Core Configuration
// Shared hosting compatible (SQLite, no external dependencies)

define('SAAS_VERSION', '2.0.0');
define('JWT_SECRET', getenv('PB_JWT_SECRET') ?: 'photon-bounce-saas-' . filemtime(__FILE__));
define('DB_PATH', __DIR__ . '/data/saas.db');
define('DATA_DIR', __DIR__ . '/data');
define('SITE_URL', 'https://photon-bounce.com');
define('API_BASE', SITE_URL . '/leads/api');

// Tier limits
$TIERS = [
    'free' => [
        'max_microsites' => 1,
        'ml_scoring' => false,
        'auto_seo' => false,
        'advanced_analytics' => false,
        'api_access' => false,
        'webhooks' => false,
        'team_members' => 1,
        'lead_export' => false,
        'priority_support' => false,
        'custom_domain' => false,
        'ads_on_mobile' => true,
    ],
    'vip' => [
        'max_microsites' => 999,
        'ml_scoring' => true,
        'auto_seo' => true,
        'advanced_analytics' => true,
        'api_access' => true,
        'webhooks' => true,
        'team_members' => 10,
        'lead_export' => true,
        'priority_support' => true,
        'custom_domain' => true,
        'ads_on_mobile' => false,
    ],
];

// Pricing
$PRICING = [
    'vip_monthly' => 19,
    'vip_yearly' => 199, // ~17% discount
    'currency' => 'USD',
];

// Ensure data directory exists
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Database connection
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }
    return $pdo;
}

// Initialize DB on first run
function init_db(): void {
    if (!file_exists(DB_PATH)) {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        db()->exec($sql);
    }
}

// JWT Helpers
function jwt_encode(array $payload): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + (60 * 60 * 24 * 7); // 7 days
    
    $b64_header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $b64_payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    
    $signature = hash_hmac('sha256', "$b64_header.$b64_payload", JWT_SECRET, true);
    $b64_sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    return "$b64_header.$b64_payload.$b64_sig";
}

function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/') . str_repeat('=', 3 - strlen($parts[1]) % 3)), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) return null;
    
    $sig = hash_hmac('sha256', "$parts[0].$parts[1]", JWT_SECRET, true);
    $b64_sig = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    if (!hash_equals($b64_sig, $parts[2])) return null;
    
    return $payload;
}

// Auth middleware
function require_auth(): array {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        http_response_code(401);
        json(['error' => 'Unauthorized', 'code' => 'NO_TOKEN']);
        exit;
    }
    $user = jwt_decode($m[1]);
    if (!$user) {
        http_response_code(401);
        json(['error' => 'Invalid or expired token', 'code' => 'BAD_TOKEN']);
        exit;
    }
    return $user;
}

function optional_auth(): ?array {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/', $auth, $m)) return null;
    return jwt_decode($m[1]) ?: null;
}

// JSON response helper
function json(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Tier helper
function get_tier_limits(string $tier): array {
    global $TIERS;
    return $TIERS[$tier] ?? $TIERS['free'];
}

function check_tier_feature(string $feature, array $user): bool {
    $limits = get_tier_limits($user['tier']);
    return $limits[$feature] ?? false;
}

// ML score helper (simple heuristic, enhanced by Python service)
function compute_lead_score(array $lead): float {
    $score = 0.5;
    
    // Budget signals
    if (!empty($lead['budget'])) {
        if (preg_match('/\$?\d{4,}/', $lead['budget'])) $score += 0.2;
        elseif (preg_match('/\$?\d{3}/', $lead['budget'])) $score += 0.1;
    }
    
    // Phone present
    if (!empty($lead['phone']) && strlen($lead['phone']) >= 10) $score += 0.15;
    
    // Email domain quality
    if (!empty($lead['email'])) {
        $domain = substr(strrchr($lead['email'], '@'), 1);
        $biz_domains = ['com', 'io', 'co', 'net', 'org'];
        $free_domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
        if (in_array($domain, $biz_domains)) $score += 0.05;
        if (!in_array($lead['email'], $free_domains)) $score += 0.05;
    }
    
    // Message length
    if (!empty($lead['message']) && strlen($lead['message']) > 50) $score += 0.1;
    
    // Spam signals
    $spam_words = ['viagra', 'casino', 'lottery', 'winner', 'click here', 'free money'];
    $text = strtolower(($lead['message'] ?? '') . ' ' . ($lead['name'] ?? ''));
    foreach ($spam_words as $word) {
        if (strpos($text, $word) !== false) $score -= 0.3;
    }
    
    return max(0.0, min(1.0, $score));
}

init_db();
