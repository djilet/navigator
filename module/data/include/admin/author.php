<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataAuthor extends LocalObject
{
    const PRIMARY_KEY = 'AuthorID';

	var $_acceptMimeTypes = array(
		'image/png',
		'image/x-png',
		'image/gif',
		'image/jpeg',
		'image/pjpeg'
	);
	var $module;
	var $params;

	function DataAuthor($module = 'data', $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;

		$this->params = array("Author" => array());
		$this->params["Author"] = LoadImageConfig("AuthorImage", $this->module, DATA_AUTHOR_IMAGE);
	}

	function LoadByID($id)
	{
		$query = "SELECT a.AuthorID, a.Title, a.Description, a.AuthorImage, a.AuthorImageConfig, a.SortOrder  				
					FROM `data_author` AS a						
					WHERE a.AuthorID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("AuthorID"))
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
		$this->_PrepareImages("Author");
	}
		
	function _PrepareImages($key)
	{
		PrepareImagePath($this->_properties, $key, $this->params[$key], "author/");
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
	    $result2 = $this->SaveAuthorImage($this->GetProperty("SavedAuthorImage"), "Author");

		if (!$result1 || !$result2)
		{
		    $this->_PrepareContentBeforeShow();
			return false;
		}

		$stmt = GetStatement();

		if ($this->GetIntProperty("AuthorID") > 0)
		{
			$query = "UPDATE `data_author` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						Description=".$this->GetPropertyForSQL("Description").", 
						AuthorImage=".$this->GetPropertyForSQL("AuthorImage").",
						AuthorImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("AuthorImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder")." 
				WHERE AuthorID=".$this->GetIntProperty("AuthorID");
		}
		else
		{
			$query = "INSERT INTO `data_author` SET
						Title=".$this->GetPropertyForSQL("Title").", 
						Description=".$this->GetPropertyForSQL("Description").", 
						AuthorImage=".$this->GetPropertyForSQL("AuthorImage").",
						AuthorImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("AuthorImageConfig"))).", 
						SortOrder=".$this->GetIntProperty("SortOrder");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("AuthorID") > 0)
				$this->SetProperty("AuthorID", $stmt->GetLastInsertID());

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
			$this->AddError("author-title-empty", $this->module);
			
		return !$this->HasErrors();
	}
 
	function SaveAuthorImage($savedImage = "", $author = "")
	{
		$fileSys = new FileSys();

		if ($savedImage)
			$original = $savedImage;
		else
			$original = true;

        $newAuthorImage = $fileSys->Upload($author . "Image", DATA_IMAGE_DIR."author/", $original, $this->_acceptMimeTypes);
        if ($newAuthorImage)
		{
		    $this->SetProperty($author . "Image", $newAuthorImage["FileName"]);

			// Remove old image if it has different name
		    if ($savedImage && $savedImage != $newAuthorImage["FileName"])
				@unlink(DATA_IMAGE_DIR."author/".$savedImage);
		}
		else
		{
			if ($savedImage)
			    $this->SetProperty($author . "Image", $savedImage);
			else
			    $this->SetProperty($author . "Image", null);
		}

		$this->_properties[$author."ImageConfig"]["Width"] = 0;
		$this->_properties[$author."ImageConfig"]["Height"] = 0;

		if ($this->GetProperty($author . 'Image'))
		{
		    if ($info = @getimagesize(DATA_IMAGE_DIR."author/".$this->GetProperty($author . 'Image')))
			{
			    $this->_properties[$author."ImageConfig"]["Width"] = $info[0];
			    $this->_properties[$author."ImageConfig"]["Height"] = $info[1];
			}
		}

		$this->AppendErrorsFromObject($fileSys);
		
		return !$fileSys->HasErrors();
	}

	function RemoveAuthorImage($authorID, $savedImage, $author = "")
	{
	    if ($savedImage)
		{
			@unlink(DATA_IMAGE_DIR."author/".$savedImage);
		}
		$key = substr($author, 0, strlen($author) - 5);
		if ($authorID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT " . $key . "Image
					FROM `data_author`
				WHERE AuthorID=".$authorID);

			if ($imageFile)
				@unlink(DATA_IMAGE_DIR."author/".$imageFile);

			$stmt->Execute("UPDATE `data_author` SET
				" . $key . "Image=NULL, " . $key . "ImageConfig=NULL 
				WHERE AuthorID=".$authorID);
		}
	}
}

