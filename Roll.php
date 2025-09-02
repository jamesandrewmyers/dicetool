<?php
include_once "constants.php";

class Roll
{
	public $id = 0;
	public $rollString = "";
	public $note = "";
	public $highlight = "";
	public $userId = 0;
	public $rollsetId = 0;

	public function __construct()
	{
	}

	public static function fromMap($rollMap)
	{
		$roll = new Roll();
		if ( isset($rollMap["id"]) )
		{
			$roll->id = $rollMap["id"];
		}
		if ( isset($rollMap["rollString"]) )
		{
			$roll->rollString = $rollMap["rollString"];
		}
		if ( isset($rollMap["note"]) )
		{
			$roll->note = $rollMap["note"];
		}
		if ( isset($rollMap["highlight"]) )
		{
			$roll->highlight = $rollMap["highlight"];
		}
		if ( isset($rollMap["userId"]) )
		{
			$roll->userId = $rollMap["userId"];
		}
		if ( isset($rollMap["rollsetId"]) )
		{
			$roll->rollsetId = $rollMap["rollsetId"];
		}
		return $roll;
	}
}