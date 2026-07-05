# SteadyPath — Recovery Companion

> **SaaS + PWA + Android App** — Your private recovery companion. Track sobriety, journal triggers, breathe through cravings, and find crisis help.

[![Live Demo](https://img.shields.io/badge/Live-Demo-teal)](https://photon-bounce.com/steadypath/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue)](https://php.net)
[![SQLite](https://img.shields.io/badge/SQLite-3-lightgrey)](https://sqlite.org)

---

## 🌿 What is SteadyPath?

**SteadyPath** is a privacy-first recovery companion built for anyone navigating addiction, substance use, or behavioral recovery. It combines a **SaaS web app** (with 7-day free trial), a **standalone PWA** (free forever), and a **native Android app** (Google Play Store).

### Features

- 🕐 **Sobriety Clock** — Live countdown with milestone tracking
- 📝 **Daily Check-Ins** — Mood, cravings, and sober status logging
- 📔 **Private Journal** — Tagged entries, encrypted on-device (PWA) or server-side (SaaS)
- 🫁 **Breathing Exercise** — Guided 4-4-4-4 box breathing
- ⚡ **Trigger Tracker** — Identify patterns by type (emotional, social, environmental, physical)
- 🆘 **Crisis Resources** — One-tap access to 988, 911, SAMHSA
- ⚠️ **Withdrawal Awareness** — Timeline and symptom info with medical warnings
- 🌙 **Dark Mode** — Easy on the eyes at night
- 💰 **Money & Time Saved Calculator** — See the tangible benefits of recovery

---

## 🚀 Live Deployment

| Platform | URL | Status |
|----------|-----|--------|
| **SaaS Web App** | https://photon-bounce.com/steadypath/ | ✅ Live |
| **PWA (Standalone)** | https://photon-bounce.com/steadypath/pwa.html | ✅ Live |
| **APK Download** | https://photon-bounce.com/steadypath/download.php | ✅ Live |
| **Privacy Policy** | https://photon-bounce.com/steadypath/privacy-policy.html | ✅ Live |
| **GitHub** | https://github.com/PhotonBounce/steadypath | ✅ Public |

---

## 📁 Repository Structure

```
steadypath/
├── saas/                          # SaaS web application (PHP + SQLite)
│   ├── api.php                    # REST API backend
│   ├── index.php                  # Landing page / auth
│   ├── app.php                    # Full app (requires login)
│   ├── download.php               # APK download endpoint
│   ├── .htaccess                  # Apache routing rules
│   ├── db/                        # SQLite database (auto-created)
│   └── apk/                       # APK file for download
│
├── steadypath/                    # PWA (standalone, no backend)
│   ├── index.html                 # Single-file PWA
│   ├── manifest.php               # PWA manifest
│   ├── sw.php                     # Service worker
│   ├── privacy-policy.html        # Privacy policy
│   └── apk/                       # Signed APK
│
├── playstore-assets/              # Google Play Store assets
│   ├── icon-512.png               # 512×512 app icon
│   ├── feature-graphic.png        # 1024×500 feature graphic
│   ├── screen-01-dashboard.png    # Phone screenshots (6 total)
│   ├── screen-02-checkin.png
│   ├── screen-03-journal.png
│   ├── screen-04-tools.png
│   ├── screen-05-help.png
│   ├── screen-06-settings.png
│   ├── listing-text.md            # Title, description, keywords
│   └── PLAYSTORE-UPLOAD-GUIDE.md  # Step-by-step upload guide
│
└── android/                       # Android project (Capacitor 6)
    ├── app/
    │   └── build.gradle           # Build config
    └── ...
```

---

## 🛠️ Local Setup (SaaS)

### Requirements

- PHP 8.1+ with SQLite3 extension
- Apache or Nginx with mod_rewrite
- SSL certificate (recommended for production)

### Installation

```bash
# Clone the repo
git clone https://github.com/PhotonBounce/steadypath.git
cd steadypath/saas

# Ensure the db directory is writable
chmod 755 db
chmod 644 db/*.db 2>/dev/null || true

# Point your web server to the saas/ directory
# Or use PHP's built-in server for testing
php -S localhost:8000
```

### Apache Configuration

```apache
<Directory /var/www/steadypath>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

The included `.htaccess` handles:
- DirectoryIndex `index.php`
- SPA routing (all non-file requests → `index.php`)
- PHP upload limits (10MB)

---

## 🔐 API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `api.php?action=register` | POST | No | Create account (7-day trial auto-assigned) |
| `api.php?action=login` | POST | No | Authenticate, start session |
| `api.php?action=logout` | POST | Yes | Destroy session |
| `api.php?action=me` | GET | Yes | Get current user + trial status |
| `api.php?action=get_data` | GET | Yes | Get all user data (sobriety, checkins, journal, triggers) |
| `api.php?action=save_sobriety` | POST | Yes | Update sobriety settings |
| `api.php?action=save_checkin` | POST | Yes | Log a daily check-in |
| `api.php?action=save_journal` | POST | Yes | Create journal entry |
| `api.php?action=save_trigger` | POST | Yes | Log a trigger |
| `api.php?action=delete_checkin` | POST | Yes | Delete a check-in by ID |
| `api.php?action=delete_journal` | POST | Yes | Delete a journal entry by ID |
| `api.php?action=delete_trigger` | POST | Yes | Delete a trigger by ID |
| `api.php?action=reset_data` | POST | Yes | Wipe all user data (irreversible) |

### Request/Response Format

All endpoints accept and return **JSON**. Example:

```bash
curl -X POST https://photon-bounce.com/steadypath/api.php?action=register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"password123"}'
```

Response:
```json
{
  "success": true,
  "user_id": 42,
  "trial_end": "2026-07-12 14:30:00"
}
```

---

## 💳 Crypto Payment Support

SteadyPath accepts cryptocurrency payments for premium subscriptions. The following networks are supported:

| Network | Symbol | Wallet Address |
|---------|--------|---------------|
| **Ethereum** | ETH | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |
| **Bitcoin** | BTC | `bc1qn67f2d50wng6h83cxsk7kc55yux7kv4l6dugrx` |
| **Solana** | SOL | `5i6AY6jYFhGj2KThPQZiWtSV7jAQRZjtSvv2vfHmuQiU` |
| **Tron** | TRX | `TGRDDVFkCD88qtAyjrHz5UhjjGoArhzwfK` |
| **BNB Smart Chain** | BNB | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |
| **Polygon** | MATIC | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |
| **Linea** | ETH | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |
| **Base** | ETH | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |
| **Arbitrum** | ETH | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |
| **Optimism** | ETH | `0x75B30d0dE751D9628510f3cb273F09f7137f9E3F` |

> **Note:** After sending crypto, contact support with your transaction hash to activate your subscription. Auto-verification via webhook is planned for a future release.

---

## 📱 Android App

### Build from Source

```bash
cd steadypath/android
./gradlew assembleRelease
```

The signed APK/AAB is generated in:
```
app/build/outputs/apk/release/
app/build/outputs/bundle/release/
```

### Keystore Info

- **Alias:** `steadypath`
- **Password:** `SteadyPath2026!`
- **Validity:** 10,000 days (~2054)

⚠️ **CRITICAL:** If you lose the keystore, you can never update the app on Google Play. Back it up to multiple locations.

---

## 🎨 Design System

| Color | Hex | Usage |
|-------|-----|-------|
| Primary | `#0d9488` | Brand, buttons, clock gradient |
| Primary Light | `#14b8a6` | Gradients, highlights |
| Primary Dark | `#0f766e` | Hover states |
| Accent | `#f59e0b` | Warnings, attention |
| Danger | `#ef4444` | Crisis, errors, delete |
| Success | `#22c55e` | Positive, trial banner |
| Background | `#f8fafc` | Light mode background |
| Card | `#ffffff` | Card surfaces |
| Text | `#1e293b` | Primary text |
| Text Secondary | `#64748b` | Labels, placeholders |

---

## 📝 License

MIT License — Free for personal and commercial use. See [LICENSE](LICENSE) for details.

---

## 🙏 Disclaimer

> **SteadyPath is not a substitute for professional medical treatment, therapy, or emergency care.** If you are experiencing severe withdrawal symptoms, suicidal thoughts, or a medical emergency, seek immediate professional help. Call 911, go to the nearest emergency room, or contact SAMHSA at 1-800-662-4357.

---

## 🌐 Connect

- **Website:** https://photon-bounce.com/steadypath/
- **GitHub:** https://github.com/PhotonBounce/steadypath
- **Developer:** Photon Bounce

---

<p align="center">💚 Built with care for everyone in recovery.</p>
