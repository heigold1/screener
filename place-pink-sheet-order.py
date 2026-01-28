# ==========================================================
# SMART ORDER ROUTER: INTERACTIVE BROKERS → E*TRADE FALLBACK
# 3-TIER PRICE LADDER ON BOTH BROKERS
# ==========================================================

from flask import Flask, request, jsonify
from flask_cors import CORS

from ib_insync import IB, LimitOrder, Stock, util

import threading
import queue
import time
import random
import string
import hmac
import hashlib
import base64
import urllib.parse
import requests
import configparser
import xml.etree.ElementTree as ET

# ==========================================================
# FLASK SETUP
# ==========================================================

app = Flask(__name__)
CORS(app)

# ==========================================================
# INTERACTIVE BROKERS SETUP
# ==========================================================

ib = IB()
task_queue = queue.Queue()

IB_HOST = '127.0.0.1'
IB_PORT = 7496
IB_CLIENT_ID = 2

# ==========================================================
# E*TRADE SETUP
# ==========================================================

config = configparser.ConfigParser()
config.read(r"C:\xampp\htdocs\newslookup\etrade.ini")

consumer_key = config["OAuth"]["oauth_consumer_key"]
consumer_secret = config["OAuth"]["consumer_secret"]
access_token = config["OAuth"]["oauth_token"]
access_token_secret = config["OAuth"]["oauth_token_secret"]

ETRADE_ACCOUNT_ID = "S11DfWByF1AJIO-pGBEw-g"

# ==========================================================
# E*TRADE HELPERS
# ==========================================================

def build_oauth_header(url, method):
    oauth_nonce = ''.join(random.choices(string.ascii_letters + string.digits, k=32))
    oauth_timestamp = str(int(time.time()))

    oauth_params = {
        "oauth_consumer_key": consumer_key,
        "oauth_nonce": oauth_nonce,
        "oauth_signature_method": "HMAC-SHA1",
        "oauth_timestamp": oauth_timestamp,
        "oauth_token": access_token,
        "oauth_version": "1.0"
    }

    param_string = "&".join(
        f"{urllib.parse.quote(k, safe='')}={urllib.parse.quote(v, safe='')}"
        for k, v in sorted(oauth_params.items())
    )

    base_string = "&".join([
        method.upper(),
        urllib.parse.quote(url, safe=''),
        urllib.parse.quote(param_string, safe='')
    ])

    signing_key = f"{urllib.parse.quote(consumer_secret)}&{urllib.parse.quote(access_token_secret)}"

    signature = base64.b64encode(
        hmac.new(signing_key.encode(), base_string.encode(), hashlib.sha1).digest()
    ).decode()

    oauth_params["oauth_signature"] = signature

    return "OAuth " + ", ".join(
        f'{urllib.parse.quote(k)}="{urllib.parse.quote(v)}"'
        for k, v in oauth_params.items()
    )


def generate_client_order_id():
    return (str(int(time.time())) +
            ''.join(random.choices(string.ascii_uppercase + string.digits, k=6)))[:20]


def dict_to_xml(tag, data):
    root = ET.Element(tag)
    _fill_xml(root, data)
    return root

def _fill_xml(elem, data):
    if isinstance(data, dict):
        for k, v in data.items():
            if isinstance(v, list):
                for item in v:
                    if isinstance(item, dict):
                        child = ET.SubElement(elem, k)
                        _fill_xml(child, item)
                    else:
                        child = ET.SubElement(elem, k)
                        child.text = str(item)
            elif isinstance(v, dict):
                child = ET.SubElement(elem, k)
                _fill_xml(child, v)
            else:
                child = ET.SubElement(elem, k)
                child.text = str(v)

    elif isinstance(data, list):
        for item in data:
            _fill_xml(elem, item)


# ==========================================================
# E*TRADE ORDER
# ==========================================================

def place_etrade_order(symbol, shares, target_price):
    try:
        prices = [round(target_price, 4)]
        results = []
        any_success = False

        for price in prices:
            print(f">>> E*TRADE attempting {symbol} @ {price}")
            client_order_id = generate_client_order_id()

            # ---------- PREVIEW ----------
            preview_url = f"https://api.etrade.com/v1/accounts/{ETRADE_ACCOUNT_ID}/orders/preview"

            preview_payload = {
                "PreviewOrderRequest": {
                    "orderType": "EQ",
                    "clientOrderId": client_order_id,
                    "Order": [{
                        "allOrNone": "false",
                        "priceType": "LIMIT",
                        "orderTerm": "GOOD_FOR_DAY",
                        "marketSession": "REGULAR",
                        "stopPrice": "",
                        "limitPrice": price,
                        "Instrument": [{
                            "Product": {"securityType": "EQ", "symbol": symbol},
                            "orderAction": "BUY",
                            "quantityType": "QUANTITY",
                            "quantity": shares
                        }]
                    }]
                }
            }

            headers = {
                "Authorization": build_oauth_header(preview_url, "POST"),
                "Content-Type": "application/json"
            }

            preview_response = requests.post(
                preview_url, headers=headers, json=preview_payload, verify=False
            )


            print("=== PREVIEW STATUS ===", preview_response.status_code)
            print(preview_response.text)

            if preview_response.status_code != 200:
                results.append({"price": price, "stage": "preview_http_error", "response": preview_response.text})
                continue

            root = ET.fromstring(preview_response.text)
            preview_id_node = root.find(".//previewId")

            if preview_id_node is None:
                results.append({"price": price, "stage": "preview_parse_error", "response": preview_response.text})
                continue

            preview_id = preview_id_node.text.strip()

            # ---------- PLACE ----------
            place_url = f"https://api.etrade.com/v1/accounts/{ETRADE_ACCOUNT_ID}/orders/place"

            place_payload = {
                "orderType": "EQ",
                "clientOrderId": client_order_id,
                "PreviewIds": {"previewId": preview_id},
                "Order": preview_payload["Order"]
            }

            xml_body = dict_to_xml("PlaceOrderRequest", place_payload)
            xml_string = ET.tostring(xml_body, encoding="utf-8", xml_declaration=True)


            headers = {
                "Authorization": build_oauth_header(place_url, "POST"),
                "Content-Type": "application/xml",
                "Accept": "application/xml"
            }

            place_response = requests.post(
                place_url, headers=headers, data=xml_string, verify=False
            )

            print("=== PLACE STATUS ===", place_response.status_code)
            print(place_response.text)

            success = place_response.status_code == 200 and "<orderId>" in place_response.text

            results.append({
                "price": price,
                "success": success,
                "preview": preview_response.text,
                "place": place_response.text
            })

            if success:
                any_success = True

        return any_success, results

    except Exception as e:
        print(">>> E*TRADE ERROR:", e)
        return False, str(e)

# ==========================================================
# SMART ROUTER
# ==========================================================

def smart_order(symbol, shares, price, ib_result):
    if ib_result.get("any_accepted"):
        return 1, ib_result

    print(">>> SMART ROUTER: IB rejected → routing to E*TRADE")
    success, response = place_etrade_order(symbol, shares, price)

    if success:
        return 2, {"etrade": response}

    return 0, {"etrade_error": response}

# ==========================================================
# IB WORKER THREAD
# ==========================================================

def ib_worker():
    util.startLoop()
    print(">>> IB worker starting...")
    ib.connect(IB_HOST, IB_PORT, clientId=IB_CLIENT_ID, timeout=5)
    print(">>> IB connected")

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
            orders = []
            any_accepted = False
            errors = []

            for price in prices:
                order = LimitOrder(action, shares, price)
                order.tif = 'DAY'
                order.outsideRth = True

                trade = ib.placeOrder(contract, order)
                ib.sleep(1.0)

                status = trade.orderStatus.status
                trade_errors = [log.message for log in trade.log if log.errorCode != 0]

                if status not in ("Cancelled", "Inactive"):
                    any_accepted = True

                orders.append({
                    "price": price,
                    "orderId": trade.order.orderId,
                    "status": status,
                    "errors": trade_errors
                })

                errors.extend(trade_errors)

            result_holder["orders"] = orders
            result_holder["any_accepted"] = any_accepted
            result_holder["errors"] = errors

        except Exception as e:
            result_holder["error"] = str(e)

        task_queue.task_done()

# ==========================================================
# START IB WORKER
# ==========================================================

threading.Thread(target=ib_worker, daemon=True).start()

# ==========================================================
# FLASK ROUTE
# ==========================================================

@app.route('/api/pink-sheet-order', methods=['POST'])
def place_order():
    try:
        data = request.get_json(force=True)

        symbol = data['symbol'].upper()
        action = data['action'].upper()
        shares = int(data['shares'])
        price = float(data['price'])

        result_holder = {}
        task_queue.put((symbol, action, shares, price, result_holder))
        task_queue.join()

        route_code, broker_result = smart_order(symbol, shares, price, result_holder)

        return jsonify({
            "success": route_code != 0,
            "route": route_code,
            "symbol": symbol,
            "result": broker_result
        })

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500

# ==========================================================
# START SERVER
# ==========================================================

if __name__ == '__main__':
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=True,
        threaded=True,
        use_reloader=False
    )
