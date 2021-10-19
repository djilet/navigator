<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobject.php");

class DataEventRegistration extends LocalObject
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObject($data);
		$this->module = $module;
	}

	public function Create($deviceID, $eventID, $firstName, $lastName, $city, $who, $class, $phone, $email)
	{
		$stmt = GetStatement();

        $userID = $stmt->FetchField('SELECT ItemID FROM `user_item2device` WHERE Device='.Connection::GetSQLString($deviceID));

		$query = "INSERT INTO `event_registrations`
						SET DeviceID=".Connection::GetSQLString($deviceID).",
							UserID=".intval($userID).",
							EventID=".intval($eventID).",
							FirstName=".Connection::GetSQLString($firstName).",
							LastName=".Connection::GetSQLString($lastName).",
							City=".Connection::GetSQLString($city).",
							Who=".Connection::GetSQLString($who).",
							Class=".Connection::GetSQLString($class).",
							Phone=".Connection::GetSQLString($phone).",
							Email=".Connection::GetSQLString($email).",
							Source = 'app'";
		
		$maxRegistrationID = $stmt->FetchField("SELECT MAX(RegistrationID) FROM `event_registrations` WHERE RegistrationID<100000000");
		$query .= ", RegistrationID=".(intval($maxRegistrationID) + 1);

		$stmt->Execute($query);
		return true;
	}
}
