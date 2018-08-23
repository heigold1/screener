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

		var nasdaqList = new Array(); 
		var nyseAmexList = new Array();
		var pinkList = new Array();

		var tableNasdaq = $('#nasdaq').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false 
		} );

		var tableNYSE = $('#nyse-amex').DataTable( {
	  		"paging":   false,
	        "ordering": false,
	        "info":     false, 
	        "searching": false 
		});

		var tablePink = $('#pink').DataTable( {
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

alert("symbol is " + symbol);

			evt.stopPropagation();
			evt.preventDefault();


			nasdaqList.push(symbol);
			console.log(nasdaqList);

			var nasdaqTable = $('#nasdaq').DataTable();
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

    		addRows();

		});

	} );

	function addRows(){
		$.get('http://localhost/screener/percent-decliners.json', function(data){

console.log("file data is:");
console.log(data);


			var tableNasdaq = $('#nasdaq').DataTable();

			tableNasdaq
    			.clear(); 

			arrayNasdaq = data.NASDAQ; 

//delete arrayNasdaq["AMDA"];

/*			var audio = new Audio('./wav/text-alert.wav');
			audio.play();
*/

			for (const [key, value] of Object.entries(arrayNasdaq))
			{

				tableNasdaq.row.add([
					key, 
					value.last, 
					value.change.toFixed(2),
					value.volume.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","), 
					value.low, 
					"<div class='nasdaq'><i class='icon-remove'></i></div>"
        			] ); 

			}

			tableNasdaq.draw();

			var tableNYSEAmex = $('#nyse-amex').DataTable();

			tableNYSEAmex
    			.clear(); 

			arrayNYSEAmex = data.NYSEAMEX; 

			for (const [key, value] of Object.entries(arrayNYSEAmex))
			{

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

			var tablePink = $('#pink').DataTable();
			tablePink
				.clear(); 

			arrayPink = data.PINK; 

			for (const [key, value] of Object.entries(arrayPink))
			{

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

		});

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

	<td>
  		<button id="clear-tables" value="submit-true">
    		Clear tables
  		</button>

	</td>


	<td  valign="top">
		<table class="display" border=1>
			<thead>
				<tr>
					<th colspan=2>
					Show PINK
					</th>
				</tr>

			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>
				</tr>			
			</tbody>	

		</table>
	</td>

		<td  valign="top">
		<table class="display" border=1>
			<thead>
				<tr>
					<th colspan=2>
					Show Nasdaq
					</th>
				</tr>
			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>
				</tr>			
			</tbody>	

		</table>
	</td>

		<td  valign="top">
		<table class="display" border=1>
			<thead>
				<tr>
					<th colspan=2>
					Show NYSE/AMEX
					</th>
				</tr>
			</thead>
			<tbody>	
				<tr >
					<td>	
						Symbol
					</td>
				</tr>			
			</tbody>	

		</table>
	</td>



</tr>


</body>
</html>