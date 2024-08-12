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
		var rawNumShares = 250/price;
		percentage = percentage.toFixed(2);

		if (price > 1.00)
		{
			price = price + 0.01; 
			price = price.toFixed(2);
		}
		else
		{
			price = price + 0.0001; 
			price = price.toFixed(4);
		}

		var numShares = parseInt(Math.ceil(rawNumShares/100)*100);
		if (numShares > 500000)
		{
			numShares = 500000;
		}
		var orderStub = symbol + " BUY " + numShares + " $" + price + " (" + percentage + "%)"; 
		return orderStub; 
	}

	function checkPinkSheetChange(symbol, last) 
	{
		var prevClose; 
		var change; 

		if (!(symbol in pinkSheetPreviousClose))
		{
	   		$.ajax({
		        url: 'http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/pink-sheet-prev-close.php?',
	        	data: {symbol: symbol},
	        	async: true, 
	        	dataType: 'html',
	        	success:  function (data) {

	        	console.log("Returned JASON object is:");
	        	console.log(data); 

	        	var responseData = JSON.parse(data); 

	        	prevClose = responseData.prevClose; 

	        	pinkSheetPreviousClose[symbol] = prevClose; 
	        },
        		error: function (xhr, ajaxOptions, thrownError) {
          		console.log("there was an error in calling pink-sheet-prev-close.php");
          		alert("ERROR in preparing order for " + symbol + ", message is " + xhr.statusText);
          		console.log(thrownError); 
	        	}
    		});
		}
		else 
		{
			prevClose = pinkSheetPreviousClose[symbol]; 
		}

		change = (prevClose - last)*100/prevClose; 

		return change; 

	}

	// unlike the other prepareImpulseBuy, we are building the order string from within the function because 
	// we have to grab the previous close via API. 

	function prepareImpulseBuyPink(symbol)
	{
 
		var orerStub = ""; 
 
    	$.ajax({
	        url: 'http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/prepare-order.php?',
        	data: {symbol: symbol, 
                   amount: "250", 
                   percentage: "84"},
        	async: true, 
        	dataType: 'html',
        	success:  function (data) {

        	console.log("Returned JASON object is:");
        	console.log(data); 

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

				"&nbsp;&nbsp;<input type='text' class='impulseBuyText' style='color: black; font-size: 35px !important; height: 45px; width: 75px; display: inline-block; '  onclick='console.log($(this)); copyToClipboard($(this)); ' value='" + orderStub + "' readonly>"

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
    			console.log('Copying text command was ' + msg);
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


	$(document).ready(function() {

	
		alert("\n - ****************************************" + 
			  "\n - ****************** PRAY ****************" + 
			  "\n - ****************************************" + 
			"\n - TAKE YOUR LUMBROKINASE" + 
			"\n\n Do you need to make any more care packages?" + 
			"\n\n - Check Jay's days off" + 
			"\n\n - Make sure your speaker is not too loud, turn it down " + 
			"\n\n - Check https://www.otcmarkets.com/market-activity/corporate-actions for any symbols that are going to the Pink Sheets for the first day, you can tell by checking big charts.  Put in your entry at 85%" +  
			"\n\n - Check the $yesterdayDays variable and make sure it's right.  CHECK IT ON THE ACTUAL SERVER" + 
			"\n\n - Unmute the websites" + 
			"\n\n - Reset the database" + 
			"\n\n - Run the Corporate Actions data structure, REFRESH the corporate actions page. " + 
			"\n\n - Grab the eTrade API token" + 
			"\n\n - Check lockup expiration dates " + 
			"\n\n - Check https://www.otcmarkets.com/market-activity/corporate-actions for any symbols that are going to the Pink Sheets for the first day, you can tell by checking big charts.  Put in your entry at 85%" +  
			"\n\n - Run NetBeans" + 
			"\n\n - Run PyCharm" + 
			"\n\n - Check the halts page for any company halted which might be being bought out" + 
			"\n\n - CHECK THE PINK SHEETS AND CORPORATE ACTIONS FOR RECENTLY DELISTED STOCKS" + 
			"\n\n - Go over the index.php notes" + 
			"\n\n - Olives & Parmesan Cheese!" + 
			"\n\n - Eat oranges!!!"); 

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
						 	((change >  parseFloat($("#pink-penny").val())) && (last < 1.00)) || 
						 	((change > parseFloat( $("#pink-dollar").val())) && (last > 1.00))
						 	) 
							&& (totalValue > 500)
						) 
					)
				{
					if (last < 1.00)
					{
         				$(row).addClass('yellowClass');		

         				var actualChange = checkPinkSheetChange(symbol, last); 

         				if (actualChange > 50)
         				{
        					$('td', row).eq(3).addClass('greenClass');
         				}
					}
					else
					{
         				$(row).addClass('lightBlueClass');
					}

					// if 
					if (change > 79)
         			{
	 					$('td', row).eq(6).addClass('orangeClass');
         			}
         		}


         	} 
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
		var tablePink = $('#pink-list').DataTable( {
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
			removePink($(this));
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
			console.log( "Grabbed decliners.json successfully 2" );
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

const corporateActionsStocks=[
"SCCO", "VATE", "CING", "SBFM", "MSTR", "BIVI", "XYLO", "TCBP", "GNLN", "OSTX", "HHH", "FDSB", "AOMN", "NIPG", "ORKT", "LINE", "CON", "OS", "NVA", "BLMZ", "PGHL", "QMMM", "TWFG", "PDCC", "ARDT", "FSUN", "USLM", "ICON", "AVGO", "WRB", "TLN", "AFCG", "WSM",


				/***********************************************
				 *** END OF CORPORATE ACTIONS DATA STRUCTURE ***
				 ***********************************************/

				/***********************************************
				 ************** BLACKLISTED STOCKS *************
				 ***********************************************/

				  "IAS", // Horrible earnings net income.  Took a bad loss on it. 
				  "LEXX", // Dropped 46% on no news, March 12th 
				  "EIGR", // Chapter 11 on April 1st 2024 
				  "ANGH", // Dropped 47.22% on no news, April 3rd 2024 
				  "CTNT", // Dropped 75% one day, then 92.89% the next, on May 21st 2024 
				  "SLRX", // Dropped 38.79% on July 31st, 2024, on no news. 
				  "RR", // Dropped 75% on no news on August 6th, 2024 
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

					for (const [key, value] of Object.entries(arrayPink))
					{

						var volumeString = value.volume.toString() + "00"; 
						var volume = parseFloat(volumeString);
						var last = value.last;
						var totalValue = volume*last; 
						var change = value.change;

						if (
								(
									(
									 ((change >  parseFloat($("#pink-penny").val())) && (last < 1.00)) || 
									 ((change > parseFloat( $("#pink-dollar").val())) && (last > 1.00))
									 ) 
									&& (totalValue > 500)
								)  /* ||
								(
									(change >  parseFloat($("#pink-penny").val())) && 
									(last < 0.01) && 
									(last > 0.001) &&
									(volume > 110000)
								) */

							)
							{
								playSound = 1;
							}

						// this is for the impulse buy, if a pink is down 90% we don't need to check, 
						// just put in the buy order.
						var impulseBuy = "";

						var changePercentagePink = value.change.toFixed(2)

						if (totalValue > 500)
						{
							var orderStub = createOrderStub(jQuery.trim(key), last, change);

							impulseBuy = "<input type=\"text\" class=\"impulseBuyText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuyPink(\"" + jQuery.trim(key) + "\"); removePink($(this));' value=\"" + orderStub + "\" readonly>";
						}

						var checkSec = $('#check-sec').is(":checked")?"1":"0"; 

						tablePink.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://ec2-34-221-98-254.us-west-2.compute.amazonaws.com/newslookup/index.php?symbol=" + key +  "&vix=" + vixNumber + "&check-sec=" + checkSec + "\"); removePink($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.low, 
							changePercentagePink,
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							"<div class='pink'><i class='icon-remove'></i></div>", 
							impulseBuy
		        			] ); 

					}
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
				Penny: <input id="pink-penny" type="text" name="fname" value="50"  style="width: 35px; font-size: 18px"><br>
  				$1.00: <input id="pink-dollar" type="text" name="lname" value="25" style="width: 35px; font-size: 18px">
			</div>
		</div>
		<div style="font-size: 20px; width: 120px; height: 120px; border:#000000 1px solid; text-align: center; padding-top: 15px" border=1 >
			<div>
				<b>NAS/NYSE</b>
			</div>
			<br>
			<div>
				Penny: <input id="nas-nyse-penny" type="text" name="fname" value="13" style="width: 35px; font-size: 18px"><br>
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
					PINK
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

<div id="myModal" class="modal" style="display: none">

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



</body>
</html>