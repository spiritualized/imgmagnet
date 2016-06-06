<?php

// includes - self explanatory
include_once('config.php');
include_once('mysql.php');

// Create the database adapter object (and open/connect to/select db)
$db = new DBLayer($db_host, $db_username, $db_password, $db_name);

if(get_magic_quotes_gpc())
{
	echo 'PHP\'s magic quotes feature is on. Please disable.';
	exit;
}



?>
