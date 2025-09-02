<?php
include_once "constants.php";

class Rollset
{
	public $id = 0;
	public $name = "";
	public $userId = 0;
	public $notes = "";
	public $rolls = [];

	public function __construct()
	{
	}

	public static function fromMap($rollsetMap)
	{
		$rollset = new Rollset();
		if ( isset($rollsetMap["id"]) )
		{
			$rollset->id = $rollsetMap["id"];
		}
		if ( isset($rollsetMap["name"]) )
		{
			$rollset->name = $rollsetMap["name"];
		}
		if ( isset($rollsetMap["userId"]) )
		{
			$rollset->userId = $rollsetMap["userId"];
		}
		if ( isset($rollsetMap["notes"]) )
		{
			$rollset->notes = $rollsetMap["notes"];
		}
		if ( isset($rollsetMap["rolls"]) )
		{
			$rollset->rolls = $rollsetMap["rolls"];
		}
		return $rollset;
	}

	public function delete()
	{
		$pdo = new PDO(DatabaseConstants::$DSN,DatabaseConstants::$DB_USER,DatabaseConstants::$DB_PASSWORD, DatabaseConstants::$DSN_OPT);
		try
		{
			$pdo->beginTransaction();
			if ( $this->id != null
			&& $this->id != 0
			&& $this->userId != null
			&& $this->userId != 0)
			{
				$st = $pdo->prepare("delete from rollset where id = ? and userId = ?");
				$st->execute(array($this->id,$this->userId));
			}
			else
			{
				$pdo->rollBack();
				trigger_error("Error deleting Rollset: id and userId are required.");
				return;
			}
			$st = null;
			$st = $pdo->prepare("delete from roll where rollsetId = :rollsetId and userId = :userId");
			$st->bindParam(":rollsetId", $this->id, PDO::PARAM_INT);
			$st->bindParam(":userId", $this->userId, PDO::PARAM_INT);
			if (!$st->execute())
			{
				$pdo->rollBack();
				trigger_error("Error deleting Rolls after deleting Rollset: " . $st->error);
				return;
			}
			$pdo->commit();
		}
		catch (Exception $ex)
		{
			$pdo->rollBack();
			trigger_error($ex);
			if (mysqli_connect_errno())
			{
				trigger_error("Database connection failure: " . mysqli_connect_error());
				return;
			}
		}
		finally
		{
			$pdo = null;
		}
	}

	public function save()
	{
		$pdo = new PDO(DatabaseConstants::$DSN,DatabaseConstants::$DB_USER,DatabaseConstants::$DB_PASSWORD, DatabaseConstants::$DSN_OPT);
		try
		{
			if ( $this->id != null )
			{
				$st = $pdo->prepare("update rollset set name = ?, notes = ? where id = ? and userId = ?");
				$st->execute(array($this->name,$this->notes,$this->id,$this->userId));
			}
			else
			{
				$st = $pdo->prepare("insert into rollset (name,userId,notes) values (?,?,?)");
				$newName = $this->name;
				$newUserId = $this->userId;
				$newNotes = $this->notes;
				$st->execute(array($newName,$newUserId,$newNotes));
				$this->id = $pdo->lastInsertId();
			}
			$st = null;
			$pdo->beginTransaction();
			$st = $pdo->prepare("delete from roll where rollsetId = :rollsetId");
			$st->bindParam(":rollsetId", $this->id, PDO::PARAM_INT);
			if (!$st->execute())
			{
				$pdo->rollBack();
				trigger_error("Error deleting user rolls prior to save: " . $st->error);
				return;
			}
			$st2 = $pdo->prepare("insert into roll (rollString,userId,note,highlight,rollsetId) values (?,?,?,?,?)");
			foreach ( $this->rolls as $roll)
			{
				$st2->bindParam(1, $roll['rollString'], PDO::PARAM_STR);
				$st2->bindParam(2, $this->userId, PDO::PARAM_INT);
				$st2->bindParam(3, $roll['note'], PDO::PARAM_STR);
				$st2->bindParam(4, $roll['highlight'], PDO::PARAM_STR);
				$st2->bindParam(5, $this->id, PDO::PARAM_INT);
				if ( !$st2->execute() )
				{
					$pdo->rollBack();
					trigger_error("Error inserting user rolls during save: " . $st2->error);
					return;
				}
			}
			$pdo->commit();
		}
		catch (Exception $ex)
		{
			$pdo->rollBack();
			trigger_error($ex);
			if (mysqli_connect_errno())
			{
				trigger_error("Database connection failure: " . mysqli_connect_error());
				return;
			}
		}
		finally
		{
			$pdo = null;
		}
	}
}