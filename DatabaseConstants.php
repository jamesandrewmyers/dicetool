<?php

include_once "constants.php";

class DatabaseConstants
{
	public static $DB_USER = 'jmyers';
	public static $DB_PASSWORD = '1nopass2';
	public static $DSN = 'mysql:host=localhost;dbname=jmyers_dicetool;charset=utf8';
	public static $DSN_OPT = array(
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	);
}