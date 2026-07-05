# SteadyPath — Google Play Store Upload Checklist

## ✅ COMPLETED BY KIMI

| Task | Status | Path/Location |
|------|--------|---------------|
| App created in Play Console | ✅ | App ID: 4975048762670245895 |
| Signed AAB built | ✅ | `D:\photonbounce\android\app\build\outputs\bundle\release\app-release.aab` |
| Signed APK built | ✅ | `D:\photonbounce\steadypath\apk\steadypath-release.apk` |
| Keystore created | ✅ | `D:\photonbounce\steadypath-release.keystore` |
| Play Store text assets | ✅ | `D:\photonbounce\playstore-assets\listing-text.md` |
| Phone screenshots (6) | ✅ | `D:\photonbounce\playstore-assets\screen-0*.png` |
| Feature graphic (1024×500) | ✅ | `D:\photonbounce\playstore-assets\feature-graphic.png` |
| Privacy policy page | ✅ | https://photon-bounce.com/steadypath/privacy-policy.html |
| PWA deployed | ✅ | https://photon-bounce.com/steadypath/ |

---

## 📋 YOUR MANUAL STEPS (do these in Play Console)

### Step 1: Upload the AAB file
1. You should already be on the **"Create internal testing release"** page
2. In the **App bundles** section, click **Upload**
3. Select this file from your computer:
   ```
   D:\photonbounce\android\app\build\outputs\bundle\release\app-release.aab
   ```
4. Wait for Google to process it (takes 1-2 minutes)
5. The AAB should show as validated with your signing key

### Step 2: Fill in Release Details
- **Release name**: `1.0.0` (or whatever version you prefer)
- **Release notes**: Copy from below:
  ```
  Initial release of SteadyPath — your private recovery companion.
  
  Features:
  • Sobriety clock with live countdown
  • Daily check-ins with mood & craving tracking
  • Private journal with local encryption
  • Trigger tracker to identify patterns
  • Guided breathing exercises
  • Crisis resources (988, SAMHSA, 911)
  • Withdrawal awareness & timeline info
  • Dark mode support
  
  100% free. No sign-up. All data stays on your device.
  ```

### Step 3: Complete Store Listing (Grow → Store presence → Main store listing)

**App Name:** `SteadyPath: Recovery Companion`

**Short description:** `Track sobriety, journal triggers, breathe through cravings. Your private recovery tool.`

**Full description:** Copy from `D:\photonbounce\playstore-assets\listing-text.md`

**Screenshots:** Upload the 6 phone screenshots from:
```
D:\photonbounce\playstore-assets\screen-01-dashboard.png
D:\photonbounce\playstore-assets\screen-02-checkin.png
D:\photonbounce\playstore-assets\screen-03-journal.png
D:\photonbounce\playstore-assets\screen-04-tools.png
D:\photonbounce\playstore-assets\screen-05-help.png
D:\photonbounce\playstore-assets\screen-06-settings.png
```

**Feature graphic:** Upload:
```
D:\photonbounce\playstore-assets\feature-graphic.png
```

**App icon:** You need to upload a 512×512 PNG icon. Create one or use:
- A simple design: teal circle (#0d9488) with a white leaf icon 🌿
- You can use Canva, Figma, or any image editor

### Step 4: Set Content Rating (Policy → App content)
1. Go to **Policy → App content → Content ratings**
2. Click **Start questionnaire**
3. **Category**: Health & Fitness / Lifestyle
4. Answer the questions:
   - **Violence**: No violence (all "No")
   - **Sexual content**: None (all "No")
   - **Language**: No profanity (all "No")
   - **Controlled substances**: **YES** — the app references drugs/alcohol in a recovery/educational context. Select "References to drugs, alcohol, or tobacco" → NO glamorization.
   - **Gambling**: None (all "No")
   - **Fear/horror**: None (all "No")
   - **Other**: Not designed for children. No user-generated shared content.
5. Expected rating: **ESRB: Teen (T)**, **PEGI: 12+**

### Step 5: Set Privacy Policy URL
1. Go to **App content → Privacy policy**
2. Enter: `https://photon-bounce.com/steadypath/privacy-policy.html`
3. Save

### Step 6: Declare App Category & Details
1. Go to **Grow → Store presence → App category**
2. **Category**: Health & Fitness
3. **Tags**: Sobriety, Recovery, Addiction, Mental Health, Self-help, Wellness

### Step 7: Target Audience
1. Go to **Policy → App content → Target audience**
2. Select: **18+ only** (NOT designed for children)
3. Confirm the app is not primarily for children

### Step 8: Data Safety Section
1. Go to **Policy → Data safety**
2. Answer:
   - **Does your app collect data?** → **NO**
   - **Is data encrypted in transit?** → N/A (no data transmitted)
   - **Is data encrypted at rest?** → NO (stored locally, not encrypted at OS level)
   - **Can users request data deletion?** → YES (app has "Reset All Data" button)
   - **Types of data collected**: None selected (or "App interactions" if you want to be safe for analytics)

### Step 9: Review & Submit
1. Go back to **Testing → Internal testing**
2. Your draft release should be ready
3. Click **Review release**
4. Fix any errors shown
5. Click **Start rollout to Internal testing**

---

## 🔐 CRITICAL: BACK UP YOUR KEYSTORE

Your keystore is at:
```
D:\photonbounce\steadypath-release.keystore
```

**If you lose this file, you can NEVER update the app on Google Play.**

Back it up to:
- [ ] USB drive
- [ ] Cloud storage (Google Drive, Dropbox)
- [ ] Password manager secure notes
- [ ] Email it to yourself

**Keystore details:**
- Alias: `steadypath`
- Password: `SteadyPath2026!`
- Valid until: ~2054

---

## 📱 NEXT STEPS AFTER INTERNAL TESTING

Once internal testing is working:

1. **Add testers** (up to 100 email addresses) in Internal Testing
2. **Test the app** on real devices
3. **Move to Closed Testing** (up to 200 testers)
4. **Move to Production** (public release)

---

## ⚠️ ADSENSE SETUP (do before production)

Your app currently has placeholder AdSense IDs:
- Publisher ID: `ca-pub-YOUR_PUBLISHER_ID`
- Ad slots: `YOUR_AD_SLOT_ID`

To enable real ads:
1. Sign up at https://www.google.com/adsense/start
2. Add your website: `https://photon-bounce.com/steadypath/`
3. Get your Publisher ID (e.g., `ca-pub-1234567890123456`)
4. Create ad units in AdSense and get Ad Slot IDs
5. Replace in `index.html` lines 12, 421, 539, 723
6. Rebuild APK/AAB and upload new release

---

## 📁 ALL ASSET LOCATIONS

```
D:\photonbounce\
├── playstore-assets\
│   ├── listing-text.md          ← All text content
│   ├── screen-01-dashboard.png  ← Phone screenshot 1
│   ├── screen-02-checkin.png    ← Phone screenshot 2
│   ├── screen-03-journal.png    ← Phone screenshot 3
│   ├── screen-04-tools.png      ← Phone screenshot 4
│   ├── screen-05-help.png       ← Phone screenshot 5
│   ├── screen-06-settings.png   ← Phone screenshot 6
│   └── feature-graphic.png      ← 1024×500 feature graphic
├── steadypath\
│   ├── apk\
│   │   └── steadypath-release.apk    ← Signed APK
│   └── privacy-policy.html           ← Live privacy policy
├── android\
│   └── app\build\outputs\bundle\release\
│       └── app-release.aab           ← AAB for Play Store
└── steadypath-release.keystore       ← 🔐 BACK THIS UP
```

---

**Questions?** The Play Console help is at https://support.google.com/googleplay/android-developer
