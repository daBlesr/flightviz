<?php
include_once('../controllers/db.php');

$firstline = true;
$iter = 0;
$myfile = fopen("700492732_T_ONTIME.csv", "r") or die("Unable to open file!");
while (($line = fgets($myfile)) !== false) {
	if(!$firstline){

		if($iter % 10000 == 0){
			echo $iter . "\n";
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
				cancelled
			) VALUES ";	
		}
		$sql .= "(";
		$vals = explode(",",$line);
		$sql .= '"' . $vals[0] . '",';
		foreach($vals as $i => $val){
			if($val == ''){
				$vals[$i] = 0;
			}
		}
		$sql .= implode(",",array_slice($vals,1,sizeof($vals) - 2));
		if($iter % 10000 < 9999){
			$sql .= "), ";
		} else{
			$sql .= ");";
		}

		//"FL_DATE" 1
		//,"AIRLINE_ID",2
		//"CARRIER",3
		//"ORIGIN_AIRPORT_ID",4
		//"ORIGIN_AIRPORT_SEQ_ID",5
		//"ORIGIN_CITY_MARKET_ID",6
		//"ORIGIN",7
		//"ORIGIN_CITY_NAME",8
		//"DEST_AIRPORT_ID",9
		//"DEST_AIRPORT_SEQ_ID",10
		//"DEST_CITY_MARKET_ID",11
		//"DEST",12
		//"DEST_CITY_NAME",13
		//"CRS_DEP_TIME",14
		//"DEP_TIME",15
		//"DEP_DELAY",16
		//"CRS_ARR_TIME",17
		//"ARR_TIME",18
		//"ARR_DELAY",19
		//"CANCELLED",20
		

		if ($iter % 10000 == 9999 && $GLOBALS['conn']->query($sql) !== TRUE) {
		    echo "Error: " . $sql . "<br>" . $GLOBALS['conn']->error;
		    exit;
		}

		$iter++;
	}
	$firstline = false;
}
$sql .= ");";
if ($GLOBALS['conn']->query($sql) !== TRUE) {
    echo "Error: " . $sql . "<br>" . $GLOBALS['conn']->error;
    exit;
}
fclose($myfile);