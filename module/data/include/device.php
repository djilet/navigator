<?php
require_once(dirname(__FILE__)."/../init.php");
es_include("localobject.php");

class DataDevice extends LocalObject
{
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObject($data);
		$this->module = $module;
	}
	
	public function LoadByID($id)
	{
		$query = "SELECT DeviceID, Created FROM `data_device` WHERE DeviceID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);
		return $this->GetProperty("DeviceID");
	}
	
	public function IsDeviceRegistered($deviceID)
	{
		$stmt = GetStatement();
		$query = "SELECT COUNT(`DeviceID`) FROM `data_device` WHERE `DeviceID`=".Connection::GetSQLString($deviceID);
		if($deviceID && $stmt->FetchField($query) > 0)
			return true;
		else
			return false;
	}

	public function Register($deviceID, $client)
	{
		$stmt = GetStatement();
		$privateKey = $this->GeneratePrivateKey($deviceID);
		$query = "SELECT COUNT(`DeviceID`) FROM `data_device` WHERE `DeviceID`=".Connection::GetSQLString($deviceID);
		if($stmt->FetchField($query) == 0)
		{
			$query = "INSERT INTO `data_device` 
						SET DeviceID=".Connection::GetSQLString($deviceID).", 
							PrivateKey=".Connection::GetSQLString($privateKey).", 
							Client=".Connection::GetSQLString($client).", 
							Created=".Connection::GetSQLString(GetCurrentDateTime());
		}
		else
		{
			$query = "UPDATE `data_device` 
						SET PrivateKey=".Connection::GetSQLString($privateKey).", Client=".Connection::GetSQLString($client)."  
					WHERE DeviceID=".Connection::GetSQLString($deviceID);
		}
		if($stmt->Execute($query))
			return $privateKey;
		else
			return false;
	}
	
	public function SavePushToken($token, $client, $deviceID)
	{
		if(strlen($token) > 0)
		{
			$stmt = GetStatement();
			$query = "DELETE FROM `data_push_token` WHERE DeviceID=".Connection::GetSQLString($deviceID);
			$stmt->Execute($query);
				
			$query = "INSERT INTO `data_push_token`
				SET DeviceID=".Connection::GetSQLString($deviceID).",
				Client=".Connection::GetSQLString($client).",
				Token=".Connection::GetSQLString($token);
			if($stmt->Execute($query))
			{
				return true;
			}
		}
		return false;
	}
	
	public function CheckRequestSign(LocalObject $request)
	{
	    $stmt = GetStatement();
	    $query = "SELECT PrivateKey FROM `data_device` WHERE DeviceID=".$request->GetPropertyForSQL("AuthDeviceID");
	    $privateKey = $stmt->FetchField($query);
	    if(!$privateKey)
	    {
	        return false;
	    }
	    else
	    {
	        ksort($request->_properties);
	        $paramStrings = array();
	        foreach ($request->GetProperties() as $key => $value)
	        {
	            if($key != "Sign" && !is_array($value))
	                $paramStrings[] = $key."=".$value;
	        }
	        $correctSign = md5(implode("&", $paramStrings).$privateKey);
	        if($request->GetProperty("Sign") == $correctSign)
	        {
	            return true;
	        }
	        else
	        {
	            return false;
	        }
	    }
	}
	
	private function GeneratePrivateKey($deviceID)
	{
		return md5($deviceID.date("U").rand(1000, 9999));
	}
}

