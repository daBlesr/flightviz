<?php
include_once('../controllers/db.php');

$sql = "SELECT origin from flights group by origin order by count(*) desc limit 80";

$result_as_array = [];

if ($query_result = $GLOBALS['conn']->query($sql)) {

    while($record = $query_result->fetch_array(MYSQL_ASSOC)) {
        $result_as_array[] = $record['origin'];
    }
    
    var_dump($result_as_array);
    $sql = "DELETE FROM flights where origin NOT IN ('".implode("', '",$result_as_array)."')";
    
    echo $sql;
    //exit;
    $GLOBALS['conn']->query($sql);
} else{
	echo "Error: " . $query . "<br>" . $GLOBALS['conn']->error;
}