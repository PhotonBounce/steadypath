#!/usr/bin/env python3
"""
Photon Bounce SaaS — Auto-SEO Engine
Checks microsite SEO health, pings search engines, generates keyword suggestions.
"""
import os
import sys
import json
import sqlite3
import re
import time
from datetime import datetime
from urllib.request import urlopen, Request
from urllib.error import URLError, HTTPError
from urllib.parse import quote

# --- Configuration ---
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(SCRIPT_DIR, '..', 'api', 'data', 'saas.db')
REPORT_DIR = os.path.join(SCRIPT_DIR, 'data')
REPORT_PATH = os.path.join(REPORT_DIR, 'seo_report.json')

BASE_DOMAIN = 'https://photon-bounce.com'
REQUEST_TIMEOUT = 15

# Niche keyword mappings for suggestion generation
NICHE_KEYWORDS = {
    'roofing': ['roof repair', 'roof replacement', 'commercial roofing', 'residential roofer', 'roof inspection'],
    'plumbing': ['emergency plumber', 'pipe repair', 'drain cleaning', 'water heater install', 'leak detection'],
    'hvac': ['air conditioning repair', 'furnace repair', 'HVAC installation', 'duct cleaning', 'AC maintenance'],
    'construction': ['general contractor', 'home renovation', 'commercial construction', 'remodeling services', 'building contractor'],
    'landscaping': ['lawn care', 'landscape design', 'tree removal', 'irrigation systems', 'hardscaping'],
    'cleaning': ['commercial cleaning', 'office cleaning', 'deep cleaning', 'janitorial services', 'move-out cleaning'],
    'electrical': ['electrician near me', 'electrical repair', 'panel upgrade', 'EV charger install', 'generator install'],
    'painting': ['house painter', 'interior painting', 'exterior painting', 'commercial painting', 'cabinet refinishing'],
    'pest': ['pest control', 'termite treatment', 'rodent removal', 'bed bug exterminator', 'mosquito control'],
    'moving': ['local movers', 'long distance moving', 'packing services', 'storage solutions', 'office relocation'],
    'legal': ['personal injury lawyer', 'business attorney', 'criminal defense', 'family law', 'contract review'],
    'dental': ['family dentist', 'emergency dental', 'teeth whitening', 'dental implants', 'invisalign'],
    'medical': ['urgent care', 'primary care', 'specialist doctor', 'telehealth', 'wellness check'],
    'fitness': ['personal trainer', 'gym membership', 'yoga classes', 'crossfit gym', 'nutrition coaching'],
    'realestate': ['homes for sale', 'real estate agent', 'property management', 'commercial real estate', 'home valuation'],
    'restaurant': ['best restaurants', 'catering services', 'private dining', 'food delivery', 'event catering'],
    'salon': ['hair salon', 'nail spa', 'skin care', 'massage therapy', 'beauty services'],
    'auto': ['auto repair', 'car detailing', 'tire shop', 'brake service', 'oil change'],
    'tech': ['IT support', 'web development', 'cybersecurity', 'cloud services', 'data backup'],
    'default': ['local business', 'services near me', 'professional services', 'best in town', 'free estimate']
}


def ensure_dirs():
    os.makedirs(REPORT_DIR, exist_ok=True)


def connect_db():
    if not os.path.exists(DB_PATH):
        raise FileNotFoundError(f"Database not found at {DB_PATH}")
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


def http_get(url, timeout=REQUEST_TIMEOUT):
    """Perform HTTP GET and return (status_code, headers, body, load_time_ms)."""
    req = Request(url, headers={
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.0'
    })
    start = time.time()
    try:
        with urlopen(req, timeout=timeout) as resp:
            body = resp.read().decode('utf-8', errors='replace')
            load_time = int((time.time() - start) * 1000)
            return resp.getcode(), dict(resp.headers), body, load_time
    except HTTPError as e:
        load_time = int((time.time() - start) * 1000)
        body = e.read().decode('utf-8', errors='replace') if e.read() else ''
        return e.code, dict(e.headers), body, load_time
    except URLError as e:
        return 0, {}, str(e.reason), 0
    except Exception as e:
        return 0, {}, str(e), 0


def ping_sitemap(sitemap_url):
    """Ping Google and Bing with sitemap URL."""
    results = {}

    google_ping = f"http://www.google.com/ping?sitemap={quote(sitemap_url, safe='')}\n"
    status, _, _, _ = http_get(google_ping, timeout=10)
    results['google'] = {'status': 'success' if status == 200 else 'failed', 'http_code': status}

    bing_ping = f"http://www.bing.com/ping?sitemap={quote(sitemap_url, safe='')}\n"
    status, _, _, _ = http_get(bing_ping, timeout=10)
    results['bing'] = {'status': 'success' if status == 200 else 'failed', 'http_code': status}

    return results


def analyze_page(html, url):
    """Analyze HTML for SEO factors. Returns dict of findings and sub-scores."""
    html_lower = html.lower()

    meta_score = 0
    has_title = bool(re.search(r'<title[^>]*>[^<]+</title>', html, re.IGNORECASE))
    has_description = 'name="description"' in html_lower or "name='description'" in html_lower
    has_keywords = 'name="keywords"' in html_lower or "name='keywords'" in html_lower
    has_og = 'property="og:' in html_lower or "property='og:" in html_lower
    has_twitter = 'name="twitter:' in html_lower or "name='twitter:" in html_lower

    if has_title:
        meta_score += 10
    if has_description:
        meta_score += 8
    if has_keywords:
        meta_score += 3
    if has_og or has_twitter:
        meta_score += 4

    schema_score = 0
    has_schema_itemtype = 'itemtype=' in html_lower and 'schema.org' in html_lower
    has_json_ld = 'application/ld+json' in html_lower
    has_rdfa = 'typeof=' in html_lower

    if has_json_ld:
        schema_score += 15
    elif has_schema_itemtype:
        schema_score += 10
    elif has_rdfa:
        schema_score += 5

    schema_types = ['localbusiness', 'organization', 'product', 'service', 'breadcrumb', 'website', 'person']
    found_types = [t for t in schema_types if t in html_lower]
    if found_types:
        schema_score += min(5, len(found_types) * 2)
    schema_score = min(20, schema_score)

    viewport_score = 0
    has_viewport = 'name="viewport"' in html_lower or "name='viewport'" in html_lower
    has_responsive = 'width=device-width' in html_lower
    if has_viewport:
        viewport_score += 10
    if has_responsive:
        viewport_score += 5

    https_score = 15 if url.startswith('https://') else 0

    freshness_score = 0
    has_date = re.search(r'\b(20\d{2})\b', html) is not None
    has_copyright = 'copyright' in html_lower or '&copy;' in html or '\u00a9' in html
    if has_date:
        freshness_score += 5
    if has_copyright:
        freshness_score += 5

    findings = {
        'has_title': has_title,
        'has_description': has_description,
        'has_keywords': has_keywords,
        'has_og_tags': has_og,
        'has_twitter_tags': has_twitter,
        'has_schema_jsonld': has_json_ld,
        'has_schema_microdata': has_schema_itemtype,
        'has_viewport': has_viewport,
        'is_https': url.startswith('https://'),
        'has_date_reference': has_date,
        'has_copyright': has_copyright,
        'schema_types_found': found_types
    }

    return {
        'meta_score': meta_score,
        'schema_score': schema_score,
        'viewport_score': viewport_score,
        'https_score': https_score,
        'freshness_score': freshness_score,
        'findings': findings
    }


def check_canonical(html, expected_url):
    """Check for canonical URL tag."""
    match = re.search(r'<link[^>]*rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']', html, re.IGNORECASE)
    if not match:
        match = re.search(r'<link[^>]*href=["\']([^"\']+)["\'][^>]*rel=["\']canonical["\']', html, re.IGNORECASE)
    if match:
        return True, match.group(1)
    return False, None


def compute_seo_score(analysis, load_time_ms):
    """Compute total SEO score (0-100)."""
    if load_time_ms == 0:
        load_score = 7
    elif load_time_ms < 500:
        load_score = 15
    elif load_time_ms < 1000:
        load_score = 12
    elif load_time_ms < 2000:
        load_score = 8
    elif load_time_ms < 4000:
        load_score = 4
    else:
        load_score = 0

    total = (
        analysis['meta_score'] +
        analysis['schema_score'] +
        analysis['viewport_score'] +
        analysis['https_score'] +
        load_score +
        analysis['freshness_score']
    )
    return min(100, total), load_score


def generate_keywords_for_niche(niche):
    """Generate keyword suggestions based on niche."""
    niche_lower = (niche or '').lower()
    keywords = []

    for key, words in NICHE_KEYWORDS.items():
        if key in niche_lower:
            keywords.extend(words)

    if not keywords:
        keywords = NICHE_KEYWORDS['default'][:]

    if niche:
        keywords.append(f"{niche} services")
        keywords.append(f"best {niche}")
        keywords.append(f"{niche} near me")
        keywords.append(f"affordable {niche}")
        keywords.append(f"professional {niche}")

    return list(set(keywords))


def upsert_keywords(conn, microsite_id, keywords):
    """Insert keyword suggestions, skipping duplicates."""
    cursor = conn.cursor()
    now = int(datetime.now().timestamp())
    added = 0

    for kw in keywords:
        try:
            cursor.execute(
                """INSERT INTO keywords (microsite_id, keyword, last_checked)
                   VALUES (?, ?, ?)
                   ON CONFLICT(microsite_id, keyword) DO UPDATE SET
                   last_checked = excluded.last_checked""",
                (microsite_id, kw, now)
            )
            added += 1
        except Exception as e:
            print(f"  [WARN] Keyword insert failed for '{kw}': {e}", file=sys.stderr)

    conn.commit()
    return added


def log_seo_run(conn, microsite_id, run_type, status, details, score_before, score_after):
    """Log an SEO run to the database."""
    cursor = conn.cursor()
    now = int(datetime.now().timestamp())
    cursor.execute(
        """INSERT INTO seo_runs (microsite_id, type, status, details, score_before, score_after, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?)""",
        (microsite_id, run_type, status, json.dumps(details), score_before, score_after, now)
    )
    conn.commit()


def process_microsite(conn, microsite):
    """Run full SEO audit on a single microsite."""
    slug = microsite['slug']
    microsite_url = microsite['url'] or f"{BASE_DOMAIN}/microsites/{slug}/"
    sitemap_url = f"{BASE_DOMAIN}/microsites/{slug}/sitemap.xml"

    score_before = microsite['seo_score'] or 0

    ping_results = ping_sitemap(sitemap_url)
    status, headers, html, load_time = http_get(microsite_url)

    if status != 200 or not html:
        details = {
            'url': microsite_url,
            'http_status': status,
            'error': 'Failed to fetch page',
            'ping_results': ping_results
        }
        log_seo_run(conn, microsite['id'], 'content_check', 'error', details, score_before, score_before)
        return {
            'id': microsite['id'],
            'slug': slug,
            'status': 'error',
            'error': f'HTTP {status}',
            'ping_results': ping_results
        }

    analysis = analyze_page(html, microsite_url)
    has_canonical, canonical_url = check_canonical(html, microsite_url)
    analysis['findings']['has_canonical'] = has_canonical
    analysis['findings']['canonical_url'] = canonical_url

    seo_score, load_score = compute_seo_score(analysis, load_time)

    details = {
        'url': microsite_url,
        'load_time_ms': load_time,
        'ping_results': ping_results,
        'analysis': analysis['findings'],
        'sub_scores': {
            'meta': analysis['meta_score'],
            'schema': analysis['schema_score'],
            'viewport': analysis['viewport_score'],
            'https': analysis['https_score'],
            'load_time': load_score,
            'freshness': analysis['freshness_score']
        }
    }
    log_seo_run(conn, microsite['id'], 'content_check', 'success', details, score_before, seo_score)

    cursor = conn.cursor()
    now = int(datetime.now().timestamp())
    cursor.execute(
        "UPDATE microsites SET seo_score = ?, last_seo_run = ? WHERE id = ?",
        (seo_score, now, microsite['id'])
    )
    conn.commit()

    keywords = generate_keywords_for_niche(microsite['niche'])
    added_keywords = upsert_keywords(conn, microsite['id'], keywords)

    return {
        'id': microsite['id'],
        'slug': slug,
        'status': 'success',
        'seo_score': seo_score,
        'score_before': score_before,
        'load_time_ms': load_time,
        'keywords_added': added_keywords,
        'ping_results': ping_results,
        'findings': analysis['findings']
    }


def get_vip_microsites(conn):
    """Fetch active microsites owned by VIP users."""
    cursor = conn.cursor()
    cursor.execute("""
        SELECT m.id, m.user_id, m.slug, m.niche, m.display_name, m.url,
               m.theme, m.is_active, m.seo_score, m.last_seo_run
        FROM microsites m
        JOIN users u ON m.user_id = u.id
        WHERE u.tier = 'vip' AND m.is_active = 1
    """)
    return cursor.fetchall()


def generate_report(results):
    """Write daily SEO report to JSON."""
    successful = [r for r in results if r['status'] == 'success']
    errors = [r for r in results if r['status'] == 'error']

    avg_score = 0
    if successful:
        avg_score = sum(r['seo_score'] for r in successful) / len(successful)

    report = {
        'generated_at': datetime.now().isoformat(),
        'microsites_checked': len(results),
        'successful': len(successful),
        'errors': len(errors),
        'average_seo_score': round(avg_score, 1),
        'score_distribution': {
            'excellent (90-100)': len([r for r in successful if r['seo_score'] >= 90]),
            'good (70-89)': len([r for r in successful if 70 <= r['seo_score'] < 90]),
            'fair (50-69)': len([r for r in successful if 50 <= r['seo_score'] < 70]),
            'poor (0-49)': len([r for r in successful if r['seo_score'] < 50]),
        },
        'details': results
    }

    with open(REPORT_PATH, 'w') as f:
        json.dump(report, f, indent=2)

    return report


def main():
    print("=" * 50)
    print("Photon Bounce Auto-SEO Engine")
    print(f"Started at: {datetime.now().isoformat()}")
    print("=" * 50)

    ensure_dirs()

    try:
        conn = connect_db()
        print(f"[OK] Connected to database: {DB_PATH}")
    except Exception as e:
        print(f"[FATAL] Database connection failed: {e}", file=sys.stderr)
        return 1

    try:
        print("\n[1/2] Fetching VIP microsites...")
        microsites = get_vip_microsites(conn)
        print(f"      Found {len(microsites)} active VIP microsites")

        results = []
        for idx, ms in enumerate(microsites, 1):
            print(f"\n      [{idx}/{len(microsites)}] Checking: {ms['slug']} ({ms['niche']})")
            try:
                result = process_microsite(conn, ms)
                if result['status'] == 'success':
                    print(f"      [OK] SEO Score: {result['seo_score']} (was {result['score_before']})")
                else:
                    print(f"      [ERROR] {result['error']}")
                results.append(result)
            except Exception as e:
                print(f"      [ERROR] Exception: {e}", file=sys.stderr)
                results.append({
                    'id': ms['id'],
                    'slug': ms['slug'],
                    'status': 'error',
                    'error': str(e)
                })

        print("\n[2/2] Generating daily SEO report...")
        report = generate_report(results)
        print(f"      Report saved to: {REPORT_PATH}")
        print(f"      Successful: {report['successful']}, Errors: {report['errors']}")
        print(f"      Avg SEO Score: {report['average_seo_score']}")

    except Exception as e:
        print(f"[FATAL] Engine error: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1
    finally:
        conn.close()

    print("\n[OK] Auto-SEO Engine completed successfully.")
    return 0


if __name__ == '__main__':
    sys.exit(main())
