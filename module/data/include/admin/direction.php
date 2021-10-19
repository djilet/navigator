<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataDirection extends LocalObject
{
	var $module;
	
	function DataDirection($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
	}

	function LoadByID($id)
	{
		$query = "SELECT d.*				
					FROM `data_direction` AS d						
					WHERE d.DirectionID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("DirectionID"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function Save()
	{
	    $result1 = $this->Validate();
		if (!$result1)
		{
		    return false;
		}

		$stmt = GetStatement();

		if ($this->GetIntProperty("DirectionID") > 0)
		{
			$query = "UPDATE `data_direction` SET
						BigDirectionID=".$this->GetPropertyForSQL("BigDirectionID").", 
						Title=".$this->GetPropertyForSQL("Title").", 
						StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
						Number=".$this->GetPropertyForSQL("Number").", 
						VideoURL=".$this->GetPropertyForSQL("VideoURL").", 
						SortOrder=".$this->GetIntProperty("SortOrder")." 
				WHERE DirectionID=".$this->GetIntProperty("DirectionID");
		}
		else
		{
			$query = "INSERT INTO `data_direction` SET
						BigDirectionID=".$this->GetPropertyForSQL("BigDirectionID").", 
						Title=".$this->GetPropertyForSQL("Title").", 
						StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
						Number=".$this->GetPropertyForSQL("Number").", 
						VideoURL=".$this->GetPropertyForSQL("VideoURL").", 
						SortOrder=".$this->GetIntProperty("SortOrder");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("DirectionID") > 0)
				$this->SetProperty("DirectionID", $stmt->GetLastInsertID());

			return true;
		}
		else
		{
			$this->AddError("sql-error");
			return false;
		}
	}
	
	function Validate()
	{
		if(!$this->ValidateNotEmpty("Title"))
			$this->AddError("direction-title-empty", $this->module);

		if(!$this->ValidateNotEmpty("StaticPath"))
			$this->AddError("direction-static-path-empty", $this->module);
			
		return !$this->HasErrors();
	}

}

