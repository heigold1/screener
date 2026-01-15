<!DOCTYPE html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
	<title>Scanner</title>
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="http://www.datatables.net/rss.xml">
	<link rel="stylesheet" type="text/css" href="./css/main.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
	<style type="text/css" class="init">
	
	</style>

	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.no-icons.min.css" rel="stylesheet">
	<link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/moment.min.js"></script>

	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" class="init">

	var newsLookupWindow; 
	var currentMinute; 
	var lowPrevHash = new Object();
	var lowCurrHash = new Object();
	const MINIMUM_LOW_DIFF = 9; 
	var pinkSheetPreviousClose = []; 
	var pinkSheetExamined = new Set(); 
	var pinkSheetOrderPlaced = new Set(); 
	var pinkSheetOrderNotPlaced = {}; 
	var pinkSheetOrderFailed = {}; 

	const pinkSheetQueue = [];
	const pinkSheetInProgress = new Set();
	let ohlcTokens = 5;

	var pinkSheetOrderPlacedMP3 = new Audio('./wav/pink-sheet-order-placed.mp3'); 
	var pinkSheetOrderFailedMP3 = new Audio('./wav/pink-sheet-order-failed.mp3'); 


	function openNewsLookupWindow(link){
		newsLookupWindow = window.open(link, "newslookup-window"); 
	}

	function closeModalWindow(){
		console.log("inside closeModal");
		var modal = document.getElementById('myModal');
		modal.style.display = "none";
	}

	function createOrderStub(symbol, price, percentage)
	{

		var prevClose = price/(1-(percentage/100)); 

		if (prevClose > 1.00)
		{
			prevClose = prevClose.toFixed(2);
		}
		else
		{
			prevClose = prevClose.toFixed(4);
		}

		if (percentage < 84.00)
		{
			percentage = 84.00; 
			price = prevClose*(1-(percentage/100));
		}

		percentage = parseFloat(percentage).toFixed(2);


		if (price > 1.00)
		{
// 			price = price + 0.01; 
			price = price.toFixed(2);
		}
		else
		{
// 			price = price + 0.0001; 
			price = price.toFixed(4);
		}

		var rawNumShares = 100/price;
		var numShares = parseInt(Math.ceil(rawNumShares/100)*100);
		if (numShares > 500000)
		{
			numShares = 500000;
		}
		var orderStub = symbol + " BUY " + numShares + " $" + price + " (" + percentage + "%) -- $" + prevClose + " CIK_NOT_FOUND"; 
		return orderStub; 
	}

	function checkPinkSheetChange(symbol, last, callback) {
	    // If we already have the previous close cached
	    if (symbol in pinkSheetPreviousClose) {
	        const prevClose = pinkSheetPreviousClose[symbol];
	        const change = ((prevClose - last) / prevClose) * 100;
	        callback(change); // call the callback immediately
	    } else {
	        // Fetch previous close from server
	        $.ajax({
	            url: 'http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/pink-sheet-prev-close.php?',
	            data: { symbol: symbol },
	            async: true,
	            dataType: 'html',
	            success: function(data) {
	                try {
	                    const responseData = JSON.parse(data);
	                    const prevClose = parseFloat(responseData.prevClose);

	                    // Cache it
	                    pinkSheetPreviousClose[symbol] = prevClose;

	                    // Calculate change
	                    const change = ((prevClose - last) / prevClose) * 100;

	                    // Call the callback with both change and prevClose
	                    callback(change);
	                } catch (err) {
	                    console.error("Failed to parse prevClose for", symbol, err);
	                    callback(null); // signal failure
	                }
	            },
	            error: function(xhr, ajaxOptions, thrownError) {
	                console.error("Error fetching prevClose for", symbol, thrownError);
	                console.log("ERROR in preparing order for " + symbol + ", message is " + xhr.statusText);
	                callback(null); // signal failure
	            }
	        });
	    }
	}


	// unlike the other prepareImpulseBuy, we are building the order string from within the function because 
	// we have to grab the previous close via API. 

	function prepareImpulseBuyPink(symbol)
	{
 
		var orerStub = ""; 
 
    	$.ajax({
	        url: 'http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/prepare-order.php?',
        	data: {symbol: symbol, 
                   amount: "100", 
                   percentage: "84"},
        	async: true, 
        	dataType: 'html',
        	success:  function (data) {

        	var responseData = JSON.parse(data); 

        	var orderStub = responseData.orderStub; 
        	var change = responseData.change; 
        	var backgroundColor = ""; 

        	if (change > 50)
        	{
        		backgroundColor = "background-color: lightgreen;"; 
        	}
        	else
        	{
        		backgroundColor = " background-color: pink; "
        	}

			var modal = document.getElementById('myModal');
			$("div#myModal").html(
				" <img style='max-width:100%; max-height:100%;' src='https://api.wsj.net/api/kaavio/charts/big.chart?nosettings=1&symb=US:" + symbol + "&uf=0&type=2&size=2&style=320&freq=1&entitlementtoken=0c33378313484ba9b46b8e24ded87dd6&time=4&rand=" + Math.random() + "&compidx=&ma=0&maval=9&lf=1&lf2=0&lf3=0&height=335&width=579&mocktick=1'></a><br><br><span style='font-size:30px;" + backgroundColor + "'>" 

				+ orderStub + "</span><br><br><span style='font-size:30px;" + backgroundColor + "'>Change is " + change + "</span><button class='closeModal' style='font-size: 35px !important; height: 45px; display: inline-block; ' onclick='closeModalWindow();'>Close</button>" + 

				"&nbsp;&nbsp;<input type='text' class='impulseBuyText' style='color: black; font-size: 35px !important; height: 45px; width: 75px; display: inline-block; '  onclick='console.log($(this)); copyToClipboard($(this)); ' value='" + orderStub + "' readonly>"  +
    			"<br><br><br><button class='placeOrderButton' style='font-size: 20px; padding: 10px;' onclick='placeEtradeOrder(\"" + orderStub + "\")'>Place E*TRADE Order</button>"

				);

        	modal.style.display = "block";

        	},
        	error: function (xhr, ajaxOptions, thrownError) {
          	console.log("there was an error in calling prepare-order.php");
          	alert("ERROR in preparing order for " + symbol + ".");
        	}
    	});

	}

	function prepareImpulseBuy(symbol, orderStub)
	{
		var modal = document.getElementById('myModal');
		$("div#myModal").html(
			" <img style='max-width:100%; max-height:100%;' src='https://api.wsj.net/api/kaavio/charts/big.chart?nosettings=1&symb=US:" + symbol + "&uf=0&type=2&size=2&style=320&freq=1&entitlementtoken=0c33378313484ba9b46b8e24ded87dd6&time=4&rand=" + Math.random() + "&compidx=&ma=0&maval=9&lf=1&lf2=0&lf3=0&height=335&width=579&mocktick=1'></a><br><br><span style='font-size:30px;'>" 

			+ orderStub + "</span><br><br><button class='closeModal' style='font-size: 35px !important; height: 45px; display: inline-block; ' onclick='closeModalWindow();'>Close</button><input id=symbol-text></input>");

		$("#symbol-text").val(orderStub); 



  		var copyTextarea = $("#symbol-text");
  			copyTextarea.select();
  			try {
    			var successful = document.execCommand('copy');
    			var msg = successful ? 'successful' : 'unsuccessful';
  			} catch (err) {
    			console.log('Oops, unable to copy');
  			}

        modal.style.display = "block";
	}

	// For stocks ending in ".RT" we impulse buy them at 84% down from previous closing price.
	// Also, we are currently using $250 per trade for this. 

	function createOrderStubRT(symbol, price, percentage)
	{
		var prevClose = price/(1 - percentage/100); 

		var newPrice = prevClose - (prevClose*0.84); 

		if (newPrice > 1.00)
		{
			newPrice = newPrice.toFixed(2);
		}
		else
		{
			newPrice = newPrice.toFixed(4);
		}

		var numShares = 250/newPrice; 
		numShares = Math.round(numShares/100)*100; 

		// There is some kind of regulation that states that you can't place an order for more than 
		// 118,500 shares. 
		if (numShares > 118500) 
		{
			numShares = 118500; 
		}

		var orderStub = symbol + " BUY " + numShares + " $" + newPrice + " (84.00%)"; 

		return orderStub; 

	}

	// For fast drop stocks I'm going to try testing buying them when I see them, provided they drop past 
	// the usual 18-20% level

	function createOrderStubFastDrop(symbol, price, percentage)
	{
		var rawNumShares = 150/price;
		var numShares = 0; 
		percentage = percentage.toFixed(2);

		if (price > 1.00)
		{
			price = price + 0.01; 
			price = price.toFixed(2);
			numShares = parseInt(Math.ceil(rawNumShares/10)*10);
		}
		else
		{
			price = price + 0.0001; 
			price = price.toFixed(4);
			numShares = parseInt(Math.ceil(rawNumShares/100)*100);
		}

		if (numShares > 500000)
		{
			numShares = 500000;
		}
		var orderStub = symbol + " BUY " + numShares + " $" + price + " (" + percentage + "%)"; 
		return orderStub; 
	}


function getYMDTradeDate(daysBack) {
    const today = new Date();
    today.setDate(today.getDate() - daysBack); // subtract daysBack days
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0'); // months are 0-based
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}



function fetchOHLCJson(symbol, callback) {
    var xhr = new XMLHttpRequest();
    var url = "http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/marketstack-api-historical-data.php?symbol=" + encodeURIComponent(symbol);

console.log("inside fetchOHLCJson, the url is:" + url); 

    xhr.open("GET", url, true); // async
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var json = JSON.parse(xhr.responseText);
console.log("inside fetchOHLCJson, json parsed ok"); 
                    callback(json);
                } catch (e) {
                    callback({ error: true });
                }
            } else {
                callback({ error: true });
            }
        }
    };
    xhr.send();
}


/* -------------------------------
   Helper function to calculate median
------------------------------- */
function median(arr) {
    if (!arr.length) return 0;
    const sorted = [...arr].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    return sorted.length % 2 !== 0
        ? sorted[mid]
        : (sorted[mid - 1] + sorted[mid]) / 2;
}

function examinePinkSheet(symbol) {

    /* -------------------------------
       CONFIG
    ------------------------------- */
    const LOOKBACK_DAYS = 10;
    const DATE_BUFFER_DAYS = 4;
    const MIN_NONZERO_VOLUME_DAYS = 4;
    const MIN_MEDIAN_VOLATILITY = 0.08;

    // NEW: Recent volume check
    const RECENT_DAYS_TO_CHECK = 2;      // last 2 days
    const MIN_VOLUME_RECENT = 200000;    // minimum volume per recent day

    // NEW: Price variance threshold for pinning detection
    const MIN_PRICE_VARIANCE = 0.005;    // 0.5% price movement

    /* -------------------------------
       DATE WINDOW
    ------------------------------- */
    const fromDate = getYMDTradeDate(LOOKBACK_DAYS + DATE_BUFFER_DAYS);
    const toDate = getYMDTradeDate(1);
    let modifiedSymbol = symbol; 

    if (symbol.length === 5) {
        modifiedSymbol = symbol.slice(0, 4);
    }

    try {
        fetchOHLCJson(modifiedSymbol, function(json){


console.log("just came back from fetchOHLCJson, the json is: ");
console.log(json); 


    console.log("RAW JSON RETURNED:", json);
if (json?.error) {
    console.warn("Fetch error");
    return;
}

if (!Array.isArray(json.ohlc) || json.ohlc.length === 0) {
    console.warn("No OHLC data returned for", symbol);
    pinkSheetExamined.add(symbol); 
    pinkSheetInProgress.delete(symbol);
    pinkSheetOrderNotPlaced[symbol] = "No OHLC data";
    return;
}


console.log("SYMBOL SENT:", modifiedSymbol);


			const recent = json.ohlc.slice(0, LOOKBACK_DAYS + DATE_BUFFER_DAYS);


            /* -------------------------------
               METRIC COLLECTION
            ------------------------------- */
            const volumes = [];
            const volatility = [];
            const closePrices = [];
            let nonZeroDays = 0;

console.log("RECENT RAW:", recent);


            for (const day of recent) {
                if (!('h' in day && 'l' in day && 'c' in day && 'v' in day))
                {
console.log("(!('high' in day && 'low' in day && 'close' in day && 'volume' in day)) failed "); 
                	continue;	
                } 
console.log("DAY OBJ:", day);

                const close = parseFloat(day.c);
                const high = parseFloat(day.h);
                const low = parseFloat(day.l);
				const vol = Number(day.v);

				if (Number.isFinite(vol) && vol > 0) {
    				nonZeroDays++;
    				volumes.push(vol);
				}

                if (close > 0) volatility.push((high - low) / close);

                closePrices.push(close);
            }

console.log(symbol, "nonZeroDays:", nonZeroDays, "volumes:", volumes);


            /* -------------------------------
               VALIDATION CHECKS
            ------------------------------- */
            if (nonZeroDays < MIN_NONZERO_VOLUME_DAYS) {
                pinkSheetExamined.add(symbol); 
                pinkSheetInProgress.delete(symbol);
                pinkSheetOrderNotPlaced[symbol] = "nonZeroDays < MIN_NONZERO_VOLUME_DAYS"; 
                return false;
            }

            if (median(volatility) < MIN_MEDIAN_VOLATILITY) {
                pinkSheetExamined.add(symbol); 
                pinkSheetInProgress.delete(symbol);
                pinkSheetOrderNotPlaced[symbol] = "median(volatility) < MIN_MEDIAN_VOLATILITY"; 
                return false;
            }

            /* -------------------------------
               IMPROVED PRICE PINNING CHECK
               - Only flag if recent variance < threshold AND median volume < min
            ------------------------------- */
            const recentCloses = closePrices.slice(0, RECENT_DAYS_TO_CHECK);
            const maxClose = Math.max(...recentCloses);
            const minClose = Math.min(...recentCloses);
            const priceVariance = (maxClose - minClose) / maxClose;

            if (priceVariance < MIN_PRICE_VARIANCE && median(volumes) < MIN_VOLUME_RECENT) {
                pinkSheetExamined.add(symbol); 
                pinkSheetInProgress.delete(symbol);
                pinkSheetOrderNotPlaced[symbol] = "Price pinned (very low variance + low volume)";
                return false;
            }

            /* -------------------------------
               NEW: RECENT DAYS VOLUME CHECK
            ------------------------------- */
            const recentVolumes = volumes.slice(0, RECENT_DAYS_TO_CHECK);
            const recentVolumePass = recentVolumes.every(v => v >= MIN_VOLUME_RECENT);

            if (!recentVolumePass) {
                pinkSheetExamined.add(symbol);
                pinkSheetInProgress.delete(symbol);
                pinkSheetOrderNotPlaced[symbol] = `Recent ${RECENT_DAYS_TO_CHECK} days volume too low`;
                return false;
            }

            /* -------------------------------
               SEND ORDER TO PYTHON
            ------------------------------- */
            const lastClose = pinkSheetPreviousClose[symbol]; 
            if (!lastClose) {
                console.warn(`No previous close stored for ${symbol}, cannot calculate 85% drop target`);
                pinkSheetExamined.add(symbol); 
                pinkSheetInProgress.delete(symbol);
                pinkSheetOrderNotPlaced[symbol] = "No previous close available";
                return false;
            }

            pinkSheetExamined.add(symbol); 
            pinkSheetInProgress.delete(symbol);

            const targetPrice = parseFloat((lastClose * (1 - 0.85)).toFixed(4));
            let numShares = Math.floor(50 / targetPrice / 100) * 100;
            if (numShares < 100) numShares = 100;

            const orderData = { symbol, action: "BUY", shares: numShares, price: targetPrice };

            fetch('http://localhost:5000/api/pink-sheet-order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    pinkSheetOrderPlaced[symbol] = "Order placed successfully";
                    pinkSheetOrderPlacedMP3.play();
                } else {
                    pinkSheetOrderFailed[symbol] = data.message || "Order rejected";
                    pinkSheetOrderFailedMP3.play();
                }
            })
            .catch(err => {
                pinkSheetOrderFailed[symbol] = err.message || "Fetch error";
                pinkSheetOrderFailedMP3.play();
            });

        }); // end fetchOHLCJson callback
    } catch (e) {
        console.error("OHLC fetch failed hard:", e);
        pinkSheetExamined.add(symbol);
        pinkSheetInProgress.delete(symbol);
        pinkSheetOrderNotPlaced[symbol] = "Callback exception";
    }

}





setInterval(() => {
    ohlcTokens = 5;
    processOHLCQueue();
}, 1000);

function enqueuePinkSheet(symbol) {
    if (pinkSheetQueue.includes(symbol)) return;
    if (pinkSheetInProgress.has(symbol)) return;   // 🚨 prevent duplicates
    if (pinkSheetExamined.has(symbol)) return;

    pinkSheetQueue.push(symbol);
    processOHLCQueue();
}


function processOHLCQueue() {
    while (ohlcTokens > 0 && pinkSheetQueue.length > 0) {
        const symbol = pinkSheetQueue.shift();
        ohlcTokens--;

        pinkSheetInProgress.add(symbol);

        examinePinkSheet(symbol);
    }
}

	function copyToClipboard(object){
  		object.select();
  		try {
    		var successful = document.execCommand('copy');
    		var msg = successful ? 'successful' : 'unsuccessful';
    		console.log('Copying text command was ' + msg);
  		} catch (err) {
    		console.log('Oops, unable to copy');
  		}
	}

	/** when the user either clicks on the "X" or the symbol text box of a NASDAQ row **/ 
	function removeNasdaq(object)
	{
		var row = object.closest('tr'); 
		var data = row.children();
		var symbol = $(data[0]).children(0).val()

		var nasdaqTable = $('#nasdaq').DataTable();
 			nasdaqTable.row( row ).remove();
 			nasdaqTable.draw();


		var tableNasdaqList = $('#nasdaq-list').DataTable();

		var today = new Date(); 
		var hours = today.getHours(); 
		var minutes = today.getMinutes();
		if (minutes < 10)
		{
			minutes = "0" + minutes; 
		}

		tableNasdaqList.row.add([
			symbol, 
			((hours + 24)%12 || 12) + ":" + minutes + " " + ((hours >= 12) ? "PM" : "AM"), 
			'<input type="text" class="newsText">',
			'<input type="checkbox" class="list-check" id="chk-' + symbol + '">',
			'', 
			"<div class='nasdaq-list'><i class='icon-remove'></i></div>"
			] ); 


		tableNasdaqList.draw();
	}

	/** when the user either clicks on the "X" or the symbol text box of a PINK row **/ 
	function removePink(object)
	{
			var row = object.closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).children(0).val()

			var pinkTable = $('#pink').DataTable();
     			pinkTable.row( row ).remove();
     			pinkTable.draw();

			var tablePinkList = $('#pink-list').DataTable();

			var today = new Date(); 
			var hours = today.getHours(); 
			var minutes = today.getMinutes();
			if (minutes < 10)
			{
				minutes = "0" + minutes; 
			}

			tablePinkList.row.add([
				symbol, 
				((hours + 24)%12 || 12) + ":" + minutes + " " + ((hours >= 12) ? "PM" : "AM"), 
				'<input type="text" class="newsText">',
				'<input type="checkbox" class="list-check" id="chk-' + symbol + '">',
				'',
				"<div class='pink-list'><i class='icon-remove'></i></div>"
    			] ); 

			tablePinkList.draw();
	}

	function removeNyseAmex(object)
	{
			var row = object.closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).children(0).val()

			var nasdaqTable = $('#nyse-amex').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();

			var tableNYSEAmexList = $('#nyse-amex-list').DataTable();

			var today = new Date(); 
			var hours = today.getHours(); 
			var minutes = today.getMinutes();
			if (minutes < 10)
			{
				minutes = "0" + minutes; 
			}

			tableNYSEAmexList.row.add([
				symbol, 
				((hours + 24)%12 || 12) + ":" + minutes + " " + ((hours >= 12) ? "PM" : "AM"), 
				'<input type="text" class="newsText">',
				'<input type="checkbox" class="list-check" id="chk-' + symbol + '">',
				'',
				"<div class='nyse-amex-list'><i class='icon-remove'></i></div>"
    			] ); 

			tableNYSEAmexList.draw();

	}


	function placeEtradeOrder(orderStub) {
	    // Parse the orderStub string (e.g., "BHILQ BUY 10300 $0.0241 (84%)")
	    var parts = orderStub.match(/(\w+)\s+BUY\s+(\d+)\s+\$(\d+\.\d+)/);

	    if (parts) {
        	var symbol = parts[1]; // Extract the symbol (e.g., "BHILQ")
        	var shares = parts[2];  // Extract the number of shares (e.g., "10300")
        	var price = parts[3];   // Extract the price (e.g., "0.0241")

        	// Send AJAX request to PHP file
        	$.ajax({
	            url: 'etrade-order-chat-gpt.php', // Your PHP file URL
            	type: 'POST',
            	data: {
	                symbol: symbol,
                	shares: shares,
                	price: price
            	},
            	success: function(response) {
	                console.log("Order placed successfully:", response);
                	alert("Order placed successfully!");
            	},
            	error: function(xhr, status, error) {
                	console.error("Error placing order:", error);
                	alert("Failed to place order.");
            	}
        	});
    	} else {
	        alert("Invalid order format.");
    	}
	}

	$(document).ready(function() {

	
		alert("\n - ****************************************" + 
			  "\n - ****************** PRAY ****************" + 
			  "\n - ****************************************" + 

			"\n - TAKE YOUR LUMBROKINASE" + 

			"\n\n Do you need to make any more care packages?" + 

			"\n\n - Check Jay's days off" + 

			"\n\n - Make sure your speaker is not too loud, turn it down " + 

			"\n\n - Check the $yesterdayDays variable and make sure it's right.  CHECK IT ON THE ACTUAL SERVER" + 

			"\n\n - Unmute the websites" + 

			"\n\n - Reset the database" + 

			"\n\n - Run the Corporate Actions data structure, REFRESH the corporate actions page. " + 

			"\n\n - If it dropped significantly the previous day (i.e. 35-40% and beyond), you need to see how it RECOVERED before making any decisions" + 

			"\n\n - GO OVER THE NOTES ON INDEX.PHP" + 

			"\n\n - Run NetBeans" + 

			"\n\n - Run PyCharm" + 

			"\n\n - Check lockup expiration dates " + 

			"\n\n - Check https://www.otcmarkets.com/market-activity/corporate-actions for any symbols that are going to the Pink Sheets for the first day, you can tell by checking big charts.  Put in your entry at 85%" +  
			"\n\n - Check the halts page for any company halted which might be being bought out" + 
			"\n\n - CHECK THE PINK SHEETS FOR RECENTLY DELISTED STOCKS" + 
			"\n\n - Go over the index.php notes"); 


// TO DO list - 
// 1) Make a Javascript reminder alert if there is a "to report" or "to highlight" in the news. 
// 2) Create the Javascript OHLC chart to replace the bigcharts 30-day chart. 


		var checkLockupDates = new Audio('./wav/check-lockup-dates.wav');

		checkLockupDates.play(); 

		currentMinute = moment().minutes(); 

		//PINK List
		var tablePink = $('#pink').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {

				var symbol = $.trim($(data[0]).val());
	        	var change = data[3];
	        	var volumeString = data[4];
        		var volume = parseFloat(volumeString.replace(/,/g, '')); 
				var last = data[1];
				var totalValue = volume*last; 

         		$(row).addClass('allRows');

				$('td', row).eq(1).addClass('innerTD');
				$('td', row).eq(2).addClass('innerTD');
				$('td', row).eq(3).addClass('innerTD');
				$('td', row).eq(4).addClass('innerTD');

				if (
				    (
				        (
				            ((change > parseFloat($("#pink-penny").val())) && (last < 1.00)) || 
				            ((change > parseFloat($("#pink-dollar").val())) && (last >= 1.00))
				        ) 
				        && (totalValue > 500)
				    ) 
				)
				{
					if (last < 1.00)
					{
         				$(row).addClass('yellowClass');		
					}
					else
					{
         				$(row).addClass('lightBlueClass');
					}

					if (pinkSheetOrderPlaced.has(symbol))
					{
    					$('td', row).eq(0).addClass('greenClass');
					}
					
					if (pinkSheetOrderNotPlaced[symbol])
					{
    					$('td', row).eq(0).addClass('pinkClass');
					}

					if (pinkSheetOrderFailed[symbol])
					{
    					$('td', row).eq(0).addClass('lightRedClass');
					}

         		}


         	} 
		});

		tablePink.on('draw', function() {
			console.log("The pinkSheetOrderNotPlaced array is currently:");
			console.log(pinkSheetOrderNotPlaced); 
			console.log("The pinkSheetOrderFailed array is currently:");
			console.log(pinkSheetOrderFailed); 
		}); 

		// NASDAQ
		var tableNasdaq = $('#nasdaq').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {

	        	var symbol = $.trim($(data[0]).val());
	        	var change = data[3];
	        	var last = data[1];
	        	var low = data[2];
	        	var lowPercent = data[4];
				var volumeString = data[5];
				var volume = parseInt(volumeString.replace(/,/g, ''));
				var volumeRatio = parseFloat(data[6]);
				var differenceInLow =  lowCurrHash[symbol] - lowPrevHash[symbol]; 

         		if ((symbol in lowPrevHash) && (differenceInLow > MINIMUM_LOW_DIFF))
				{
					var differenceInLow =  lowCurrHash[symbol] - lowPrevHash[symbol]; 
					if (differenceInLow > MINIMUM_LOW_DIFF)
					{
						$(row).addClass('orangeClass');            			
					}
				}
            	else if (((last > 1.00) && (change > parseFloat($("#nas-nyse-dollar").val()))) || 
            		((last < 1.00) && (change > parseFloat($("#nas-nyse-penny").val()))))
            	{

            		if (last < 1.00)
            		{
						$(row).addClass('yellowClass');
            		}
            		else if (volume < 20000)
            		{
         				$(row).addClass('darkBlueClass');            			
            		}
            		else 
            		{
         				$(row).addClass('lightBlueClass');            			
            		}
         		}


         		$(row).addClass('allRows');

				$('td', row).eq(1).addClass('innerTD');
				$('td', row).eq(2).addClass('innerTD');
				$('td', row).eq(3).addClass('innerTD');
				$('td', row).eq(4).addClass('innerTD');
				$('td', row).eq(5).addClass('innerTD');
				$('td', row).eq(6).addClass('innerTD');

            	if (((low > 1.00) && (lowPercent > parseFloat($("#nas-nyse-dollar").val()))) || 
            		((low < 1.00) && (lowPercent > parseFloat($("#nas-nyse-penny").val()))))
            	{
					$('td', row).eq(4).addClass('lightBrownClass');
            	}

	         	if (volume > 100000)
         		{
 					$('td', row).eq(5).addClass('blackClass');
         		}
	         	else if (volume > 70000)
         		{
 					$('td', row).eq(5).addClass('darkGreyClass');
         		}
         		else if (volume > 35000)
         		{
 					$('td', row).eq(5).addClass('lightGreyClass');
         		}

         		if (volumeRatio > 0.17)
         		{
					$('td', row).eq(6).addClass('redClass');	
         		}
         		else if (volumeRatio > 0.1)
         		{
					$('td', row).eq(6).addClass('lightRedClass');	
         		}
         	}
		} );

		//NYSE
		var tableNYSEAmex = $('#nyse-amex').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {

				var symbol = $.trim($(data[0]).val());
	        	var change = data[3];
	        	var last = data[1];
				var low = data[2];
	        	var lowPercent = data[4];
				var volumeString = data[5];
				var volume = parseInt(volumeString.replace(/,/g, ''))
				var volumeRatio = parseFloat(data[6]);
				var differenceInLow =  lowCurrHash[symbol] - lowPrevHash[symbol]; 

         		if ((symbol in lowPrevHash) && (differenceInLow > MINIMUM_LOW_DIFF))
				{
					var differenceInLow =  lowCurrHash[symbol] - lowPrevHash[symbol]; 
					if (differenceInLow > MINIMUM_LOW_DIFF)
					{
						$(row).addClass('orangeClass');            			
					}
				}
            	else if (((last > 1.00) && (change > parseFloat($("#nas-nyse-dollar").val()))) || 
            		((last < 1.00) && (change > parseFloat($("#nas-nyse-penny").val()))))
            	{
            		if (last < 1.00)
            		{
						$(row).addClass('yellowClass');
            		}
            		else if (volume < 20000)
            		{
         				$(row).addClass('darkBlueClass');            			
            		}
            		else 
            		{
         				$(row).addClass('lightBlueClass');            			
            		}
         		}

         		$(row).addClass('allRows');

				$('td', row).eq(1).addClass('innerTD');
				$('td', row).eq(2).addClass('innerTD');
				$('td', row).eq(3).addClass('innerTD');
				$('td', row).eq(4).addClass('innerTD');
				$('td', row).eq(5).addClass('innerTD');
				$('td', row).eq(6).addClass('innerTD');

				// if the low is under the radar, turn the cell brown
            	if (((low > 1.00) && (lowPercent > parseFloat($("#nas-nyse-dollar").val()))) || 
            		((low < 1.00) && (lowPercent > parseFloat($("#nas-nyse-penny").val()))))
            	{
					$('td', row).eq(4).addClass('lightBrownClass');
            	}


	         	if (volume > 100000)
         		{
 					$('td', row).eq(5).addClass('blackClass');
         		}
	         	else if (volume > 70000)
         		{
 					$('td', row).eq(5).addClass('darkGreyClass');
         		}
         		else if (volume > 35000)
         		{
 					$('td', row).eq(5).addClass('lightGreyClass');
         		}
         		
         		if (volumeRatio > 0.17)
         		{
					$('td', row).eq(6).addClass('redClass');	
         		}
         		else if (volumeRatio > 0.1)
         		{
					$('td', row).eq(6).addClass('lightRedClass');	
         		}

         	} 
		});

		//PINK List
		var tablePinkList = $('#pink-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
			"createdRow": function( row, data, dataIndex ) {
				$(row).addClass('allRows');
				$(row).addClass('trList'); 
				$(row).attr('id', 'tr-list-' + data[0]); 
			}
		});

		// Nasdaq List
		var tableNasdaqList = $('#nasdaq-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
			"createdRow": function( row, data, dataIndex ) {
				$(row).addClass('allRows');
				$(row).addClass('trList'); 
				$(row).attr('id', 'tr-list-' + data[0]); 
			}
		});


		var tableNYSEAmexList = $('#nyse-amex-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
			"createdRow": function( row, data, dataIndex ) {
				$(row).addClass('allRows');
				$(row).addClass('trList'); 
				$(row).attr('id', 'tr-list-' + data[0]); 
			}
		});

		function iterateThroughEarningsSymbols(value, index, array) {
  			var checkbox = $("#chk-" + value);
  			checkbox.prop("checked", true);
  			var row = checkbox.closest('tr');
  			row.removeClass('whiteClass');
  			row.addClass('orangeClass');
		}

		$(document).on('click', '#check-earnings', function (evt) {
		    $.ajax({
		        url: 'http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/get-earnings-stocks.php',
		        async: true, 
		        dataType: 'json',
		        success:  function (data) {
					data.forEach(iterateThroughEarningsSymbols); 
		        },
		        error: function (xhr, ajaxOptions, thrownError) {
		          console.log("there was an error in calling save-earnings-stocks.php");
		          alert("ERROR in grabbing the earnings symbols file.");
		        }

		    });

		});

		$(document).on('click', '.list-check', function (evt) {
			if($(this).prop("checked") == true){
				$(this).closest('tr').removeClass('whiteClass');
				$(this).closest('tr').addClass('orangeClass');
			}
			else
			{
				$(this).closest('tr').removeClass('orangeClass');
				$(this).closest('tr').addClass('whiteClass');

			}
		});


		/** Clicking on the "X" of a nasdaq row **/
		$(document).on('click', '.nasdaq', function (evt) {
			evt.stopPropagation();
			evt.preventDefault();
			removeNasdaq($(this));
		});


		/** Clicking on the "X" of a pink row **/
		$(document).on('click', '.pink', function (evt) {
			evt.stopPropagation();
			evt.preventDefault();
			// removePink($(this));
		});

		/** Clicking on the "X" of a nyse-amex row **/
		$(document).on('click', '.nyse-amex', function (evt) {
			evt.stopPropagation();
			evt.preventDefault();
			removeNyseAmex($(this));
		});

		$(document).on('click', '.nasdaq-list', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();

			var nasdaqListTable = $('#nasdaq-list').DataTable();
     			nasdaqListTable.row( row ).remove();
     			nasdaqListTable.draw();
     	});

		$("#btnManualAddNasdaq").click(function(){

			var today = new Date(); 
			var hours = today.getHours(); 
			var minutes = today.getMinutes();
			if (minutes < 10) 
			{
				minutes = "0" + minutes; 
			}

			tableNasdaqList.row.add([
			$.trim($("#manualAddNasdaq").val()), 
			((hours + 24)%12 || 12) + ":" + minutes + " " + ((hours >= 12) ? "PM" : "AM"), 
			'<input type="text" class="newsText">',
			'<input type="checkbox" checked>',
			' ', 
			"<div class='nasdaq-list'><i class='icon-remove'></i></div>"
			] ); 

			$("#manualAddNasdaq").val("");

			tableNasdaqList.draw();
		}); 


		$(document).on('click', '.pink-list', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();

			var nasdaqTable = $('#pink-list').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();
     	});

		$(document).on('click', '.nyse-amex-list', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text()

			evt.stopPropagation();
			evt.preventDefault();

			var nyseAmexListTable = $('#nyse-amex-list').DataTable();
     			nyseAmexListTable.row( row ).remove();
     			nyseAmexListTable.draw();
     	}); 

		$(document).on('click', '.innerTD', function(evt){
			var vixNumber = $("#vixNumber").html();
			var row = $(this).closest('tr'); 
			var data = row.find('.symbolText');
			var symbol = data[0].value;
			var checkSec = $('#check-sec').is(":checked")?"1":"0"; 

			copyToClipboard(data);

			openNewsLookupWindow("http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/index.php?symbol=" + symbol + "&vix=" + vixNumber  + "&check-sec=" + checkSec); 

		});



		$("#btnManualAddNyseAmex").click(function(){

			var today = new Date(); 
			var hours = today.getHours(); 
			var minutes = today.getMinutes();
			if (minutes < 10) 
			{
				minutes = "0" + minutes; 
			}

			tableNYSEAmexList.row.add([
			$.trim($("#manualAddNyseAmex").val()), 
			((hours + 24)%12 || 12) + ":" + minutes + " " + ((hours >= 12) ? "PM" : "AM"), 
			'<input type="text" class="newsText">',
			'<input type="checkbox" checked>',
			'',
			"<div class='nyse-amex-list'><i class='icon-remove'></i></div>"
			] ); 

			$("#manualAddNyseAmex").val("");

			tableNYSEAmexList.draw();
		}); 

		$("#btnManualAddPink").click(function(){

			var today = new Date(); 
			var hours = today.getHours(); 
			var minutes = today.getMinutes();
			if (minutes < 10) 
			{
				minutes = "0" + minutes; 
			}

			tablePinkList.row.add([
			$.trim($("#manualAddPink").val()), 
			((hours + 24)%12 || 12) + ":" + minutes + " " + ((hours >= 12) ? "PM" : "AM"), 
			'<input type="text" class="newsText">',
			'<input type="checkbox" checked>',
			'',
			"<div class='pink-list'><i class='icon-remove'></i></div>"
			] ); 

			$("#manualAddPink").val("");

			tablePinkList.draw();
		}); 


		$( "#clear-tables" ).click(function() {
			var tableNasdaq = $('#nasdaq').DataTable();
 
			tableNasdaq
    			.clear()
    			.draw();

			var tableNYSE = $('#nyse-amex').DataTable();
 
			tableNYSE
    			.clear()
    			.draw();

			var tablePink = $('#pink').DataTable();
 
			tablePink
    			.clear()
    			.draw();


    		addRows();

		});

 		countdown();	

	});

	var globalData = "";
	var prevGlobalData = "";

	function addRows(){

		// every minute set the new lows 
		var nowMinute = moment().minutes();
		if (currentMinute != nowMinute)
		{
			currentMinute = nowMinute; 
			for (const [key, value] of Object.entries(lowCurrHash))
			{
				lowPrevHash[key] = lowCurrHash[key];
			}
		}

		$.get('http://localhost/screener/percent-decliners.json', function(){
			})
			.done(function(data){

				var audioAlert = new Audio('./wav/text-alert.wav');
				var audioEmergency = new Audio('./wav/fire-truck-air-horn_daniel-simion.wav');
				var audioEqual = new Audio('./wav/equal.wav');
				var playSound = 0; 
				var tableNasdaq = $('#nasdaq').DataTable();
				var symbol = ""; 
				var countSymbols = 0;
				var vixNumber; 

				globalData = data; 

				/********************************************
				 ***** CORPORATE ACTIONS DATA STRUCTURE *****
				 ********************************************/

// stockanalysis.com 

	const corporateActionsStocks=["OCG", "HUBC", "VMAR", "VSME", "GOVX", "DRCT", "AKAN", "ICON", "FTEL", "ELAB", "MLEC", "ICU", "CANF", "RVYL", "PAVM",	 





// tipranks.com reverse splits 

	"AMCR", "OCG", "ASBP", "HUBC", "VRRCF", "FTFT", "PCLA", 






// capedge.com reverse splits 

	"SNBH", "CODX", "PAVM", "RVYL", "MLEC", "ICU", "CANF", "ELAB", "ICON", "FTEL", "DGLY", "VSME", "AKAN", "DRCT", "CDIX", "GOVX", "VMAR", "AMCR", "WHLR", 






// Lockup expirations: 





// Other stocks to ignore that aren't on the usual https://stockanalysis.com/actions/ page: 




// END OF stocks to ignore that aren't on the usual https://stockanalysis.com/actions/ page: 


				/***********************************************
				 *** END OF CORPORATE ACTIONS DATA STRUCTURE ***
				 ***********************************************/

				/**********************************************
				 ************** BLACKLISTED STOCKS *************
				 ***********************************************/

				  "IAS", // Horrible earnings net income.  Took a bad loss on it. 
				  "LEXX", // Dropped 46% on no news, March 12th 
				  "ANGH", // Dropped 47.22% on no news, April 3rd 2024 
				  "CTNT", // Dropped 75% one day, then 92.89% the next, on May 21st 2024 
				  "SLRX", // Dropped 38.79% on July 31st, 2024, on no news. 
				  "RR", // Droped 75% on no news on August 6th, 2024 
				  "NDRA", // Reverse split on August 20, 2024, dropped 53% and never recovered 
				  "SMXT", // Dropped 68.62% on no news, on August 28th 2024 
				  "TCBP", // Biotech stock, Dropped 62.65% on no news starting at 11:42 AM Pacific time (so 8:42 AM my time), on October 21st, 2024 
				  "EFSH", // dropped to $0.3412 (72.92%) on an offering that was at $1.26/share on October 29th, 2024. I stayed away from this one and it was a good idea.
				  "TFFP", // Winding down operations, news came out on November 15th, 2024 
				  "YHC", // Dropped 77% on mere purchase order news on March 21s, 2025 
				  "WOLF", // At risk of declaring bankruptcy, May 21st, 2025 
				  "YYAI", // Dropped like 85% on barely any news, October 7th, 2025 
                ]; 


				  // NVTA - Keep putting in an order a 85% in every day, was halted, was going to be delisted 

				/***********************************************
				 ******** END OF BLACKLISTED STOCKS ************
				 ***********************************************/


				var prevGlobalDataString = JSON.stringify(prevGlobalData);
				var globalDataString = JSON.stringify(globalData);

				if (prevGlobalDataString == globalDataString)
				{
					audioEqual.play();
				}

				var prevCurrModal = document.getElementById('prev-current-modal');	
				if ($("#display-prev-curr").is(":checked"))
				{
					$("div#prev-div").html("prevGlobalDataString is *" + prevGlobalDataString + "*");
					$("div#curr-div").html("globalDataString is *" + globalDataString + "*"); 
		        	prevCurrModal.style.display = "block";
				}
				else
				{
					prevCurrModal.style.display = "none";
				}

				prevGlobalData = JSON.parse(JSON.stringify(globalData));

				var tableNasdaqList = $('#nasdaq-list').DataTable();
				tableNasdaq.clear(); 	
				arrayNasdaq = data.NASDAQ; 
				vixNumber = data.VIX; 

				if (vixNumber)
				{
					$("#vixNumber").html(vixNumber);
				}

				if (arrayNasdaq)
				{

					countSymbols += Object.keys(arrayNasdaq).length;

					tableNasdaqList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
		    			var data = this.data();
		    			symbol = data[0];
						if (symbol in arrayNasdaq)
						{
							tableNasdaqList.cell(rowIdx, 4).data(arrayNasdaq[symbol].low_percent.toFixed(2));	
		    				delete arrayNasdaq[symbol];
		    			}	
		    			
					});

					// Here we go through the corporate actions array and eliminate any stock found in that array 
					// from the list 

					for (let i = 0; i < corporateActionsStocks.length; i++)
					{
						delete arrayNasdaq[corporateActionsStocks[i]]; 
					}


					for (const [key, value] of Object.entries(arrayNasdaq))
					{
						// if a stock's low drops more than 10 percent in one minute (i.e. super fast drop)
						// then make the alert noise.
						lowCurrHash[key] = value.low_percent.toFixed(2);
						var differenceInLow = 0;
						var fastDrop = false; 
						if (key in lowPrevHash)
						{
							differenceInLow = lowCurrHash[key] - lowPrevHash[key]; 

							if (differenceInLow > MINIMUM_LOW_DIFF)
							{
								playSound = 1;
								fastDrop = true; 
							}
						}

	            		if (((value.last > 1.00) && (value.change > parseFloat($("#nas-nyse-dollar").val()))) || 
	            			((value.last < 1.00) && (value.change > parseFloat($("#nas-nyse-penny").val()))))
						{
							playSound = 1;
						}

						var rtSymbol = parseInt(key.search(/\.RT/)); 
						var impulseBuy = "";
						if (rtSymbol >= 0)
						{
							var orderStub = createOrderStubRT(jQuery.trim(key), value.last, value.change);

							impulseBuy = "<input type=\"text\" class=\"impulseBuyText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuy(\"" + jQuery.trim(key) +  "\", \"" + orderStub + "\"); removeNyseAmex($(this));' value=\"" + orderStub + "\" readonly>";
						}
						else if (fastDrop == true)
						{
							var orderStub = createOrderStubFastDrop(jQuery.trim(key), value.last, value.change);

							impulseBuy = "<input type=\"text\" class=\"impulseBuyText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuy(\"" + jQuery.trim(key) +  "\", \"" + orderStub + "\"); removeNyseAmex($(this));' value=\"" + orderStub + "\" readonly>";
						}

						var volumeString = value.volume.toString() + "00"; 
						var volume = parseInt(value.volume.toString() + "00");
						var avgVolume = parseInt(value.avg_volume.toString() + "00"); 
						var volumeRatio = volume/avgVolume;
						var checkSec = $('#check-sec').is(":checked")?"1":"0"; 

						var hasPeriod = jQuery.trim(key).indexOf('.'); 
						var symbolBackground = ""; 
						var length = jQuery.trim(key).length; 
						if ((length == 5) || (hasPeriod != -1))
						{
							symbolBackground = "background-color: pink; "
						}

						tableNasdaq.row.add([
							"<input type='text' class='symbolText' style='color: black; " + symbolBackground + " ' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/index.php?symbol=" + key +  "&vix=" + vixNumber + "&check-sec=" + checkSec + "\"); removeNasdaq($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.low, 
							value.change.toFixed(2),
							value.low_percent.toFixed(2), 
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							volumeRatio.toFixed(2),
							"<div class='nasdaq'><i class='icon-remove'></i></div>", 
							impulseBuy
		        			]); 

					}
			 	}  // if(arrayNasdaq)
				tableNasdaq.draw();

				var tableNYSEAmex = $('#nyse-amex').DataTable();
				tableNYSEAmex.clear(); 
				arrayNYSEAmex = data.NYSEAMEX; 

				if (arrayNYSEAmex)
				{
					countSymbols += Object.keys(arrayNYSEAmex).length;

					var tableNYSEAmexList = $('#nyse-amex-list').DataTable();

					tableNYSEAmexList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
		    			var data = this.data();
		    			symbol = data[0];
						if (symbol in arrayNYSEAmex)
						{
							tableNYSEAmexList.cell(rowIdx, 4).data(arrayNYSEAmex[symbol].low_percent.toFixed(2));
		    				delete arrayNYSEAmex[symbol];
		    			}							
		    			
					});

					// Here we go through the corporate actions array and eliminate any stock found in that array 
					// from the list 

					for (let i = 0; i < corporateActionsStocks.length; i++)
					{
						delete arrayNYSEAmex[corporateActionsStocks[i]]; 
					}


					for (const [key, value] of Object.entries(arrayNYSEAmex))
					{
						// if a stock's low drops more than 10 percent in one minute 
						// then make the alert noise.
						lowCurrHash[key] = value.low_percent.toFixed(2);
						var differenceInLow = 0;
						var fastDrop = false; 
						if (key in lowPrevHash)
						{
							differenceInLow =  lowCurrHash[key] - lowPrevHash[key]; 

							if (differenceInLow > MINIMUM_LOW_DIFF)
							{
								playSound = 1;
								fastDrop = true; 
							}
						}

	            		if (((value.last > 1.00) && (value.change > parseFloat($("#nas-nyse-dollar").val()))) || 
	            			((value.last < 1.00) && (value.change > parseFloat($("#nas-nyse-penny").val()))))
						{
							playSound = 1;
						}

						var rtSymbol = parseInt(key.search(/\.RT/)); 
						var impulseBuy = "";
						if (rtSymbol >= 0)
						{
							var orderStub = createOrderStubRT(jQuery.trim(key), value.last, value.change);

							impulseBuy = "<input type=\"text\" class=\"impulseBuyText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuy(\"" + jQuery.trim(key) +  "\", \"" + orderStub + "\"); removeNyseAmex($(this));' value=\"" + orderStub + "\" readonly>";
						}
						else if (fastDrop == true)
						{
							var orderStub = createOrderStubFastDrop(jQuery.trim(key), value.last, value.change);

							impulseBuy = "<input type=\"text\" class=\"impulseBuyText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuy(\"" + jQuery.trim(key) +  "\", \"" + orderStub + "\"); removeNyseAmex($(this));' value=\"" + orderStub + "\" readonly>";
						}

						var volumeString = value.volume.toString() + "00"; 
						var volume = parseInt(value.volume.toString() + "00");
						var avgVolume = parseInt(value.avg_volume.toString() + "00"); 
						var volumeRatio = volume/avgVolume;
						var checkSec = $('#check-sec').is(":checked")?"1":"0"; 

						var hasPeriod = jQuery.trim(key).indexOf('.'); 
						var symbolBackground = ""; 
						var length = jQuery.trim(key).length; 
						if ((length == 5) || (hasPeriod != -1))  
						{
							symbolBackground = "background-color: pink; "
						}

						tableNYSEAmex.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black; " + symbolBackground + "' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/index.php?symbol=" + key +  "&vix=" + vixNumber + "&check-sec=" + checkSec + "\"); removeNyseAmex($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.low,
							value.change.toFixed(2),
							value.low_percent.toFixed(2),
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							volumeRatio.toFixed(2),
							"<div class='nyse-amex'><i class='icon-remove'></i></div>", 
							impulseBuy
		        			] ); 

					}
				} // if(arrayNYSEAmex)
				tableNYSEAmex.draw();

				var tablePink = $('#pink').DataTable();
				tablePink.clear(); 
				arrayPink = data.PINK; 

				if (arrayPink)
				{
					countSymbols += Object.keys(arrayPink).length;

					var tablePinkList = $('#pink-list').DataTable();

					tablePinkList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
		    			var data = this.data();
		    			symbol = data[0];
						if (symbol in arrayPink)
						{
							tablePinkList.cell(rowIdx, 4).data(arrayPink[symbol].low_percent.toFixed(2));
		    				delete arrayPink[symbol];
		    			}	
					});

					for (const [key, value] of Object.entries(arrayPink)) {
					    const volumeString = value.volume.toString() + "00"; 
					    const volume = parseFloat(volumeString);
					    const last = parseFloat(value.last);
					    const totalValue = parseFloat(volume * last); 
					    const symbol = key; 

					    // Only bother fetching previous close if totalValue > 500
					    if (totalValue > 500) {
					        checkPinkSheetChange(symbol, last, function(actualChange) {
					            if (actualChange === null) return; // fetch failed, skip

					            // Use verified actualChange
					            const changePercentagePink = actualChange.toFixed(2);

					            // Play sound if conditions are met
					            if (
					                ((actualChange > parseFloat($("#pink-penny").val()) && last < 1.00) ||
					                 (actualChange > parseFloat($("#pink-dollar").val()) && last >= 1.00))
					            ) {
					                playSound = 1;
					            }

					            // Impulse buy input
					            let impulseBuy = "";
					            const orderStub = createOrderStub(symbol, last, changePercentagePink);
					            impulseBuy = `<input type="text" class="impulseBuyText" style="color: black" target="_blank"
					                onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuyPink("${symbol}");' 
					                value="${orderStub}" readonly>`;

					            const checkSec = $('#check-sec').is(":checked") ? "1" : "0";

					            // Now create the row
					            tablePink.row.add([
					                `<input type="text" class="symbolText" style="color: black" target="_blank"
					                    onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(
					                    "http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/index.php?symbol=${symbol}&vix=${vixNumber}&check-sec=${checkSec}"
					                    ); removePink($(this))' value="${symbol}" readonly>`,
					                last,
					                value.low,
					                changePercentagePink,
					                volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","),
					                "<div class='pink' onclick='removePink($(this));'><i class='icon-remove'></i></div>",
					                impulseBuy
					            ]);

								if (
								    !pinkSheetExamined.has(symbol) &&
								    $('#auto-pink-sheet-buy').is(":checked") &&
								    (actualChange > parseFloat($("#pink-penny").val()))
								) {
								    enqueuePinkSheet(symbol);
								}




					        }); // end callback
					    }
					} // end for loop


				} // if(arrayPink)
				tablePink.draw();

	        	$("#num-symbols").html(countSymbols);

				if (playSound == 1)
				{
					audioAlert.play();
				}

				if (countSymbols == 0)
				{
					audioEmergency.play();
				}

		});

	}

	function countdown() {
    // your code goes here
    	var count = 4;
    	var timerId = setInterval(function() {
	        count--;
        	$("#seconds-display").html(count);

        	if(count == 0) {
	            addRows();
            	count = 4;
        	}
    	}, 1000);
	}


	</script>



</head>

<html>

<body>

<table class=display>

<tr>

	<td  valign="top">
		<table id="pink"  class="display tableFont" border=1 style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=7>
					PINK
					</th>
				</tr>
				<tr height = "15px;">
					<th>	
						Symbol
					</th>
					<th>	
						Last
					</th>	
					<th>
						Low
					</th>
					<th>	
						Change %
					</th>
					<th>	
						Volume
					</th>
					<th>
						
					</th>
					<th>
						Buy
					</th>
				</tr>
			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>
					<td>
						Last
					</td>
					<td>
						Low
					</td>
					<td>	
						Change%
					</td>
					<td>	
						Volume
					</td>
					<td>
						
					</td>
					<th>
						
					</th>
				</tr>			
			</tbody>	

		</table>
	</td>

	<td  valign="top">
		<table id="nasdaq"  class="display" border=1  style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=9>
					NASDAQ
					</th>
				</tr>
				<tr height = "15px;">
					<th>	
						Symbol
					</th>
					<th>	
						Last
					</th>	
					<th>	
						Low
					</th>
					<th>	
						Change %
					</th>
					<th>
						Low %
					</th>
					<th>	
						Volume
					</th>
					<th>	
						Vol Ratio
					</th>
					<th>
						
					</th>
					<th>
						Buy
					</th>
				</tr>
			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>
					<td>
						Last
					</td>
					<td>
						Low
					</td>
					<td>	
						Change %
					</td>
					<td>
						Low % 
					</td>
					<td>	
						Volume
					</td>
					<td>	
						Vol Ratio
					</td>
					<td>
						
					</td>
					<td>
						Buy
					</td>
				</tr>			
			</tbody>	

		</table>
	</td>

	<td valign="top">
		<table id="nyse-amex"  class="display" border=1   style="font-size: 20px;" >
			<thead>
				<tr>
					<th colspan=9>
					NYSE/AMEX
					</th>
				</tr>

				<tr height = "15px;">
					<th>	
						Symbol
					</th>
					<th>	
						Last
					</th>	
					<th>
						Low
					</th>
					<th>	
						Change %
					</th>
					<th>
						Low %
					</th>
					<th>	
						Volume
					</th>
					<th>	
						Vol Ratio
					</th>
					<th>
						
					</th>
					<th>
						Buy
					</th>

				</tr>
			</thead>
			<tbody>	

				<tr height = "15px;">
					<td>	
						Symbol
					</td>
					<td>
						Last
					</td>
					<td>
						Low
					<td>	
						Change %
					</td>
					<td>
						Low %
					</td>
					<td>	
						Volume
					</td>
					<td>	
						Vol Ratio
					</td>
					<td>
						
					</td>
					<td>
						Buy
					</td>
				</tr>			
			</tbody>	

		</table>
	</td>

	<td valign="top" >
		<div id="seconds-display" style="font-size: 70px; width: 120px; height: 75px; border:#000000 1px solid; text-align: center; padding-top: 55px" border=1 >
		</div>
		<div id="num-symbols" style="font-size: 70px; width: 120px; height: 75px; border:#000000 1px solid; text-align: center; padding-top: 55px" border=1 >
		</div>
		<div style="font-size: 20px; background-color: pink; width: 120px; height: 120px; border:#000000 1px solid; text-align: center; padding-top: 15px" border=1 >
			<div>
				<b>PINK</b>
			</div>
			<br>
			<div>
				Penny: <input id="pink-penny" type="text" name="fname" value="35"  style="width: 35px; font-size: 18px"><br>
  				$1.00: <input id="pink-dollar" type="text" name="lname" value="25" style="width: 35px; font-size: 18px">
			</div>
		</div>
		<div style="font-size: 20px; width: 120px; height: 120px; border:#000000 1px solid; text-align: center; padding-top: 15px" border=1 >
			<div>
				<b>NAS/NYSE</b>
			</div>
			<br>
			<div>
				Penny: <input id="nas-nyse-penny" type="text" name="fname" value="18" style="width: 35px; font-size: 18px"><br>
  				$1.00: <input id="nas-nyse-dollar" type="text" name="lname" value="11" style="width: 35px; font-size: 18px">
			</div>

		</div>
		<div style="width: 120px; height: 80px; border:#000000 1px solid; text-align: center; padding-top: 15px" border=1 >
			<div style="font-size: 20px;">
				<b>VIX</b>
			</div><br> 
			<div style="font-size: 40px;" id="vixNumber">

			</div>
		</div>
		<div>
			<button id="check-earnings">
    			Check Earnings
  			</button>
		</div>
		<div>
			<input type="checkbox" id="display-prev-curr">
			<label for="display-prev-curr">Display prev/curr</label>
		</div>
		<div>
			<input type="checkbox" id="check-sec" checked>
			<label for="check-sec">Check SEC</label>
		</div>
		<div>
			<input type="checkbox" id="auto-pink-sheet-buy">
			<label for="check-sec">Auto Pink Sheet Buy</label>
		</div>
		<br>


<!--
  		<button id="clear-tables" value="submit-true">
    		Clear tables
  		</button>
-->
	</td>

	<td  valign="top">
		<table id="pink-list"  class="display tableFont" border=1 style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=6>
					PINK <input id="manualAddPink" type="text" class="manualAddText"> <button id="btnManualAddPink" type="button">Add Symbol</button> 
					</th>
				</tr>
				<tr height = "15px;">
					<th>	
						Symbol
					</th>
					<th>
						Time
					</th>
					<th>
						News
					</th>
					<th>

					</th>
					<th>
						Low
					</th>
					<th>
					
					</th>
				</tr>
			</thead>
			<tbody>	
			</tbody>	
		</table>
	</td>

	<td  valign="top">
		<table id="nasdaq-list"  class="display tableFont" border=1 style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=6>
						Nasdaq  <input id="manualAddNasdaq" type="text" class="manualAddText"> <button id="btnManualAddNasdaq" type="button">Add Symbol</button> 
					</th>
				</tr>
				<tr height = "15px;">
					<th>	
						Symbol
					</th>
					<th>
						Time
					</th>
					<th>
						News 
					</th>
					<th>
					
					</th>
					<th>
						Low
					</th>
					<th>
					
					</th>
				</tr>
			</thead>
			<tbody>	
			</tbody>	
		</table>
	</td>

	<td  valign="top">
		<table id="nyse-amex-list"  class="display tableFont" border=1 style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=6>
					NYSE/AMEX   <input id="manualAddNyseAmex" type="text" class="manualAddText"> <button id="btnManualAddNyseAmex" type="button">Add Symbol</button> 
					</th>
				</tr>
				<tr height = "15px;">
					<th>	
						Symbol
					</th>
					<th>
						Time
					</th>
					<th>
						News 
					</th>
					<th>
					
					</th>
					<th>
						Low
					</th>
					<th>

					</th>
				</tr>
			</thead>
			<tbody>	
			</tbody>	
		</table>
	</td>

</tr>

<div id="myModal" class="modal" style="display: none; height: 550px; ">

  <!-- Modal content -->

<!-- 
  <div id="modalContent" class="modal-content">
    
  </div>
-->

</div>


<div id="prev-current-modal" class="modal" style="display: none; overflow-x: hidden; overflow-y: auto; text-align: center; width: 1000px; height: 700px">

  <!-- Modal content -->
  <div id="prev-div">
    
  </div>
  <div id="curr-div">
    
  </div>

</div>

<div id="pink-sheet-fail">

</div>





</body>
</html>