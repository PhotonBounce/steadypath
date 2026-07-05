<?php
// Photon Bounce SaaS — REST API Router
// All endpoints return JSON. Auth via Bearer JWT token.

require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/leads/api', '', $path);
$path = trim($path, '/');
$segments = explode('/', $path);
$route = $segments[0] ?? '';
$subroute = $segments[1] ?? '';
$id = is_numeric($subroute) ? (int)$subroute : null;

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// ============================================================
// AUTH ROUTES
// ============================================================
if ($route === 'auth') {
    
    // POST /auth/register
    if ($method === 'POST' && $subroute === 'register') {
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json(['error' => 'Valid email required', 'code' => 'BAD_EMAIL'], 400);
        }
        if (strlen($password) < 8) {
            json(['error' => 'Password must be at least 8 characters', 'code' => 'SHORT_PASSWORD'], 400);
        }
        if (!$name) {
            json(['error' => 'Name required', 'code' => 'NO_NAME'], 400);
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $api_key = bin2hex(random_bytes(16));
        
        try {
            $stmt = db()->prepare('INSERT INTO users (email, password_hash, name, api_key) VALUES (?,?,?,?)');
            $stmt->execute([$email, $hash, $name, $api_key]);
            $user_id = db()->lastInsertId();
            
            $token = jwt_encode(['sub' => $user_id, 'email' => $email, 'tier' => 'free']);
            json([
                'token' => $token,
                'user' => ['id' => $user_id, 'email' => $email, 'name' => $name, 'tier' => 'free'],
                'tier_limits' => get_tier_limits('free'),
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                json(['error' => 'Email already registered', 'code' => 'EMAIL_EXISTS'], 409);
            }
            json(['error' => 'Registration failed', 'code' => 'DB_ERROR'], 500);
        }
    }
    
    // POST /auth/login
    if ($method === 'POST' && $subroute === 'login') {
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? '';
        
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            json(['error' => 'Invalid email or password', 'code' => 'BAD_CREDENTIALS'], 401);
        }
        
        // Update last login
        db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([time(), $user['id']]);
        
        $token = jwt_encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'tier' => $user['tier'],
        ]);
        
        json([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'tier' => $user['tier'],
                'tier_expires_at' => $user['tier_expires_at'],
            ],
            'tier_limits' => get_tier_limits($user['tier']),
        ]);
    }
    
    // POST /auth/refresh
    if ($method === 'POST' && $subroute === 'refresh') {
        $user = require_auth();
        $token = jwt_encode([
            'sub' => $user['sub'],
            'email' => $user['email'],
            'tier' => $user['tier'],
        ]);
        json(['token' => $token]);
    }
    
    json(['error' => 'Unknown auth route'], 404);
}

// ============================================================
// USER ROUTES (require auth)
// ============================================================
if ($route === 'me') {
    $user = require_auth();
    
    if ($method === 'GET') {
        $stmt = db()->prepare('SELECT id, email, name, tier, tier_expires_at, timezone, email_notifications, created_at FROM users WHERE id = ?');
        $stmt->execute([$user['sub']]);
        $u = $stmt->fetch();
        
        // Count microsites and leads
        $ms_count = db()->query('SELECT COUNT(*) FROM microsites WHERE user_id = ' . $user['sub'])->fetchColumn();
        $lead_count = db()->query('SELECT COUNT(*) FROM leads WHERE user_id = ' . $user['sub'])->fetchColumn();
        
        $u['microsites_count'] = (int)$ms_count;
        $u['leads_count'] = (int)$lead_count;
        $u['tier_limits'] = get_tier_limits($u['tier']);
        
        json($u);
    }
    
    if ($method === 'PUT') {
        $allowed = ['name', 'timezone', 'email_notifications'];
        $sets = [];
        $vals = [];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $sets[] = "$field = ?";
                $vals[] = $input[$field];
            }
        }
        if ($sets) {
            $vals[] = $user['sub'];
            db()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
        }
        json(['success' => true]);
    }
}

// ============================================================
// MICROSITES ROUTES
// ============================================================
if ($route === 'microsites') {
    $user = require_auth();
    $limits = get_tier_limits($user['tier']);
    
    // GET /microsites
    if ($method === 'GET' && !$id) {
        $stmt = db()->prepare('SELECT * FROM microsites WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['sub']]);
        $sites = $stmt->fetchAll();
        
        // Add lead counts
        foreach ($sites as &$site) {
            $c = db()->prepare('SELECT COUNT(*) FROM leads WHERE microsite_id = ?');
            $c->execute([$site['id']]);
            $site['lead_count'] = (int)$c->fetchColumn();
        }
        
        json(['microsites' => $sites, 'tier_limit' => $limits['max_microsites']]);
    }
    
    // POST /microsites
    if ($method === 'POST' && !$id) {
        // Check tier limit
        $count = db()->query('SELECT COUNT(*) FROM microsites WHERE user_id = ' . $user['sub'])->fetchColumn();
        if ($count >= $limits['max_microsites']) {
            json(['error' => 'Microsite limit reached. Upgrade to VIP.', 'code' => 'TIER_LIMIT'], 403);
        }
        
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($input['slug'] ?? ''));
        $niche = trim($input['niche'] ?? '');
        $display_name = trim($input['display_name'] ?? '');
        $url = filter_var($input['url'] ?? '', FILTER_VALIDATE_URL);
        $theme = $input['theme'] ?? 'default';
        
        if (!$slug || !$niche || !$display_name || !$url) {
            json(['error' => 'slug, niche, display_name, and url required', 'code' => 'MISSING_FIELDS'], 400);
        }
        
        try {
            $stmt = db()->prepare('INSERT INTO microsites (user_id, slug, niche, display_name, url, theme) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$user['sub'], $slug, $niche, $display_name, $url, $theme]);
            $site_id = db()->lastInsertId();
            
            $stmt = db()->prepare('SELECT * FROM microsites WHERE id = ?');
            $stmt->execute([$site_id]);
            json(['microsite' => $stmt->fetch(), 'success' => true]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                json(['error' => 'Slug already exists for this user', 'code' => 'DUPLICATE_SLUG'], 409);
            }
            json(['error' => 'Failed to create microsite', 'code' => 'DB_ERROR'], 500);
        }
    }
    
    // GET /microsites/:id
    if ($method === 'GET' && $id) {
        $stmt = db()->prepare('SELECT * FROM microsites WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['sub']]);
        $site = $stmt->fetch();
        if (!$site) json(['error' => 'Microsite not found', 'code' => 'NOT_FOUND'], 404);
        
        // Get recent leads
        $stmt = db()->prepare('SELECT * FROM leads WHERE microsite_id = ? ORDER BY created_at DESC LIMIT 10');
        $stmt->execute([$id]);
        $site['recent_leads'] = $stmt->fetchAll();
        
        // Get SEO runs
        $stmt = db()->prepare('SELECT * FROM seo_runs WHERE microsite_id = ? ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$id]);
        $site['seo_runs'] = $stmt->fetchAll();
        
        json($site);
    }
    
    // PUT /microsites/:id
    if ($method === 'PUT' && $id) {
        $allowed = ['display_name', 'url', 'theme', 'is_active'];
        $sets = [];
        $vals = [];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $sets[] = "$field = ?";
                $vals[] = $input[$field];
            }
        }
        if (!$sets) json(['error' => 'No fields to update'], 400);
        
        $vals[] = $id;
        $vals[] = $user['sub'];
        $stmt = db()->prepare('UPDATE microsites SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?');
        $stmt->execute($vals);
        
        json(['success' => true, 'updated' => $stmt->rowCount()]);
    }
    
    // DELETE /microsites/:id
    if ($method === 'DELETE' && $id) {
        $stmt = db()->prepare('DELETE FROM microsites WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['sub']]);
        json(['success' => true, 'deleted' => $stmt->rowCount()]);
    }
}

// ============================================================
// LEADS ROUTES
// ============================================================
if ($route === 'leads') {
    
    // POST /leads — Public endpoint (called by microsites)
    if ($method === 'POST' && !$id) {
        $api_key = $input['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
        
        // Find user by API key or microsite
        $user_id = null;
        $microsite_id = null;
        
        if ($api_key) {
            $stmt = db()->prepare('SELECT id, tier FROM users WHERE api_key = ?');
            $stmt->execute([$api_key]);
            $u = $stmt->fetch();
            if ($u) {
                $user_id = $u['id'];
            }
        }
        
        // Fallback: match by niche slug
        if (!$user_id && !empty($input['niche'])) {
            $stmt = db()->prepare('SELECT id, user_id FROM microsites WHERE slug = ? LIMIT 1');
            $stmt->execute([$input['niche']]);
            $ms = $stmt->fetch();
            if ($ms) {
                $user_id = $ms['user_id'];
                $microsite_id = $ms['id'];
            }
        }
        
        // If no matching user/microsite, store under a default system account for manual review
        if (!$user_id) {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute(['admin@photon-bounce.com']);
            $admin = $stmt->fetch();
            $user_id = $admin['id'] ?? 1;
        }
        
        // Spam check
        $spam_score = 0;
        $spam_words = ['viagra', 'casino', 'lottery', 'winner', 'click here', 'free money', 'crypto giveaway'];
        $text = strtolower(($input['message'] ?? '') . ' ' . ($input['name'] ?? ''));
        foreach ($spam_words as $word) {
            if (strpos($text, $word) !== false) $spam_score += 0.3;
        }
        // Duplicate detection
        if (!empty($input['email'])) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM leads WHERE email = ? AND created_at > ?');
            $stmt->execute([$input['email'], time() - 86400]);
            if ($stmt->fetchColumn() > 0) $spam_score += 0.2;
        }
        $spam_score = min(1.0, $spam_score);
        
        // Compute ML score
        $ml_score = compute_lead_score($input);
        
        $stmt = db()->prepare('
            INSERT INTO leads (user_id, microsite_id, name, email, phone, niche, budget, message, source, ip_address, user_agent, ml_score, spam_score, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ');
        $stmt->execute([
            $user_id, $microsite_id,
            trim($input['name'] ?? 'Anonymous'),
            filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL) ?: null,
            preg_replace('/[^0-9+\-]/', '', $input['phone'] ?? '') ?: null,
            $input['niche'] ?? null,
            $input['budget'] ?? null,
            trim($input['message'] ?? ''),
            $input['source'] ?? 'direct',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $ml_score,
            $spam_score,
            $spam_score > 0.7 ? 'spam' : 'new'
        ]);
        
        $lead_id = db()->lastInsertId();
        
        // Log activity
        db()->prepare('INSERT INTO lead_activities (lead_id, action, details) VALUES (?,?,?)')
            ->execute([$lead_id, 'status_change', 'Lead created' . ($spam_score > 0.7 ? ' (flagged as spam)' : '')]);
        
        json([
            'success' => true,
            'lead_id' => $lead_id,
            'ml_score' => round($ml_score, 2),
            'spam_score' => round($spam_score, 2),
            'status' => $spam_score > 0.7 ? 'spam' : 'new',
        ]);
    }
    
    // Everything below requires auth
    $user = require_auth();
    $limits = get_tier_limits($user['tier']);
    
    // GET /leads
    if ($method === 'GET' && !$id) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $per_page;
        
        $where = ['user_id = ?'];
        $params = [$user['sub']];
        
        if (!empty($_GET['status'])) {
            $where[] = 'status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['niche'])) {
            $where[] = 'niche = ?';
            $params[] = $_GET['niche'];
        }
        if (isset($_GET['microsite_id'])) {
            $where[] = 'microsite_id = ?';
            $params[] = (int)$_GET['microsite_id'];
        }
        if (!empty($_GET['search'])) {
            $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
            $s = '%' . $_GET['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        
        $where_sql = implode(' AND ', $where);
        
        // Get leads
        $stmt = db()->prepare("SELECT * FROM leads WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$per_page, $offset]));
        $leads = $stmt->fetchAll();
        
        // Get total count
        $stmt = db()->prepare("SELECT COUNT(*) FROM leads WHERE $where_sql");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        // Get status breakdown
        $stmt = db()->prepare('SELECT status, COUNT(*) as count FROM leads WHERE user_id = ? GROUP BY status');
        $stmt->execute([$user['sub']]);
        $status_counts = $stmt->fetchAll();
        
        json([
            'leads' => $leads,
            'pagination' => ['page' => $page, 'per_page' => $per_page, 'total' => $total, 'pages' => ceil($total / $per_page)],
            'status_breakdown' => $status_counts,
        ]);
    }
    
    // GET /leads/:id
    if ($method === 'GET' && $id) {
        $stmt = db()->prepare('SELECT * FROM leads WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['sub']]);
        $lead = $stmt->fetch();
        if (!$lead) json(['error' => 'Lead not found', 'code' => 'NOT_FOUND'], 404);
        
        // Get activities
        $stmt = db()->prepare('SELECT * FROM lead_activities WHERE lead_id = ? ORDER BY created_at DESC');
        $stmt->execute([$id]);
        $lead['activities'] = $stmt->fetchAll();
        
        json($lead);
    }
    
    // PUT /leads/:id
    if ($method === 'PUT' && $id) {
        $allowed = ['name', 'email', 'phone', 'budget', 'message', 'status', 'notes', 'follow_up_at'];
        $sets = [];
        $vals = [];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $sets[] = "$field = ?";
                $vals[] = $input[$field];
            }
        }
        if (!$sets) json(['error' => 'No fields to update'], 400);
        
        $vals[] = $id;
        $vals[] = $user['sub'];
        $stmt = db()->prepare('UPDATE leads SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?');
        $stmt->execute($vals);
        
        // Log activity if status changed
        if (isset($input['status'])) {
            db()->prepare('INSERT INTO lead_activities (lead_id, user_id, action, details) VALUES (?,?,?,?)')
                ->execute([$id, $user['sub'], 'status_change', 'Status changed to ' . $input['status']]);
        }
        
        json(['success' => true, 'updated' => $stmt->rowCount()]);
    }
    
    // DELETE /leads/:id
    if ($method === 'DELETE' && $id) {
        $stmt = db()->prepare('DELETE FROM leads WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['sub']]);
        json(['success' => true, 'deleted' => $stmt->rowCount()]);
    }
    
    // POST /leads/:id/note
    if ($method === 'POST' && $id && $subroute === 'note') {
        $note = trim($input['note'] ?? '');
        if (!$note) json(['error' => 'Note required'], 400);
        
        db()->prepare('INSERT INTO lead_activities (lead_id, user_id, action, details) VALUES (?,?,?,?)')
            ->execute([$id, $user['sub'], 'note_added', $note]);
        
        json(['success' => true]);
    }
}

// ============================================================
// ANALYTICS ROUTES (VIP only for advanced features)
// ============================================================
if ($route === 'analytics') {
    $user = require_auth();
    
    // GET /analytics/dashboard
    if ($method === 'GET' && ($subroute === 'dashboard' || !$subroute)) {
        $days = min(90, (int)($_GET['days'] ?? 30));
        $since = time() - ($days * 86400);
        
        // Basic stats (all tiers)
        $stats = [];
        
        $stmt = db()->prepare('SELECT COUNT(*) FROM leads WHERE user_id = ? AND created_at > ?');
        $stmt->execute([$user['sub'], $since]);
        $stats['total_leads'] = (int)$stmt->fetchColumn();
        
        $stmt = db()->prepare('SELECT COUNT(*) FROM leads WHERE user_id = ? AND status = ? AND created_at > ?');
        $stmt->execute([$user['sub'], 'won', $since]);
        $stats['won_leads'] = (int)$stmt->fetchColumn();
        
        $stmt = db()->prepare('SELECT AVG(ml_score) FROM leads WHERE user_id = ? AND created_at > ?');
        $stmt->execute([$user['sub'], $since]);
        $stats['avg_ml_score'] = round((float)$stmt->fetchColumn(), 2);
        
        $stmt = db()->prepare('SELECT COUNT(DISTINCT niche) FROM leads WHERE user_id = ? AND created_at > ?');
        $stmt->execute([$user['sub'], $since]);
        $stats['niches_reached'] = (int)$stmt->fetchColumn();
        
        // VIP: Trend data
        $trends = [];
        if (check_tier_feature('advanced_analytics', $user)) {
            $stmt = db()->prepare('
                SELECT date(created_at, "unixepoch") as day, COUNT(*) as count, AVG(ml_score) as avg_score
                FROM leads WHERE user_id = ? AND created_at > ?
                GROUP BY day ORDER BY day
            ');
            $stmt->execute([$user['sub'], $since]);
            $trends = $stmt->fetchAll();
        }
        
        // VIP: Top niches
        $top_niches = [];
        if (check_tier_feature('advanced_analytics', $user)) {
            $stmt = db()->prepare('
                SELECT niche, COUNT(*) as count, AVG(ml_score) as avg_score
                FROM leads WHERE user_id = ? AND created_at > ? AND niche IS NOT NULL
                GROUP BY niche ORDER BY count DESC LIMIT 10
            ');
            $stmt->execute([$user['sub'], $since]);
            $top_niches = $stmt->fetchAll();
        }
        
        json([
            'stats' => $stats,
            'trends' => $trends,
            'top_niches' => $top_niches,
            'tier' => $user['tier'],
        ]);
    }
}

// ============================================================
// SEO ROUTES (VIP only)
// ============================================================
if ($route === 'seo') {
    $user = require_auth();
    
    if (!check_tier_feature('auto_seo', $user)) {
        json(['error' => 'SEO features require VIP tier', 'code' => 'TIER_REQUIRED'], 403);
    }
    
    // GET /seo
    if ($method === 'GET' && !$id) {
        $stmt = db()->prepare('
            SELECT m.*, 
                (SELECT COUNT(*) FROM seo_runs WHERE microsite_id = m.id) as run_count,
                (SELECT MAX(created_at) FROM seo_runs WHERE microsite_id = m.id) as last_run
            FROM microsites m WHERE m.user_id = ?
        ');
        $stmt->execute([$user['sub']]);
        json(['microsites' => $stmt->fetchAll()]);
    }
    
    // POST /seo/run/:microsite_id
    if ($method === 'POST' && $subroute === 'run' && $id) {
        // This triggers an async SEO job (handled by Python cron)
        $stmt = db()->prepare('INSERT INTO seo_runs (microsite_id, type, status, details) VALUES (?,?,?,?)');
        $stmt->execute([$id, 'manual_trigger', 'pending', json_encode(['triggered_by' => $user['sub'], 'time' => time()])]);
        
        json(['success' => true, 'message' => 'SEO run queued. Results will appear shortly.']);
    }
}

// ============================================================
// EXPORT ROUTES (VIP only)
// ============================================================
if ($route === 'export') {
    $user = require_auth();
    
    if (!check_tier_feature('lead_export', $user)) {
        json(['error' => 'Export requires VIP tier', 'code' => 'TIER_REQUIRED'], 403);
    }
    
    if ($method === 'GET' && $subroute === 'leads') {
        $stmt = db()->prepare('SELECT * FROM leads WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['sub']]);
        $leads = $stmt->fetchAll();
        
        // Generate CSV
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Niche', 'Budget', 'Status', 'ML Score', 'Message', 'Source', 'Created']);
        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead['id'], $lead['name'], $lead['email'], $lead['phone'],
                $lead['niche'], $lead['budget'], $lead['status'], $lead['ml_score'],
                $lead['message'], $lead['source'], date('Y-m-d H:i', $lead['created_at'])
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leads_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
}

// ============================================================
// TIER / BILLING ROUTES
// ============================================================
if ($route === 'tier') {
    $user = require_auth();
    
    if ($method === 'GET') {
        json([
            'current' => $user['tier'],
            'limits' => get_tier_limits($user['tier']),
            'pricing' => $GLOBALS['PRICING'],
            'all_tiers' => [
                'free' => get_tier_limits('free'),
                'vip' => get_tier_limits('vip'),
            ],
        ]);
    }
}

// ============================================================
// FALLBACK
// ============================================================
json(['error' => 'Not found', 'route' => $route, 'method' => $method], 404);
