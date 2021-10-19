<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataType extends LocalObject
{
	var $_acceptMimeTypes = array(
		'image/png',
		'image/x-png',
		'image/gif',
		'image/jpeg',
		'image/pjpeg'
	);
	var $module;
	var $params;

	function DataType($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;

		$this->params = array("Type" => array());
		$this->params["Type"] = LoadImageConfig("TypeImage", $this->module, DATA_TYPE_IMAGE);
	}

	function LoadByID($id)
	{
		$query = "SELECT t.TypeID, t.Title, t.TypeImage, t.TypeImageConfig, t.SortOrder  				
					FROM `data_type` AS t						
					WHERE t.TypeID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("TypeID"))
		{
			$this->_PrepareContentBeforeShow();
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function _PrepareContentBeforeShow()
	{
		$this->_PrepareImages("Type");
	}
		
	function _PrepareImages($key)
	{
		PrepareImagePath($this->_properties, $key, $this->params[$key], "type/");
	}
	
	function GetImageParams($key)
	{
		$paramList = array();
		for ($i = 0; $i < count($this->params[$key]); $i++)
		{
			$paramList[] = array(
				"Name" => $this->params[$key][$i]['Name'],
				"SourceName" => $this->params[$key][$i]['SourceName'],
				"Width" => $this->params[$key][$i]['Width'],
				"Height" => $this->params[$key][$i]['Height'],
				"Resize" => $this->params[$key][$i]['Resize'],
				"X1" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."X1"),
				"Y1" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."Y1"),
				"X2" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."X2"),
				"Y2" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."Y2")
			);
		}
		return $paramList;
	}
		
	function Save()
	{
	    $result1 = $this->Validate();
	    $result2 = $this->SaveTypeImage($this->GetProperty("SavedTypeImage"), "Type");

		if (!$result1 || !$result2)
		{
		    $this->_PrepareContentBeforeShow();
			return false;
		}

		$stmt = GetStatement();

		if ($this->GetIntProperty("TypeID") > 0)
		{
			$query = "UPDATE `data_type` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						TypeImage=".$this->GetPropertyForSQL("TypeImage").",
						TypeImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("TypeImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder")." 
				WHERE TypeID=".$this->GetIntProperty("TypeID");
		}
		else
		{
			$query = "INSERT INTO `data_type` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						TypeImage=".$this->GetPropertyForSQL("TypeImage").",
						TypeImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("TypeImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("TypeID") > 0)
				$this->SetProperty("TypeID", $stmt->GetLastInsertID());

			return true;
		}
		else
		{
			$this->AddError("sql-error");
			$this->_PrepareContentBeforeShow();
			return false;
		}
	}
	
	function Validate()
	{
		if(!$this->ValidateNotEmpty("Title"))
			$this->AddError("type-title-empty", $this->module);
			
		return !$this->HasErrors();
	}
 
	function SaveTypeImage($savedImage = "", $type = "")
	{
		$fileSys = new FileSys();

		if ($savedImage)
			$original = $savedImage;
		else
			$original = true;

        $newTypeImage = $fileSys->Upload($type . "Image", DATA_IMAGE_DIR."type/", $original, $this->_acceptMimeTypes);
		if ($newTypeImage)
		{
			$this->SetProperty($type . "Image", $newTypeImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newTypeImage["FileName"])
				@unlink(DATA_IMAGE_DIR."type/".$savedImage);
		}
		else
		{
			if ($savedImage)
				$this->SetProperty($type . "Image", $savedImage);
			else
				$this->SetProperty($type . "Image", null);
		}

		$this->_properties[$type."ImageConfig"]["Width"] = 0;
		$this->_properties[$type."ImageConfig"]["Height"] = 0;

		if ($this->GetProperty($type . 'Image'))
		{
			if ($info = @getimagesize(DATA_IMAGE_DIR."type/".$this->GetProperty($type . 'Image')))
			{
				$this->_properties[$type."ImageConfig"]["Width"] = $info[0];
				$this->_properties[$type."ImageConfig"]["Height"] = $info[1];
			}
		}

		$this->AppendErrorsFromObject($fileSys);
		
		return !$fileSys->HasErrors();
	}

	function RemoveTypeImage($typeID, $savedImage, $type = "")
	{
	    if ($savedImage)
		{
			@unlink(DATA_IMAGE_DIR."type/".$savedImage);
		}
		$key = substr($type, 0, strlen($type) - 5);
		if ($typeID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT " . $key . "Image
					FROM `data_type`
				WHERE TypeID=".$typeID);

			if ($imageFile)
				@unlink(DATA_IMAGE_DIR."type/".$imageFile);

			$stmt->Execute("UPDATE `data_type` SET
				" . $key . "Image=NULL, " . $key . "ImageConfig=NULL 
				WHERE TypeID=".$typeID);
		}
	}
}

