# ==========================================================
# add_test_rows.py
# Inserts TEST rows into pink_orders.db so you can try
# pink-sheet-chart-orders.py without placing real orders.
#
# For each symbol it looks up the real previous close (Yahoo daily),
# then logs a fake order at the 25% level, placed at 06:35 PT today.
#
# Usage (screener folder):
#   python add_test_rows.py                 -> FMCC NXGB CHOW CHSN
#   python add_test_rows.py ABCD EFGH       -> your own list
# Remove the test rows afterward with:
#   python add_test_rows.py --clean
# ==========================================================

import sqlite3
import sys
from datetime import datetime

import yfinance as yf

import order_log

SYMBOLS = ["FMCC", "NXGB", "CHOW", "CHSN"]
TEST_TIME = "06:35:00"     # fake order time (PT)
TEST_DROP = 0.25           # fake order at 25% below previous close
TEST_TAG = "TEST ROW"      # stored in the error column so test rows are easy to delete


def previous_close(symbol, today):
    """Close of the last trading day before `today` (YYYY-MM-DD)."""
    df = yf.Ticker(symbol).history(period="10d", interval="1d", auto_adjust=False)
    if df is None or df.empty:
        return None
    df = df[[d.strftime("%Y-%m-%d") < today for d in df.index]]
    if df.empty:
        return None
    return float(df["Close"].iloc[-1])


def add_rows(symbols):
    order_log.init_db()
    today = datetime.now().strftime("%Y-%m-%d")
    conn = sqlite3.connect(order_log.DB_PATH)
    try:
        with conn:
            for sym in symbols:
                pc = previous_close(sym, today)
                if pc is None:
                    print(f"{sym}: no previous close found on Yahoo, skipped")
                    continue
                price = round(pc * (1 - TEST_DROP), 4)
                shares = max(1, int(100 / price))
                conn.execute("""
                    INSERT INTO pink_orders
                        (logged_at, trade_date, symbol, action, last_close, route, broker,
                         tier, order_price, shares, drop_pct, status, order_id, error)
                    VALUES (?, ?, ?, 'BUY', ?, 'IB', 'IBKR', 1, ?, ?, ?, 'TEST', NULL, ?)
                """, (f"{today} {TEST_TIME}", today, sym, pc, price, shares,
                      round(TEST_DROP * 100, 2), TEST_TAG))
                print(f"{sym}: prev close {pc:.4f} -> test order {price} ({shares} sh)")
    finally:
        conn.close()


def clean():
    conn = sqlite3.connect(order_log.DB_PATH)
    try:
        with conn:
            n = conn.execute("DELETE FROM pink_orders WHERE error = ?", (TEST_TAG,)).rowcount
    finally:
        conn.close()
    print(f"Deleted {n} test row(s)")


if __name__ == "__main__":
    args = sys.argv[1:]
    if args and args[0] == "--clean":
        clean()
    else:
        add_rows([a.upper() for a in args] or SYMBOLS)
        order_log.show_recent()