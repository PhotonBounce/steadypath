# Photon Bounce Leads - Android App

## Project Overview
Native Kotlin Android app for the Photon Bounce SaaS lead management platform.

## Architecture
- **Language:** Kotlin
- **Architecture Pattern:** MVVM with Repository pattern
- **UI:** XML layouts with Material Design 3
- **Networking:** Retrofit + OkHttp with JWT authentication
- **Local Storage:** Room database with offline support
- **DI:** Manual dependency injection via ViewModelFactory

## Project Structure
```
app/src/main/java/com/photonbounce/leads/
├── activities/          # MainActivity, AuthActivity, LeadDetailActivity
├── fragments/           # Dashboard, Leads, Microsites, Analytics, Settings, Login, Register
├── viewmodels/          # State management (MVVM)
├── adapters/            # RecyclerView adapters
├── api/                 # Retrofit service, interceptors
├── db/                  # Room entities, DAOs, database
├── repository/          # Data layer abstraction
├── models/              # Data classes (User, Lead, Microsite, Analytics)
├── billing/             # Google Play Billing integration
├── ads/                 # AdMob manager
├── fcm/                 # Firebase Cloud Messaging service
├── utils/               # TokenManager, NetworkMonitor, IntentUtils
└── LeadsApplication.kt  # Application class

res/
├── layout/              # XML layouts
├── navigation/          # Navigation graph
├── menu/                # Bottom nav menu
├── values/              # Themes, colors, strings
├── drawable/            # Vector icons
└── xml/                 # Data extraction rules
```

## Key Classes

### Authentication & Security
- **TokenManager** - Securely stores JWT in EncryptedSharedPreferences
- **AuthInterceptor** - Attaches Bearer token to all API requests
- **AuthViewModel** - Handles login/register state

### Data Layer
- **AppDatabase** - Room database with LeadEntity and MicrositeEntity
- **LeadsRepository** - Caches leads, queues offline changes, syncs when online
- **MicrositeRepository** - Manages microsite CRUD with offline support
- **UserRepository** - Fetches dashboard stats, analytics, user profile

### UI Components
- **MainActivity** - Hosts bottom navigation, shows banner ads for free users
- **DashboardFragment** - Stats cards, recent leads, pull-to-refresh
- **LeadsFragment** - Search, filter by status, swipe actions
- **LeadDetailActivity** - Full lead info, status changer, call/email
- **MicrositesFragment** - Grid layout, add microsite dialog
- **AnalyticsFragment** - MPAndroidChart bar/pie charts, VIP lock overlay
- **SettingsFragment** - Profile edit, tier display, logout

### Monetization
- **AdManager** - Banner ads, interstitials every 5 lead views (free only)
- **BillingManager** - Google Play Billing for monthly ($19) and yearly ($199) subscriptions

### Push Notifications
- **FCMService** - Receives new lead and daily summary push notifications

## Build Instructions

### Prerequisites
- Android Studio Hedgehog (2023.1.1) or newer
- JDK 17
- Android SDK 34

### Setup
1. Open the project in Android Studio
2. Replace placeholder values:
   - `app/google-services.json` - Add your Firebase config
   - `AndroidManifest.xml` - Replace AdMob app ID
   - `AdManager.kt` - Replace ad unit IDs
   - `BillingManager.kt` - Configure SKU IDs in Google Play Console

3. Sync project with Gradle files
4. Build and run on emulator or device

### Gradle Commands
```bash
./gradlew assembleDebug      # Build debug APK
./gradlew assembleRelease    # Build release APK
./gradlew test               # Run unit tests
```

## API Endpoints
Base URL: `https://photon-bounce.com/leads/api`
- POST /auth/login
- POST /auth/register
- GET /me, PUT /me
- GET /microsites, POST /microsites, PUT /microsites/{id}
- GET /leads, PUT /leads/{id}
- GET /analytics/dashboard
- GET /tier

## Feature Checklist
- [x] JWT Authentication with EncryptedSharedPreferences
- [x] Auto-login on app start
- [x] Material Design 3 dark theme (#0a0f1e, #00d4ff)
- [x] Bottom navigation (5 tabs)
- [x] Dashboard with stats cards and recent leads
- [x] Leads list with search, filter, swipe actions
- [x] Lead detail with status changer, notes, call/email
- [x] Microsites grid with add/edit/toggle
- [x] Analytics with MPAndroidChart (bar + pie)
- [x] VIP lock overlay for advanced analytics
- [x] Settings with profile, tier, notifications, logout
- [x] AdMob banner + interstitial ads (free tier)
- [x] Google Play Billing (monthly/yearly subscriptions)
- [x] Firebase Cloud Messaging push notifications
- [x] Room database offline caching
- [x] Offline status change queue with sync
- [x] Retrofit with JWT interceptor
- [x] All API endpoints integrated
