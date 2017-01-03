<?php
include_once('../controllers/db.php');

$iter = 0;
$myfile = fopen("airports.dat.txt", "r") or die("Unable to open file!");
while (($line = fgets($myfile)) !== false) {
	if($iter % 10000 == 0){
		$sql = "INSERT INTO airports (
			airport_id,
			name,
			city,
			country,
			IATA,
			ICAO,
			latitude,
			longitude,
			altitude,
			timezone,
			DST,
			TZ
		) VALUES ";	
	}
	$sql .= "(" . $line;
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
 $sql = rtrim($sql, ", ") . ';';
if ($GLOBALS['conn']->query($sql) !== TRUE) {
    echo "Error: " . $sql . "<br>" . $GLOBALS['conn']->error;
    exit;
}

fclose($myfile);

