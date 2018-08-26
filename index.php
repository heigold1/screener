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
	$(document).ready(function() {

		//PINK List
		var tablePink = $('#pink').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false, 
	        "createdRow": function( row, data, dataIndex ) {
            	if ( data[2] > 30  ) {
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
            	if ( data[2] > 10  ) {
         			$(row).addClass('redClass');
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
            	if ( data[2] > 10  ) {
         			$(row).addClass('redClass');
         		}
         	} 
		});

		//PINK List
		var tablePink = $('#pink-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false
		});

		// Nasdaq List
		var tableNasdaqList = $('#nasdaq-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false 
		});


		var tableNYSEAmexList = $('#nyse-amex-list').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false 
		});



/*
		$('#nasdaq tbody').on('click', 'tr', function () {
			var data = tableNasdaq.row( this ).data();
			alert( 'You clicked on '+data[0]+'\'s row' ); 
		} );
*/ 

		$(document).on('click', '.nasdaq', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();

			var nasdaqTable = $('#nasdaq').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();


			var tableNasdaqList = $('#nasdaq-list').DataTable();

			tableNasdaqList.row.add([
				symbol, 
				"<div class='nasdaq-list'><i class='icon-remove'></i></div>"
    			] ); 


			tableNasdaqList.draw();

		});


		$(document).on('click', '.pink', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();


			var nasdaqTable = $('#pink').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();


			var tablePinkList = $('#pink-list').DataTable();

			tablePinkList.row.add([
				symbol, 
				"<div class='pink-list'><i class='icon-remove'></i></div>"
    			] ); 


			tablePinkList.draw();

		});


		$(document).on('click', '.nyse-amex', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();

			var nasdaqTable = $('#nyse-amex').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();

			var tableNYSEAmexList = $('#nyse-amex-list').DataTable();

			tableNYSEAmexList.row.add([
				symbol, 
				"<div class='nyse-amex-list'><i class='icon-remove'></i></div>"
    			] ); 

			tableNYSEAmexList.draw();
		});



		$(document).on('click', '.nasdaq-list', function (evt) {
			var row = $(this).closest('tr'); 
			var data = row.children();
			var symbol = $(data[0]).text();

			evt.stopPropagation();
			evt.preventDefault();

			var nasdaqTable = $('#nasdaq-list').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();
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

			var nasdaqTable = $('#nyse-amex-list').DataTable();
     			nasdaqTable.row( row ).remove();
     			nasdaqTable.draw();
     	}); 

		
/*
		$('#nyse-amex tbody').on('click', 'tr', function () {

			var data = tableNYSE.row( this ).data();
			alert( 'You clicked on '+data[0]+'\'s row' );

		} );
*/

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

	function addRows(){
		$.get('http://localhost/screener/percent-decliners.json', function(data){

			var audio = new Audio('./wav/text-alert.wav');
			var playSound = 0; 
			var tableNasdaq = $('#nasdaq').DataTable();
			var symbol = ""; 


			arrayNasdaq = data.NASDAQ; 

			if (arrayNasdaq)
			{
				tableNasdaq
    				.clear(); 	

				var tableNasdaqList = $('#nasdaq-list').DataTable();

				tableNasdaqList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
	    			var data = this.data();
	    			symbol = data[0];
	    			console.log("Symbol is " + symbol); 
	    			delete arrayNasdaq[symbol];
				});

				for (const [key, value] of Object.entries(arrayNasdaq))
				{
					if (value.change > 10)
					{
						playSound = 1;
					}

					tableNasdaq.row.add([
						key, 
						value.last, 
						value.change.toFixed(2),
						value.volume.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
						value.low, 
						"<div class='nasdaq'><i class='icon-remove'></i></div>"
	        			]); 

				}

				tableNasdaq.draw();
		 	}  // if(arrayNasdaq)


			arrayNYSEAmex = data.NYSEAMEX; 
			if (arrayNYSEAmex)
			{
				var tableNYSEAmex = $('#nyse-amex').DataTable();

				tableNYSEAmex
    				.clear(); 

				var tableNYSEAmexList = $('#nyse-amex-list').DataTable();

				tableNYSEAmexList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
	    			var data = this.data();
	    			symbol = data[0];
	    			delete arrayNYSEAmex[symbol];
				});


				for (const [key, value] of Object.entries(arrayNYSEAmex))
				{
					if (value.change > 10)
					{
						playSound = 1;
					}

					tableNYSEAmex.row.add([
						key, 
						value.last, 
						value.change.toFixed(2),
						value.volume.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
						value.low, 
						"<div class='nyse-amex'><i class='icon-remove'></i></div>"
	        			] ); 

				}

				tableNYSEAmex.draw();
			} // if(arrayNYSEAmex)


		arrayPink = data.PINK; 
		if (arrayPink)
		{
			var tablePink = $('#pink').DataTable();

			tablePink
				.clear(); 

			var tablePinkList = $('#pink-list').DataTable();

			tablePinkList.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
    			var data = this.data();
    			symbol = data[0];
    			delete arrayPink[symbol];
			});

			for (const [key, value] of Object.entries(arrayPink))
			{
				if (value.change > 30)
				{
					playSound = 1;
				}

				tablePink.row.add([
					key, 
					value.last, 
					value.change.toFixed(2),
					value.volume.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
					value.low, 
					"<div class='pink'><i class='icon-remove'></i></div>"
        			] ); 

			}

			tablePink.draw();
		} // if(arrayPink)



			if (playSound == 1)
			{
				audio.play();
			}

		});

	}

	function countdown() {
    // your code goes here
    	var count = 9;
    	var timerId = setInterval(function() {
	        count--;
console.log("count is " + count);
        	$("#seconds-display").html(count);

        	if(count == 0) {
	            addRows();
            	count = 9;
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
						
					</th>
				</tr>
			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>

					<td>
						
					</td>
				</tr>			
			</tbody>	
		</table>
	</td>

	<td  valign="top">
		<table id="nasdaq-list"  class="display tableFont" border=1 style="font-size: 20px;">
			<thead>
				<tr>
					<th colspan=6>
					Nasdaq 
					</th>
				</tr>
				<tr height = "15px;">
					<th>	
						Symbol
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
						
					</td>
				</tr>			
			</tbody>	
		</table>
	</td>

	<td  valign="top">
		<table id="nyse-amex-list"  class="display tableFont" border=1 style="font-size: 20px;">
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
						
					</th>
				</tr>
			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>

					<td>
						
					</td>
				</tr>			
			</tbody>	
		</table>
	</td>

</tr>


</body>
</html>