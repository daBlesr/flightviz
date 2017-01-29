<?php
include_once('../controllers/db.php');


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
	case 'connections-to-airport':
		connectionsToAirport($_GET['from'], $_GET['to']);
		break;
	case 'compute-flight-delays-for-all-airports':
		computeFlightDelaysForAllAirports();
		break;
	case 'compute-flight-delays-airports':
		computeFlightDelaysForAirports($_GET['a']);
		break;
	case 'compute-flight-carriers-for-all-airports':
		computeFlightCarriersForAllAirports();
		break;
	case 'compute-flight-carriers-airports':
		computeFlightCarriersForAirports($_GET['a']);
		break;
	case 'compute-flight-delays-by-airline':
		computeFlightDelaysByAirline($_GET['a']);
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
		" 	SELECT x.dest as dest, x.c as total_traffic, x.c as incoming, a1.latitude as latitude1, a1.longitude as longitude1, a2.latitude as latitude2, 
				a2.longitude as longitude2 
			FROM airports a1, airports a2,
				( SELECT origin, dest, count(*) as c FROM international_flights WHERE origin = '$iata' and dest IN ('$airports') group by dest ) as x
		  	WHERE a1.IATA = x.origin and a2.IATA = x.dest

		  	UNION

		  	SELECT x.dest as dest, x.c as total_traffic, x.c as outgoing, a1.latitude as latitude1, a1.longitude as longitude1, a2.latitude as latitude2, 
				a2.longitude as longitude2 
			FROM airports a1, airports a2,
				( SELECT origin, dest, count(*) as c FROM flights WHERE origin = '$iata' and dest IN ('$airports') group by dest ) as x
		  	WHERE a1.IATA = x.origin and a2.IATA = x.dest
		"
	);
}


function airports($amount){
	getJSONFromQuery(
		" SELECT name, city, country, x.airport as airport, x.c as total_traffic, latitude, longitude FROM airports, 
			( SELECT dest as airport, count(dest) as c FROM international_flights group by dest order by c DESC LIMIT $amount ) as x
		 	WHERE x.airport = airports.IATA"
	);
}

function connectionsToAirport($airport_from, $airport_to){

	// 1. INTERNATIONAL AIRPORT -> TRANSFER IN AMERICA -> AIRPORT IN AMERICA
	// 2. INTERNATIONAL AIRPORT -> AIRPORT IN AMERICA
	// NOT DONE : 
	// 3. AIRPORT IN AMERICA -> AIRPORT IN AMERICA -> AIRPORT IN AMERICA
	// 4. AIRPORT IN AMERICA -> AIRPORT IN AMERICA
	getJSONFromQuery(
		"  SELECT ifl.origin as origin, fl.origin as transfer, fl.dest as dest, a1.latitude as latitude1, a1.longitude as longitude1, a2.latitude as latitude2, 	a2.longitude as longitude2, a3.latitude as latitude3, a3.longitude as longitude3 , count(*) as total_traffic 
			FROM flights fl, international_flights ifl, airports a1, airports a2, airports a3
			WHERE fl.dest = '$airport_to' and ifl.origin = '$airport_from' and ifl.dest = fl.origin and 
				a1.IATA = ifl.origin and a2.IATA = ifl.dest and a3.IATA = fl.dest
			GROUP BY fl.origin

			UNION

			SELECT ifl.origin as origin, NULL as transfer, ifl.dest as dest, a1.latitude as latitude1, a1.longitude as longitude1,
				NULL as latitude2, NULL as longitude2, a3.latitude as latitude3, a3.longitude as longitude3 , count(*) as total_traffic 
			FROM international_flights ifl, airports a1, airports a3
			WHERE ifl.dest = '$airport_to' and ifl.origin = '$airport_from' and 
				a1.IATA = ifl.origin and a3.IATA = ifl.dest
		"
	);
}

function computeFlightDelaysForAllAirports(){
	getJSONFromQuery(
		" SELECT AVG(flights.dep_delay) as delay, flights.origin, flights.fl_date, flights.origin_city_name, count(*) as c from flights,
			(SELECT origin FROM flights group by origin order by count(*) limit 20) as x
			where x.origin = flights.origin
		 	group by origin, Month(fl_date) order by fl_date, origin
		"
	); 
}

function computeFlightDelaysForAirports($airports){
	$airports = implode("','",explode(',',$airports));
	getJSONFromQuery(
		" SELECT AVG(flights.dep_delay) as delay, flights.origin, flights.fl_date, flights.origin_city_name, count(*) as c from flights
			WHERE flights.origin IN ('$airports')
		 	group by origin, MONTH(fl_date) order by fl_date, origin
		"
	); 
}

function computeFlightDelaysByAirline($airport){
	getJSONFromQuery(
		" SELECT AVG(flights.dep_delay) as delay, flights.carrier as origin, flights.fl_date, flights.carrier as origin_city_name, carrier, count(*) as c from flights
			WHERE flights.origin = '$airport'
		 	group by carrier, MONTH(fl_date) order by fl_date, carrier
		"
	); 
}

function computeFlightCarriersForAllAirports(){
	getJSONFromQuery(
		" SELECT * from 
			(SELECT origin, count(*) as total from flights group by origin) as y, 
			(SELECT carrier, count(*) as total2 from flights group by carrier) as z, 
			(SELECT flights.carrier, flights.origin, flights.origin_city_name, count(*) as c from flights group by origin, flights.carrier ) as x
			WHERE y.origin = x.origin and z.carrier = x.carrier order by y.total desc, z.total2 desc
		"
	); 
}

function computeFlightCarriersForAirports($airports){
	$airports = implode("','",explode(',',$airports));
	getJSONFromQuery(
		" SELECT * from 
			(SELECT origin, count(*) as total from flights where flights.origin IN ('$airports') group by origin) as y, 
			(SELECT carrier, count(*) as total2 from flights group by carrier) as z, 
			(SELECT flights.carrier, flights.origin, flights.origin_city_name, count(*) as c from flights 
				where flights.origin IN ('$airports') group by origin, flights.carrier ) as x
			WHERE y.origin = x.origin and z.carrier = x.carrier order by y.total desc, z.total2 desc
		"
	); 
}