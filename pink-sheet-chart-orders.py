# ==========================================================
# chart_orders.py
# Reads pink_orders.db (written by place-pink-sheet-order.py),
# pulls 1-minute candles for each symbol for that day (yfinance),
# and writes ONE HTML page with a chart per stock showing:
#   - previous close line (gray)
#   - 25% study line (orange)
#   - each order price line + a triangle at the time it was placed (blue)
# Usage (from the screener folder):
#   python chart_orders.py                 -> today
#   python chart_orders.py 2026-11-04      -> a specific day
# Note: Yahoo only keeps 1-minute bars for roughly the last 30 days,
# so run it the same day or within a few days.
# ==========================================================

import json
import math
import os
import sqlite3
import sys
import webbrowser
from datetime import datetime, timedelta

import yfinance as yf

HERE = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(HERE, "pink_orders.db")
LOCAL_TZ = "America/Los_Angeles"
STUDY_DROP = 0.25          # the 25% level drawn on every chart
RETEST_AWAY = 0.03         # a re-test counts only after price moved 3%+ above the level
MIN_PREV_CLOSE = 1.00      # only chart dollar stocks (previous close >= $1.00); set to 0 to chart everything
REPORTS_DIR = "pink_reports"   # daily HTML pages go in this subfolder (git-ignored)
NEWSLOOKUP_URL = ("http://ec2-52-89-7-59.us-west-2.compute.amazonaws.com/"
                  "newslookup/index.php?symbol={sym}&vix=15.21&check-sec=1")


# ---------------- DATA ----------------

def load_orders(trade_date):
    """Return {symbol: [order rows]} for one trade_date."""
    if not os.path.exists(DB_PATH):
        print(f"No database found at {DB_PATH}")
        return {}
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    try:
        rows = conn.execute(
            "SELECT * FROM pink_orders WHERE trade_date = ? "
            "ORDER BY symbol, logged_at, tier",
            (trade_date,)
        ).fetchall()
    finally:
        conn.close()
    by_symbol = {}
    for r in rows:
        by_symbol.setdefault(r["symbol"], []).append(dict(r))
    return by_symbol


def fetch_1min(symbol, trade_date):
    """1-minute bars for one day, times in Pacific. Returns (bars, error)."""
    start = datetime.strptime(trade_date, "%Y-%m-%d")
    end = start + timedelta(days=1)
    try:
        df = yf.Ticker(symbol).history(
            start=start.strftime("%Y-%m-%d"),
            end=end.strftime("%Y-%m-%d"),
            interval="1m",
            prepost=True,
            auto_adjust=False,
        )
    except Exception as e:
        return None, f"Data error: {e}"

    if df is None or df.empty:
        return None, "No 1-minute data found"

    if df.index.tz is None:
        df.index = df.index.tz_localize("America/New_York")
    df.index = df.index.tz_convert(LOCAL_TZ)

    bars = []
    for ts, row in df.iterrows():
        o, h, l, c = row["Open"], row["High"], row["Low"], row["Close"]
        if any(x is None or (isinstance(x, float) and math.isnan(x)) for x in (o, h, l, c)):
            continue
        v = row.get("Volume", 0)
        v = 0 if v is None or (isinstance(v, float) and math.isnan(v)) else int(v)
        bars.append({"t": ts.strftime("%H:%M"), "o": float(o), "h": float(h),
                     "l": float(l), "c": float(c), "v": v})

    if not bars:
        return None, "No 1-minute data found"
    return bars, None


def bar_index_at(bars, hhmm):
    """Index of the first bar at or after HH:MM (else the last bar)."""
    for i, b in enumerate(bars):
        if b["t"] >= hhmm:
            return i
    return len(bars) - 1


def build_item(symbol, orders):
    trade_date = orders[0]["trade_date"]
    prev_close = orders[0]["last_close"]
    bars, err = fetch_1min(symbol, trade_date)

    item = {
        "symbol": symbol,
        "link": NEWSLOOKUP_URL.format(sym=symbol),
        "prevClose": prev_close,
        "studyLevel": round(prev_close * (1 - STUDY_DROP), 4) if prev_close else None,
        "error": err,
        "bars": bars or [],
        "orders": [],
        "stats": {},
    }

    for o in orders:
        hhmm = o["logged_at"][11:16]
        idx = bar_index_at(bars, hhmm) if bars else None
        filled = None
        if bars and o["order_price"]:
            filled = any(b["l"] <= o["order_price"] for b in bars[idx:])
        item["orders"].append({
            "time": hhmm,
            "idx": idx,
            "price": o["order_price"],
            "dropPct": o["drop_pct"],
            "tier": o["tier"],
            "broker": o["broker"],
            "status": o["status"],
            "wouldFill": filled,
        })

    if bars and prev_close:
        lows = [b["l"] for b in bars]
        highs = [b["h"] for b in bars]
        day_low = min(lows)
        low_idx = lows.index(day_low)
        stats = {
            "dayLow": day_low,
            "dayLowTime": bars[low_idx]["t"],
            "dayLowDropPct": round((1 - day_low / prev_close) * 100, 1),
            "dayHigh": max(highs),
            "lastPrice": bars[-1]["c"],
            "lastChangePct": round((bars[-1]["c"] / prev_close - 1) * 100, 1),
        }
        # 25% study: when it first hit the level, and the best bounce after that
        lvl = item["studyLevel"]
        hit = next((i for i, b in enumerate(bars) if b["l"] <= lvl), None)
        if hit is not None:
            after_high = max(b["h"] for b in bars[hit:])
            stats["hit25Time"] = bars[hit]["t"]
            stats["hit25Idx"] = hit
            stats["bounceFrom25Pct"] = round((after_high / lvl - 1) * 100, 1)

            # Re-tests: after the first touch, price must move RETEST_AWAY (3%)
            # above the level, then come back down to touch the level again.
            retests = []
            away = False
            for i in range(hit + 1, len(bars)):
                b = bars[i]
                if away and b["l"] <= lvl:
                    retests.append({"t": b["t"], "idx": i})
                    away = False
                if not away and b["h"] >= lvl * (1 + RETEST_AWAY):
                    away = True
            stats["retests"] = retests
        item["stats"] = stats

    return item


# ---------------- HTML ----------------

HTML_TEMPLATE = r"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pink Orders __DATE__</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial@0.2.1/dist/chartjs-chart-financial.min.js"></script>
<style>
  body { font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
         max-width: 1000px; margin: 20px auto; padding: 0 16px; color: #1a1a1a; }
  h1 { font-size: 20px; margin-bottom: 4px; }
  .sub { color: #666; font-size: 13px; margin-top: 0; }
  .entry { border: 1px solid #ddd; border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; }
  .entry-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
  .entry-symbol { font-size: 18px; font-weight: 700; }
  .stats { font-size: 12px; color: #444; margin-bottom: 4px; line-height: 1.6; }
  .stats b { color: #1a1a1a; }
  .ord { font-size: 12px; color: #2a63d6; }
  .fill-yes { color: #0a8f3c; font-weight: 700; }
  .fill-no  { color: #888; }
  .nodata { color: #b00020; font-weight: 600; font-size: 14px; padding: 6px 0; }
  .chart-wrap { position: relative; height: 320px; }
  .vol-wrap   { position: relative; height: 90px; margin-top: 4px; }
</style>
</head>
<body>
<h1>Pink Sheet Orders — __DATE__</h1>
<p class="sub">1-minute candles (Pacific time). Gray = previous close, orange = 25% level (circles = first touch + re-tests), blue = your order price (triangle = time placed).</p>
<div id="report"></div>

<script>
const DATA = __DATA__;

function el(tag, cls, txt) {
  const e = document.createElement(tag);
  if (cls) e.className = cls;
  if (txt !== undefined) e.textContent = txt;
  return e;
}
function fmt(p) {
  if (p === null || p === undefined) return "-";
  return p >= 1 ? p.toFixed(2) : p.toFixed(4);
}
function flatLine(label, n, y, color, dash) {
  return { type: "line", label: label, data: [{x: 0, y: y}, {x: n - 1, y: y}],
           borderColor: color, borderWidth: 1.5, borderDash: dash || [],
           pointRadius: 0, fill: false };
}

function renderItem(item) {
  const report = document.getElementById("report");
  const entry = el("div", "entry");
  const head = el("div", "entry-head");
  const symDiv = el("div", "entry-symbol");
  const a = document.createElement("a");
  a.href = item.link; a.target = "_blank"; a.textContent = item.symbol;
  symDiv.appendChild(a);
  head.appendChild(symDiv);
  head.appendChild(el("div", "stats", "Prev close $" + fmt(item.prevClose) +
                      " | 25% level $" + fmt(item.studyLevel)));
  entry.appendChild(head);

  // Orders summary
  item.orders.forEach(o => {
    const d = el("div", "ord");
    d.textContent = "Tier " + o.tier + " " + o.broker + ": $" + fmt(o.price) +
                    " (" + o.dropPct + "% drop) at " + o.time + " PT, status " + o.status + " — ";
    const f = el("span", o.wouldFill ? "fill-yes" : "fill-no",
                 o.wouldFill === null ? "fill unknown" : (o.wouldFill ? "price reached (would fill)" : "price not reached"));
    d.appendChild(f);
    entry.appendChild(d);
  });

  // Day stats
  const s = item.stats || {};
  if (s.dayLow !== undefined) {
    const st = el("div", "stats");
    let html = "Day low <b>$" + fmt(s.dayLow) + "</b> (" + s.dayLowDropPct + "% below prev close) at " + s.dayLowTime +
               " | Day high $" + fmt(s.dayHigh) +
               " | Last $" + fmt(s.lastPrice) + " (" + s.lastChangePct + "%)";
    if (s.hit25Time) {
      html += "<br>Hit 25% level at <b>" + s.hit25Time + "</b>, best bounce after that: <b>+" + s.bounceFrom25Pct + "%</b>";
      const rt = s.retests || [];
      if (rt.length) {
        html += "<br>Re-tested 25% level <b>" + rt.length + " time" + (rt.length > 1 ? "s" : "") +
                "</b> (after 3%+ bounces): " + rt.map(r => r.t).join(", ");
      } else {
        html += "<br>No re-tests (never came back to the level after a 3%+ bounce)";
      }
    } else {
      html += "<br>Never reached the 25% level";
    }
    st.innerHTML = html;
    entry.appendChild(st);
  }

  report.appendChild(entry);

  if (item.error || !item.bars.length) {
    entry.appendChild(el("div", "nodata", item.error || "No 1-minute data found"));
    return;
  }

  const bars = item.bars;
  const n = bars.length;
  const labels = bars.map(b => b.t);
  const candles = bars.map((b, i) => ({x: i, o: b.o, h: b.h, l: b.l, c: b.c}));

  const datasets = [{
    label: item.symbol + " 1-min",
    data: candles,
    color:       { up: "#0a8f3c", down: "#c0271b", unchanged: "#888" },
    borderColor: { up: "#0a8f3c", down: "#c0271b", unchanged: "#888" }
  }];
  if (item.prevClose)  datasets.push(flatLine("Prev close", n, item.prevClose, "#888888", [6, 4]));
  if (item.studyLevel) datasets.push(flatLine("25% level", n, item.studyLevel, "#e08a00", [6, 4]));
  // Orange circles: first touch of the 25% level + each re-test
  const st = item.stats || {};
  if (item.studyLevel && st.hit25Idx !== undefined) {
    const touches = [{x: st.hit25Idx, y: item.studyLevel}]
      .concat((st.retests || []).map(r => ({x: r.idx, y: item.studyLevel})));
    datasets.push({ type: "line", label: "25% touches", data: touches, showLine: false,
                    pointStyle: "circle", pointRadius: 5,
                    backgroundColor: "#e08a00", borderColor: "#e08a00" });
  }
  item.orders.forEach(o => {
    if (!o.price) return;
    datasets.push(flatLine("Tier " + o.tier + " $" + fmt(o.price), n, o.price, "#2a63d6", [2, 3]));
    if (o.idx !== null) {
      datasets.push({ type: "line", label: "Placed " + o.time,
                      data: [{x: o.idx, y: o.price}], showLine: false,
                      pointStyle: "triangle", pointRadius: 8,
                      backgroundColor: "#2a63d6", borderColor: "#2a63d6" });
    }
  });

  const priceWrap = el("div", "chart-wrap");
  const priceCanvas = document.createElement("canvas");
  priceWrap.appendChild(priceCanvas);
  entry.appendChild(priceWrap);

  new Chart(priceCanvas.getContext("2d"), {
    type: "candlestick",
    data: { labels: labels, datasets: datasets },
    options: {
      responsive: true, maintainAspectRatio: false, parsing: false, animation: false,
      plugins: { legend: { display: true, labels: { boxWidth: 12, font: { size: 10 } } } },
      scales: {
        x: { type: "category", labels: labels, ticks: { maxRotation: 0, autoSkip: true, font: { size: 9 } } },
        y: { position: "right" }
      }
    }
  });

  const volWrap = el("div", "vol-wrap");
  const volCanvas = document.createElement("canvas");
  volWrap.appendChild(volCanvas);
  entry.appendChild(volWrap);

  new Chart(volCanvas.getContext("2d"), {
    type: "bar",
    data: { labels: labels, datasets: [{ label: "Volume", data: bars.map(b => b.v), backgroundColor: "#5a7fc0" }] },
    options: {
      responsive: true, maintainAspectRatio: false, animation: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { maxRotation: 0, autoSkip: true, font: { size: 9 } } },
        y: { position: "right", ticks: { font: { size: 9 } } }
      }
    }
  });
}

if (!DATA.length) {
  document.getElementById("report").appendChild(el("div", "nodata", "No orders logged for this day."));
}
DATA.forEach(renderItem);
</script>
</body>
</html>
"""


def main():
    trade_date = sys.argv[1] if len(sys.argv) > 1 else datetime.now().strftime("%Y-%m-%d")
    by_symbol = load_orders(trade_date)
    print(f"{len(by_symbol)} symbol(s) with orders on {trade_date}")

    # Dollar stocks only (based on the previous close sent with the order)
    skipped = [s for s, o in by_symbol.items()
               if (o[0]["last_close"] or 0) < MIN_PREV_CLOSE]
    for s in skipped:
        del by_symbol[s]
    if skipped:
        print(f"  skipped (prev close under ${MIN_PREV_CLOSE:.2f}): {', '.join(skipped)}")

    items = []
    for symbol, orders in by_symbol.items():
        print(f"  fetching 1-min bars for {symbol}...")
        items.append(build_item(symbol, orders))

    html = (HTML_TEMPLATE
            .replace("__DATE__", trade_date)
            .replace("__DATA__", json.dumps(items)))
    out_dir = os.path.join(HERE, REPORTS_DIR)
    os.makedirs(out_dir, exist_ok=True)
    out_path = os.path.join(out_dir, f"pink_orders_{trade_date}.html")
    with open(out_path, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"Wrote {out_path}")
    webbrowser.open("file:///" + out_path.replace("\\", "/"))


if __name__ == "__main__":
    main()