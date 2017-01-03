<?php
include_once('db.php');


switch($_GET['q']){
	case 'all-airports':
		allAirports();
		break;
	case 'flights':
		flights($_POST['airports'], $_GET['iata']);
		break;
	case 'airports':
		airports($_GET['a']);
		break;
	case 'flights-by-airport':
		getFlightsByAirports($_POST['airports']);
		break;
}

function getJSONFromQuery($query){
	
	$result_as_array = [];

	if ($query_result = $GLOBALS['conn']->query($query)) {

	    while($record = $query_result->fetch_array(MYSQL_ASSOC)) {
	            $result_as_array[] = $record;
	    }
	    echo json_encode($result_as_array);
	} else{
		echo "Error: " . $query . "<br>" . $GLOBALS['conn']->error;
	}
}


function flights($airports, $iata){
	$airports = implode("','",$airports);
	getJSONFromQuery(
		" 	SELECT x.dest as dest, y.dest as dest2, (x.c + y.c) as total_traffic, x.c as incoming, y.c as outgoing, a1.latitude as latitude1, a1.longitude as longitude1, a2.latitude as latitude2, a2.longitude as longitude2  from airports a1, airports a2,
				( SELECT origin, dest, count(*) as c FROM international_flights WHERE origin = '$iata' and dest IN ('$airports') group by dest, origin ) as x,
				( SELECT origin, dest, count(*) as c FROM international_flights WHERE origin IN ('$airports') and dest = '$iata' group by origin, dest ) as y
		  	WHERE x.dest = y.origin and x.origin = y.dest  and a1.IATA = x.origin and a2.IATA = x.dest
		"
	);
}

function allAirports(){
	getJSONFromQuery(
		"SELECT id, city, name, latitude, longitude FROM airports"
	);
}


function airports($amount){
	getJSONFromQuery(
		" SELECT name, city, z.airport, z.total_traffic, z.incoming, z.outgoing, latitude, longitude FROM airports, 
			( SELECT x.airport as airport, (x.c + y.c) as total_traffic, x.c as incoming, y.c as outgoing from
				( SELECT dest as airport, count(dest) as c FROM international_flights group by dest order by c DESC LIMIT $amount ) as x,
				( SELECT origin as airport, count(origin) as c FROM international_flights group by origin order by c DESC LIMIT $amount ) as y
				WHERE x.airport = y.airport order by total_traffic DESC
			) as z
		 WHERE z.airport = airports.IATA"
	);
}

function getFlightsByAirports($airports){
	$airports = implode("','",$airports);
	getJSONFromQuery(
		" SELECT x.dest as dest, y.dest as dest2, (x.c + y.c) as total_traffic, a1.latitude as latitude1, a1.longitude as longitude1, a2.latitude as latitude2, a2.longitude as longitude2 FROM airports a1, airports a2,
				( SELECT origin, dest, count(*) as c FROM international_flights WHERE origin IN ('$airports') and dest IN ('$airports') group by dest, origin ) as x,
				( SELECT origin, dest, count(*) as c FROM international_flights WHERE origin IN ('$airports') and dest IN ('$airports') group by origin, dest ) as y
		  	WHERE x.dest = y.origin and x.origin = y.dest and a1.IATA = x.origin and a2.IATA = x.dest
		"
	);
}
