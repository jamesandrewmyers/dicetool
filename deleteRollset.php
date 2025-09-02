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
$rollsetIdMap = json_decode(file_get_contents('php://input'),TRUE);
$rollset = Rollset::fromMap($rollsetIdMap);
$rollset->userId = $user->id;
$rollset->delete();