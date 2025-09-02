<?php
include_once "constants.php";
include_once "DatabaseConstants.php";

class User
{
	public $rolls = [];
	public $rollsets = [];
	public $updatePassword = false;
	public $email;
	public $password;
	public $id;

	public static function sessionExists($session)
	{
		if ( isset($session['userId']) )
		{
			return true;
		}
		return false;
	}
	public static function requireSession($session)
	{
		if ( !User::sessionExists($session) )
		{
			http_response_code(401);
			exit;
		}
	}
	public static function authenticate($username,$password)
	{
		$user = User::getUserByLogin($username);
		if ( $user
		&& password_verify($password,$user->password) )
		{
			return $user->id;
		}
		return 0;
	}
	public static function getUser($id)
	{
		if ( $id <= 0 )
		{
			return null;
		}
		$conn = new mysqli('localhost', DB_USER, DB_PASS, 'jmyers_dicetool');
		try
		{
			if ($result = $conn->query("SELECT * FROM user WHERE id = {$id} LIMIT 1"))
			{
				$userObj = $result->fetch_object('User');
				$result->close();
				return $userObj;
			}
		}
		catch (Exception $ex)
		{
			if (mysqli_connect_errno())
			{
				printf("Database connection failure: %s\n", mysqli_connect_error());
				return null;
			}
		}
		finally
		{
			$conn->close();
		}
		return null;
	}
	public static function getCurrentUserId($session)
	{
		if ( isset($session['userId']) )
		{
			return $session['userId'];
		}
		http_response_code(401);
		exit;
	}
	public static function getCurrentUser($session)
	{
		return User::getUser(User::getCurrentUserId($session));
	}
	public static function clearCurrentUser()
	{
		unset($GLOBALS['_SESSION']['userId']);
	}
	public static function getUserByLogin($loginId)
	{
		//$conn = new mysqli('localhost', DB_USER, DB_PASS, 'jmyers_dicetool');
		/*
		$dsn = "mysql:host=localhost;dbname=jmyers_dicetool;charset=utf8";
		$opt = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		*/
		$pdo = new PDO(DatabaseConstants::$DSN,DatabaseConstants::$DB_USER,DatabaseConstants::$DB_PASSWORD, DatabaseConstants::$DSN_OPT);
		try
		{
			$st = $pdo->prepare("select * from user where email = ?");
			if ($st->execute(array($loginId)))
			{
				return $st->fetchObject('User');
			}
			return null;
		}
		catch (Exception $ex)
		{
			printf("Database connection failure: %s\n", $ex->getMessage());
			return null;
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
			$st = null;
			if ( $this->id != null )
			{
				if ($this->updatePassword)
				{
					$st = $pdo->prepare("update user set email = ?, password = ?, modified = NOW() where id = ?");
					$st->execute(array($this->email,password_hash($this->password, PASSWORD_DEFAULT),$this->id));
				}
				else
				{
					$st = $pdo->prepare("update user set email = ?, modified = NOW() where id = ?");
					$st->execute(array($this->email,$this->id));
				}
			}
			else
			{
				$st = $pdo->prepare("insert into user (email,password,created,modified) values (?,?,NOW(),NOW())");
				$newemail = $this->email;
				$newpassword = $this->password;
				$newpassword = password_hash($newpassword, PASSWORD_DEFAULT);
				$st->execute(array($newemail,$newpassword));
			}
		}
		catch (Exception $ex)
		{
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

	public function loadRolls($rollsetId)
	{
		$conn = new mysqli('localhost', DB_USER, DB_PASS, 'jmyers_dicetool');
		try
		{
			$st = $conn->prepare("select id, rollString, note, highlight, rollsetId from roll where userId = ? AND rollsetId = ?");
			$st->bind_param("ii", $this->id, $rollsetId);

			if (!$st->execute())
			{
				trigger_error("Error loading user rolls: " . $st->error);
				return;
			}
			$st->bind_result($id,$rollString,$note,$highlight,$rollsetId);
			while ( $st->fetch() )
			{
				$roll = new Roll();
				$roll->id = $id;
				$roll->rollString = $rollString;
				$roll->note = $note;
				$roll->highlight = $highlight;
				$roll->rollsetId = $rollsetId;
				$roll->userId = $this->id;
				$this->addRoll($roll);
			}
			while ( false ) {}
		}
		catch (Exception $ex)
		{
			if (mysqli_connect_errno())
			{
				trigger_error("Database connection failure: " . mysqli_connect_error());
				return;
			}
		}
		finally
		{
			$conn->close();
		}
	}

	public function saveRolls($rollsetId)
	{
		$conn = new mysqli('localhost', DB_USER, DB_PASS, 'jmyers_dicetool');
		try
		{
			$st = null;
			$conn->begin_transaction();
			$st = $conn->prepare("delete from roll where userId = ? and rollsetId = ?");
			$st->bind_param("ii", $this->id,$rollsetId);
			if (!$st->execute())
			{
				$conn->rollback();
				trigger_error("Error deleting user rolls prior to save: " . $st->error);
				return;
			}
			//$st->close();
			$st2 = $conn->prepare("insert into roll (rollString,userId,note,highlight,rollsetId) values (?,?,?,?,?)");
			foreach ( $this->rolls as $roll)
			{
				$st2->bind_param("sissi", $roll->rollString, $this->id, $roll->note,$roll->highlight,$roll->rollsetId);
				if ( !$st2->execute() )
				{
					$conn->rollback();
					trigger_error("Error inserting user rolls during save: " . $st2->error);
					return;
				}
			}
			$conn->commit();
		}
		catch (Exception $ex)
		{
			if (mysqli_connect_errno())
			{
				trigger_error("Database connection failure: " . mysqli_connect_error());
				return;
			}
		}
		finally
		{
			$conn->close();
		}
	}

	public function addRoll($rollObj)
	{
		array_push($this->rolls,$rollObj);
	}

	public function loadRollsets()
	{
		$conn = new mysqli('localhost', DB_USER, DB_PASS, 'jmyers_dicetool');
		try
		{
			$st = $conn->prepare("select id, name, notes from rollset where userId = ? order by name ASC");
			$st->bind_param("i", $this->id);

			if (!$st->execute())
			{
				trigger_error("Error loading user rollsets: " . $st->error);
				return;
			}
			$st->bind_result($id,$name,$notes);
			while ( $st->fetch() )
			{
				$rollset = new Rollset();
				$rollset->id = $id;
				$rollset->userId = $this->id;
				$rollset->name = $name;
				$rollset->notes = $notes;
				$this->addrollset($rollset);
			}
			while ( false ) {}
		}
		catch (Exception $ex)
		{
			if (mysqli_connect_errno())
			{
				trigger_error("Database connection failure: " . mysqli_connect_error());
				return;
			}
		}
		finally
		{
			$conn->close();
		}
	}

	public function saveRollsets()
	{
		$conn = new mysqli('localhost', DB_USER, DB_PASS, 'jmyers_dicetool');
		try
		{
			$st = null;
			$conn->begin_transaction();
			$st = $conn->prepare("delete from rollset where userId = ?");
			$st->bind_param("i", $this->id);
			if (!$st->execute())
			{
				$conn->rollback();
				trigger_error("Error deleting user rollsets prior to save: " . $st->error);
				return;
			}
			$st2 = $conn->prepare("insert into rollset (userId,name,notes) values (?,?,?)");
			foreach ( $this->rollsets as $rollset)
			{
				$st2->bind_param("iss", $rollset->userId, $this->name, $rollset->notes);
				if ( !$st2->execute() )
				{
					$conn->rollback();
					trigger_error("Error inserting user rollsets during save: " . $st2->error);
					return;
				}
			}
			$conn->commit();
		}
		catch (Exception $ex)
		{
			if (mysqli_connect_errno())
			{
				trigger_error("Database connection failure: " . mysqli_connect_error());
				return;
			}
		}
		finally
		{
			$conn->close();
		}
	}

	public function addrollset($rollsetObj)
	{
		array_push($this->rollsets,$rollsetObj);
	}
}