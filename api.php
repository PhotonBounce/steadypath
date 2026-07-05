<?php
/**
 * SteadyPath SaaS API
 * SQLite backend with user auth, trial management, and data persistence
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$DB_PATH = __DIR__ . '/db/steadypath.db';

def getDB() {
    global $DB_PATH;
    $db = new SQLite3($DB_PATH);
    $db->busyTimeout(5000);
    return $db;
}

function initDB() {
    global $DB_PATH;
    if (file_exists($DB_PATH)) return;
    $db = new SQLite3($DB_PATH);
    $db->exec("PRAGMA journal_mode = WAL;");
    
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        name TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        trial_end_date TIMESTAMP,
        is_paid INTEGER DEFAULT 0,
        subscription_status TEXT DEFAULT 'trial'
    );");
    
    $db->exec("CREATE TABLE IF NOT EXISTS sobriety_data (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        start_date TEXT,
        substance TEXT DEFAULT 'Alcohol',
        daily_spend REAL DEFAULT 25,
        emergency_contact TEXT,
        dark_mode INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );");
    
    $db->exec("CREATE TABLE IF NOT EXISTS checkins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        mood INTEGER,
        cravings INTEGER,
        sober TEXT,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );");
    
    $db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        text TEXT,
        tags TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );");
    
    $db->exec("CREATE TABLE IF NOT EXISTS triggers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT,
        trigger_type TEXT,
        count INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );");
    
    $db->close();
}

initDB();

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function requireAuth() {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

function getTrialStatus($user) {
    $trialEnd = strtotime($user['trial_end_date']);
    $now = time();
    $daysLeft = ceil(($trialEnd - $now) / 86400);
    $expired = $now > $trialEnd;
    $active = $user['is_paid'] || !$expired;
    return [
        'trial_end' => $user['trial_end_date'],
        'days_left' => max(0, $daysLeft),
        'expired' => $expired && !$user['is_paid'],
        'active' => $active,
        'is_paid' => (bool)$user['is_paid']
    ];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$db = getDB();

switch ($action) {
    
    case 'register':
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Invalid email address'], 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $trialEnd = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, name, trial_end_date) VALUES (:email, :hash, :name, :trial)");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':trial', $trialEnd, SQLITE3_TEXT);
        
        try {
            $stmt->execute();
            $userId = $db->lastInsertRowID();
            
            // Create default sobriety data
            $stmt2 = $db->prepare("INSERT INTO sobriety_data (user_id, start_date) VALUES (:uid, :date)");
            $stmt2->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $stmt2->bindValue(':date', date('Y-m-d'), SQLITE3_TEXT);
            $stmt2->execute();
            
            $_SESSION['user_id'] = $userId;
            jsonResponse(['success' => true, 'user_id' => $userId, 'trial_end' => $trialEnd]);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Email already registered'], 409);
        }
        break;
    
    case 'login':
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Invalid email or password'], 401);
        }
        
        $_SESSION['user_id'] = $user['id'];
        $trial = getTrialStatus($user);
        jsonResponse(['success' => true, 'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'trial' => $trial
        ]]);
        break;
    
    case 'logout':
        session_destroy();
        jsonResponse(['success' => true]);
        break;
    
    case 'me':
        requireAuth();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) {
            session_destroy();
            jsonResponse(['error' => 'User not found'], 404);
        }
        
        $trial = getTrialStatus($user);
        jsonResponse(['user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'trial' => $trial
        ]]);
        break;
    
    case 'get_data':
        requireAuth();
        $uid = $_SESSION['user_id'];
        
        // Sobriety data
        $stmt = $db->prepare("SELECT * FROM sobriety_data WHERE user_id = :uid LIMIT 1");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $sobriety = $stmt->execute()->fetchArray(SQLITE3_ASSOC) ?: [
            'start_date' => date('Y-m-d'),
            'substance' => 'Alcohol',
            'daily_spend' => 25,
            'emergency_contact' => '',
            'dark_mode' => 0
        ];
        
        // Checkins
        $stmt = $db->prepare("SELECT * FROM checkins WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $checkins = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $checkins[] = $row;
        
        // Journal
        $stmt = $db->prepare("SELECT * FROM journal_entries WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $journal = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $journal[] = $row;
        
        // Triggers
        $stmt = $db->prepare("SELECT * FROM triggers WHERE user_id = :uid ORDER BY count DESC");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $triggers = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $triggers[] = $row;
        
        jsonResponse([
            'sobriety' => $sobriety,
            'checkins' => $checkins,
            'journal' => $journal,
            'triggers' => $triggers
        ]);
        break;
    
    case 'save_sobriety':
        requireAuth();
        $uid = $_SESSION['user_id'];
        
        $stmt = $db->prepare("SELECT id FROM sobriety_data WHERE user_id = :uid");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $existing = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        $startDate = $input['start_date'] ?? date('Y-m-d');
        $substance = $input['substance'] ?? 'Alcohol';
        $dailySpend = floatval($input['daily_spend'] ?? 25);
        $emergencyContact = $input['emergency_contact'] ?? '';
        $darkMode = intval($input['dark_mode'] ?? 0);
        
        if ($existing) {
            $stmt = $db->prepare("UPDATE sobriety_data SET start_date = :sd, substance = :sub, daily_spend = :ds, emergency_contact = :ec, dark_mode = :dm WHERE user_id = :uid");
        } else {
            $stmt = $db->prepare("INSERT INTO sobriety_data (user_id, start_date, substance, daily_spend, emergency_contact, dark_mode) VALUES (:uid, :sd, :sub, :ds, :ec, :dm)");
        }
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':sd', $startDate, SQLITE3_TEXT);
        $stmt->bindValue(':sub', $substance, SQLITE3_TEXT);
        $stmt->bindValue(':ds', $dailySpend, SQLITE3_FLOAT);
        $stmt->bindValue(':ec', $emergencyContact, SQLITE3_TEXT);
        $stmt->bindValue(':dm', $darkMode, SQLITE3_INTEGER);
        $stmt->execute();
        
        jsonResponse(['success' => true]);
        break;
    
    case 'save_checkin':
        requireAuth();
        $uid = $_SESSION['user_id'];
        
        $stmt = $db->prepare("INSERT INTO checkins (user_id, mood, cravings, sober, note) VALUES (:uid, :mood, :cravings, :sober, :note)");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':mood', intval($input['mood'] ?? 5), SQLITE3_INTEGER);
        $stmt->bindValue(':cravings', intval($input['cravings'] ?? 1), SQLITE3_INTEGER);
        $stmt->bindValue(':sober', $input['sober'] ?? 'yes', SQLITE3_TEXT);
        $stmt->bindValue(':note', $input['note'] ?? '', SQLITE3_TEXT);
        $stmt->execute();
        
        jsonResponse(['success' => true, 'id' => $db->lastInsertRowID()]);
        break;
    
    case 'save_journal':
        requireAuth();
        $uid = $_SESSION['user_id'];
        
        $stmt = $db->prepare("INSERT INTO journal_entries (user_id, text, tags) VALUES (:uid, :text, :tags)");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':text', $input['text'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':tags', $input['tags'] ?? '', SQLITE3_TEXT);
        $stmt->execute();
        
        jsonResponse(['success' => true, 'id' => $db->lastInsertRowID()]);
        break;
    
    case 'save_trigger':
        requireAuth();
        $uid = $_SESSION['user_id'];
        $name = trim($input['name'] ?? '');
        $type = $input['type'] ?? 'other';
        
        // Check if trigger exists for this user
        $stmt = $db->prepare("SELECT id, count FROM triggers WHERE user_id = :uid AND name = :name");
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $existing = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if ($existing) {
            $stmt = $db->prepare("UPDATE triggers SET count = count + 1, trigger_type = :type WHERE id = :id");
            $stmt->bindValue(':id', $existing['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO triggers (user_id, name, trigger_type) VALUES (:uid, :name, :type)");
            $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->execute();
        }
        
        jsonResponse(['success' => true]);
        break;
    
    case 'delete_checkin':
        requireAuth();
        $uid = $_SESSION['user_id'];
        $id = intval($input['id'] ?? 0);
        
        $stmt = $db->prepare("DELETE FROM checkins WHERE id = :id AND user_id = :uid");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->execute();
        
        jsonResponse(['success' => true]);
        break;
    
    case 'delete_journal':
        requireAuth();
        $uid = $_SESSION['user_id'];
        $id = intval($input['id'] ?? 0);
        
        $stmt = $db->prepare("DELETE FROM journal_entries WHERE id = :id AND user_id = :uid");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->execute();
        
        jsonResponse(['success' => true]);
        break;
    
    case 'delete_trigger':
        requireAuth();
        $uid = $_SESSION['user_id'];
        $id = intval($input['id'] ?? 0);
        
        $stmt = $db->prepare("DELETE FROM triggers WHERE id = :id AND user_id = :uid");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->execute();
        
        jsonResponse(['success' => true]);
        break;
    
    case 'reset_data':
        requireAuth();
        $uid = $_SESSION['user_id'];
        
        $db->exec("DELETE FROM checkins WHERE user_id = $uid");
        $db->exec("DELETE FROM journal_entries WHERE user_id = $uid");
        $db->exec("DELETE FROM triggers WHERE user_id = $uid");
        $db->exec("UPDATE sobriety_data SET start_date = '".date('Y-m-d')."', substance = 'Alcohol', daily_spend = 25, emergency_contact = '', dark_mode = 0 WHERE user_id = $uid");
        
        jsonResponse(['success' => true]);
        break;
    
    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}

$db->close();
