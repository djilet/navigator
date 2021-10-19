<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataUniversityList extends LocalObjectList
{
	var $module;
	
	function DataUniversityList($module = 'data', $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "u.ShortTitle ASC",
			"title_desc" => "u.ShortTitle DESC",
		));

		$this->SetOrderBy("title_asc");
	}

	function LoadUniversityList()
	{
		$where = array();
	
		$query = "SELECT u.UniversityID, u.ShortTitle, u.Title, u.Opened 
					FROM `data_university` AS u			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
	}
	
	function LoadForSelection($universityID)
	{
		$query = "SELECT u.UniversityID, u.ShortTitle as Title, (CASE u.UniversityID WHEN ".intval($universityID)." THEN 1 ELSE 0 END) as Selected FROM `data_university` AS u";
		$this->LoadFromSQL($query);
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			
			$query = "SELECT SpecialityID from `data_speciality` WHERE UniversityID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$specIDs = $stmt->FetchRows($query);
			if(count($specIDs) > 0)
			{
				$specialityList = new DataSpecialityList($this->module);
				$specialityList->Remove($specIDs);
			}
			
			$query = "DELETE FROM `data_university` WHERE UniversityID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("university-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}
	
	public function LoadForSuggest(LocalObject $request)
	{
		$this->_items = array();
		$where = array();
		
		if($request->GetProperty("term")) {
			$where[] = "(INSTR(u.Title, ".$request->GetPropertyForSQL("term").") OR INSTR(u.ShortTitle, ".$request->GetPropertyForSQL("term")."))";
		}
		if($request->GetProperty("FilterRegionID")){
			$where[] = "u.RegionID=".$request->GetIntProperty("FilterRegionID");
		}
		if($request->GetProperty("FilterTypeID")){
			$where[] = "u.TypeID=".$request->GetIntProperty("FilterTypeID");
		}
		if($request->GetProperty("FilterUniverType") == 1){
			$where[] = "u.UniverType='Государственный'";
		}
		if($request->GetProperty("FilterHostel") == 1){
			$where[] = "(u.Hostel='Есть' OR u.Hostel='Да')";
		}
		if($request->GetProperty("FilterMilitaryDepartment") == 1){
			$where[] = "(u.MilitaryDepartment='Есть' OR u.MilitaryDepartment='Да')";
		}
		if($request->GetProperty("FilterDelayArmy") == 1){
			$where[] = "(u.DelayArmy='Есть' OR u.DelayArmy='Да')";
		}
		if($request->GetProperty('ItemIDs')) {
			$query .= " AND u.UniversityID NOT IN (".implode(',', Connection::GetSQLArray($request->GetProperty('ItemIDs'))).")";
		}
	
		$query = "SELECT u.UniversityID AS `value`, u.Title AS `label` FROM `data_university` AS u". (!empty($where) ? " WHERE ".implode(" AND ", $where) : "");
		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}
	
	public function exportUsersToCSV($from, $to, $type)
	{
		$stmt = GetStatement();
		$modifiedTo = new DateTime($to);
		$modifiedTo->modify('+1 day');
		$query = null;
		$typeTitle = null;
		
		if($type == "university")
		{
			$typeTitle = "Название вуза";
			$query = "SELECT u2u.Created AS RegistrationDate, un.ShortTitle AS Title, u.UserName, u.UserEmail, u.UserPhone, u.UserWho, u.ClassNumber, u.City, u.Created AS UserCreated,
				CONCAT('source=', u2u.utm_source, CONCAT_WS('',', medium=', u2u.utm_medium, ', campaign=', u2u.utm_campaign, ', term=', u2u.utm_term, ', content=', u2u.utm_content)) as UTM
				FROM `data_user_university` AS u2u
				LEFT JOIN `data_university` AS un ON un.UniversityID=u2u.UniversityID
				LEFT JOIN `users_item` AS u ON u.UserID=u2u.UserID
				WHERE u2u.SpecialityID IS NULL AND u2u.Created>".Connection::GetSQLDateTime($from)." AND u2u.Created<".Connection::GetSQLDateTime($modifiedTo->format('Y-m-d H:i:s'));
		}
		else if($type == "speciality")
		{
			$typeTitle = "Название специальности";
			$query = "SELECT u2u.Created AS RegistrationDate, CONCAT(s.Title, ' ', un.ShortTitle) AS Title, u.UserName, u.UserEmail, u.UserPhone, u.UserWho, u.ClassNumber, u.City, u.Created AS UserCreated,
				CONCAT('source=', u2u.utm_source, CONCAT_WS('',', medium=', u2u.utm_medium, ', campaign=', u2u.utm_campaign, ', term=', u2u.utm_term, ', content=', u2u.utm_content)) as UTM
				FROM `data_user_university` AS u2u
				LEFT JOIN `data_speciality` AS s ON s.SpecialityID=u2u.SpecialityID
				LEFT JOIN `data_university` AS un ON un.UniversityID=s.UniversityID
				LEFT JOIN `users_item` AS u ON u.UserID=u2u.UserID
				WHERE u2u.SpecialityID IS NOT NULL AND u2u.Created>".Connection::GetSQLDateTime($from)." AND u2u.Created<".Connection::GetSQLDateTime($modifiedTo->format('Y-m-d H:i:s'));
		}
		
		$itemList = $stmt->FetchList($query);
	
		ob_start();
		$f = fopen("php://output", "w");
	
		$row = array("Дата выбора",$typeTitle,"Имя","E-mail","Телефон","Статус","Класс","Город","Зарегистрирован","UTM");
		fputcsv($f, $row, ";");
	
		foreach($itemList as $item)
		{
			$row = array(
					$item["RegistrationDate"],
					$item["Title"],
					$item["UserName"],
					$item["UserEmail"],
					$item["UserPhone"],
					$item["UserWho"],
					$item["ClassNumber"],
					$item["City"],
					$item["UserCreated"],
					$item["UTM"]
			);
			fputcsv($f, $row, ";");
		}
	
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition: attachment;filename="registrations.csv"');
		header("Content-Transfer-Encoding: binary");
	
		echo(ob_get_clean());
		exit();
	}
}

?>