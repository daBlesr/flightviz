<?php
include_once('../controllers/db.php');
$c = 0;
$firstline = true;
$iter = 0;
$path = 'C:/Users/s115426/Documents/visdata/';
$files = [
	'86207366_T_ONTIME.csv',
	'86207366_T_ONTIME_1.csv',
	'86207366_T_ONTIME_2.csv',
	'86207366_T_ONTIME_3.csv',
	'86207366_T_ONTIME_4.csv',
	'86207366_T_ONTIME_5.csv',
	'86207366_T_ONTIME_6.csv',
	'86207366_T_ONTIME_7.csv',
];

foreach($files as $file){
	$iter = 0;
	$myfile = fopen($path . $file, "r") or die("Unable to open file!");
	$firstline = true;
	
	$sql = "INSERT INTO flights (
		fl_date,
		airline_id,
		carrier,
		origin_airport_id,
		origin_airport_seq_id,
		origin_city_market_id,
		origin,
		origin_city_name,
		dest_airport_id,
		dest_airport_seq_id,
		dest_city_market_id,
		dest,
		dest_city_name,
		crs_dep_time,
		dep_time,
		dep_delay,
		crs_arr_time,
		arr_time,
		arr_delay,
		cancelled,
		cancellation_code,
		airtime,
		distance
	) VALUES ";	

	while (($line = fgets($myfile)) !== false) {
		if(!$firstline){

			$sql .= "(";
			$vals = str_getcsv( $line);
			$sql .= '"' . implode('","',array_slice($vals,0,sizeof($vals) - 1)) . '"';
			$sql .= "), ";
			//var_dump($line, $vals, array_slice($vals,0,sizeof($vals) - 1));
			if ($iter > 9999){
				$sql = rtrim($sql, ", ") . ';';
				if( $GLOBALS['conn']->query($sql) !== TRUE) {
			    	echo "Error: ". $sql . "<br>" . $GLOBALS['conn']->error;
			    	exit;
				} else{
					echo $c . PHP_EOL;
					$iter = 0;
					$sql = "INSERT INTO flights (
						fl_date,
						airline_id,
						carrier,
						origin_airport_id,
						origin_airport_seq_id,
						origin_city_market_id,
						origin,
						origin_city_name,
						dest_airport_id,
						dest_airport_seq_id,
						dest_city_market_id,
						dest,
						dest_city_name,
						crs_dep_time,
						dep_time,
						dep_delay,
						crs_arr_time,
						arr_time,
						arr_delay,
						cancelled,
						cancellation_code,
						airtime,
						distance
					) VALUES ";	
				}
			} else{
				$iter++;
				$c++;
			}
		}

		$firstline = false;
	}

	if($iter > 0){
		$sql = rtrim($sql, ", ") . ';';
		if( $GLOBALS['conn']->query($sql) !== TRUE) {
	    	echo "Error: ". $sql . "<br>" . $GLOBALS['conn']->error;
	    	exit;
		}
	}

	fclose($myfile);
}