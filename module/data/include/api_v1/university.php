<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataUniversity extends LocalObject
{
	var $module;
	private $params;
	
	function __construct($module, $data = array())
	{
		parent::LocalObject($data);
		$this->module = $module;
		$this->params = LoadImageConfig('ItemImage', $module, '296x152|8|Thumb');
	}

	function LoadByID($id, $userItemID)
	{
		$query = "SELECT u.*, r.Title AS RegionTitle, t.Title AS TypeTitle, uu.Created AS Signed
			FROM `data_university` AS u
			LEFT JOIN `data_region` AS r ON u.RegionID=r.RegionID
			LEFT JOIN `data_type` AS t ON u.TypeID=t.TypeID
			LEFT JOIN `data_user_university` AS uu ON u.UniversityID=uu.UniversityID AND uu.SpecialityID IS NULL AND uu.UserID=".intval($userItemID)."
			WHERE u.UniversityID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("UniversityID"))
		{
			$stmt = GetStatement();
			$images = $stmt->FetchList('SELECT * FROM `data_university_image`
					WHERE UniversityID='.$this->GetIntProperty("UniversityID").' ORDER BY `SortOrder` ASC');
			if ($images) {
				$imageList = [];
				foreach ($images as $image) {
					foreach ($this->params as $param) {
						$imageList[] = [
							'ItemImage' => $image['ItemImage'],
							$param['Name'].'Path' => $param['Path'].'univer/'.$image['ItemImage']
						];
					}
				}
				$this->_properties['ImagesList'] = $imageList;
			}
			
			return true;
		}
		else
		{
			return false;
		}
	}

	public function SaveUserStatus($universityID, $userItemID, $request)
	{
		$stmt = GetStatement();
	
		$query = "DELETE FROM `data_user_university`
			WHERE UniversityID=".intval($universityID)."
			AND SpecialityID IS NULL 
			AND UserID=".intval($userItemID);
		$stmt->Execute($query);
	
		if($request->GetProperty("Status") == "signed")
		{
			$query = "INSERT INTO `data_user_university`
				SET UniversityID=".intval($universityID).",
				SpecialityID=NULL,
				UserID=".intval($userItemID).",
				Created=".Connection::GetSQLString(GetCurrentDateTime());
			return $stmt->Execute($query);
		}
		return false;
	}
}

