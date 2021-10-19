<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataRegion extends LocalObject
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

	function DataRegion($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;

		$this->params = array("Region" => array());
		$this->params["Region"] = LoadImageConfig("RegionImage", $this->module, DATA_REGION_IMAGE);
	}

	function LoadByID($id)
	{
		$query = "SELECT r.RegionID, r.Title, r.AreaID, r.RegionImage, r.RegionImageConfig, r.SortOrder  				
					FROM `data_region` AS r						
					WHERE r.RegionID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("RegionID"))
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
		$this->_PrepareImages("Region");
	}
		
	function _PrepareImages($key)
	{
		PrepareImagePath($this->_properties, $key, $this->params[$key], "region/");
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
	    $result2 = $this->SaveRegionImage($this->GetProperty("SavedRegionImage"), "Region");

		if (!$result1 || !$result2)
		{
		    $this->_PrepareContentBeforeShow();
			return false;
		}

		$stmt = GetStatement();

		if ($this->GetIntProperty("RegionID") > 0)
		{
			$query = "UPDATE `data_region` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						AreaID=".$this->GetPropertyForSQL("AreaID").", 
						RegionImage=".$this->GetPropertyForSQL("RegionImage").",
						RegionImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("RegionImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder")." 
				WHERE RegionID=".$this->GetIntProperty("RegionID");
		}
		else
		{
			$query = "INSERT INTO `data_region` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						AreaID=".$this->GetPropertyForSQL("AreaID").", 
						RegionImage=".$this->GetPropertyForSQL("RegionImage").",
						RegionImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("RegionImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("RegionID") > 0)
				$this->SetProperty("RegionID", $stmt->GetLastInsertID());

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
			$this->AddError("region-title-empty", $this->module);
			
		return !$this->HasErrors();
	}
 
	function SaveRegionImage($savedImage = "", $type = "")
	{
		$fileSys = new FileSys();

		if ($savedImage)
			$original = $savedImage;
		else
			$original = true;

        $newRegionImage = $fileSys->Upload($type . "Image", DATA_IMAGE_DIR."region/", $original, $this->_acceptMimeTypes);
		if ($newRegionImage)
		{
			$this->SetProperty($type . "Image", $newRegionImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newRegionImage["FileName"])
				@unlink(DATA_IMAGE_DIR."region/".$savedImage);
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
			if ($info = @getimagesize(DATA_IMAGE_DIR."region/".$this->GetProperty($type . 'Image')))
			{
				$this->_properties[$type."ImageConfig"]["Width"] = $info[0];
				$this->_properties[$type."ImageConfig"]["Height"] = $info[1];
			}
		}

		$this->AppendErrorsFromObject($fileSys);
		
		return !$fileSys->HasErrors();
	}

	function RemoveRegionImage($regionID, $savedImage, $type = "")
	{
	    if ($savedImage)
		{
			@unlink(DATA_IMAGE_DIR."region/".$savedImage);
		}
		$key = substr($type, 0, strlen($type) - 5);
		if ($regionID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT " . $key . "Image
					FROM `data_region`
				WHERE RegionID=".$regionID);

			if ($imageFile)
				@unlink(DATA_IMAGE_DIR."region/".$imageFile);

			$stmt->Execute("UPDATE `data_region` SET
				" . $key . "Image=NULL, " . $key . "ImageConfig=NULL 
				WHERE RegionID=".$regionID);
		}
	}
}

