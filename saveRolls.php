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
$user = User::getCurrentUser($_SESSION);
$user->rolls = [];
$rollsInput = json_decode(file_get_contents('php://input'),TRUE);
foreach ( $rollsInput as $rollInput )
{
	$roll = Roll::fromMap($rollInput);
	$user->addRoll($roll);
}
$user->saveRolls();