<?php
try
{
	include_once "include.php";
	include_once 'User.php';
	include_once 'Rollset.php';
}
catch( ErrorException $e )
{
	echo "Unable to include required files." . $e->getMessage();
}
$user = User::getCurrentUser($_SESSION);
$user->loadRollsets();
echo json_encode($user->rollsets);