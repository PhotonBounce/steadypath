<?php
// Photon Bounce SaaS — Dashboard SPA Shell
// Serves the main dashboard application

// Check for JWT in cookie or redirect to login
$token = $_COOKIE['pb_token'] ?? '';
$is_authed = false;
$user = null;

if ($token) {
    require __DIR__ . '/api/config.php';
    $user = jwt_decode($token);
    if ($user) {
        $is_authed = true;
        $stmt = db()->prepare('SELECT name, email, tier FROM users WHERE id = ?');
        $stmt->execute([$user['sub']]);
        $user = array_merge($user, $stmt->fetch() ?: []);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Photon Bounce — Lead Command Center</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
<style>
:root {
  --bg: #0a0f1e; --bg2: #111827; --bg3: #1a2332;
  --text: #e2e8f0; --text2: #94a3b8; --text3: #64748b;
  --accent: #00d4ff; --accent2: #ffd400; --accent3: #ff6b6b;
  --success: #2dd4bf; --warning: #f59e0b; --danger: #ef4444;
  --vip: #c9a84c; --radius: 12px; --shadow: 0 4px 24px rgba(0,0,0,0.3);
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--bg); color: var(--text); min-height: 100vh;
  overflow-x: hidden;
}
body.mobile-app-mode { padding-top: env(safe-area-inset-top); }

/* Loading Screen */
#loader {
  position: fixed; inset: 0; background: var(--bg); z-index: 9999;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  transition: opacity 0.4s;
}
#loader.done { opacity: 0; pointer-events: none; }
#loader .logo { font-size: 4rem; animation: pulse 2s ease-in-out infinite; }
#loader .title { font-size: 1.5rem; font-weight: 700; margin-top: 16px; letter-spacing: -0.5px; }
#loader .subtitle { color: var(--text2); font-size: 0.9rem; margin-top: 4px; }
#loader .bar { width: 200px; height: 3px; background: var(--bg3); border-radius: 3px; margin-top: 24px; overflow: hidden; }
#loader .bar-inner { height: 100%; width: 0%; background: var(--accent); border-radius: 3px; animation: loadBar 1.5s ease forwards; }
@keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }
@keyframes loadBar { to { width: 100%; } }

/* Auth Screens */
.auth-screen {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  padding: 24px; background: radial-gradient(ellipse at 50% 0%, #1a2332 0%, var(--bg) 70%);
}
.auth-card {
  background: var(--bg2); border: 1px solid rgba(0,212,255,0.1);
  border-radius: var(--radius); padding: 40px; width: 100%; max-width: 420px;
  box-shadow: var(--shadow);
}
.auth-card .logo { font-size: 3rem; text-align: center; }
.auth-card h1 { text-align: center; font-size: 1.5rem; margin: 12px 0 4px; }
.auth-card .tagline { text-align: center; color: var(--text2); font-size: 0.9rem; margin-bottom: 28px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 0.85rem; color: var(--text2); margin-bottom: 6px; }
.form-group input {
  width: 100%; padding: 12px 14px; background: var(--bg3); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px; color: var(--text); font-size: 0.95rem; transition: all 0.2s;
}
.form-group input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,212,255,0.1); }
.btn {
  width: 100%; padding: 14px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; text-align: center;
}
.btn-primary { background: var(--accent); color: var(--bg); }
.btn-primary:hover { background: #33ddff; transform: translateY(-1px); }
.btn-vip { background: var(--vip); color: var(--bg); }
.btn-vip:hover { background: #d4b76a; }
.btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,0.15); color: var(--text); }
.btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
.auth-switch { text-align: center; margin-top: 18px; font-size: 0.9rem; color: var(--text2); }
.auth-switch a { color: var(--accent); cursor: pointer; }
.auth-switch a:hover { text-decoration: underline; }
.tier-cards { display: grid; gap: 12px; margin-top: 20px; }
.tier-card {
  background: var(--bg3); border: 1px solid rgba(255,255,255,0.06);
  border-radius: 10px; padding: 20px; cursor: pointer; transition: all 0.2s;
}
.tier-card:hover { border-color: rgba(0,212,255,0.2); }
.tier-card.vip { border-color: var(--vip); }
.tier-card .tier-name { font-weight: 700; font-size: 1.1rem; }
.tier-card .tier-price { color: var(--accent); font-size: 1.3rem; font-weight: 700; margin: 4px 0; }
.tier-card .tier-features { font-size: 0.85rem; color: var(--text2); }
.tier-card .tier-features li { margin: 3px 0; list-style: none; }
.tier-card .tier-features li::before { content: "✓ "; color: var(--success); }
.tier-card.vip .tier-features li::before { color: var(--vip); }

/* App Layout */
.app-layout { display: none; min-height: 100vh; }
.app-layout.active { display: flex; }
.sidebar {
  width: 260px; background: var(--bg2); border-right: 1px solid rgba(255,255,255,0.05);
  display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100;
  transition: transform 0.3s;
}
.sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.sidebar-header .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.1rem; }
.sidebar-header .brand-icon { font-size: 1.5rem; }
.sidebar-header .tier-badge {
  display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem;
  font-weight: 700; text-transform: uppercase; margin-left: 6px;
}
.tier-badge.free { background: var(--bg3); color: var(--text2); }
.tier-badge.vip { background: rgba(201,168,76,0.15); color: var(--vip); border: 1px solid var(--vip); }
.nav { padding: 12px; flex: 1; overflow-y: auto; }
.nav-item {
  display: flex; align-items: center; gap: 12px; padding: 10px 14px;
  border-radius: 8px; color: var(--text2); cursor: pointer; transition: all 0.15s;
  margin-bottom: 2px; font-size: 0.9rem;
}
.nav-item:hover { background: rgba(0,212,255,0.05); color: var(--text); }
.nav-item.active { background: rgba(0,212,255,0.1); color: var(--accent); }
.nav-item .icon { font-size: 1.1rem; width: 24px; text-align: center; }
.nav-item .badge {
  margin-left: auto; background: var(--accent3); color: white; font-size: 0.7rem;
  padding: 2px 6px; border-radius: 10px; font-weight: 700;
}
.sidebar-footer { padding: 16px; border-top: 1px solid rgba(255,255,255,0.05); font-size: 0.8rem; color: var(--text3); }
.sidebar-footer a { color: var(--accent); }

.main {
  flex: 1; margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column;
}
.topbar {
  height: 60px; background: var(--bg2); border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex; align-items: center; justify-content: space-between; padding: 0 24px;
  position: sticky; top: 0; z-index: 50;
}
.topbar .page-title { font-size: 1.2rem; font-weight: 700; }
.topbar .actions { display: flex; gap: 10px; align-items: center; }
.topbar .icon-btn {
  width: 36px; height: 36px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);
  background: var(--bg3); color: var(--text2); cursor: pointer; display: flex;
  align-items: center; justify-content: center; font-size: 1rem; transition: all 0.2s;
}
.topbar .icon-btn:hover { border-color: var(--accent); color: var(--accent); }
.user-menu { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.user-menu .avatar {
  width: 32px; height: 32px; border-radius: 50%; background: var(--accent);
  display: flex; align-items: center; justify-content: center; font-weight: 700;
  color: var(--bg); font-size: 0.85rem;
}
.content { flex: 1; padding: 24px; }

/* Stats Cards */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card {
  background: var(--bg2); border: 1px solid rgba(255,255,255,0.05);
  border-radius: var(--radius); padding: 20px; position: relative; overflow: hidden;
}
.stat-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
}
.stat-card.accent::before { background: var(--accent); }
.stat-card.success::before { background: var(--success); }
.stat-card.vip::before { background: var(--vip); }
.stat-card.warning::before { background: var(--warning); }
.stat-card .label { font-size: 0.8rem; color: var(--text3); text-transform: uppercase; letter-spacing: 0.5px; }
.stat-card .value { font-size: 2rem; font-weight: 700; margin: 6px 0; }
.stat-card .change { font-size: 0.8rem; }
.stat-card .change.up { color: var(--success); }
.stat-card .change.down { color: var(--danger); }

/* Tables */
.table-wrap { background: var(--bg2); border: 1px solid rgba(255,255,255,0.05); border-radius: var(--radius); overflow: hidden; }
.table-wrap table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.table-wrap th { text-align: left; padding: 14px 16px; color: var(--text3); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.table-wrap td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.03); }
.table-wrap tr:hover td { background: rgba(0,212,255,0.03); }
.table-wrap tr:last-child td { border-bottom: none; }
.status-pill {
  display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem;
  font-weight: 600; text-transform: capitalize;
}
.status-pill.new { background: rgba(0,212,255,0.12); color: var(--accent); }
.status-pill.contacted { background: rgba(245,158,11,0.12); color: var(--warning); }
.status-pill.qualified { background: rgba(45,212,191,0.12); color: var(--success); }
.status-pill.proposal { background: rgba(168,85,247,0.12); color: #a855f7; }
.status-pill.won { background: rgba(34,197,94,0.12); color: #22c55e; }
.status-pill.lost { background: rgba(148,163,184,0.12); color: var(--text3); }
.status-pill.spam { background: rgba(239,68,68,0.12); color: var(--danger); }
.ml-score-bar { width: 60px; height: 6px; background: var(--bg3); border-radius: 3px; overflow: hidden; }
.ml-score-bar .fill { height: 100%; border-radius: 3px; }

/* Filters */
.filters { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.filters input, .filters select {
  padding: 8px 12px; background: var(--bg2); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px; color: var(--text); font-size: 0.9rem;
}
.filters input:focus, .filters select:focus { outline: none; border-color: var(--accent); }

/* VIP Lock Overlay */
.vip-lock {
  position: relative; opacity: 0.5; pointer-events: none; filter: grayscale(0.3);
}
.vip-lock::after {
  content: "⭐ VIP Only"; position: absolute; inset: 0; display: flex;
  align-items: center; justify-content: center; background: rgba(10,15,30,0.7);
  font-weight: 700; color: var(--vip); font-size: 1.2rem; border-radius: inherit;
}

/* Mobile */
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main { margin-left: 0; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .content { padding: 16px; }
  .auth-card { padding: 28px 20px; }
}

/* Animations */
@keyframes fadeIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0); } }
.fade-in { animation: fadeIn 0.3s ease; }

/* Chart containers */
.chart-container { background: var(--bg2); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; }
.chart-container h3 { font-size: 1rem; margin-bottom: 16px; color: var(--text2); }
</style>
</head>
<body>

<!-- Loading Screen -->
<div id="loader">
  <div class="logo">🚀</div>
  <div class="title">Photon Bounce</div>
  <div class="subtitle">Lead Command Center</div>
  <div class="bar"><div class="bar-inner"></div></div>
</div>

<!-- Auth Container -->
<div id="auth-container" class="auth-screen" style="display:none;">
  <!-- Filled by JS -->
</div>

<!-- App Layout -->
<div id="app-layout" class="app-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="brand">
        <span class="brand-icon">🚀</span>
        <span>Photon Bounce</span>
        <span class="tier-badge" id="user-tier-badge">FREE</span>
      </div>
    </div>
    <nav class="nav" id="main-nav">
      <div class="nav-item active" data-page="dashboard">
        <span class="icon">📊</span> Dashboard
      </div>
      <div class="nav-item" data-page="leads">
        <span class="icon">📨</span> Leads <span class="badge" id="nav-lead-count" style="display:none;">0</span>
      </div>
      <div class="nav-item" data-page="microsites">
        <span class="icon">🌐</span> Microsites
      </div>
      <div class="nav-item" data-page="analytics">
        <span class="icon">📈</span> Analytics
      </div>
      <div class="nav-item" data-page="seo" id="nav-seo">
        <span class="icon">🔍</span> Auto SEO
      </div>
      <div class="nav-item" data-page="settings">
        <span class="icon">⚙️</span> Settings
      </div>
    </nav>
    <div class="sidebar-footer">
      <div>v<?php echo SAAS_VERSION; ?></div>
      <div><a href="https://photon-bounce.com" target="_blank">photon-bounce.com</a></div>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <button class="icon-btn" id="menu-toggle" style="display:none;">☰</button>
      <span class="page-title" id="page-title">Dashboard</span>
      <div class="actions">
        <button class="icon-btn" id="btn-notifications" title="Notifications">🔔</button>
        <div class="user-menu" id="user-menu">
          <div class="avatar" id="user-avatar">?</div>
          <span id="user-name">User</span>
        </div>
      </div>
    </div>
    <div class="content" id="main-content">
      <!-- Dynamic content -->
    </div>
  </main>
</div>

<script>
// ============================================================
// Photon Bounce SaaS Dashboard — Vanilla JS SPA
// ============================================================

const API_BASE = '/leads/api';
let token = localStorage.getItem('pb_token') || '';
let currentUser = null;
let currentPage = 'dashboard';

// Check for token in cookie (from PHP)
const cookieToken = document.cookie.match(/pb_token=([^;]+)/);
if (cookieToken && !token) {
  token = decodeURIComponent(cookieToken[1]);
  localStorage.setItem('pb_token', token);
}

// Show loader briefly then init
setTimeout(() => {
  document.getElementById('loader').classList.add('done');
  init();
}, 1500);

async function init() {
  if (token) {
    const me = await api('GET', '/me');
    if (me && !me.error) {
      currentUser = me;
      showApp();
      return;
    }
    token = '';
    localStorage.removeItem('pb_token');
  }
  showAuth('login');
}

// API helper
async function api(method, path, body) {
  const opts = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : '',
    },
  };
  if (body) opts.body = JSON.stringify(body);
  try {
    const res = await fetch(API_BASE + path, opts);
    return await res.json();
  } catch (e) {
    return { error: 'Network error', details: e.message };
  }
}

// Auth UI
function showAuth(mode) {
  const container = document.getElementById('auth-container');
  document.getElementById('app-layout').classList.remove('active');
  container.style.display = 'flex';

  const isLogin = mode === 'login';
  container.innerHTML = `
    <div class="auth-card fade-in">
      <div class="logo">🚀</div>
      <h1>${isLogin ? 'Welcome Back' : 'Get Started'}</h1>
      <div class="tagline">${isLogin ? 'Sign in to your lead command center' : 'Start converting more leads today'}</div>
      <form id="auth-form">
        ${!isLogin ? `
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" id="auth-name" placeholder="Dmitriy" required>
        </div>` : ''}
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="auth-email" placeholder="you@company.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" id="auth-password" placeholder="Min 8 characters" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary">${isLogin ? 'Sign In' : 'Create Account'}</button>
      </form>
      <div class="auth-switch">
        ${isLogin ? 'New here? <a onclick="showAuth(\'register\')">Create account</a>' : 'Already have an account? <a onclick="showAuth(\'login\')">Sign in</a>'}
      </div>
      ${!isLogin ? `
      <div class="tier-cards">
        <div class="tier-card" onclick="selectTier('free')">
          <div class="tier-name">🆓 Free</div>
          <div class="tier-price">$0/mo</div>
          <ul class="tier-features">
            <li>1 microsite</li>
            <li>Basic lead inbox</li>
            <li>Standard analytics</li>
          </ul>
        </div>
        <div class="tier-card vip" onclick="selectTier('vip')">
          <div class="tier-name">⭐ VIP</div>
          <div class="tier-price">$19/mo</div>
          <ul class="tier-features">
            <li>Unlimited microsites</li>
            <li>AI lead scoring</li>
            <li>Auto SEO engine</li>
            <li>Advanced analytics</li>
            <li>Export & API</li>
          </ul>
        </div>
      </div>` : ''}
    </div>
  `;

  document.getElementById('auth-form').onsubmit = async (e) => {
    e.preventDefault();
    const body = {
      email: document.getElementById('auth-email').value,
      password: document.getElementById('auth-password').value,
    };
    if (!isLogin) body.name = document.getElementById('auth-name').value;

    const res = await api('POST', `/auth/${isLogin ? 'login' : 'register'}`, body);
    if (res.error) {
      alert(res.error);
      return;
    }
    token = res.token;
    localStorage.setItem('pb_token', token);
    document.cookie = `pb_token=${encodeURIComponent(token)}; path=/; max-age=${60*60*24*7}`;
    currentUser = res.user;
    currentUser.tier_limits = res.tier_limits;
    showApp();
  };
}

// App UI
function showApp() {
  document.getElementById('auth-container').style.display = 'none';
  document.getElementById('app-layout').classList.add('active');

  // Update user info
  document.getElementById('user-name').textContent = currentUser.name || currentUser.email;
  document.getElementById('user-avatar').textContent = (currentUser.name || currentUser.email).charAt(0).toUpperCase();
  document.getElementById('user-tier-badge').textContent = currentUser.tier;
  document.getElementById('user-tier-badge').className = 'tier-badge ' + currentUser.tier;

  // VIP gating
  if (currentUser.tier !== 'vip') {
    document.getElementById('nav-seo').classList.add('vip-lock');
  }

  // Nav
  document.querySelectorAll('.nav-item').forEach(el => {
    el.onclick = () => {
      document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
      el.classList.add('active');
      navigate(el.dataset.page);
    };
  });

  // Logout on avatar click
  document.getElementById('user-menu').onclick = () => {
    if (confirm('Log out?')) {
      token = '';
      localStorage.removeItem('pb_token');
      document.cookie = 'pb_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
      location.reload();
    }
  };

  navigate('dashboard');
}

// Page router
function navigate(page) {
  currentPage = page;
  document.getElementById('page-title').textContent = page.charAt(0).toUpperCase() + page.slice(1);
  const content = document.getElementById('main-content');
  content.innerHTML = '<div style="text-align:center;padding:60px;color:var(--text3)">Loading...</div>';

  switch (page) {
    case 'dashboard': renderDashboard(content); break;
    case 'leads': renderLeads(content); break;
    case 'microsites': renderMicrosites(content); break;
    case 'analytics': renderAnalytics(content); break;
    case 'seo': renderSEO(content); break;
    case 'settings': renderSettings(content); break;
  }
}

// ============================================================
// DASHBOARD PAGE
// ============================================================
async function renderDashboard(el) {
  const analytics = await api('GET', '/analytics/dashboard?days=30');
  const leads = await api('GET', '/leads?per_page=5');

  let html = '<div class="fade-in">';

  // Stats
  html += '<div class="stats-grid">';
  html += statCard('📨 Total Leads (30d)', analytics.stats?.total_leads ?? 0, 'accent');
  html += statCard('✅ Won', analytics.stats?.won_leads ?? 0, 'success');
  html += statCard('🎯 Avg ML Score', Math.round((analytics.stats?.avg_ml_score ?? 0) * 100) + '%', 'vip');
  html += statCard('🌐 Niches', analytics.stats?.niches_reached ?? 0, 'warning');
  html += '</div>';

  // Recent leads table
  html += '<div class="table-wrap">';
  html += '<table><thead><tr><th>Name</th><th>Niche</th><th>Status</th><th>ML Score</th><th>Date</th></tr></thead><tbody>';
  if (leads.leads?.length) {
    leads.leads.forEach(l => {
      html += `<tr>
        <td><strong>${esc(l.name)}</strong>${l.email ? '<br><small style="color:var(--text3)">' + esc(l.email) + '</small>' : ''}</td>
        <td>${esc(l.niche || '-')}</td>
        <td><span class="status-pill ${l.status}">${l.status}</span></td>
        <td>${renderMLScore(l.ml_score)}</td>
        <td>${fmtDate(l.created_at)}</td>
      </tr>`;
    });
  } else {
    html += '<tr><td colspan="5" style="text-align:center;color:var(--text3);padding:40px;">No leads yet. Your microsites are working — leads will appear here!</td></tr>';
  }
  html += '</tbody></table></div>';

  // VIP upsell if free
  if (currentUser.tier !== 'vip') {
    html += `
    <div style="background:linear-gradient(135deg, rgba(201,168,76,0.1), rgba(201,168,76,0.05)); border:1px solid var(--vip); border-radius:12px; padding:24px; margin-top:24px; text-align:center;">
      <div style="font-size:1.3rem; font-weight:700; color:var(--vip); margin-bottom:8px;">⭐ Unlock VIP Features</div>
      <div style="color:var(--text2); margin-bottom:16px;">AI lead scoring, auto SEO, unlimited microsites, and advanced analytics.</div>
      <button class="btn btn-vip" style="width:auto; padding:12px 32px; display:inline-block;" onclick="alert('Upgrade flow coming soon! Contact hello@photon-bounce.com')">Upgrade to VIP — $19/mo</button>
    </div>`;
  }

  html += '</div>';
  el.innerHTML = html;
}

function statCard(label, value, cls) {
  return `<div class="stat-card ${cls}"><div class="label">${label}</div><div class="value">${value}</div></div>`;
}

// ============================================================
// LEADS PAGE
// ============================================================
async function renderLeads(el, page = 1) {
  const status = document.getElementById('lead-filter-status')?.value || '';
  const search = document.getElementById('lead-filter-search')?.value || '';
  const data = await api('GET', `/leads?page=${page}&per_page=20&status=${status}&search=${encodeURIComponent(search)}`);

  let html = '<div class="fade-in">';

  // Filters
  html += `<div class="filters">
    <input type="text" id="lead-filter-search" placeholder="🔍 Search leads..." value="${esc(search)}" onchange="renderLeads(document.getElementById('main-content'), 1)">
    <select id="lead-filter-status" onchange="renderLeads(document.getElementById('main-content'), 1)">
      <option value="">All Statuses</option>
      <option value="new" ${status==='new'?'selected':''}>New</option>
      <option value="contacted" ${status==='contacted'?'selected':''}>Contacted</option>
      <option value="qualified" ${status==='qualified'?'selected':''}>Qualified</option>
      <option value="proposal" ${status==='proposal'?'selected':''}>Proposal</option>
      <option value="won" ${status==='won'?'selected':''}>Won</option>
      <option value="lost" ${status==='lost'?'selected':''}>Lost</option>
      <option value="spam" ${status==='spam'?'selected':''}>Spam</option>
    </select>
    ${currentUser.tier === 'vip' ? '<button class="btn btn-ghost" style="width:auto; padding:8px 16px;" onclick="exportLeads()">📥 Export CSV</button>' : ''}
  </div>`;

  // Table
  html += '<div class="table-wrap">';
  html += '<table><thead><tr><th>Name</th><th>Contact</th><th>Niche</th><th>Budget</th><th>Status</th><th>ML</th><th>Actions</th></tr></thead><tbody>';

  if (data.leads?.length) {
    data.leads.forEach(l => {
      html += `<tr>
        <td><strong>${esc(l.name)}</strong></td>
        <td>${l.email ? esc(l.email) + '<br>' : ''}${l.phone ? esc(l.phone) : ''}</td>
        <td>${esc(l.niche || '-')}</td>
        <td>${esc(l.budget || '-')}</td>
        <td><span class="status-pill ${l.status}">${l.status}</span></td>
        <td>${renderMLScore(l.ml_score)}</td>
        <td>
          <select onchange="updateLeadStatus(${l.id}, this.value)" style="background:var(--bg3); color:var(--text); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:4px 8px; font-size:0.8rem;">
            ${['new','contacted','qualified','proposal','won','lost','spam'].map(s =>
              `<option value="${s}" ${l.status===s?'selected':''}>${s}</option>`
            ).join('')}
          </select>
        </td>
      </tr>`;
      if (l.message) {
        html += `<tr><td colspan="7" style="padding-top:0; color:var(--text3); font-size:0.85rem; border-bottom:1px solid rgba(255,255,255,0.05);">${esc(l.message.substring(0, 120))}${l.message.length>120?'...':''}</td></tr>`;
      }
    });
  } else {
    html += '<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px;">No leads found</td></tr>';
  }
  html += '</tbody></table></div>';

  // Pagination
  if (data.pagination?.pages > 1) {
    html += '<div style="display:flex; gap:8px; justify-content:center; margin-top:16px;">';
    for (let i = 1; i <= data.pagination.pages; i++) {
      html += `<button onclick="renderLeads(document.getElementById('main-content'), ${i})" style="padding:6px 12px; border-radius:6px; border:1px solid rgba(255,255,255,0.1); background:${i===page?'var(--accent)':'var(--bg2)'}; color:${i===page?'var(--bg)':'var(--text)'}; cursor:pointer;">${i}</button>`;
    }
    html += '</div>';
  }

  html += '</div>';
  el.innerHTML = html;
}

async function updateLeadStatus(id, status) {
  await api('PUT', `/leads/${id}`, { status });
  // Refresh current page
  navigate('leads');
}

async function exportLeads() {
  window.open(API_BASE + '/export/leads', '_blank');
}

// ============================================================
// MICROSITES PAGE
// ============================================================
async function renderMicrosites(el) {
  const data = await api('GET', '/microsites');

  let html = '<div class="fade-in">';

  html += '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">';
  html += '<h2>Your Microsites</h2>';
  html += `<span style="color:var(--text3);">${data.microsites?.length || 0} / ${data.tier_limit}</span>`;
  html += '</div>';

  if (data.microsites?.length) {
    html += '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:16px;">';
    data.microsites.forEach(ms => {
      html += `
      <div style="background:var(--bg2); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px;">
          <div>
            <div style="font-weight:700; font-size:1.1rem;">${esc(ms.display_name)}</div>
            <div style="color:var(--text3); font-size:0.85rem;">${esc(ms.slug)}</div>
          </div>
          <span class="status-pill ${ms.is_active ? 'won' : 'lost'}">${ms.is_active ? 'Active' : 'Inactive'}</span>
        </div>
        <div style="color:var(--text2); font-size:0.9rem; margin-bottom:8px;">${esc(ms.url)}</div>
        <div style="display:flex; gap:16px; font-size:0.85rem; color:var(--text3);">
          <span>📨 ${ms.lead_count} leads</span>
          <span>🎨 ${ms.theme}</span>
        </div>
        <div style="margin-top:12px; display:flex; gap:8px;">
          <a href="${esc(ms.url)}" target="_blank" class="btn btn-ghost" style="width:auto; padding:6px 12px; font-size:0.8rem;">View Site</a>
          <button class="btn btn-ghost" style="width:auto; padding:6px 12px; font-size:0.8rem;" onclick="toggleMicrosite(${ms.id}, ${ms.is_active ? 0 : 1})">${ms.is_active ? 'Pause' : 'Activate'}</button>
        </div>
      </div>`;
    });
    html += '</div>';
  }

  // Add microsite form
  if ((data.microsites?.length || 0) < data.tier_limit) {
    html += `
    <div style="background:var(--bg2); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:24px; margin-top:20px;">
      <h3 style="margin-bottom:16px;">➕ Add Microsite</h3>
      <form id="add-ms-form" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group" style="margin:0;"><label>Slug</label><input type="text" id="ms-slug" placeholder="my-business" required></div>
        <div class="form-group" style="margin:0;"><label>Niche</label><input type="text" id="ms-niche" placeholder="plumbing" required></div>
        <div class="form-group" style="margin:0; grid-column:1/-1;"><label>Display Name</label><input type="text" id="ms-name" placeholder="My Business Name" required></div>
        <div class="form-group" style="margin:0; grid-column:1/-1;"><label>URL</label><input type="url" id="ms-url" placeholder="https://..." required></div>
        <button type="submit" class="btn btn-primary" style="grid-column:1/-1;">Add Microsite</button>
      </form>
    </div>`;
  }

  html += '</div>';
  el.innerHTML = html;

  const form = document.getElementById('add-ms-form');
  if (form) {
    form.onsubmit = async (e) => {
      e.preventDefault();
      const res = await api('POST', '/microsites', {
        slug: document.getElementById('ms-slug').value,
        niche: document.getElementById('ms-niche').value,
        display_name: document.getElementById('ms-name').value,
        url: document.getElementById('ms-url').value,
      });
      if (res.error) { alert(res.error); return; }
      navigate('microsites');
    };
  }
}

async function toggleMicrosite(id, active) {
  await api('PUT', `/microsites/${id}`, { is_active: active });
  navigate('microsites');
}

// ============================================================
// ANALYTICS PAGE
// ============================================================
async function renderAnalytics(el) {
  const data = await api('GET', '/analytics/dashboard?days=30');

  let html = '<div class="fade-in">';
  html += '<h2 style="margin-bottom:20px;">📈 Analytics</h2>';

  // Basic stats
  html += '<div class="stats-grid">';
  html += statCard('Total Leads', data.stats?.total_leads ?? 0, 'accent');
  html += statCard('Conversion Rate', data.stats?.won_leads ? Math.round((data.stats.won_leads / data.stats.total_leads) * 100) + '%' : '0%', 'success');
  html += statCard('Avg ML Score', Math.round((data.stats?.avg_ml_score ?? 0) * 100) + '%', 'vip');
  html += statCard('Niches', data.stats?.niches_reached ?? 0, 'warning');
  html += '</div>';

  // VIP: Trends
  if (data.trends?.length) {
    html += '<div class="chart-container">';
    html += '<h3>Lead Volume (30 days)</h3>';
    html += '<div style="display:flex; align-items:end; gap:4px; height:150px; padding-top:20px;">';
    const max = Math.max(...data.trends.map(t => t.count), 1);
    data.trends.forEach(t => {
      const h = (t.count / max) * 100;
      html += `<div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px;">
        <div style="width:100%; background:var(--accent); border-radius:4px 4px 0 0; height:${h}px; opacity:0.8; transition:opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.8"></div>
        <div style="font-size:0.65rem; color:var(--text3); transform:rotate(-45deg); white-space:nowrap;">${t.day?.slice(5) || ''}</div>
      </div>`;
    });
    html += '</div></div>';
  } else if (currentUser.tier === 'vip') {
    html += '<div class="chart-container"><h3>Lead Volume</h3><p style="color:var(--text3); padding:40px 0; text-align:center;">Not enough data yet. Leads will appear here once they start coming in.</p></div>';
  } else {
    html += '<div class="chart-container vip-lock"><h3>Lead Volume</h3><p style="padding:40px;">Trend data</p></div>';
  }

  // VIP: Top niches
  if (data.top_niches?.length) {
    html += '<div class="chart-container">';
    html += '<h3>Top Performing Niches</h3>';
    html += '<table style="width:100%;"><thead><tr><th>Niche</th><th>Leads</th><th>Avg Score</th></tr></thead><tbody>';
    data.top_niches.forEach(n => {
      html += `<tr><td>${esc(n.niche)}</td><td>${n.count}</td><td>${Math.round((n.avg_score || 0) * 100)}%</td></tr>`;
    });
    html += '</tbody></table></div>';
  }

  html += '</div>';
  el.innerHTML = html;
}

// ============================================================
// SEO PAGE (VIP only)
// ============================================================
async function renderSEO(el) {
  if (currentUser.tier !== 'vip') {
    el.innerHTML = `
      <div style="text-align:center; padding:80px 20px;">
        <div style="font-size:4rem; margin-bottom:16px;">🔒</div>
        <h2>Auto SEO is a VIP Feature</h2>
        <p style="color:var(--text2); margin:12px 0 24px;">Upgrade to VIP to unlock automatic daily SEO optimization for all your microsites.</p>
        <button class="btn btn-vip" style="width:auto; padding:12px 32px; display:inline-block;" onclick="alert('Contact hello@photon-bounce.com to upgrade')">Upgrade to VIP</button>
      </div>`;
    return;
  }

  const data = await api('GET', '/seo');

  let html = '<div class="fade-in">';
  html += '<h2 style="margin-bottom:20px;">🔍 Auto SEO Engine</h2>';

  if (data.microsites?.length) {
    html += '<div style="display:grid; gap:16px;">';
    data.microsites.forEach(ms => {
      html += `
      <div style="background:var(--bg2); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <div style="font-weight:700;">${esc(ms.display_name)}</div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span style="color:var(--text3); font-size:0.85rem;">SEO Score: ${ms.seo_score || 0}/100</span>
            <button class="btn btn-primary" style="width:auto; padding:6px 14px; font-size:0.8rem;" onclick="runSEO(${ms.id})">Run Now</button>
          </div>
        </div>
        <div style="background:var(--bg3); height:8px; border-radius:4px; overflow:hidden;">
          <div style="width:${ms.seo_score || 0}%; height:100%; background:${(ms.seo_score || 0) > 70 ? 'var(--success)' : (ms.seo_score || 0) > 40 ? 'var(--warning)' : 'var(--danger)'}; border-radius:4px; transition:width 0.5s;"></div>
        </div>
        <div style="display:flex; gap:16px; margin-top:10px; font-size:0.8rem; color:var(--text3);">
          <span>Runs: ${ms.run_count || 0}</span>
          <span>Last: ${ms.last_run ? fmtDate(ms.last_run) : 'Never'}</span>
        </div>
      </div>`;
    });
    html += '</div>';
  } else {
    html += '<p style="color:var(--text3); text-align:center; padding:40px;">No microsites connected. Add a microsite first.</p>';
  }

  html += '</div>';
  el.innerHTML = html;
}

async function runSEO(id) {
  const res = await api('POST', `/seo/run/${id}`);
  alert(res.message || res.error || 'Done');
  navigate('seo');
}

// ============================================================
// SETTINGS PAGE
// ============================================================
function renderSettings(el) {
  el.innerHTML = `
    <div class="fade-in" style="max-width:600px;">
      <h2 style="margin-bottom:24px;">⚙️ Settings</h2>

      <div style="background:var(--bg2); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:24px; margin-bottom:16px;">
        <h3 style="margin-bottom:16px; font-size:1rem;">Profile</h3>
        <div class="form-group"><label>Name</label><input type="text" id="set-name" value="${esc(currentUser.name || '')}"></div>
        <div class="form-group"><label>Email</label><input type="email" value="${esc(currentUser.email || '')}" disabled style="opacity:0.5;"></div>
        <div class="form-group"><label>Timezone</label>
          <select id="set-tz">
            <option value="America/New_York" ${currentUser.timezone==='America/New_York'?'selected':''}>Eastern (NY)</option>
            <option value="America/Chicago" ${currentUser.timezone==='America/Chicago'?'selected':''}>Central (Chicago)</option>
            <option value="America/Denver" ${currentUser.timezone==='America/Denver'?'selected':''}>Mountain (Denver)</option>
            <option value="America/Los_Angeles" ${currentUser.timezone==='America/Los_Angeles'?'selected':''}>Pacific (LA)</option>
            <option value="Europe/London" ${currentUser.timezone==='Europe/London'?'selected':''}>London</option>
            <option value="Europe/Berlin" ${currentUser.timezone==='Europe/Berlin'?'selected':''}>Berlin</option>
            <option value="Asia/Tokyo" ${currentUser.timezone==='Asia/Tokyo'?'selected':''}>Tokyo</option>
          </select>
        </div>
        <button class="btn btn-primary" onclick="saveSettings()">Save Changes</button>
      </div>

      <div style="background:var(--bg2); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:24px; margin-bottom:16px;">
        <h3 style="margin-bottom:16px; font-size:1rem;">Account</h3>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <div>
            <div style="font-weight:600;">Current Plan</div>
            <div style="color:var(--text3); font-size:0.9rem;">${currentUser.tier.toUpperCase()}</div>
          </div>
          ${currentUser.tier !== 'vip' ? '<button class="btn btn-vip" style="width:auto; padding:8px 20px; font-size:0.85rem;" onclick="alert(\'Contact hello@photon-bounce.com to upgrade\')">Upgrade</button>' : '<span style="color:var(--vip); font-weight:700;">⭐ VIP Active</span>'}
        </div>
        <div style="color:var(--text3); font-size:0.85rem;">
          API Key: <code style="background:var(--bg3); padding:2px 8px; border-radius:4px;">${currentUser.api_key || 'N/A'}</code>
        </div>
      </div>

      <button class="btn btn-ghost" style="color:var(--danger); border-color:var(--danger);" onclick="if(confirm('Delete account? This cannot be undone.')) alert('Contact hello@photon-bounce.com')">Delete Account</button>
    </div>
  `;
}

async function saveSettings() {
  const res = await api('PUT', '/me', {
    name: document.getElementById('set-name').value,
    timezone: document.getElementById('set-tz').value,
  });
  if (res.success) {
    alert('Saved!');
    currentUser.name = document.getElementById('set-name').value;
    document.getElementById('user-name').textContent = currentUser.name;
    document.getElementById('user-avatar').textContent = currentUser.name.charAt(0).toUpperCase();
  }
}

// ============================================================
// UTILS
// ============================================================
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

function fmtDate(ts) {
  if (!ts) return '-';
  return new Date(ts * 1000).toLocaleDateString();
}

function renderMLScore(score) {
  if (score == null) return '-';
  const pct = Math.round(score * 100);
  const color = pct > 70 ? 'var(--success)' : pct > 40 ? 'var(--warning)' : 'var(--danger)';
  return `<div style="display:flex; align-items:center; gap:6px;"><div class="ml-score-bar"><div class="fill" style="width:${pct}%; background:${color};"></div></div><span style="font-size:0.8rem; color:${color};">${pct}%</span></div>`;
}

// Mobile menu
if (window.innerWidth <= 768) {
  document.getElementById('menu-toggle').style.display = 'block';
  document.getElementById('menu-toggle').onclick = () => {
    document.getElementById('sidebar').classList.toggle('open');
  };
}
</script>
</body>
</html>
