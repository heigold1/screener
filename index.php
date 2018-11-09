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

	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" class="init">

	var newsLookupWindow; 

	function openNewsLookupWindow(link){
		newsLookupWindow = window.open(link, "newslookup-window"); 
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
			'<input type="checkbox" checked>',
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

			var nasdaqTable = $('#pink').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();

			var tablePinkList = $('#pink-list').DataTable();

			tablePinkList.row.add([
				symbol, 
				'<input type="text" class="newsText">',
			 	'<input type="checkbox" checked>',
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
			 	'<input type="checkbox" checked>',
				'',
				"<div class='nyse-amex-list'><i class='icon-remove'></i></div>"
    			] ); 

			tableNYSEAmexList.draw();

	}


	$(document).ready(function() {

		//PINK List
		var tablePink = $('#pink').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {

	        	var change = data[2];
	        	var volumeString = data[3];
        		var volume = parseFloat(volumeString.replace(/,/g, '')); 
				var last = data[1];
				var totalValue = volume*last; 

				if ((((change >  parseFloat($("#pink-penny").val())) && (last < 1.00)) || 
					 ((change > parseFloat( $("#pink-dollar").val())) && (last > 1.00))) && (totalValue > 10000))
				{
         			$(row).addClass('redClass');
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

	        	var change = data[2];
	        	var last = data[1];
				var volumeString = data[3];
				var volume = parseInt(volumeString.replace(/,/g, ''))

            	if (((last > 1.00) && (change > parseFloat($("#nas-nyse-dollar").val()))) || 
            		((last < 1.00) && (change > parseFloat($("#nas-nyse-penny").val()))))
            	{

            		if (volume < 20000)
            		{
         				$(row).addClass('redClass');            			
            		}
            		else 
            		{
         				$(row).addClass('lightRedClass');            			
            		}
         		}

         		$(row).addClass('allRows');
         	}
		} );

		//NYSE
		var tableNYSEAmex = $('#nyse-amex').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {
	        	var change = data[2];
	        	var last = data[1];
				var volumeString = data[3];
				var volume = parseInt(volumeString.replace(/,/g, ''))

            	if (((last > 1.00) && (change > parseFloat($("#nas-nyse-dollar").val()))) || 
            		((last < 1.00) && (change > parseFloat($("#nas-nyse-penny").val()))))
            	{
            		if (volume < 20000)
            		{
         				$(row).addClass('redClass');            			
            		}
            		else 
            		{
         				$(row).addClass('lightRedClass');            			
            		}
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
			}
		});


		var tableNYSEAmexList = $('#nyse-amex-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
			"createdRow": function( row, data, dataIndex ) {
				$(row).addClass('allRows');
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

		$.get('http://localhost/screener/percent-decliners.json', function(){
			console.log( "Grabbed percent-decliners.json successfully" );
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
					console.log("Number of symbols is " + countSymbols); 

					tableNasdaqList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
		    			var data = this.data();
		    			symbol = data[0];
						if (symbol in arrayNasdaq)
						{
							tableNasdaqList.cell(rowIdx, 3).data(arrayNasdaq[symbol].change.toFixed(2));							
		    				delete arrayNasdaq[symbol];
		    			}	
		    			
					});

					for (const [key, value] of Object.entries(arrayNasdaq))
					{

	            		if (((value.last > 1.00) && (value.change > parseFloat($("#nas-nyse-dollar").val()))) || 
	            			((value.last < 1.00) && (value.change > parseFloat($("#nas-nyse-penny").val()))))
						{
							playSound = 1;
						}

						var volumeString = value.volume.toString() + "00"; 

						tableNasdaq.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://www.heigoldinvestments.com/newslookup/index.php?symbol=" + key +  "\"); removeNasdaq($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.change.toFixed(2),
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							value.low, 
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
							tableNYSEAmexList.cell(rowIdx, 3).data(arrayNYSEAmex[symbol].change.toFixed(2));
		    				delete arrayNYSEAmex[symbol];
		    			}							
		    			
					});


					for (const [key, value] of Object.entries(arrayNYSEAmex))
					{
	            		if (((value.last > 1.00) && (value.change > parseFloat($("#nas-nyse-dollar").val()))) || 
	            			((value.last < 1.00) && (value.change > parseFloat($("#nas-nyse-penny").val()))))
						{
							playSound = 1;
						}

						var volumeString = value.volume.toString() + "00"; 

						tableNYSEAmex.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://www.heigoldinvestments.com/newslookup/index.php?symbol=" + key +  "\"); removeNyseAmex($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.change.toFixed(2),
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							value.low, 
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
							tablePinkList.cell(rowIdx, 3).data(arrayPink[symbol].change.toFixed(2));
		    				delete arrayPink[symbol];
		    			}	
					});

					for (const [key, value] of Object.entries(arrayPink))
					{

						var volumeString = value.volume.toString() + "00"; 
						var volume = parseFloat(volumeString);
						var last = value.last;
						var totalValue = volume*last; 

						if ((((value.change > parseFloat($("#pink-penny").val())) && (value.last < 1.00)) || 
						     ((value.change > parseFloat( $("#pink-dollar").val())) && (value.last > 1.00))) && (totalValue > 10000))
						{
							playSound = 1;
						}

						tablePink.row.add([
							"<input type=\"text\" class=\"symbolText\" style='color: black' target='_blank'  onclick='console.log($(this)); copyToClipboard($(this)); openNewsLookupWindow(\"http://www.heigoldinvestments.com/newslookup/index.php?symbol=" + key +  "\"); removePink($(this));' value=\"" + jQuery.trim(key) + "\" readonly>", 
							value.last, 
							value.change.toFixed(2),
							volumeString.replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
							value.low, 
							"<div class='pink'><i class='icon-remove'></i></div>"
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
					<th colspan=6>
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
						Change %
					</th>
					<th>	
						Volume
					</th>
					<th>	
						Low
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
						Change%
					</td>
					<td>	
						Volume
					</td>
					<td>	
						Low
					</td>
					<td>
						
					</td>
				</tr>			
			</tbody>	

		</table>
	</td>

	<td  valign="top">
		<table id="nasdaq"  class="display" border=1  style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=6>
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
						Change %
					</th>
					<th>	
						Volume
					</th>
					<th>	
						Low
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
						Change%
					</td>
					<td>	
						Volume
					</td>
					<td>	
						Low
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
					<th colspan=6>
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
						Change %
					</th>
					<th>	
						Volume
					</th>
					<th>	
						Low
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
						Change %
					</td>
					<td>	
						Volume
					</td>
					<td>	
						Low
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
				Penny: <input id="nas-nyse-penny" type="text" name="fname" value="16" style="width: 35px; font-size: 18px"><br>
  				$1.00: <input id="nas-nyse-dollar" type="text" name="lname" value="14" style="width: 35px; font-size: 18px">
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


</body>
</html>