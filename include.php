<?php

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

session_start();

// timeout session after 30 minutes of inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800))
{
	session_unset();
	session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();