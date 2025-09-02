<?php
try
{
	include_once "include.php";
	include_once 'User.php';
	include_once 'Roll.php';
}
catch( ErrorException $e )
{
	echo "Unable to include required files." . $e->getMessage();
}
$rollsetIdMap = json_decode(file_get_contents('php://input'),TRUE);
$rollsetId = $rollsetIdMap['rollsetId'];
$user = User::getCurrentUser($_SESSION);
$user->loadRolls($rollsetId);
echo json_encode($user->rolls);