-- Photon Bounce SaaS — Database Schema
-- SQLite compatible (for shared hosting simplicity)

PRAGMA foreign_keys = ON;

-- Users table with tier management
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    tier TEXT NOT NULL DEFAULT 'free' CHECK(tier IN ('free', 'vip')),
    tier_expires_at INTEGER, -- unix timestamp, NULL = never
    api_key TEXT UNIQUE,
    timezone TEXT DEFAULT 'America/New_York',
    email_notifications INTEGER DEFAULT 1,
    push_token TEXT, -- FCM token for mobile push
    created_at INTEGER DEFAULT (strftime('%s','now')),
    updated_at INTEGER DEFAULT (strftime('%s','now')),
    last_login INTEGER
);

-- Microsites managed by users
CREATE TABLE IF NOT EXISTS microsites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    slug TEXT NOT NULL,
    niche TEXT NOT NULL,
    display_name TEXT NOT NULL,
    url TEXT NOT NULL,
    theme TEXT DEFAULT 'default',
    is_active INTEGER DEFAULT 1,
    seo_score INTEGER DEFAULT 0, -- 0-100
    last_seo_run INTEGER,
    created_at INTEGER DEFAULT (strftime('%s','now')),
    UNIQUE(user_id, slug)
);

-- Leads table (enhanced from original)
CREATE TABLE IF NOT EXISTS leads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    microsite_id INTEGER REFERENCES microsites(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    niche TEXT,
    budget TEXT,
    message TEXT,
    source TEXT DEFAULT 'direct',
    ip_address TEXT,
    user_agent TEXT,
    -- ML fields
    ml_score REAL, -- 0.0 to 1.0 lead quality
    ml_tags TEXT, -- JSON array of ML tags ["hot", "budget_confirmed", "urgent"]
    spam_score REAL DEFAULT 0, -- 0.0 to 1.0, >0.7 = likely spam
    -- Pipeline
    status TEXT DEFAULT 'new' CHECK(status IN ('new', 'contacted', 'qualified', 'proposal', 'won', 'lost', 'spam')),
    assigned_to INTEGER REFERENCES users(id),
    notes TEXT,
    follow_up_at INTEGER, -- unix timestamp
    -- Timestamps
    created_at INTEGER DEFAULT (strftime('%s','now')),
    updated_at INTEGER DEFAULT (strftime('%s','now'))
);

-- Lead activity log
CREATE TABLE IF NOT EXISTS lead_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lead_id INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id),
    action TEXT NOT NULL, -- 'status_change', 'note_added', 'email_sent', 'call_made'
    details TEXT,
    created_at INTEGER DEFAULT (strftime('%s','now'))
);

-- SEO runs log
CREATE TABLE IF NOT EXISTS seo_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    microsite_id INTEGER NOT NULL REFERENCES microsites(id) ON DELETE CASCADE,
    type TEXT NOT NULL, -- 'sitemap_ping', 'meta_optimize', 'content_check', 'speed_test', 'keyword_track'
    status TEXT NOT NULL, -- 'success', 'warning', 'error'
    details TEXT, -- JSON with results
    score_before INTEGER,
    score_after INTEGER,
    created_at INTEGER DEFAULT (strftime('%s','now'))
);

-- Keyword tracking
CREATE TABLE IF NOT EXISTS keywords (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    microsite_id INTEGER NOT NULL REFERENCES microsites(id) ON DELETE CASCADE,
    keyword TEXT NOT NULL,
    position INTEGER, -- Google position, NULL = not ranked
    search_volume INTEGER,
    last_checked INTEGER,
    created_at INTEGER DEFAULT (strftime('%s','now')),
    UNIQUE(microsite_id, keyword)
);

-- Analytics events
CREATE TABLE IF NOT EXISTS analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    microsite_id INTEGER,
    event_type TEXT NOT NULL, -- 'page_view', 'lead_submit', 'conversion'
    value REAL,
    meta TEXT, -- JSON
    created_at INTEGER DEFAULT (strftime('%s','now'))
);

-- Subscriptions / billing
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    platform TEXT NOT NULL DEFAULT 'web' CHECK(platform IN ('web', 'android', 'ios')),
    provider_subscription_id TEXT, -- Stripe/Google Play/Apple
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'cancelled', 'past_due', 'trialing')),
    current_period_start INTEGER,
    current_period_end INTEGER,
    cancel_at_period_end INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT (strftime('%s','now')),
    updated_at INTEGER DEFAULT (strftime('%s','now'))
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_leads_user ON leads(user_id);
CREATE INDEX IF NOT EXISTS idx_leads_microsite ON leads(microsite_id);
CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status);
CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at);
CREATE INDEX IF NOT EXISTS idx_leads_ml_score ON leads(ml_score);
CREATE INDEX IF NOT EXISTS idx_microsites_user ON microsites(user_id);
CREATE INDEX IF NOT EXISTS idx_analytics_user ON analytics(user_id);
CREATE INDEX IF NOT EXISTS idx_analytics_event ON analytics(event_type);
CREATE INDEX IF NOT EXISTS idx_seo_runs_microsite ON seo_runs(microsite_id);

-- Seed admin user (change password after first login)
INSERT OR IGNORE INTO users (email, password_hash, name, tier) VALUES 
('admin@photon-bounce.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'vip');
