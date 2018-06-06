<!DOCTYPE html>
<html lang="de">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Greenhouse</title>
	<meta name="description" content="Greenhouse built with raspberry pi">
	<meta name="author" content="Lukas Frena">
	
	<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/bootstrap-datepicker.min.js"></script>
	<script type="text/javascript" src="js/moment.min.js"></script>
	<script type="text/javascript" src="js/chart.min.js"></script>
	
	<link rel="stylesheet" type="text/css" href="css/bootstrap-datepicker3.min.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
</head>

<body>

	<div style="margin: 20px 0px 0px 20px">
		<button type="button" class="btn btn-success" style="float:left;" onclick="days(1);">1 day</button>
		<button type="button" class="btn btn-success" style="float:left;margin-left:5px;" onclick="days(2);">2 days</button>
		<button type="button" class="btn btn-success" style="float:left;margin-left:5px;" onclick="days(3);">3 days</button>
		<input id="datepicker" style="width:108px;float:left;margin-left:5px;" type="text" class="form-control">
		<div style="clear:both"></div>
	</div>
	
	<div style="width:80%;min-width:600px;margin:auto;">
		<canvas id="tempChart"></canvas>
		<canvas id="adcChart"></canvas>
	</div>
	
	<?php
		$servername = "localhost";
		$username = "pi";
		$password = "luky_luke8";
		$dbname = "raspberrydb";

		// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);
		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		} 

		$date = $_GET['date'];
		if(!$date){
			$date = "1";
		}
		
		if($date == "1" || $date == "2" || $date == "3"){
			$where_clause = "datetime > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ".$date." DAY)";
		}
		else {
			$where_clause = "DATE_FORMAT(datetime, '%Y %m %d') = DATE_FORMAT('".$date."', '%Y %m %d')";
		}
	
		$sql = "SELECT id, datetime, temperature, humidity, brightness, moisture FROM sensor_data WHERE ".$where_clause." ORDER BY id ASC";
		$result = $conn->query($sql);

		if ($result->num_rows > 0){
			$border = array("temperature" => 2, "humidity" => 2, "moisture" => 100, "brightness" => 100);	//border for axis
			$data = array("temperature" => "", "humidity" => "", "moisture" => "", "brightness" => "");	//data array
			$min = array("temperature" => 100, "humidity" => 100, "moisture" => 1024, "brightness" => 1024);	//start vals for minimas
			$max = array("temperature" => -100, "humidity" => 0, "moisture" => 0, "brightness" => 0);	//start vals for maximas
			
			while($row = $result->fetch_assoc()){
				//collect data
				foreach($data as $key => $value){
					$data[$key] = $data[$key]."{x: new Date('".$row["datetime"]."'), y: ".$row[$key]."},";
				}
				
				//find minima and maxima
				foreach($min as $key => $value){
					$min[$key] = min($min[$key], $row[$key]);
					$max[$key] = max($max[$key], $row[$key]);
				}
			}
			
			//set border
			foreach($min as $key => $value){
				$min[$key] = intval($min[$key] - $border[$key]);
				$max[$key] = intval($max[$key] + $border[$key]);
			}
		}

		$conn->close();
	?>
	
	<script type="text/javascript">
	
		$.urlParam = function(name){
			var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
			if(results)
				return results[1];
			else
				return 0;
		}
		
		function days(days){
			window.location.href = window.location.origin + window.location.pathname + "?date=" + days;
		}

		$(document).ready(function(){
			$('#datepicker').datepicker({
				format: 'dd/mm/yyyy',
				startDate: '-30d',
				endDate: '+0d'
			});
			
			//init input element
			var selDate;
			if($.urlParam('date')){
				selDate = moment($.urlParam('date')).format('DD/MM/YYYY');
			}
			$('#datepicker').datepicker('setDate', selDate);
			
			$('#datepicker').datepicker().on('changeDate', function(){
				var selection = moment($('#datepicker').datepicker('getDate')).format('YYYY-MM-DD');
				window.location.href = window.location.origin + window.location.pathname + "?date=" + selection;
			});
		});
	
		window.chartColors = {
			red: 'rgb(255, 99, 132)',
			orange: 'rgb(255, 159, 64)',
			yellow: 'rgb(255, 205, 86)',
			green: 'rgb(75, 192, 192)',
			blue: 'rgb(54, 162, 235)',
			purple: 'rgb(153, 102, 255)',
			grey: 'rgb(201, 203, 207)'
		};
		
		var tempCtx = document.getElementById("tempChart").getContext('2d');
		var adcCtx = document.getElementById("adcChart").getContext('2d');
		
		var tempChart = new Chart(tempCtx, {
			type: 'line',
			data: {
				datasets: [{
					label: 'Temperature [°C]',
					data: [<?=$data["temperature"]?>],
					fill: false,
					yAxisID: 'y-axis-temp',
					borderColor: window.chartColors.red,
					backgroundColor: window.chartColors.red,
					borderWidth: 2,
					pointRadius: 0
				},
				{
					label: 'Humidity [%]',
					data: [<?=$data["humidity"]?>],
					fill: false,
					yAxisID: 'y-axis-humidity',
					borderColor: window.chartColors.blue,
					backgroundColor: window.chartColors.blue,
					borderWidth: 2,
					pointRadius: 0
				}]
			},
			options: {
				responsive: true,
				hoverMode: 'index',
				stacked: false,
				scales: {
					xAxes: [{
						type: 'time',
						time: {	
							tooltipFormat: 'HH:mm',
							unit: 'hour',
							stepSize: '2',
							displayFormats: {
								hour: 'ddd / HH:mm'
							}
						},
						scaleLabel: {
							display: true,
							labelString: 'Time'
						},
						ticks: {
							major: {
								fontStyle: 'bold',
								fontColor: '#FF0000'
							}
						}
					}],
					yAxes: [{
						type: 'linear',
						display: true,
						position: 'left',
						id: 'y-axis-temp',
						scaleLabel: {
							display: true,
							labelString: '°C'
						},
						ticks: {
							min: <?=$min["temperature"]?>,
							max: <?=$max["temperature"]?>
						}
					}, {
						type: 'linear',
						display: true,
						position: 'right',
						id: 'y-axis-humidity',
						gridLines: {
							drawOnChartArea: false,
						},
						scaleLabel: {
							display: true,
							labelString: '%'
						},
						ticks: {
							min: <?=$min["humidity"]?>,
							max: <?=$max["humidity"]?>
						}
					}],
				}
			}
		});
		
		var adcChart = new Chart(adcCtx, {
			type: 'line',
			data: {
				datasets: [{
					label: 'Moisture [ADC]',
					data: [<?=$data["moisture"]?>],
					fill: false,
					yAxisID: 'y-axis-moisture',
					borderColor: window.chartColors.red,
					backgroundColor: window.chartColors.red,
					borderWidth: 2,
					pointRadius: 0
				},
				{
					label: 'Brightness [ADC]',
					data: [<?=$data["brightness"]?>],
					fill: false,
					yAxisID: 'y-axis-moisture',
					borderColor: window.chartColors.blue,
					backgroundColor: window.chartColors.blue,
					borderWidth: 2,
					pointRadius: 0
				}]
			},
			options: {
				responsive: true,
				hoverMode: 'index',
				stacked: false,
				scales: {
					xAxes: [{
						type: 'time',
						time: {	
							tooltipFormat: 'HH:mm',
							unit: 'hour',
							stepSize: '2',
							displayFormats: {
								hour: 'ddd / HH:mm'
							}
						},
						scaleLabel: {
							display: true,
							labelString: 'Time'
						},
						ticks: {
							major: {
								fontStyle: 'bold',
								fontColor: '#FF0000'
							}
						}
					}],
					yAxes: [{
						type: 'linear',
						display: true,
						position: 'left',
						id: 'y-axis-moisture',
						scaleLabel: {
							display: true,
							labelString: 'ADC'
						},
						ticks: {
							min: <?=$min["moisture"]?>,
							max: <?=$max["moisture"]?>
						}
					}],
				}
			}
		});
	</script>
</body>
</html>
