<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobject.php");

class DataDevice extends LocalObject
{
	var $module;

	function DataDevice($module, $data = array())
	{
		parent::LocalObject($data);
		$this->module = $module;
	}
	
	function LoadByID($id)
	{
		$query = "SELECT DeviceID, Created FROM `data_device` 
					WHERE DeviceID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);
		if($this->GetProperty("DeviceID"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

