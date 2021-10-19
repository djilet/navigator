<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataArea extends LocalObject
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

	function DataArea($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;

		$this->params = array("Area" => array());
		$this->params["Area"] = LoadImageConfig("AreaImage", $this->module, DATA_AREA_IMAGE);
	}

	function LoadByID($id)
	{
		$query = "SELECT a.AreaID, a.Title, a.AreaImage, a.AreaImageConfig, a.SortOrder  				
					FROM `data_area` AS a						
					WHERE a.AreaID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);
		
		if ($this->GetProperty("AreaID"))
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
		$this->_PrepareImages("Area");
	}
		
	function _PrepareImages($key)
	{
		PrepareImagePath($this->_properties, $key, $this->params[$key], "area/");
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
	    $result2 = $this->SaveAreaImage($this->GetProperty("SavedAreaImage"), "Area");

		if (!$result1 || !$result2)
		{
		    $this->_PrepareContentBeforeShow();
			return false;
		}

		$stmt = GetStatement();

		if ($this->GetIntProperty("AreaID") > 0)
		{
			$query = "UPDATE `data_area` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						AreaImage=".$this->GetPropertyForSQL("AreaImage").",
						AreaImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("AreaImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder")." 
				WHERE AreaID=".$this->GetIntProperty("AreaID");
		}
		else
		{
			$query = "INSERT INTO `data_area` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						AreaImage=".$this->GetPropertyForSQL("AreaImage").",
						AreaImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("AreaImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("AreaID") > 0)
				$this->SetProperty("AreaID", $stmt->GetLastInsertID());

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
			$this->AddError("area-title-empty", $this->module);
			
		return !$this->HasErrors();
	}
 
	function SaveAreaImage($savedImage = "", $type = "")
	{
		$fileSys = new FileSys();

		if ($savedImage)
			$original = $savedImage;
		else
			$original = true;

        $newAreaImage = $fileSys->Upload($type . "Image", DATA_IMAGE_DIR."area/", $original, $this->_acceptMimeTypes);
		if ($newAreaImage)
		{
			$this->SetProperty($type . "Image", $newAreaImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newAreaImage["FileName"])
				@unlink(DATA_IMAGE_DIR."area/".$savedImage);
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
			if ($info = @getimagesize(DATA_IMAGE_DIR."area/".$this->GetProperty($type . 'Image')))
			{
				$this->_properties[$type."ImageConfig"]["Width"] = $info[0];
				$this->_properties[$type."ImageConfig"]["Height"] = $info[1];
			}
		}

		$this->AppendErrorsFromObject($fileSys);
		
		return !$fileSys->HasErrors();
	}

	function RemoveAreaImage($areaID, $savedImage, $type = "")
	{
	    if ($savedImage)
		{
			@unlink(DATA_IMAGE_DIR."area/".$savedImage);
		}
		$key = substr($type, 0, strlen($type) - 5);
		if ($areaID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT " . $key . "Image
					FROM `data_area`
				WHERE AreaID=".$areaID);

			if ($imageFile)
				@unlink(DATA_IMAGE_DIR."area/".$imageFile);

			$stmt->Execute("UPDATE `data_area` SET
				" . $key . "Image=NULL, " . $key . "ImageConfig=NULL 
				WHERE AreaID=".$areaID);
		}
	}
}

