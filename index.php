<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0d9488">
<title>SteadyPath — Recovery Companion</title>
<style>
:root {
  --primary: #0d9488;
  --primary-light: #14b8a6;
  --primary-dark: #0f766e;
  --accent: #f59e0b;
  --danger: #ef4444;
  --success: #22c55e;
  --bg: #f8fafc;
  --card: #ffffff;
  --text: #1e293b;
  --text-secondary: #64748b;
  --border: #e2e8f0;
  --shadow: 0 1px 3px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
  --radius: 12px;
  --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); }
body { overflow-x: hidden; }

.landing { min-height: 100vh; }
.hero {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 60px 24px 80px;
  text-align: center;
}
.hero h1 { font-size: 2.4rem; margin-bottom: 12px; }
.hero p { font-size: 1.1rem; opacity: 0.9; margin-bottom: 24px; }
.hero .badge {
  display: inline-block; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px;
  font-size: 0.85rem; margin-bottom: 24px;
}

.features {
  padding: 40px 24px;
  max-width: 600px; margin: 0 auto;
}
.features h2 { text-align: center; margin-bottom: 24px; color: var(--primary-dark); }
.feature-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
.feature-card {
  background: var(--card); border-radius: var(--radius); padding: 20px;
  border: 1px solid var(--border); text-align: center;
}
.feature-card .icon { font-size: 2rem; margin-bottom: 8px; }
.feature-card h3 { font-size: 0.95rem; margin-bottom: 4px; }
.feature-card p { font-size: 0.8rem; color: var(--text-secondary); }

.auth-section {
  padding: 40px 24px; max-width: 480px; margin: 0 auto;
}
.card {
  background: var(--card); border-radius: var(--radius); padding: 28px;
  border: 1px solid var(--border); box-shadow: var(--shadow-lg);
}
.card h2 { margin-bottom: 20px; text-align: center; color: var(--primary); }
.form-group { margin-bottom: 16px; }
label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
input {
  width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px;
  font-size: 1rem; background: var(--card); color: var(--text); font-family: var(--font);
}
input:focus { outline: none; border-color: var(--primary); }
.btn {
  width: 100%; padding: 14px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
  cursor: pointer; transition: background 0.2s; text-align: center;
}
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); margin-top: 12px; }
.btn-outline:hover { background: var(--primary); color: white; }
.error { color: var(--danger); font-size: 0.85rem; margin-top: 8px; text-align: center; }
.success { color: var(--success); font-size: 0.85rem; margin-top: 8px; text-align: center; }
.toggle-auth { text-align: center; margin-top: 16px; color: var(--text-secondary); font-size: 0.9rem; }
.toggle-auth a { color: var(--primary); cursor: pointer; font-weight: 600; }

.trial-banner {
  background: linear-gradient(90deg, var(--success), var(--primary));
  color: white; text-align: center; padding: 12px; font-size: 0.9rem; font-weight: 600;
}
.apk-section { text-align: center; padding: 40px 24px; background: var(--bg); }
.apk-section h2 { margin-bottom: 16px; }
.apk-section p { color: var(--text-secondary); margin-bottom: 20px; max-width: 480px; margin-left: auto; margin-right: auto; }

.disclaimer { padding: 20px 24px; text-align: center; font-size: 0.8rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto; }

.hidden { display: none !important; }
</style>
</head>
<body>

<div class="landing">
  <div class="hero">
    <div style="font-size:4rem;margin-bottom:16px;">🌿</div>
    <h1>SteadyPath</h1>
    <div class="badge">💚 100% Free · No Credit Card Required</div>
    <p>Your private recovery companion. Track sobriety, journal triggers, breathe through cravings, and find crisis help — all in one place.</p>
  </div>

  <div class="trial-banner">
    🎉 Start your 7-day free trial today. No credit card required.
  </div>

  <div class="features">
    <h2>What You Get</h2>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="icon">🕐</div>
        <h3>Sobriety Clock</h3>
        <p>Live countdown to every milestone</p>
      </div>
      <div class="feature-card">
        <div class="icon">📝</div>
        <h3>Daily Check-Ins</h3>
        <p>Track mood and cravings</p>
      </div>
      <div class="feature-card">
        <div class="icon">📔</div>
        <h3>Private Journal</h3>
        <p>Your thoughts, encrypted</p>
      </div>
      <div class="feature-card">
        <div class="icon">🫁</div>
        <h3>Breathing Exercise</h3>
        <p>Guided calm in 4 minutes</p>
      </div>
      <div class="feature-card">
        <div class="icon">⚡</div>
        <h3>Trigger Tracker</h3>
        <p>Know what sets you off</p>
      </div>
      <div class="feature-card">
        <div class="icon">🆘</div>
        <h3>Crisis Help</h3>
        <p>988, SAMHSA, 911 — one tap</p>
      </div>
    </div>
  </div>

  <div class="auth-section">
    <!-- Login Form -->
    <div id="login-form" class="card">
      <h2>Welcome Back</h2>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="login-email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="login-password" placeholder="••••••" required>
      </div>
      <div id="login-error" class="error hidden"></div>
      <button class="btn btn-primary" onclick="doLogin()">Sign In</button>
      <div class="toggle-auth">Don't have an account? <a onclick="showRegister()">Sign up free</a></div>
    </div>

    <!-- Register Form -->
    <div id="register-form" class="card hidden">
      <h2>Create Free Account</h2>
      <div class="form-group">
        <label>Your Name</label>
        <input type="text" id="reg-name" placeholder="First name" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="reg-email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="reg-password" placeholder="Min 6 characters" required>
      </div>
      <div id="reg-error" class="error hidden"></div>
      <div id="reg-success" class="success hidden"></div>
      <button class="btn btn-primary" onclick="doRegister()">Start Free Trial</button>
      <div class="toggle-auth">Already have an account? <a onclick="showLogin()">Sign in</a></div>
    </div>
  </div>

  <div class="apk-section">
    <h2>📱 Get the Android App</h2>
    <p>Download the SteadyPath APK directly to your Android device. No Google Play required.</p>
    <a href="download.php" class="btn btn-primary" style="max-width:300px;margin:0 auto;display:block;">Download APK</a>
    <p style="margin-top:12px;font-size:0.8rem;color:var(--text-secondary);">Android 7.0+ required. 2.8 MB.</p>
  </div>

  <div class="disclaimer">
    <p><strong>SteadyPath is not a substitute for professional medical treatment.</strong></p>
    <p>If you are experiencing severe withdrawal, suicidal thoughts, or a medical emergency, call 911 or 988 immediately.</p>
    <p style="margin-top:12px;">© 2026 SteadyPath. Free forever. 💚</p>
  </div>
</div>

<script>
function showLogin() {
  document.getElementById('login-form').classList.remove('hidden');
  document.getElementById('register-form').classList.add('hidden');
}
function showRegister() {
  document.getElementById('login-form').classList.add('hidden');
  document.getElementById('register-form').classList.remove('hidden');
}

async function doLogin() {
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const err = document.getElementById('login-error');
  err.classList.add('hidden');
  
  if (!email || !password) { err.textContent = 'Please fill in all fields'; err.classList.remove('hidden'); return; }
  
  try {
    const res = await fetch('api.php?action=login', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email, password})
    });
    const data = await res.json();
    if (data.error) { err.textContent = data.error; err.classList.remove('hidden'); }
    else { window.location.href = 'app.php'; }
  } catch (e) { err.textContent = 'Network error. Please try again.'; err.classList.remove('hidden'); }
}

async function doRegister() {
  const name = document.getElementById('reg-name').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-password').value;
  const err = document.getElementById('reg-error');
  const succ = document.getElementById('reg-success');
  err.classList.add('hidden'); succ.classList.add('hidden');
  
  if (!name || !email || !password) { err.textContent = 'Please fill in all fields'; err.classList.remove('hidden'); return; }
  if (password.length < 6) { err.textContent = 'Password must be at least 6 characters'; err.classList.remove('hidden'); return; }
  
  try {
    const res = await fetch('api.php?action=register', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({name, email, password})
    });
    const data = await res.json();
    if (data.error) { err.textContent = data.error; err.classList.remove('hidden'); }
    else {
      succ.textContent = 'Account created! Redirecting to your app...';
      succ.classList.remove('hidden');
      setTimeout(() => window.location.href = 'app.php', 1000);
    }
  } catch (e) { err.textContent = 'Network error. Please try again.'; err.classList.remove('hidden'); }
}

// Enter key support
document.getElementById('login-password').addEventListener('keypress', e => { if (e.key === 'Enter') doLogin(); });
document.getElementById('reg-password').addEventListener('keypress', e => { if (e.key === 'Enter') doRegister(); });
</script>

</body>
</html>
