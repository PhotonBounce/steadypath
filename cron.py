#!/usr/bin/env python3
"""
Photon Bounce SaaS — Cron Wrapper
Runs ML Engine and Auto-SEO Engine in sequence with logging.
"""
import os
import sys
import subprocess
from datetime import datetime


SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ML_ENGINE = os.path.join(SCRIPT_DIR, 'ml', 'engine.py')
SEO_ENGINE = os.path.join(SCRIPT_DIR, 'seo', 'engine.py')


def run_engine(engine_path, name):
    """Run an engine script and capture output."""
    print(f"\n{'='*50}")
    print(f"Running: {name}")
    print(f"Path: {engine_path}")
    print(f"Time: {datetime.now().isoformat()}")
    print('='*50)

    if not os.path.exists(engine_path):
        print(f"[ERROR] Engine not found: {engine_path}", file=sys.stderr)
        return {'name': name, 'status': 'missing', 'returncode': -1, 'output': ''}

    try:
        result = subprocess.run(
            [sys.executable, engine_path],
            capture_output=True,
            text=True,
            timeout=300
        )

        if result.stdout:
            print(result.stdout)

        if result.stderr:
            print(result.stderr, file=sys.stderr)

        status = 'success' if result.returncode == 0 else 'failed'
        print(f"[{'OK' if status == 'success' else 'FAIL'}] {name} exited with code {result.returncode}")

        return {
            'name': name,
            'status': status,
            'returncode': result.returncode,
            'output': result.stdout,
            'errors': result.stderr
        }

    except subprocess.TimeoutExpired:
        print(f"[ERROR] {name} timed out after 300 seconds", file=sys.stderr)
        return {'name': name, 'status': 'timeout', 'returncode': -1, 'output': '', 'errors': 'Timeout'}
    except Exception as e:
        print(f"[ERROR] Failed to run {name}: {e}", file=sys.stderr)
        return {'name': name, 'status': 'error', 'returncode': -1, 'output': '', 'errors': str(e)}


def main():
    print("=" * 60)
    print("Photon Bounce SaaS — Daily Cron Runner")
    print(f"Started at: {datetime.now().isoformat()}")
    print(f"Python: {sys.executable}")
    print("=" * 60)

    results = []

    ml_result = run_engine(ML_ENGINE, 'ML Engine')
    results.append(ml_result)

    seo_result = run_engine(SEO_ENGINE, 'Auto-SEO Engine')
    results.append(seo_result)

    print(f"\n{'='*60}")
    print("CRON SUMMARY")
    print('='*60)

    all_success = all(r['status'] == 'success' for r in results)
    for r in results:
        icon = 'OK' if r['status'] == 'success' else 'FAIL'
        print(f"  [{icon}] {r['name']}: {r['status'].upper()} (code: {r['returncode']})")

    if all_success:
        print("\n[OK] All engines completed successfully.")
        return 0
    else:
        print("\n[WARNING] One or more engines failed. Check logs above.", file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
