<?php

$servername = "localhost";
$username = "root";
$password = "abra";

$GLOBALS['conn'] = new mysqli($servername, $username, $password, "flights");

if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}