from flask import Flask, request, jsonify
from flask_cors import CORS
from ib_insync import IB, LimitOrder, Stock, util
import threading
import queue
import time

# -------------------------------
# Flask setup
# -------------------------------
app = Flask(__name__)
CORS(app)

# -------------------------------
# IB worker thread + queue
# -------------------------------
ib = IB()
task_queue = queue.Queue()

IB_HOST = '127.0.0.1'
IB_PORT = 7496
IB_CLIENT_ID = 2

# -------------------------------
# IB worker (ONLY place IB calls here)
# -------------------------------
def ib_worker():
    util.startLoop()

    print(">>> IB worker starting...")
    ib.connect(IB_HOST, IB_PORT, clientId=IB_CLIENT_ID, timeout=5)
    print(">>> IB connected (worker thread)")

    while True:
        task = task_queue.get()
        if task is None:
            break

        symbol, action, shares, target_price, result_holder = task

        try:
            prices = [
                round(target_price, 4),
                round(target_price * 0.8, 4),
                round(target_price * 0.6, 4)
            ]

            contract = Stock(symbol=symbol, exchange='SMART', currency='USD')
            qualified = ib.qualifyContracts(contract)
            if not qualified:
                raise Exception(f"{symbol} not tradable")

            contract = qualified[0]

            print(
                f">>> {symbol} qualified "
                f"| primaryExchange={contract.primaryExchange} "
                f"| conId={contract.conId}"
            )

            orders = []
            for price in prices:
                order = LimitOrder(action, shares, price)
                order.tif = 'DAY'
                order.outsideRth = True

                trade = ib.placeOrder(contract, order)

                print(
                    f">>> Placed {action} {symbol} @ {price} "
                    f"| exch={contract.primaryExchange} "
                    f"| status={trade.orderStatus.status}"
                )

                orders.append({
                    "symbol": symbol,
                    "exchange": contract.primaryExchange,
                    "price": price,
                    "orderId": trade.order.orderId,
                    "status": trade.orderStatus.status
                })

                ib.sleep(0.1)

            result_holder["orders"] = orders

        except Exception as e:
            print(">>> IB WORKER ERROR:", e)
            result_holder["error"] = str(e)

        task_queue.task_done()

# -------------------------------
# Start IB worker thread
# -------------------------------
threading.Thread(target=ib_worker, daemon=True).start()

# -------------------------------
# Flask route
# -------------------------------
@app.route('/api/pink-sheet-order', methods=['POST'])
def place_order():
    print(">>> POST /api/pink-sheet-order ENTERED")

    try:
        data = request.get_json(force=True)

        symbol = data['symbol'].upper()
        action = data['action'].upper()
        shares = int(data['shares'])
        target_price = float(data['price'])

        if action not in ('BUY', 'SELL'):
            return jsonify({"success": False, "message": "Invalid action"}), 400

        result_holder = {}
        task_queue.put((symbol, action, shares, target_price, result_holder))

        # Wait for IB worker to finish
        task_queue.join()

        if "error" in result_holder:
            return jsonify({"success": False, "error": result_holder["error"]}), 500

        return jsonify({
            "success": True,
            "symbol": symbol,
            "orders": result_holder["orders"]
        })

    except Exception as e:
        print(">>> FLASK ERROR:", e)
        return jsonify({"success": False, "error": str(e)}), 500

# -------------------------------
# Startup
# -------------------------------
if __name__ == '__main__':
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=True,
        threaded=True,
        use_reloader=False
    )
