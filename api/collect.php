<?php
// Photon Bounce SaaS — Lead Collection Endpoint
// Accepts leads from microsites, stores in SaaS DB, returns ML score

require __DIR__ . '/api/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json(['error' => 'POST only']);
}

$input = array_merge($_POST, json_decode(file_get_contents('php://input'), true) ?? []);

// Required fields
$name = trim($input['name'] ?? '');
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = preg_replace('/[^0-9+\-]/', '', $input['phone'] ?? '');
$niche = preg_replace('/[^a-z0-9-]/', '', strtolower($input['niche'] ?? ''));
$budget = $input['budget'] ?? '';
$message = trim($input['message'] ?? '');
$variant = in_array($input['variant'] ?? '', ['a','b','c','d','e','f','g','h']) ? $input['variant'] : 'a';
$source = filter_var($input['source'] ?? 'https://photon-bounce.com', FILTER_VALIDATE_URL) ?: 'https://photon-bounce.com';

if (!$name) {
    http_response_code(400);
    json(['error' => 'Name is required']);
}

// Find user by niche microsite
$user_id = null;
$microsite_id = null;

if ($niche) {
    $stmt = db()->prepare('SELECT id, user_id FROM microsites WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$niche]);
    $ms = $stmt->fetch();
    if ($ms) {
        $user_id = $ms['user_id'];
        $microsite_id = $ms['id'];
    }
}

// Fallback to admin account for orphaned leads
if (!$user_id) {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute(['admin@photon-bounce.com']);
    $admin = $stmt->fetch();
    $user_id = $admin['id'] ?? 1;
}

// Spam detection
$spam_score = 0;
$spam_words = ['viagra', 'casino', 'lottery', 'winner', 'click here', 'free money', 'crypto giveaway', 'earn $', 'make money fast'];
$text = strtolower($message . ' ' . $name);
foreach ($spam_words as $word) {
    if (strpos($text, $word) !== false) $spam_score += 0.25;
}
// Honeypot check
if (!empty($input['website'])) $spam_score += 0.5; // honeypot field
// Rate limiting - same IP too many leads
$stmt = db()->prepare('SELECT COUNT(*) FROM leads WHERE ip_address = ? AND created_at > ?');
$stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', time() - 3600]);
if ($stmt->fetchColumn() > 10) $spam_score += 0.3;
$spam_score = min(1.0, $spam_score);

// ML Score
$ml_score = compute_lead_score([
    'name' => $name, 'email' => $email, 'phone' => $phone,
    'budget' => $budget, 'message' => $message
]);

// Insert lead
$stmt = db()->prepare('
    INSERT INTO leads (user_id, microsite_id, name, email, phone, niche, budget, message, source, ip_address, user_agent, ml_score, spam_score, status, variant)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
');
$stmt->execute([
    $user_id, $microsite_id, $name, $email ?: null, $phone ?: null,
    $niche ?: null, $budget ?: null, $message, $source,
    $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null,
    $ml_score, $spam_score, $spam_score > 0.7 ? 'spam' : 'new', $variant
]);

$lead_id = db()->lastInsertId();

// Log analytics event
db()->prepare('INSERT INTO analytics (user_id, microsite_id, event_type, value, meta) VALUES (?,?,?,?,?)')
    ->execute([$user_id, $microsite_id, 'lead_submit', $ml_score, json_encode(['spam_score' => $spam_score, 'variant' => $variant])]);

// Return response
$response = [
    'success' => true,
    'message' => 'Thank you! We will be in touch within 24 hours.',
    'lead_id' => $lead_id,
];

// Include ML data if user has VIP tier
$user_stmt = db()->prepare('SELECT tier FROM users WHERE id = ?');
$user_stmt->execute([$user_id]);
$user_tier = $user_stmt->fetchColumn();
if ($user_tier === 'vip') {
    $response['ml_score'] = round($ml_score, 2);
    $response['spam_score'] = round($spam_score, 2);
    $response['ml_tags'] = [];
    if ($ml_score > 0.7) $response['ml_tags'][] = 'hot';
    if ($ml_score > 0.5 && !empty($budget)) $response['ml_tags'][] = 'budget_confirmed';
    if (!empty($phone)) $response['ml_tags'][] = 'phone_ready';
    if ($spam_score > 0.5) $response['ml_tags'][] = 'review_for_spam';
}

header('Content-Type: application/json');
echo json_encode($response);
