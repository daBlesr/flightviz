<?php
include_once('../controllers/db.php');

$firstline = true;
$iter = 0;
$myfile = fopen("C:/Users/s115426/Downloads/476928803_T_T100I_MARKET_ALL_CARRIER.csv", "r") or die("Unable to open file!");
while (($line = fgets($myfile)) !== false) {
	if(!$firstline){

		if($iter % 10000 == 0){
			echo $iter . "\n";
			$sql = "INSERT INTO international_flights (
				airline_id,
				carrier,
				carrier_name,
				origin_airport_id,
				origin_airport_seq_id,
				origin_city_market_id,
				origin,
				origin_city_name,
				origin_coutry,
				origin_country_name,
				dest_airport_id,
				dest_airport_seq_id,
				dest_city_market_id,
				dest,
				dest_city_name,
				dest_country,
				dest_country_name,
				year,
				quarter,
				month
			) VALUES ";	
		}
		$sql .= "(" ;

		$vals = explode(",",$line);
		foreach($vals as $i => $val){
			if($val == ''){
				$vals[$i] = 0;
			}
		}
		$sql .= implode(",",array_slice($vals,0,-1));
		if($iter % 10000 < 9999){
			$sql .= "), ";
		} else{
			$sql .= ");";
		}

		if ($iter % 10000 == 9999 && $GLOBALS['conn']->query($sql) !== TRUE) {
		    echo "Error: " . $sql . "<br>" . $GLOBALS['conn']->error;
		    exit;
		}
		$iter++;
	}
	$firstline = false;
}
 $sql = rtrim($sql, ", ") . ';';
if ($GLOBALS['conn']->query($sql) !== TRUE) {
    echo "Error: " . $sql . "<br>" . $GLOBALS['conn']->error;
    exit;
}
fclose($myfile);