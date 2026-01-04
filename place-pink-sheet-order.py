from flask import Flask, request, jsonify
from flask_cors import CORS 
from ib_insync import IB, LimitOrder, Stock, util 
import threading

util.startLoop()

app = Flask(__name__)
CORS(app)

# -------------------------------
# Global IB instance (one per process)
# -------------------------------
ib = IB()
ib_lock = threading.Lock()

IB_HOST = '127.0.0.1'
IB_PORT = 7496        # TWS Paper Trading
IB_CLIENT_ID = 2     # Small, stable client ID


# -------------------------------
# Lazy connection helper
# -------------------------------
def get_ib():
    with ib_lock:
        if not ib.isConnected():
            ib.connect(
                host=IB_HOST,
                port=IB_PORT,
                clientId=IB_CLIENT_ID,
                timeout=5
            )
    return ib


# -------------------------------
# Flask route
# -------------------------------
@app.route('/api/pink-sheet-order', methods=['POST'])
def place_order():
    try:
        ib = get_ib()

        data = request.get_json(force=True)
        symbol = data['symbol'].upper()
        action = data['action'].upper()   # BUY or SELL
        shares = int(data['shares'])
        target_price = float(data['price'])

        if action not in ('BUY', 'SELL'):
            return jsonify({"success": False, "message": "Invalid action"}), 400

        if shares <= 0 or target_price <= 0:
            return jsonify({"success": False, "message": "Invalid shares or price"}), 400

        # --------------------------------
        # Create OTC / Pink Sheet contract
        # --------------------------------
        contract = Stock(
            symbol=symbol,
            exchange='SMART',
            currency='USD',
        )

        qualified_contracts = ib.qualifyContracts(contract)

        if not qualified_contracts:
            print(f"{symbol} cannot be traded")
        else:
            # IB returns the fully-qualified contract, including the proper exchange
            contract = qualified_contracts[0]
            print(f"{symbol} qualified on exchange: {contract.primaryExchange}")



        # --------------------------------
        # Calculate limit prices
        # --------------------------------
        order_prices = [
            round(target_price, 4),
            round(target_price * 0.8, 4),
            round(target_price * 0.6, 4)
        ]

        placed_orders = []

        # --------------------------------
        # Place staggered LIMIT orders only
        # --------------------------------
        for price in order_prices:
            order = LimitOrder(action, shares, price)
            order.tif = 'DAY' 
            order.outsideRth = True 
            trade = ib.placeOrder(contract, order)

            # Wait briefly for IB to acknowledge
            trade.filledEvent += lambda t: None
            ib.sleep(0.25)

            status = trade.orderStatus.status
            filled = trade.orderStatus.filled
            remaining = trade.orderStatus.remaining

            placed_orders.append({
                "symbol": symbol,
                "action": action,
                "shares": shares,
                "limit_price": price,
                "status": status,
                "filled": filled,
                "remaining": remaining
            })

        return jsonify({
            "success": True,
            "orders": placed_orders
        })

    except Exception as e:
        return jsonify({
            "success": False,
            "message": str(e)
        }), 500


# -------------------------------
# Flask startup (NO reloader)
# -------------------------------
if __name__ == '__main__':
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=True,
        use_reloader=False   # 🚨 CRITICAL for IB
    )
