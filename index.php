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

	function prepareImpulseBuy(symbol, orderStub)
	{
		var modal = document.getElementById('myModal');
		$("div#myModal").html(" <img style='max-width:100%; max-height:100%;' src='http://bigcharts.marketwatch.com/kaavio.Webhost/charts/big.chart?nosettings=1&symb=" + symbol + "&uf=0&type=2&size=2&freq=1&entitlementtoken=0c33378313484ba9b46b8e24ded87dd6&time=4&rand=" + Math.random() + "&compidx=&ma=0&maval=9&lf=1&lf2=0&lf3=0&height=335&width=579&mocktick=1)'></a><br><br><span style='font-size:30px;'>" + orderStub + "</span><br><br><button class='closeModal' style='font-size: 35px !important; height: 45px' onclick='closeModalWindow();'>Close</button>");
        modal.style.display = "block";
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

		tableNasdaqList.row.add([
			symbol, 
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

			tablePinkList.row.add([
				symbol, 
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

			tableNYSEAmexList.row.add([
				symbol, 
				'<input type="text" class="newsText">',
				'<input type="checkbox" class="list-check" id="chk-' + symbol + '">',
				'',
				"<div class='nyse-amex-list'><i class='icon-remove'></i></div>"
    			] ); 

			tableNYSEAmexList.draw();

	}


	$(document).ready(function() {

		alert("SET YOUR YESTERDAY DAY VARIABLES IN NEWSLOOKUP proxy.php AND proxy_sec.php");

		currentMinute = moment().minutes(); 

		//PINK List
		var tablePink = $('#pink').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {

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
					)  /* ||
					(
						(change >  parseFloat($("#pink-penny").val())) && 
						(last < 0.01) && 
						(last > 0.001) &&
						(volume > 110000)
					)  */

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
		        url: 'http://ec2-54-210-42-143.compute-1.amazonaws.com/newslookup/get-earnings-stocks.php',
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
			tableNasdaqList.row.add([
			$.trim($("#manualAddNasdaq").val()), 
			'<input type="text" class="newsText">',
			'<input type="checkbox" checked>',
			'',
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
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();

			var nyseAmexListTable = $('#nyse-amex-list').DataTable();
     			nyseAmexListTable.row( row ).remove();
     			nyseAmexListTable.draw();
     	}); 

		$(document).on('click', '.innerTD', function(evt){
			var row = $(this).closest('tr'); 
			var data = row.find('.symbolText');
			var symbol = data[0].value;
			copyToClipboard(data);

			openNewsLookupWindow("http://ec2-54-210-42-143.compute-1.amazonaws.com/newslookup/index.php?symbol=" + symbol)
		});



		$("#btnManualAddNyseAmex").click(function(){
			tableNYSEAmexList.row.add([
			$.trim($("#manualAddNyseAmex").val()), 
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

		$.get('http://localhost/screener/decliners.json', function(){
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

				globalData = data; 

				var prevGlobalDataString = JSON.stringify(prevGlobalData);
				var globalDataString = JSON.stringify(globalData);

				if (prevGlobalDataString == globalDataString)
				{
					audioEqual.play();
				}

				prevGlobalData = JSON.parse(JSON.stringify(globalData));

				var tableNasdaqList = $('#nasdaq-list').DataTable();
				tableNasdaq.clear(); 	
				arrayNasdaq = data.NASDAQ; 

				if (arrayNasdaq)
				{
					countSymbols += Object.keys(arrayNasdaq).length;

					tableNasdaqList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
		    			var data = this.data();
		    			symbol = data[0];
						if (symbol in arrayNasdaq)
						{
							tableNasdaqList.cell(rowIdx, 3).data(arrayNasdaq[symbol].low_percent.toFixed(2));	
		    				delete arrayNasdaq[symbol];
		    			}	
		    			
					});

					for (const [key, value] of Object.entries(arrayNasdaq))
					{
						// if a stock's low drops more than 10 percent in one minute 
						// then make the alert noise.
						lowCurrHash[key] = value.low_percent.toFixed(2);
						var differenceInLow = 0;
						if (key in lowPrevHash)
						{
							differenceInLow = lowCurrHash[key] - lowPrevHash[key]; 

							if (differenceInLow > MINIMUM_LOW_DIFF)
							{
								playSound = 1;
							}
						}

	            		if (((value.last > 1.00) && (value.change > parseFloat($("#nas-nyse-dollar").val()))) || 
	            			((value.last < 1.00) && (value.change > parseFloat($("#nas-nyse-penny").val()))))
						{
							playSound = 1;
						}

						var volumeString = value.volume.toString() + "00"; 
						var volume = parseInt(value.volume.toString() + "00");
						var avgVolume = parseInt(value.avg_volume.toString() + "00"); 
						var volumeRatio = volume/avgVolume;

						tableNasdaq.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://ec2-54-210-42-143.compute-1.amazonaws.com/newslookup/index.php?symbol=" + key +  "\"); removeNasdaq($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.low, 
							value.change.toFixed(2),
							value.low_percent.toFixed(2), 
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							volumeRatio.toFixed(2),
							"<div class='nasdaq'><i class='icon-remove'></i></div>"
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
							tableNYSEAmexList.cell(rowIdx, 3).data(arrayNYSEAmex[symbol].low_percent.toFixed(2));
		    				delete arrayNYSEAmex[symbol];
		    			}							
		    			
					});

					for (const [key, value] of Object.entries(arrayNYSEAmex))
					{
						// if a stock's low drops more than 10 percent in one minute 
						// then make the alert noise.
						lowCurrHash[key] = value.low_percent.toFixed(2);
						var differenceInLow = 0;
						if (key in lowPrevHash)
						{
							differenceInLow =  lowCurrHash[key] - lowPrevHash[key]; 

							if (differenceInLow > MINIMUM_LOW_DIFF)
							{
								playSound = 1;
							}
						}

	            		if (((value.last > 1.00) && (value.change > parseFloat($("#nas-nyse-dollar").val()))) || 
	            			((value.last < 1.00) && (value.change > parseFloat($("#nas-nyse-penny").val()))))
						{
							playSound = 1;
						}

						var volumeString = value.volume.toString() + "00"; 
						var volume = parseInt(value.volume.toString() + "00");
						var avgVolume = parseInt(value.avg_volume.toString() + "00"); 
						var volumeRatio = volume/avgVolume;

						tableNYSEAmex.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://ec2-54-210-42-143.compute-1.amazonaws.com/newslookup/index.php?symbol=" + key +  "\"); removeNyseAmex($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.low,
							value.change.toFixed(2),
							value.low_percent.toFixed(2),
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							volumeRatio.toFixed(2),
							"<div class='nyse-amex'><i class='icon-remove'></i></div>"
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
							tablePinkList.cell(rowIdx, 3).data(arrayPink[symbol].low_percent.toFixed(2));
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


						// setting the threshold of 79%, anything lower than 79% we can impulse-buy.
						if (changePercentagePink > 79 && (totalValue > 500))
						{
							var orderStub = createOrderStub(jQuery.trim(key), last, change);

							impulseBuy = "<input type=\"text\" class=\"impulseBuyText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); prepareImpulseBuy(\"" + jQuery.trim(key) +  "\", \"" + orderStub + "\"); removePink($(this));' value=\"" + orderStub + "\" readonly>";
						}

						tablePink.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://ec2-54-210-42-143.compute-1.amazonaws.com/newslookup/index.php?symbol=" + key +  "\"); removePink($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
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
					<th colspan=8>
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
				</tr>			
			</tbody>	

		</table>
	</td>

	<td valign="top">
		<table id="nyse-amex"  class="display" border=1   style="font-size: 20px;" >
			<thead>
				<tr>
					<th colspan=8>
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
  				$1.00: <input id="pink-dollar" type="text" name="lname" value="32" style="width: 35px; font-size: 18px">
			</div>
		</div>
		<div style="font-size: 20px; width: 120px; height: 120px; border:#000000 1px solid; text-align: center; padding-top: 15px" border=1 >
			<div>
				<b>NAS/NYSE</b>
			</div>
			<br>
			<div>
				Penny: <input id="nas-nyse-penny" type="text" name="fname" value="17" style="width: 35px; font-size: 18px"><br>
  				$1.00: <input id="nas-nyse-dollar" type="text" name="lname" value="11" style="width: 35px; font-size: 18px">
			</div>
			<div>
				<button id="check-earnings">
    				Check Earnings
  				</button>
			</div>
		</div><br>

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

</body>
</html>