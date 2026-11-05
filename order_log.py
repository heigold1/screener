# ==========================================================
# order_log.py
# SQLite logging for place-pink-sheet-order.py
# One row per order (per tier, per broker).
# Put this file in the SAME folder as place-pink-sheet-order.py
# ==========================================================

import os
import sqlite3
from datetime import datetime

# Database file lives next to this script: pink_orders.db
DB_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "pink_orders.db")


def init_db():
    """Create the table (and index) if they don't exist yet. Safe to call every startup."""
    conn = sqlite3.connect(DB_PATH)
    try:
        with conn:
            conn.execute("""
                CREATE TABLE IF NOT EXISTS pink_orders (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    logged_at   TEXT NOT NULL,   -- 'YYYY-MM-DD HH:MM:SS' local time
                    trade_date  TEXT NOT NULL,   -- 'YYYY-MM-DD' (the chart script filters on this)
                    symbol      TEXT NOT NULL,
                    action      TEXT,            -- BUY / SELL
                    last_close  REAL,            -- previous close sent by the screener
                    route       TEXT,            -- IB / E*TRADE / NONE
                    broker      TEXT,            -- IBKR / ETRADE
                    tier        INTEGER,         -- 1, 2, ...
                    order_price REAL,
                    shares      INTEGER,
                    drop_pct    REAL,            -- % below last close (e.g. 84.5)
                    status      TEXT,            -- broker status / accepted / failed
                    order_id    TEXT,
                    error       TEXT
                )
            """)
            conn.execute(
                "CREATE INDEX IF NOT EXISTS idx_pink_orders_date ON pink_orders(trade_date)"
            )
    finally:
        conn.close()


def log_order(symbol, action, last_close, route, broker, tier,
              order_price, shares, status, order_id=None, error=None):
    """
    Insert one order row. Never raises: a logging failure must
    never stop an order from being placed or reported.
    """
    try:
        now = datetime.now()
        drop_pct = None
        if last_close and order_price:
            drop_pct = round((1 - order_price / last_close) * 100, 2)

        conn = sqlite3.connect(DB_PATH)
        try:
            with conn:
                conn.execute("""
                    INSERT INTO pink_orders
                        (logged_at, trade_date, symbol, action, last_close, route, broker,
                         tier, order_price, shares, drop_pct, status, order_id, error)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, (
                    now.strftime("%Y-%m-%d %H:%M:%S"),
                    now.strftime("%Y-%m-%d"),
                    symbol, action, last_close, route, broker,
                    tier, order_price, shares, drop_pct, status,
                    None if order_id is None else str(order_id),
                    None if error is None else str(error),
                ))
        finally:
            conn.close()
    except Exception as e:
        print(">>> DB LOG ERROR:", e)


def show_recent(limit=20):
    """Print the most recent rows (for testing)."""
    conn = sqlite3.connect(DB_PATH)
    try:
        rows = conn.execute(
            "SELECT id, logged_at, symbol, route, broker, tier, order_price, shares, "
            "drop_pct, status FROM pink_orders ORDER BY id DESC LIMIT ?", (limit,)
        ).fetchall()
    finally:
        conn.close()
    for r in rows:
        print(r)
    if not rows:
        print("(no rows)")