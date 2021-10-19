<?php
require_once(dirname(__FILE__)."/../../init.php");
require_once(dirname(__FILE__)."/../ArticleTagList.php");

es_include("localobject.php");
es_include("image_manager.php");

class DataArticle extends LocalObject
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

	function DataArticle($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		
		$this->params = array("Article" => array());
		$this->params["Article"] = LoadImageConfig("ArticleImage", $this->module, DATA_ARTICLE_IMAGE);
		$this->params["ArticleMain"] = LoadImageConfig("ArticleMainImage", $this->module, DATA_ARTICLEMAIN_IMAGE);
	}

	function LoadByID($id)
	{
		$query = "SELECT a.*,
					GROUP_CONCAT(t.TagID SEPARATOR ',') as TagIDs
					FROM `data_article` AS a 
					LEFT JOIN `data_article2tag` at ON a.ArticleID=at.ArticleID 
					LEFT JOIN `data_article_tag` t ON at.TagID=t.TagID 
					WHERE a.ArticleID=".Connection::GetSQLString($id)."
					GROUP BY a.ArticleID";
		$this->LoadFromSQL($query);
		
		if ($this->GetProperty("ArticleID"))
		{
			$this->_PrepareContentBeforeShow();
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getTagIDs(): array
    {
        if (is_array($this->GetProperty('TagIDs'))){
            return $this->GetProperty('TagIDs');
        }
	    return explode(',', $this->GetProperty('TagIDs'));
    }
	
	function _PrepareContentBeforeShow()
	{
		$this->_PrepareImages("Article");
		$this->_PrepareImages("ArticleMain");
	}
	
	function _PrepareImages($key)
	{
		PrepareImagePath($this->_properties, $key, $this->params[$key], "article/");
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
	    $result2 = $this->SaveArticleImage($this->GetProperty("SavedArticleImage"), "Article");
	    $result3 = $this->SaveArticleImage($this->GetProperty("SavedArticleMainImage"), "ArticleMain");

		if (!$result1 || !$result2 || !$result3)
		{
		    $this->_PrepareContentBeforeShow();
			return false;
		}

		$stmt = GetStatement();

		if ($this->GetIntProperty("ArticleID") > 0)
		{
			$query = "UPDATE `data_article` SET 
						Title=".$this->GetPropertyForSQL("Title").", 
						StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
						ArticleImage=".$this->GetPropertyForSQL("ArticleImage").", 
						ArticleImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("ArticleImageConfig"))).", 
						ArticleMainImage=".$this->GetPropertyForSQL("ArticleMainImage").", 
						ArticleMainImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("ArticleMainImageConfig"))).", 
						DateTime=".Connection::GetSQLDateTime($this->GetProperty("DateTime")).",
						AuthorID=".$this->GetPropertyForSQL("AuthorID").",
						Description=".$this->GetPropertyForSQL("Description").", 
						Content=".$this->GetPropertyForSQL("Content").",
						QuestionTitle=".$this->GetPropertyForSQL("QuestionTitle").",
						Popular=".$this->GetPropertyForSQL("Popular").",
						Active=".$this->GetPropertyForSQL("Active").",
						ToIndex=".$this->GetPropertyForSQL("ToIndex").",
						OnMain=".$this->GetPropertyForSQL("OnMain").",
						MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
						MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
						TimeToRead=".$this->GetPropertyForSQL("TimeToRead").",  
						Keywords=".$this->GetPropertyForSQL("Keywords")."  
				WHERE ArticleID=".$this->GetIntProperty("ArticleID");
		}
		else
		{
 			$query = "INSERT INTO `data_article` SET
						Title=".$this->GetPropertyForSQL("Title").",
						StaticPath=".$this->GetPropertyForSQL("StaticPath").",  
						ArticleImage=".$this->GetPropertyForSQL("ArticleImage").", 
						ArticleImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("ArticleImageConfig"))).",
						ArticleMainImage=".$this->GetPropertyForSQL("ArticleMainImage").", 
						ArticleMainImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("ArticleMainImageConfig"))).",  
						DateTime=".Connection::GetSQLDateTime($this->GetProperty("DateTime")).",
						AuthorID=".$this->GetPropertyForSQL("AuthorID").",
						Description=".$this->GetPropertyForSQL("Description").", 
						Content=".$this->GetPropertyForSQL("Content").",
						QuestionTitle=".$this->GetPropertyForSQL("QuestionTitle").",
						Popular=".$this->GetPropertyForSQL("Popular").",
						Active=".$this->GetPropertyForSQL("Active").",
						ToIndex=".$this->GetPropertyForSQL("ToIndex").",
						OnMain=".$this->GetPropertyForSQL("OnMain").",
			 			MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
			 			TimeToRead=".$this->GetPropertyForSQL("TimeToRead").",
			 			MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
                        Keywords=".$this->GetPropertyForSQL("Keywords");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("ArticleID") > 0)
				$this->SetProperty("ArticleID", $stmt->GetLastInsertID());
			
			$this->UpdateTags();

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
		if ($this->GetProperty("Popular") != "Y")
			$this->SetProperty("Popular", "N");

		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");

		if ($this->GetProperty("ToIndex") != "Y")
			$this->SetProperty("ToIndex", "N");

        if ($this->GetProperty("OnMain") != "Y")
            $this->SetProperty("OnMain", "N");
		
		if(!preg_match("/^(\d\d?)\.(\d\d?)\.(\d\d\d\d) (\d\d?):(\d\d?)$/i", $this->GetProperty("DateTime")))
			$this->AddError("article-datetime-incorrect", $this->module);
		
		if(!$this->ValidateNotEmpty("Title"))
			$this->AddError("article-title-empty", $this->module);

        $similarArticles = $this->GetProperty('SimilarArticle');
        if (count($similarArticles) > 4){
            $this->AddError('similar-too-much', $this->module);
        }

        return !$this->HasErrors();

    }
	
	function SaveArticleImage($savedImage = "", $type = "")
	{
        return ImageManager::SaveImage($this, DATA_IMAGE_DIR . 'article/', $savedImage, $type);
	}
	
	function RemoveArticleImage($articleID, $savedImage, $type = "")
	{
		if ($savedImage)
		{
			@unlink(DATA_IMAGE_DIR."article/".$savedImage);
		}
		$key = substr($type, 0, strlen($type) - 5);
		if ($articleID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT " . $key . "Image
					FROM `data_article`
					WHERE ArticleID=".$articleID);
	
			if ($imageFile)
				@unlink(DATA_IMAGE_DIR."article/".$imageFile);
	
			$stmt->Execute("UPDATE `data_article` SET
					" . $key . "Image=NULL, " . $key . "ImageConfig=NULL
					WHERE ArticleID=".$articleID);
		}
	}
	
	function UpdateTags()
	{
		$stmt = GetStatement();
		$query= "DELETE FROM `data_article2tag` WHERE ArticleID=".$this->GetIntProperty("ArticleID");
		$stmt->Execute($query);
	
		if(!empty($tags = $this->getTagIDs()))
		{
			for($i=0; $i<count($tags); $i++)
			{
                $query= "INSERT INTO `data_article2tag` SET ArticleID=".$this->GetIntProperty("ArticleID").", TagID=".$tags[$i];
                $stmt->Execute($query);
			}
		}
		
		//remove unused tags
        ArticleTagList::removeUnused();
	}

	public function saveSimilar($ids){
        $articleID = 99999;
        $stmt = GetStatement();
        $query= "DELETE FROM `data_article_similar` WHERE ArticleID=" . $articleID;
        if ($stmt->Execute($query)){
            foreach ($ids as $index => $id) {
                $query = "INSERT INTO data_article_similar SET ArticleID = " . $articleID . ", SimilarArticleID = " . $id;
                $stmt->Execute($query);
            }
        }
    }

	public static function changeBest($id){
        $stmt = GetStatement();
        $query = "UPDATE data_article SET Best = 'N' WHERE Best = 'Y'";
        if ($stmt->Execute($query)){
            $query = "UPDATE data_article SET Best = 'Y' WHERE ArticleID = " . intval($id);

            if ($stmt->Execute($query)){
                return true;
            }
        }

        return false;
    }
}

