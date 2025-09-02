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
$user = new User();
$user->email = $_POST['username'];
$user->password = $_POST['password'];
$user->save();