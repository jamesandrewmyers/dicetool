<?php
try
{
	include_once "include.php";
	include_once 'User.php';
}
catch( ErrorException $e )
{
	echo "Unable to include required files." . $e->getMessage();
}
User::clearCurrentUser($_SESSION);