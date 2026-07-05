#!/usr/bin/env python3
"""
Photon Bounce SaaS — ML Engine
Scores leads using heuristic ML models, detects anomalies, generates reports.
"""
import os
import sys
import json
import sqlite3
import re
from datetime import datetime, timedelta

# --- Configuration ---
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(SCRIPT_DIR, '..', 'api', 'data', 'saas.db')
REPORT_DIR = os.path.join(SCRIPT_DIR, 'data')
REPORT_PATH = os.path.join(REPORT_DIR, 'ml_report.json')

# Email domain quality tiers
FREE_EMAIL_DOMAINS = {
    'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com',
    'icloud.com', 'protonmail.com', 'mail.com', 'yandex.com', 'qq.com',
    'live.com', 'msn.com', 'me.com'
}
SUSPICIOUS_DOMAINS = {
    'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
    'throwawaymail.com', 'yopmail.com', 'fakeinbox.com', 'sharklasers.com'
}

# Urgency keywords
URGENCY_KEYWORDS = [
    'urgent', 'asap', 'immediately', 'emergency', 'rush', 'hurry',
    'follow up', 'follow-up', 'call me', 'call back', 'reply soon',
    'need now', 'needed now', 'quick', 'fast', 'today', 'tonight'
]

# Business hours (assumed America/New_York context)
BUSINESS_HOURS_START = 8
BUSINESS_HOURS_END = 18


def ensure_dirs():
    os.makedirs(REPORT_DIR, exist_ok=True)


def connect_db():
    if not os.path.exists(DB_PATH):
        raise FileNotFoundError(f"Database not found at {DB_PATH}")
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


def parse_budget(budget_text):
    """Extract numeric budget value from text."""
    if not budget_text:
        return 0
    matches = re.findall(r'[\$£€]?\s*([\d,]+(?:\.\d{2})?)\s*([kK]?)', budget_text)
    if not matches:
        nums = re.findall(r'\d[\d,]*(?:\.\d+)?', budget_text)
        if nums:
            return float(nums[0].replace(',', ''))
        return 0
    val_str, suffix = matches[0]
    val = float(val_str.replace(',', ''))
    if suffix.lower() == 'k':
        val *= 1000
    return val


def get_email_domain_score(email):
    if not email or '@' not in email:
        return 0.0
    domain = email.split('@')[1].lower().strip()
    if domain in SUSPICIOUS_DOMAINS:
        return -0.15
    if domain in FREE_EMAIL_DOMAINS:
        return 0.0
    return 0.08


def get_time_of_day_score(timestamp):
    """Score based on hour of submission."""
    if not timestamp:
        return 0.0
    try:
        dt = datetime.fromtimestamp(timestamp)
        hour = dt.hour
        if BUSINESS_HOURS_START <= hour < BUSINESS_HOURS_END:
            return 0.05
        elif hour < 6 or hour > 23:
            return -0.05
        return 0.0
    except Exception:
        return 0.0


def check_duplicates(conn, lead):
    """Check for duplicate/similar leads in the past 30 days."""
    cursor = conn.cursor()
    thirty_days_ago = int((datetime.now() - timedelta(days=30)).timestamp())

    if lead['email']:
        cursor.execute(
            "SELECT COUNT(*) as cnt FROM leads WHERE email = ? AND id != ? AND created_at > ?",
            (lead['email'], lead['id'], thirty_days_ago)
        )
        if cursor.fetchone()['cnt'] > 0:
            return -0.12

    if lead['phone']:
        cursor.execute(
            "SELECT COUNT(*) as cnt FROM leads WHERE phone = ? AND id != ? AND created_at > ?",
            (lead['phone'], lead['id'], thirty_days_ago)
        )
        if cursor.fetchone()['cnt'] > 0:
            return -0.10

    cursor.execute(
        "SELECT COUNT(*) as cnt FROM leads WHERE name = ? AND niche = ? AND id != ? AND created_at > ?",
        (lead['name'], lead['niche'], lead['id'], thirty_days_ago)
    )
    count = cursor.fetchone()['cnt']
    if count >= 3:
        return -0.15
    elif count >= 1:
        return -0.05

    return 0.0


def compute_ml_score(conn, lead):
    """Compute ML score for a single lead."""
    score = 0.5

    budget_val = parse_budget(lead['budget'])
    if budget_val >= 10000:
        score += 0.15
    elif budget_val >= 5000:
        score += 0.10
    elif budget_val >= 1000:
        score += 0.05
    elif budget_val > 0:
        score += 0.02

    if lead['phone'] and len(lead['phone']) >= 10:
        score += 0.08

    message = lead['message'] or ''
    msg_len = len(message)
    if 50 <= msg_len <= 500:
        score += 0.08
    elif 20 <= msg_len < 50:
        score += 0.03
    elif msg_len > 2000:
        score -= 0.05
    elif msg_len < 10:
        score -= 0.03

    score += get_email_domain_score(lead['email'])
    score += get_time_of_day_score(lead['created_at'])
    score += check_duplicates(conn, lead)

    spam_score = lead['spam_score'] or 0
    if spam_score > 0.4:
        score -= 0.2
    elif spam_score > 0.2:
        score -= 0.1

    return max(0.0, min(1.0, score))


def generate_tags(lead, ml_score):
    """Generate ML tags based on lead attributes and score."""
    tags = []

    if ml_score > 0.75:
        tags.append('hot')
    elif ml_score >= 0.5:
        tags.append('warm')
    else:
        tags.append('cold')

    budget_val = parse_budget(lead['budget'])
    if budget_val > 0:
        tags.append('budget_confirmed')

    if lead['phone'] and len(lead['phone']) >= 10:
        tags.append('phone_ready')

    message = (lead['message'] or '').lower()
    if any(kw in message for kw in URGENCY_KEYWORDS):
        tags.append('urgent')

    spam_score = lead['spam_score'] or 0
    if spam_score > 0.4:
        tags.append('review_for_spam')

    return tags


def process_leads(conn):
    """Process all unscored leads."""
    cursor = conn.cursor()
    cursor.execute("""
        SELECT id, name, email, phone, niche, budget, message, source,
               spam_score, status, created_at
        FROM leads
        WHERE ml_score IS NULL
    """)
    leads = cursor.fetchall()

    processed = 0
    errors = 0
    tag_counts = {
        'hot': 0, 'warm': 0, 'cold': 0, 'budget_confirmed': 0,
        'phone_ready': 0, 'urgent': 0, 'review_for_spam': 0
    }

    for lead in leads:
        try:
            score = compute_ml_score(conn, lead)
            tags = generate_tags(lead, score)

            cursor.execute(
                "UPDATE leads SET ml_score = ?, ml_tags = ? WHERE id = ?",
                (score, json.dumps(tags), lead['id'])
            )
            processed += 1

            for tag in tags:
                if tag in tag_counts:
                    tag_counts[tag] += 1
        except Exception as e:
            print(f"  [ERROR] Lead ID {lead['id']}: {e}", file=sys.stderr)
            errors += 1

    conn.commit()
    return {
        'processed': processed,
        'errors': errors,
        'tag_counts': tag_counts,
        'total_unscored': len(leads)
    }


def detect_anomalies(conn):
    """Detect sudden spikes in lead volume (possible bot attacks)."""
    cursor = conn.cursor()
    now = datetime.now()

    today_start = int(datetime(now.year, now.month, now.day).timestamp())
    today_end = today_start + 86400

    cursor.execute(
        "SELECT COUNT(*) as cnt FROM leads WHERE created_at >= ? AND created_at < ?",
        (today_start, today_end)
    )
    today_count = cursor.fetchone()['cnt']

    daily_counts = []
    for i in range(1, 8):
        day_start = today_start - (i * 86400)
        day_end = day_start + 86400
        cursor.execute(
            "SELECT COUNT(*) as cnt FROM leads WHERE created_at >= ? AND created_at < ?",
            (day_start, day_end)
        )
        daily_counts.append(cursor.fetchone()['cnt'])

    avg_leads = sum(daily_counts) / len(daily_counts) if daily_counts else 0
    max_leads = max(daily_counts) if daily_counts else 0

    anomaly_detected = False
    anomaly_details = {}

    if avg_leads > 0 and today_count > avg_leads * 3 and today_count > 10:
        anomaly_detected = True
        anomaly_details = {
            'type': 'lead_spike',
            'today_count': today_count,
            'avg_7day': round(avg_leads, 1),
            'max_7day': max_leads,
            'severity': 'high' if today_count > avg_leads * 5 else 'medium'
        }
    elif today_count > 50 and avg_leads < 5:
        anomaly_detected = True
        anomaly_details = {
            'type': 'lead_spike',
            'today_count': today_count,
            'avg_7day': round(avg_leads, 1),
            'max_7day': max_leads,
            'severity': 'high'
        }

    return {
        'anomaly_detected': anomaly_detected,
        'details': anomaly_details,
        'today_count': today_count,
        'avg_7day': round(avg_leads, 1)
    }


def generate_report(conn, lead_stats, anomaly_info):
    """Write daily ML report to JSON."""
    cursor = conn.cursor()

    cursor.execute("SELECT COUNT(*) as total FROM leads")
    total_leads = cursor.fetchone()['total']

    cursor.execute("SELECT COUNT(*) as scored FROM leads WHERE ml_score IS NOT NULL")
    scored_leads = cursor.fetchone()['scored']

    cursor.execute("""
        SELECT AVG(ml_score) as avg_score, MAX(ml_score) as max_score, MIN(ml_score) as min_score
        FROM leads WHERE ml_score IS NOT NULL
    """)
    row = cursor.fetchone()
    score_stats = {
        'avg': round(row['avg_score'] or 0, 3),
        'max': round(row['max_score'] or 0, 3),
        'min': round(row['min_score'] or 0, 3)
    }

    cursor.execute("SELECT status, COUNT(*) as cnt FROM leads GROUP BY status")
    status_breakdown = {r['status']: r['cnt'] for r in cursor.fetchall()}

    report = {
        'generated_at': datetime.now().isoformat(),
        'total_leads': total_leads,
        'scored_leads': scored_leads,
        'unscored_leads': total_leads - scored_leads,
        'score_statistics': score_stats,
        'status_breakdown': status_breakdown,
        'today_processing': lead_stats,
        'anomaly_detection': anomaly_info
    }

    with open(REPORT_PATH, 'w') as f:
        json.dump(report, f, indent=2)

    return report


def main():
    print("=" * 50)
    print("Photon Bounce ML Engine")
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
        print("\n[1/3] Processing unscored leads...")
        lead_stats = process_leads(conn)
        print(f"      Processed: {lead_stats['processed']}")
        print(f"      Errors: {lead_stats['errors']}")
        print(f"      Tags: {json.dumps(lead_stats['tag_counts'])}")

        print("\n[2/3] Running anomaly detection...")
        anomaly_info = detect_anomalies(conn)
        if anomaly_info['anomaly_detected']:
            print(f"      [ALERT] Anomaly detected: {anomaly_info['details']}")
        else:
            print(f"      [OK] No anomalies. Today: {anomaly_info['today_count']}, Avg 7d: {anomaly_info['avg_7day']}")

        print("\n[3/3] Generating daily report...")
        report = generate_report(conn, lead_stats, anomaly_info)
        print(f"      Report saved to: {REPORT_PATH}")
        print(f"      Total leads: {report['total_leads']}, Scored: {report['scored_leads']}")

    except Exception as e:
        print(f"[FATAL] Engine error: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1
    finally:
        conn.close()

    print("\n[OK] ML Engine completed successfully.")
    return 0


if __name__ == '__main__':
    sys.exit(main())
