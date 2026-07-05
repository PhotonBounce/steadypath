<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$db = new SQLite3(__DIR__ . '/db/steadypath.db');
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
$db->close();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$trialEnd = strtotime($user['trial_end_date']);
$now = time();
$daysLeft = ceil(($trialEnd - $now) / 86400);
$expired = ($now > $trialEnd) && !$user['is_paid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0d9488">
<title>SteadyPath — Recovery Companion</title>
<style>
/* ========== CSS VARIABLES & RESET ========== */
:root {
  --primary: #0d9488;
  --primary-light: #14b8a6;
  --primary-dark: #0f766e;
  --accent: #f59e0b;
  --danger: #ef4444;
  --danger-light: #fee2e2;
  --danger-dark: #b91c1c;
  --success: #22c55e;
  --warning: #f59e0b;
  --bg: #f8fafc;
  --card: #ffffff;
  --text: #1e293b;
  --text-secondary: #64748b;
  --border: #e2e8f0;
  --shadow: 0 1px 3px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
  --radius: 12px;
  --radius-sm: 8px;
  --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}
[data-theme="dark"] {
  --bg: #0f172a;
  --card: #1e293b;
  --text: #f1f5f9;
  --text-secondary: #94a3b8;
  --border: #334155;
  --shadow: 0 1px 3px rgba(0,0,0,0.3);
  --shadow-lg: 0 10px 25px rgba(0,0,0,0.4);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); transition: background 0.3s, color 0.3s; }
body { overflow-x: hidden; -webkit-tap-highlight-color: transparent; }

/* ========== TRIAL BANNER ========== */
.trial-banner {
  background: linear-gradient(90deg, var(--success), var(--primary));
  color: white; padding: 10px 16px; text-align: center; font-size: 0.85rem; font-weight: 600;
  display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200;
}
.trial-banner.expired {
  background: linear-gradient(90deg, var(--danger), #dc2626);
}
.trial-banner .trial-btn {
  background: rgba(255,255,255,0.25); color: white; border: none; padding: 6px 14px;
  border-radius: 16px; font-size: 0.75rem; font-weight: 600; cursor: pointer; text-decoration: none;
}
.trial-banner .trial-btn:hover { background: rgba(255,255,255,0.35); }
.trial-banner .logout-btn {
  background: none; border: none; color: white; font-size: 0.75rem; cursor: pointer; opacity: 0.8;
  margin-left: 8px;
}

/* ========== APP SHELL ========== */
#app { display: flex; flex-direction: column; height: 100vh; max-width: 600px; margin: 0 auto; }
#app-header {
  background: var(--card); padding: 12px 16px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
}
#app-header h1 { font-size: 1.1rem; color: var(--primary); display: flex; align-items: center; gap: 8px; }
#app-header .header-actions { display: flex; gap: 8px; }
#app-header button.icon-btn {
  background: none; border: none; font-size: 1.3rem; cursor: pointer; padding: 6px; border-radius: 8px;
  color: var(--text-secondary); transition: background 0.2s;
}
#app-header button.icon-btn:hover { background: var(--border); }

#app-content { flex: 1; overflow-y: auto; padding: 16px; scroll-behavior: smooth; }

#app-nav {
  background: var(--card); border-top: 1px solid var(--border); display: flex; justify-content: space-around;
  padding: 6px 0; position: sticky; bottom: 0; z-index: 100;
}
#app-nav button {
  background: none; border: none; display: flex; flex-direction: column; align-items: center;
  gap: 2px; font-size: 0.65rem; color: var(--text-secondary); cursor: pointer; padding: 4px 8px;
  border-radius: var(--radius-sm); transition: all 0.2s; flex: 1;
}
#app-nav button .nav-icon { font-size: 1.4rem; }
#app-nav button.active { color: var(--primary); background: rgba(13,148,136,0.08); }
#app-nav button:hover { color: var(--primary); }

/* ========== CRISIS BAR ========== */
#crisis-bar {
  background: linear-gradient(90deg, var(--danger), #dc2626); color: white; padding: 10px 16px;
  display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem; font-weight: 600;
  border-radius: 0 0 var(--radius) var(--radius); margin: -16px -16px 16px -16px;
}
#crisis-bar a { color: white; text-decoration: none; padding: 6px 12px; background: rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.8rem; }
#crisis-bar a:hover { background: rgba(255,255,255,0.3); }

/* ========== CARDS ========== */
.card {
  background: var(--card); border-radius: var(--radius); padding: 16px; margin-bottom: 16px;
  box-shadow: var(--shadow); border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover { transform: translateY(-1px); box-shadow: var(--shadow-lg); }
.card-title { font-size: 1rem; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: var(--text); }
.card-title .icon { font-size: 1.2rem; }

/* ========== SOBRIETY CLOCK ========== */
.sobriety-clock {
  text-align: center; padding: 24px 16px; background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white; border-radius: var(--radius); margin-bottom: 16px;
}
.sobriety-clock .time-display { font-size: 2.4rem; font-weight: 700; letter-spacing: -1px; margin-bottom: 4px; }
.sobriety-clock .time-label { font-size: 0.85rem; opacity: 0.9; margin-bottom: 12px; }
.sobriety-clock .substance-tag {
  display: inline-block; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px;
  font-size: 0.8rem; margin-top: 8px;
}
.sobriety-clock .milestone { font-size: 0.85rem; margin-top: 12px; background: rgba(255,255,255,0.15); padding: 8px; border-radius: var(--radius-sm); }

/* ========== STATS ROW ========== */
.stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.stat-card {
  background: var(--card); border-radius: var(--radius); padding: 14px; text-align: center;
  border: 1px solid var(--border); box-shadow: var(--shadow);
}
.stat-card .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
.stat-card .stat-label { font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; }

/* ========== BUTTONS & INPUTS ========== */
.btn {
  display: inline-block; padding: 10px 18px; border-radius: var(--radius-sm); font-size: 0.9rem;
  font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-align: center; text-decoration: none;
}
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-danger { background: var(--danger); color: white; }
.btn-danger:hover { background: var(--danger-dark); }
.btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
.btn-outline:hover { background: var(--primary); color: white; }
.btn-block { width: 100%; display: block; }
.btn-lg { padding: 14px 24px; font-size: 1rem; }

input[type="text"], input[type="number"], input[type="email"], input[type="password"], textarea, select {
  width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm);
  font-size: 0.95rem; background: var(--card); color: var(--text); font-family: var(--font);
  transition: border 0.2s;
}
input:focus, textarea:focus, select:focus { outline: none; border-color: var(--primary); }
textarea { min-height: 80px; resize: vertical; }

label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
.form-group { margin-bottom: 16px; }

/* ========== SLIDER ========== */
.slider-container { margin: 12px 0; }
.slider-labels { display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 4px; }
input[type="range"] {
  width: 100%; -webkit-appearance: none; height: 8px; border-radius: 4px; background: var(--border); outline: none;
}
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none; width: 22px; height: 22px; border-radius: 50%; background: var(--primary); cursor: pointer;
  box-shadow: 0 2px 6px rgba(13,148,136,0.4);
}

/* ========== CHECK-IN HISTORY ========== */
.checkin-item {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 12px; margin-bottom: 10px; display: flex; gap: 12px; align-items: flex-start;
}
.checkin-item .date { font-size: 0.75rem; color: var(--text-secondary); min-width: 60px; }
.checkin-item .content { flex: 1; }
.checkin-item .mood { font-size: 0.85rem; }
.checkin-item .note { font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px; }
.mood-good { color: var(--success); }
.mood-okay { color: var(--accent); }
.mood-bad { color: var(--danger); }

/* ========== JOURNAL ========== */
.journal-entry {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 14px; margin-bottom: 12px;
}
.journal-entry .journal-date { font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 6px; }
.journal-entry .journal-text { font-size: 0.9rem; line-height: 1.5; }
.journal-entry .journal-tags { margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; }
.journal-entry .tag { font-size: 0.7rem; padding: 3px 8px; border-radius: 12px; background: var(--bg); color: var(--text-secondary); }

/* ========== TRIGGER TRACKER ========== */
.trigger-item {
  display: flex; align-items: center; justify-content: space-between; padding: 12px;
  border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 8px; background: var(--card);
}
.trigger-item .trigger-info { flex: 1; }
.trigger-item .trigger-name { font-weight: 600; font-size: 0.9rem; }
.trigger-item .trigger-meta { font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; }
.trigger-item .trigger-count { font-size: 1.2rem; font-weight: 700; color: var(--primary); min-width: 40px; text-align: center; }
.trigger-item .delete-btn { background: none; border: none; color: var(--danger); font-size: 1rem; cursor: pointer; padding: 4px; }

/* ========== BREATHING EXERCISE ========== */
.breath-circle {
  width: 160px; height: 160px; border-radius: 50%; margin: 24px auto;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; font-weight: 600;
  box-shadow: 0 0 30px rgba(13,148,136,0.3); transition: transform 4s ease-in-out;
}
.breath-circle.inhale { transform: scale(1.5); }
.breath-circle.exhale { transform: scale(1); }
.breath-instruction { text-align: center; font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 20px; }

/* ========== CRISIS RESOURCES ========== */
.crisis-card {
  background: var(--danger-light); border: 1px solid var(--danger); border-radius: var(--radius);
  padding: 16px; margin-bottom: 12px; text-align: center;
}
.crisis-card .crisis-title { color: var(--danger-dark); font-weight: 700; font-size: 1rem; margin-bottom: 8px; }
.crisis-card .crisis-number { font-size: 1.5rem; font-weight: 700; color: var(--danger); margin-bottom: 8px; }
.crisis-card .crisis-desc { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px; }
.crisis-card a.btn { color: white; text-decoration: none; }

.resource-card {
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 14px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; cursor: pointer;
  transition: all 0.2s; text-decoration: none; color: inherit;
}
.resource-card:hover { border-color: var(--primary); background: rgba(13,148,136,0.03); }
.resource-card .resource-icon { font-size: 1.5rem; }
.resource-card .resource-info { flex: 1; }
.resource-card .resource-name { font-weight: 600; font-size: 0.9rem; }
.resource-card .resource-desc { font-size: 0.75rem; color: var(--text-secondary); }
.resource-card .resource-arrow { color: var(--text-secondary); font-size: 1.2rem; }

/* ========== WITHDRAWAL INFO ========== */
.withdrawal-warning {
  background: var(--danger-light); border: 2px solid var(--danger); border-radius: var(--radius);
  padding: 16px; margin-bottom: 16px;
}
.withdrawal-warning h3 { color: var(--danger-dark); margin-bottom: 8px; font-size: 1rem; }
.withdrawal-warning p { color: var(--text); font-size: 0.9rem; line-height: 1.5; }
.withdrawal-warning .warning-box {
  background: white; border: 1px solid var(--danger); border-radius: var(--radius-sm); padding: 12px; margin-top: 12px;
}
[data-theme="dark"] .withdrawal-warning .warning-box { background: #1e293b; }
.withdrawal-warning .warning-box p { font-size: 0.85rem; margin-bottom: 6px; }
.withdrawal-warning .warning-box strong { color: var(--danger); }

.timeline-item {
  display: flex; gap: 12px; margin-bottom: 14px; padding-left: 8px; border-left: 3px solid var(--primary);
}
.timeline-item .time { font-weight: 700; color: var(--primary); font-size: 0.85rem; min-width: 80px; }
.timeline-item .desc { font-size: 0.85rem; color: var(--text-secondary); }

/* ========== AD SLOTS ========== */
.ad-slot {
  background: var(--card); border: 1px dashed var(--border); border-radius: var(--radius-sm);
  padding: 20px; text-align: center; margin-bottom: 16px; color: var(--text-secondary); font-size: 0.8rem;
  min-height: 90px; display: flex; align-items: center; justify-content: center; flex-direction: column;
}
.ad-slot .ad-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }

/* ========== SETTINGS ========== */
.setting-row {
  display: flex; align-items: center; justify-content: space-between; padding: 14px 0;
  border-bottom: 1px solid var(--border);
}
.setting-row:last-child { border-bottom: none; }
.setting-row .setting-info { flex: 1; }
.setting-row .setting-name { font-weight: 600; font-size: 0.9rem; }
.setting-row .setting-desc { font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; }

/* Toggle Switch */
.toggle {
  position: relative; width: 50px; height: 28px; cursor: pointer;
}
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle .slider {
  position: absolute; inset: 0; background: var(--border); border-radius: 28px; transition: 0.3s;
}
.toggle .slider:before {
  content: ""; position: absolute; height: 22px; width: 22px; left: 3px; bottom: 3px;
  background: white; border-radius: 50%; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.toggle input:checked + .slider { background: var(--primary); }
.toggle input:checked + .slider:before { transform: translateX(22px); }

/* ========== PAYWALL ========== */
#paywall {
  position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 10000;
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
#paywall-box {
  background: var(--card); max-width: 480px; width: 100%; border-radius: var(--radius);
  padding: 32px; box-shadow: var(--shadow-lg); text-align: center;
}
#paywall-box h2 { color: var(--primary); margin-bottom: 12px; }
#paywall-box p { color: var(--text-secondary); line-height: 1.6; margin-bottom: 20px; }
#paywall-box .price { font-size: 2.5rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
#paywall-box .price-sub { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px; }

/* ========== UTILITY ========== */
.hidden { display: none !important; }
.text-center { text-align: center; }
.mt-1 { margin-top: 8px; } .mt-2 { margin-top: 16px; } .mt-3 { margin-top: 24px; }
.mb-1 { margin-bottom: 8px; } .mb-2 { margin-bottom: 16px; } .mb-3 { margin-bottom: 24px; }
.p-1 { padding: 8px; } .p-2 { padding: 16px; }
.small { font-size: 0.8rem; }
.danger-text { color: var(--danger); }
.success-text { color: var(--success); }

/* ========== ANIMATIONS ========== */
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeIn 0.3s ease-out; }

@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
.pulse { animation: pulse 2s infinite; }

/* ========== RESPONSIVE ========== */
@media (min-width: 600px) {
  #app { border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
}

/* Scrollbar styling */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }
</style>
</head>
<body>

<!-- Trial Banner -->
<div class="trial-banner <?php echo $expired ? 'expired' : ''; ?>" id="trial-banner">
  <span>🎉 <?php echo $expired ? 'Your trial has expired' : ($daysLeft . ' day' . ($daysLeft !== 1 ? 's' : '') . ' left in your free trial'); ?></span>
  <div>
    <?php if ($expired): ?>
      <a href="#" class="trial-btn" onclick="showPaywall()">Upgrade</a>
    <?php endif; ?>
    <button class="logout-btn" onclick="doLogout()">Logout</button>
  </div>
</div>

<!-- Paywall Modal -->
<div id="paywall" class="hidden">
  <div id="paywall-box">
    <div style="font-size:3rem;margin-bottom:12px;">💚</div>
    <h2>Continue Your Recovery</h2>
    <p>Your 7-day free trial has ended. Upgrade to keep your data, journal, and progress safe.</p>
    <div class="price">$4.99</div>
    <div class="price-sub">per month — cancel anytime</div>
    <button class="btn btn-primary btn-lg btn-block" onclick="alert('Stripe integration coming soon. Contact support for manual activation.')">Upgrade Now</button>
    <p class="small" style="margin-top:16px;">Not ready? You can still <a href="download.php">download the free APK</a> or use the <a href="index.html">PWA version</a>.</p>
  </div>
</div>

<!-- ========== APP SHELL ========== -->
<div id="app">
  <header id="app-header">
    <h1>🌿 SteadyPath</h1>
    <div class="header-actions">
      <button class="icon-btn" id="theme-toggle" title="Toggle Dark Mode">🌙</button>
    </div>
  </header>

  <div id="crisis-bar">
    <span>🚨 Need help now?</span>
    <a href="tel:988">Call 988</a>
  </div>

  <main id="app-content">
    <!-- ========== DASHBOARD VIEW ========== -->
    <div id="view-dashboard" class="view">
      <div class="sobriety-clock fade-in">
        <div class="time-display" id="clock-time">0d 0h 0m</div>
        <div class="time-label">since your last drink or substance</div>
        <div class="substance-tag" id="clock-substance">Alcohol</div>
        <div class="milestone" id="clock-milestone">🎯 Next milestone: 24 hours</div>
      </div>

      <div class="stats-row fade-in">
        <div class="stat-card">
          <div class="stat-value" id="stat-money">$0</div>
          <div class="stat-label">Money Saved</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" id="stat-checkins">0</div>
          <div class="stat-label">Check-ins</div>
        </div>
      </div>

      <div class="card fade-in">
        <div class="card-title"><span class="icon">📋</span> Quick Check-In</div>
        <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:12px;">How are you feeling right now?</p>
        <div class="form-group">
          <label>Mood (1-10)</label>
          <div class="slider-labels"><span>😢</span><span>😐</span><span>😊</span></div>
          <input type="range" min="1" max="10" value="5" id="quick-mood">
        </div>
        <div class="form-group">
          <label>Cravings (1-10)</label>
          <div class="slider-labels"><span>None</span><span>Moderate</span><span>Extreme</span></div>
          <input type="range" min="1" max="10" value="1" id="quick-cravings">
        </div>
        <button class="btn btn-primary btn-block" id="btn-quick-checkin">Save Check-In</button>
      </div>

      <div class="card fade-in">
        <div class="card-title"><span class="icon">💡</span> Daily Motivation</div>
        <p id="daily-quote" style="font-style:italic;font-size:0.95rem;line-height:1.6;">"Recovery is not a race. You don't have to feel guilty if it takes you longer than you thought it would."</p>
        <p id="quote-author" style="text-align:right;font-size:0.8rem;color:var(--text-secondary);margin-top:8px;">— Unknown</p>
      </div>
    </div>

    <!-- ========== CHECK-IN VIEW ========== -->
    <div id="view-checkin" class="view hidden">
      <div class="card">
        <div class="card-title"><span class="icon">📝</span> Daily Check-In</div>
        <div class="form-group">
          <label>How are you feeling today? (1-10)</label>
          <div class="slider-labels"><span>Very low</span><span>Okay</span><span>Great</span></div>
          <input type="range" min="1" max="10" value="5" id="checkin-mood">
        </div>
        <div class="form-group">
          <label>How strong are your cravings? (1-10)</label>
          <div class="slider-labels"><span>None</span><span>Moderate</span><span>Unbearable</span></div>
          <input type="range" min="1" max="10" value="1" id="checkin-cravings">
        </div>
        <div class="form-group">
          <label>Did you stay sober today?</label>
          <select id="checkin-sober">
            <option value="yes">Yes, I stayed sober today</option>
            <option value="no">No, I had a slip today</option>
            <option value="struggle">I struggled but stayed sober</option>
          </select>
        </div>
        <div class="form-group">
          <label>Journal Note (optional)</label>
          <textarea id="checkin-note" placeholder="What's on your mind? What triggered you? What helped?"></textarea>
        </div>
        <button class="btn btn-primary btn-block btn-lg" id="btn-save-checkin">Save Check-In</button>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">📅</span> Your History</div>
        <div id="checkin-history"></div>
      </div>
    </div>

    <!-- ========== JOURNAL VIEW ========== -->
    <div id="view-journal" class="view hidden">
      <div class="card">
        <div class="card-title"><span class="icon">📔</span> New Journal Entry</div>
        <div class="form-group">
          <textarea id="journal-text" placeholder="Write freely. This is your private space. No one else can read this."></textarea>
        </div>
        <div class="form-group">
          <label>Tags</label>
          <input type="text" id="journal-tags" placeholder="gratitude, struggle, victory, fear...">
        </div>
        <button class="btn btn-primary btn-block" id="btn-save-journal">Save Entry</button>
      </div>
      <div class="card">
        <div class="card-title"><span class="icon">📚</span> Your Journal</div>
        <div id="journal-entries"></div>
      </div>
    </div>

    <!-- ========== TOOLS VIEW ========== -->
    <div id="view-tools" class="view hidden">
      <div class="card">
        <div class="card-title"><span class="icon">💰</span> Money & Time Saved</div>
        <div class="form-group">
          <label>How much did you spend per day on your substance? ($)</label>
          <input type="number" id="daily-spend" placeholder="e.g. 25" min="0">
        </div>
        <div class="stats-row" style="margin-top:12px;">
          <div class="stat-card">
            <div class="stat-value" id="calc-money">$0</div>
            <div class="stat-label">Money Saved</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" id="calc-hours">0</div>
            <div class="stat-label">Hours Reclaimed</div>
          </div>
        </div>
        <button class="btn btn-outline btn-block" id="btn-update-calc">Update Calculator</button>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">🌬️</span> Breathing Exercise</div>
        <p class="breath-instruction" id="breath-text">Press start to begin a guided breathing exercise.</p>
        <div class="breath-circle" id="breath-circle">Ready</div>
        <button class="btn btn-primary btn-block" id="btn-breath">Start Breathing</button>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">⚡</span> Trigger Tracker</div>
        <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:12px;">Log what triggers your cravings to build awareness.</p>
        <div class="form-group">
          <input type="text" id="trigger-name" placeholder="What triggered you? (e.g., stress at work, party)">
        </div>
        <div class="form-group">
          <select id="trigger-type">
            <option value="emotional">Emotional (stress, anger, sadness)</option>
            <option value="social">Social (friends, parties, events)</option>
            <option value="environmental">Environmental (bar, store, location)</option>
            <option value="physical">Physical (pain, fatigue, hunger)</option>
            <option value="other">Other</option>
          </select>
        </div>
        <button class="btn btn-primary btn-block" id="btn-add-trigger">Log Trigger</button>
        <div id="trigger-list" style="margin-top:16px;"></div>
      </div>
    </div>

    <!-- ========== HELP VIEW ========== -->
    <div id="view-help" class="view hidden">
      <div class="withdrawal-warning">
        <h3>🚨 Withdrawal Can Be Dangerous</h3>
        <p>Alcohol and benzodiazepine (Xanax, Valium, Klonopin) withdrawal can cause <strong>seizures, delirium tremens, and death</strong>. Opioid withdrawal is rarely fatal but extremely uncomfortable.</p>
        <div class="warning-box">
          <p><strong>Never stop alcohol or benzodiazepines without medical supervision.</strong></p>
          <p>Your doctor can prescribe medications to make withdrawal safer and more comfortable.</p>
          <p><strong>If you experience severe symptoms, call 911 or go to the emergency room.</strong></p>
        </div>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">🆘</span> Crisis Resources — Call Now</div>
        <div class="crisis-card">
          <div class="crisis-title">Emergency</div>
          <div class="crisis-number">911</div>
          <div class="crisis-desc">For immediate medical emergencies</div>
          <a href="tel:911" class="btn btn-danger btn-block">Call 911</a>
        </div>
        <div class="crisis-card">
          <div class="crisis-title">Suicide & Crisis Lifeline</div>
          <div class="crisis-number">988</div>
          <div class="crisis-desc">Free, confidential, 24/7</div>
          <a href="tel:988" class="btn btn-danger btn-block">Call 988</a>
        </div>
        <div class="crisis-card">
          <div class="crisis-title">SAMHSA Treatment Locator</div>
          <div class="crisis-number">1-800-662-HELP</div>
          <div class="crisis-desc">Find detox, rehab, and treatment near you</div>
          <a href="tel:18006624357" class="btn btn-danger btn-block">Call SAMHSA</a>
        </div>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">📍</span> Find Support Near You</div>
        <a href="https://findtreatment.gov/" target="_blank" class="resource-card">
          <span class="resource-icon">🏥</span>
          <div class="resource-info">
            <div class="resource-name">SAMHSA Treatment Finder</div>
            <div class="resource-desc">Find detox, rehab, and counseling near you</div>
          </div>
          <span class="resource-arrow">›</span>
        </a>
        <a href="https://www.aa.org/" target="_blank" class="resource-card">
          <span class="resource-icon">🤝</span>
          <div class="resource-info">
            <div class="resource-name">Alcoholics Anonymous</div>
            <div class="resource-desc">Find AA meetings in your area</div>
          </div>
          <span class="resource-arrow">›</span>
        </a>
        <a href="https://www.na.org/" target="_blank" class="resource-card">
          <span class="resource-icon">💚</span>
          <div class="resource-info">
            <div class="resource-name">Narcotics Anonymous</div>
            <div class="resource-desc">Find NA meetings worldwide</div>
          </div>
          <span class="resource-arrow">›</span>
        </a>
        <a href="https://www.smartrecovery.org/" target="_blank" class="resource-card">
          <span class="resource-icon">🧠</span>
          <div class="resource-info">
            <div class="resource-name">SMART Recovery</div>
            <div class="resource-desc">Science-based recovery meetings</div>
          </div>
          <span class="resource-arrow">›</span>
        </a>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">⏱️</span> General Withdrawal Timeline</div>
        <p style="font-size:0.8rem;color:var(--danger);margin-bottom:12px;font-weight:600;">⚠️ This is general information only. Symptoms vary widely. Always consult a doctor.</p>
        <div class="timeline-item">
          <span class="time">6-12 hours</span>
          <span class="desc">Alcohol: anxiety, tremors, nausea, headache, insomnia</span>
        </div>
        <div class="timeline-item">
          <span class="time">24-48 hours</span>
          <span class="desc">Alcohol: seizures possible. Benzos: severe anxiety, panic, possible seizures.</span>
        </div>
        <div class="timeline-item">
          <span class="time">48-72 hours</span>
          <span class="desc">Alcohol: delirium tremens (DTs) risk — confusion, fever, hallucinations. Seek emergency care.</span>
        </div>
        <div class="timeline-item">
          <span class="time">3-7 days</span>
          <span class="desc">Symptoms typically peak and begin to improve with medical supervision.</span>
        </div>
        <div class="timeline-item">
          <span class="time">1-2 weeks</span>
          <span class="desc">Physical symptoms usually resolve. Psychological cravings may persist.</span>
        </div>
        <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:12px;"><strong>Remember:</strong> This timeline is educational only. Your experience may differ. <span class="danger-text">Always consult a healthcare provider before stopping.</span></p>
      </div>
    </div>

    <!-- ========== SETTINGS VIEW ========== -->
    <div id="view-settings" class="view hidden">
      <div class="card">
        <div class="card-title"><span class="icon">⚙️</span> Settings</div>
        <div class="setting-row">
          <div class="setting-info">
            <div class="setting-name">Dark Mode</div>
            <div class="setting-desc">Easier on the eyes at night</div>
          </div>
          <label class="toggle">
            <input type="checkbox" id="setting-dark">
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-row">
          <div class="setting-info">
            <div class="setting-name">Sobriety Start Date</div>
            <div class="setting-desc">When did you stop?</div>
          </div>
          <input type="date" id="setting-date" style="width:auto;min-width:140px;">
        </div>
        <div class="setting-row">
          <div class="setting-info">
            <div class="setting-name">Substance</div>
            <div class="setting-desc">What are you tracking?</div>
          </div>
          <select id="setting-substance" style="width:auto;min-width:140px;">
            <option>Alcohol</option>
            <option>Cannabis</option>
            <option>Opioids</option>
            <option>Benzodiazepines</option>
            <option>Cocaine</option>
            <option>Methamphetamine</option>
            <option>Nicotine</option>
            <option>Other</option>
          </select>
        </div>
        <div class="setting-row">
          <div class="setting-info">
            <div class="setting-name">Daily Spend</div>
            <div class="setting-desc">How much did you spend per day?</div>
          </div>
          <input type="number" id="setting-spend" placeholder="25" style="width:auto;min-width:100px;">
        </div>
        <div class="setting-row">
          <div class="setting-info">
            <div class="setting-name">Emergency Contact</div>
            <div class="setting-desc">Someone to call in crisis</div>
          </div>
          <input type="text" id="setting-contact" placeholder="Name & Phone" style="width:auto;min-width:150px;">
        </div>
        <div class="mt-2">
          <button class="btn btn-primary btn-block" id="btn-save-settings">Save Settings</button>
        </div>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">📄</span> Legal & Privacy</div>
        <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.5;margin-bottom:12px;">
          <strong>Medical Disclaimer:</strong> SteadyPath is not a medical service. It is for informational and peer support purposes only. It is not intended to diagnose, treat, cure, or prevent any disease or condition. Always seek the advice of qualified healthcare providers.
        </p>
        <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.5;margin-bottom:12px;">
          <strong>Privacy:</strong> Your data is stored securely on our servers. We do not sell your information. You can delete your account at any time.
        </p>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">⚠️</span> Danger Zone</div>
        <button class="btn btn-danger btn-block" id="btn-reset-data" style="margin-bottom:8px;">Reset All Data</button>
        <p class="small text-center">This will permanently delete all your check-ins, journal entries, and settings. This cannot be undone.</p>
      </div>

      <div class="card">
        <div class="card-title"><span class="icon">📱</span> Get the App</div>
        <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:12px;">Download the Android APK for offline access.</p>
        <a href="download.php" class="btn btn-outline btn-block">Download APK</a>
      </div>

      <!-- Footer disclaimer -->
      <div style="text-align:center;padding:16px 12px 80px;font-size:0.75rem;color:var(--text-secondary);line-height:1.5;">
        <p>SteadyPath is not a substitute for professional medical care.</p>
        <p>If you need help, contact SAMHSA at <strong>1-800-662-HELP</strong> or call <strong>988</strong>.</p>
        <p style="margin-top:8px;">© 2026 SteadyPath. Free forever. 💚</p>
      </div>
    </div>
  </main>

  <!-- ========== BOTTOM NAVIGATION ========== -->
  <nav id="app-nav">
    <button data-view="dashboard" class="active">
      <span class="nav-icon">🏠</span>
      <span>Home</span>
    </button>
    <button data-view="checkin">
      <span class="nav-icon">📝</span>
      <span>Check-In</span>
    </button>
    <button data-view="journal">
      <span class="nav-icon">📔</span>
      <span>Journal</span>
    </button>
    <button data-view="tools">
      <span class="nav-icon">🧰</span>
      <span>Tools</span>
    </button>
    <button data-view="help">
      <span class="nav-icon">🆘</span>
      <span>Help</span>
    </button>
    <button data-view="settings">
      <span class="nav-icon">⚙️</span>
      <span>More</span>
    </button>
  </nav>
</div>

<!-- Toast notification -->
<div id="toast" style="position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--primary);color:white;padding:10px 20px;border-radius:20px;font-size:0.85rem;z-index:1000;transition:transform 0.3s;box-shadow:var(--shadow-lg);opacity:0;"></div>

<script>
// ========== STEADYPATH SAAS APP ==========

const APP = {
  data: { startDate: '', substance: 'Alcohol', dailySpend: 25, checkins: [], journal: [], triggers: [], emergencyContact: '', darkMode: false },
  quotes: [
    { text: "Recovery is not a race. You don't have to feel guilty if it takes you longer than you thought it would.", author: "Unknown" },
    { text: "The only person you are destined to become is the person you decide to be.", author: "Ralph Waldo Emerson" },
    { text: "One day at a time. That's all any of us can do.", author: "Unknown" },
    { text: "You don't have to see the whole staircase, just take the first step.", author: "Martin Luther King Jr." },
    { text: "Your present circumstances don't determine where you go; they merely determine where you start.", author: "Nido Qubein" },
    { text: "Courage isn't having the strength to go on — it is going on when you don't have strength.", author: "Napoleon Bonaparte" },
    { text: "Rock bottom became the solid foundation on which I rebuilt my life.", author: "J.K. Rowling" },
    { text: "You are stronger than you think. You have gotten through every bad day so far.", author: "Unknown" },
    { text: "Progress, not perfection.", author: "Unknown" },
    { text: "Every moment is a fresh beginning.", author: "T.S. Eliot" },
    { text: "It does not matter how slowly you go as long as you do not stop.", author: "Confucius" },
    { text: "The greatest glory in living lies not in never falling, but in rising every time we fall.", author: "Nelson Mandela" }
  ],
  
  async init() {
    await this.loadData();
    this.setupNavigation();
    this.setupTheme();
    this.setupDashboard();
    this.setupCheckIn();
    this.setupJournal();
    this.setupTools();
    this.setupSettings();
    this.setupBreathing();
    
    const qIdx = Math.floor(Math.random() * this.quotes.length);
    const q = this.quotes[qIdx];
    document.getElementById('daily-quote').textContent = '"' + q.text + '"';
    document.getElementById('quote-author').textContent = '— ' + q.author;
    
    setInterval(() => this.updateClock(), 1000);
    this.updateClock();
    this.updateStats();
    this.renderCheckInHistory();
    this.renderJournal();
    this.renderTriggers();
  },
  
  async api(action, data) {
    try {
      const res = await fetch('api.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });
      const result = await res.json();
      if (result.error === 'Unauthorized') {
        window.location.href = 'index.php';
        return null;
      }
      return result;
    } catch (e) {
      this.showToast('Network error. Check your connection.');
      return null;
    }
  },
  
  async loadData() {
    const result = await this.api('get_data', {});
    if (!result) return;
    
    const s = result.sobriety || {};
    this.data.startDate = s.start_date || new Date().toISOString().split('T')[0];
    this.data.substance = s.substance || 'Alcohol';
    this.data.dailySpend = s.daily_spend || 25;
    this.data.emergencyContact = s.emergency_contact || '';
    this.data.darkMode = s.dark_mode ? true : false;
    
    this.data.checkins = (result.checkins || []).map(c => ({
      id: c.id,
      date: c.created_at,
      mood: c.mood,
      cravings: c.cravings,
      sober: c.sober,
      note: c.note
    }));
    
    this.data.journal = (result.journal || []).map(j => ({
      id: j.id,
      date: j.created_at,
      text: j.text,
      tags: j.tags
    }));
    
    this.data.triggers = (result.triggers || []).map(t => ({
      id: t.id,
      name: t.name,
      type: t.trigger_type,
      count: t.count
    }));
    
    // Apply dark mode
    if (this.data.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
      document.getElementById('theme-toggle').textContent = '☀️';
    }
    
    // Fill settings form
    document.getElementById('setting-date').value = this.data.startDate;
    document.getElementById('setting-substance').value = this.data.substance;
    document.getElementById('setting-spend').value = this.data.dailySpend;
    document.getElementById('setting-contact').value = this.data.emergencyContact;
    document.getElementById('daily-spend').value = this.data.dailySpend;
    document.getElementById('setting-dark').checked = this.data.darkMode;
  },
  
  setupNavigation() {
    const navButtons = document.querySelectorAll('#app-nav button');
    const views = document.querySelectorAll('.view');
    
    navButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const viewName = btn.dataset.view;
        navButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        views.forEach(v => v.classList.add('hidden'));
        document.getElementById('view-' + viewName).classList.remove('hidden');
      });
    });
  },
  
  setupTheme() {
    document.getElementById('theme-toggle').addEventListener('click', () => {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        document.getElementById('theme-toggle').textContent = '🌙';
        this.data.darkMode = false;
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.getElementById('theme-toggle').textContent = '☀️';
        this.data.darkMode = true;
      }
      this.saveSettings();
    });
  },
  
  setupDashboard() {
    document.getElementById('btn-quick-checkin').addEventListener('click', async () => {
      const mood = document.getElementById('quick-mood').value;
      const cravings = document.getElementById('quick-cravings').value;
      const result = await this.api('save_checkin', { mood: parseInt(mood), cravings: parseInt(cravings), sober: 'yes', note: 'Quick check-in' });
      if (result) {
        this.showToast('Check-in saved! 💚');
        await this.loadData();
        this.updateStats();
        this.renderCheckInHistory();
      }
    });
  },
  
  updateClock() {
    const start = new Date(this.data.startDate + 'T00:00:00');
    const now = new Date();
    const diff = now - start;
    if (diff < 0) { document.getElementById('clock-time').textContent = '0d 0h 0m'; return; }
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    document.getElementById('clock-time').textContent = days + 'd ' + hours + 'h ' + mins + 'm';
    document.getElementById('clock-substance').textContent = this.data.substance;
    let milestone = '';
    if (days < 1) milestone = '🎯 Next milestone: 24 hours';
    else if (days < 7) milestone = '🎯 Next milestone: 7 days';
    else if (days < 30) milestone = '🎯 Next milestone: 30 days';
    else if (days < 90) milestone = '🎯 Next milestone: 90 days';
    else if (days < 180) milestone = '🎯 Next milestone: 6 months';
    else if (days < 365) milestone = '🎯 Next milestone: 1 year';
    else milestone = '⭐ You are an inspiration!';
    document.getElementById('clock-milestone').textContent = milestone;
  },
  
  updateStats() {
    const start = new Date(this.data.startDate + 'T00:00:00');
    const now = new Date();
    const days = Math.max(0, Math.floor((now - start) / (1000 * 60 * 60 * 24)));
    const moneySaved = days * (this.data.dailySpend || 0);
    document.getElementById('stat-money').textContent = '$' + moneySaved.toLocaleString();
    document.getElementById('stat-checkins').textContent = this.data.checkins.length;
    document.getElementById('calc-money').textContent = '$' + moneySaved.toLocaleString();
    document.getElementById('calc-hours').textContent = (days * 2).toLocaleString();
  },
  
  setupCheckIn() {
    document.getElementById('btn-save-checkin').addEventListener('click', async () => {
      const mood = document.getElementById('checkin-mood').value;
      const cravings = document.getElementById('checkin-cravings').value;
      const sober = document.getElementById('checkin-sober').value;
      const note = document.getElementById('checkin-note').value.trim();
      const result = await this.api('save_checkin', { mood: parseInt(mood), cravings: parseInt(cravings), sober, note: note || 'No note' });
      if (result) {
        this.showToast('Check-in saved! Keep going! 💚');
        document.getElementById('checkin-note').value = '';
        await this.loadData();
        this.renderCheckInHistory();
        this.updateStats();
      }
    });
  },
  
  renderCheckInHistory() {
    const container = document.getElementById('checkin-history');
    if (!this.data.checkins.length) { container.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:20px;">No check-ins yet. Start today!</p>'; return; }
    container.innerHTML = this.data.checkins.slice(0, 20).map(c => {
      const d = new Date(c.date);
      const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const moodClass = c.mood >= 7 ? 'mood-good' : (c.mood >= 4 ? 'mood-okay' : 'mood-bad');
      const moodEmoji = c.mood >= 7 ? '😊' : (c.mood >= 4 ? '😐' : '😢');
      return '<div class="checkin-item">' +
        '<div class="date">' + dateStr + '</div>' +
        '<div class="content">' +
          '<div class="mood ' + moodClass + '">' + moodEmoji + ' Mood: ' + c.mood + '/10 · Cravings: ' + c.cravings + '/10</div>' +
          '<div class="note">' + (c.note || '') + '</div>' +
        '</div>' +
      '</div>';
    }).join('');
  },
  
  setupJournal() {
    document.getElementById('btn-save-journal').addEventListener('click', async () => {
      const text = document.getElementById('journal-text').value.trim();
      if (!text) { this.showToast('Please write something first.'); return; }
      const tags = document.getElementById('journal-tags').value.trim();
      const result = await this.api('save_journal', { text, tags });
      if (result) {
        this.showToast('Journal entry saved! 📔');
        document.getElementById('journal-text').value = '';
        document.getElementById('journal-tags').value = '';
        await this.loadData();
        this.renderJournal();
      }
    });
  },
  
  renderJournal() {
    const container = document.getElementById('journal-entries');
    if (!this.data.journal.length) { container.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:20px;">No entries yet. Write your first one above.</p>'; return; }
    container.innerHTML = this.data.journal.map(j => {
      const d = new Date(j.date);
      const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
      const tags = j.tags ? j.tags.split(',').map(t => '<span class="tag">' + t.trim() + '</span>').join('') : '';
      return '<div class="journal-entry">' +
        '<div class="journal-date">' + dateStr + '</div>' +
        '<div class="journal-text">' + j.text.replace(/\n/g, '<br>') + '</div>' +
        '<div class="journal-tags">' + tags + '</div>' +
      '</div>';
    }).join('');
  },
  
  setupTools() {
    document.getElementById('btn-update-calc').addEventListener('click', () => {
      this.data.dailySpend = parseFloat(document.getElementById('daily-spend').value) || 0;
      this.saveSettings();
      this.updateStats();
      this.showToast('Calculator updated');
    });
    
    document.getElementById('btn-add-trigger').addEventListener('click', async () => {
      const name = document.getElementById('trigger-name').value.trim();
      if (!name) { this.showToast('Enter a trigger name'); return; }
      const type = document.getElementById('trigger-type').value;
      const result = await this.api('save_trigger', { name, type });
      if (result) {
        this.showToast('Trigger logged!');
        document.getElementById('trigger-name').value = '';
        await this.loadData();
        this.renderTriggers();
      }
    });
  },
  
  renderTriggers() {
    const container = document.getElementById('trigger-list');
    if (!this.data.triggers.length) { container.innerHTML = '<p style="color:var(--text-secondary);text-align:center;">No triggers logged yet.</p>'; return; }
    container.innerHTML = this.data.triggers.map(t => {
      const typeLabels = { emotional: 'Emotional', social: 'Social', environmental: 'Environmental', physical: 'Physical', other: 'Other' };
      return '<div class="trigger-item">' +
        '<div class="trigger-info">' +
          '<div class="trigger-name">' + t.name + '</div>' +
          '<div class="trigger-meta">' + (typeLabels[t.type] || t.type) + '</div>' +
        '</div>' +
        '<div class="trigger-count">' + t.count + '</div>' +
      '</div>';
    }).join('');
  },
  
  setupBreathing() {
    let breathing = false;
    const circle = document.getElementById('breath-circle');
    const text = document.getElementById('breath-text');
    const btn = document.getElementById('btn-breath');
    
    btn.addEventListener('click', () => {
      if (breathing) { breathing = false; btn.textContent = 'Start Breathing'; text.textContent = 'Press start to begin a guided breathing exercise.'; circle.classList.remove('inhale', 'exhale'); circle.style.transform = 'scale(1)'; return; }
      breathing = true;
      btn.textContent = 'Stop';
      let phase = 0;
      const phases = [
        { text: 'Inhale... (4s)', action: () => { circle.textContent = 'Inhale'; circle.classList.add('inhale'); circle.classList.remove('exhale'); } },
        { text: 'Hold... (4s)', action: () => { circle.textContent = 'Hold'; } },
        { text: 'Exhale... (4s)', action: () => { circle.textContent = 'Exhale'; circle.classList.remove('inhale'); circle.classList.add('exhale'); } },
        { text: 'Hold... (4s)', action: () => { circle.textContent = 'Hold'; } }
      ];
      
      const run = () => {
        if (!breathing) return;
        text.textContent = phases[phase].text;
        phases[phase].action();
        phase = (phase + 1) % 4;
        setTimeout(run, 4000);
      };
      run();
    });
  },
  
  setupSettings() {
    document.getElementById('btn-save-settings').addEventListener('click', () => {
      this.data.startDate = document.getElementById('setting-date').value;
      this.data.substance = document.getElementById('setting-substance').value;
      this.data.dailySpend = parseFloat(document.getElementById('setting-spend').value) || 0;
      this.data.emergencyContact = document.getElementById('setting-contact').value;
      this.data.darkMode = document.getElementById('setting-dark').checked;
      this.saveSettings();
      this.updateClock();
      this.updateStats();
      this.showToast('Settings saved!');
    });
    
    document.getElementById('btn-reset-data').addEventListener('click', async () => {
      if (!confirm('WARNING: This will permanently delete ALL your data. This cannot be undone. Are you sure?')) return;
      const result = await this.api('reset_data', {});
      if (result) {
        this.showToast('All data has been reset.');
        await this.loadData();
        this.updateClock();
        this.updateStats();
        this.renderCheckInHistory();
        this.renderJournal();
        this.renderTriggers();
      }
    });
  },
  
  async saveSettings() {
    await this.api('save_sobriety', {
      start_date: this.data.startDate,
      substance: this.data.substance,
      daily_spend: this.data.dailySpend,
      emergency_contact: this.data.emergencyContact,
      dark_mode: this.data.darkMode ? 1 : 0
    });
  },
  
  showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';
    setTimeout(() => {
      toast.style.transform = 'translateX(-50%) translateY(100px)';
      toast.style.opacity = '0';
    }, 3000);
  }
};

async function doLogout() {
  await fetch('api.php?action=logout', { method: 'POST' });
  window.location.href = 'index.php';
}

function showPaywall() {
  document.getElementById('paywall').classList.remove('hidden');
}

APP.init();
</script>

</body>
</html>
