<?php
include_once('db.php');


switch($_GET['q']){
	case 'flights':
		flights();
		break;
	case 'airports':
		airports();
		break;
	case 'most-incoming':
		mostIncoming();
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
		echo "Error: " . $sql . "<br>" . $GLOBALS['conn']->error;
	}
}


function flights(){
	getJSONFromQuery(
		" SELECT * FROM flights LIMIT 5"
	);
}

function airports(){
	getJSONFromQuery(
		" SELECT z.airport, z.total_traffic, z.incoming, z.outgoing, latitude, longitude FROM airports, 
			( SELECT x.airport as airport, (x.c + y.c) as total_traffic, x.c as incoming, y.c as outgoing from
				( SELECT dest as airport, count(dest) as c FROM flights group by dest order by c DESC LIMIT 15 ) as x,
				( SELECT origin as airport, count(origin) as c FROM flights group by origin order by c DESC LIMIT 15 ) as y
				WHERE x.airport = y.airport order by total_traffic DESC
			) as z
		 WHERE z.airport = airports.IATA"
	);
}

function mostIncoming(){
	getJSONFromQuery(
		
	);	
}