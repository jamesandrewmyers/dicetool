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

$username = null;
$password = null;
if (isset($_POST['username']))
{
	$username = $_POST['username'];
}
if (isset($_POST['password']))
{
	$password = $_POST['password'];
}
if ($username != null && $password != null)
{
	$userId = User::authenticate($username,$password);
	if ( $userId )
	{
		$_SESSION["userId"] = $userId;
	}
	else
	{
		http_response_code(401);
	}
}
else
{
	User::requireSession($_SESSION);
}