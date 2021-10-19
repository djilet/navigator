<?php

class DataProfessionList extends LocalObjectList {
	
	private $module;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "p.Title ASC",
			"title_desc" => "p.Title DESC",
			"sortorder_asc" => "p.SortOrder ASC",
			"sortorder_desc" => "p.SortOrder DESC",
		));

		$this->SetOrderBy("title_asc");
	}

	public function load($onPage = 40)
	{
		$query = 'SELECT p.*, ind.IndustryTitle
				  FROM `data_profession` AS p
				  LEFT JOIN `data_profession_industry` AS ind ON p.Industry=ind.IndustryID';
		$this->SetItemsOnPage($onPage);
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}

	public function LoadForSuggest(LocalObject $request)
	{
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}

		$query = "SELECT p.`ProfessionID` AS `value`, p.`Title` AS `label` FROM `data_profession` AS p
			WHERE INSTR(p.`Title`, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND p.`ProfessionID` NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}
		
		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}
	
	public function exportUsersToCSV($from, $to)
	{
		$stmt = GetStatement();
		$modifiedTo = new DateTime($to);
		$modifiedTo->modify('+1 day');
		$query = "SELECT p2u.Created AS RegistrationDate, p.Title, u.UserName, u.UserEmail, u.UserPhone, u.UserWho, u.ClassNumber, u.City, u.Created AS UserCreated, 
			CONCAT('source=', p2u.utm_source, CONCAT_WS('',', medium=', p2u.utm_medium, ', campaign=', p2u.utm_campaign, ', term=', p2u.utm_term, ', content=', p2u.utm_content)) as UTM
			FROM `data_profession2user` AS p2u
			LEFT JOIN `data_profession` AS p ON p.ProfessionID=p2u.ProfessionID
			LEFT JOIN `users_item` AS u ON u.UserID=p2u.UserID
			WHERE p2u.Created>".Connection::GetSQLDateTime($from)." AND p2u.Created<".Connection::GetSQLDateTime($modifiedTo->format('Y-m-d H:i:s'));
		$itemList = $stmt->FetchList($query);
	
		ob_start();
		$f = fopen("php://output", "w");
		
		$row = array("Дата выбора","Название профессии","Имя","E-mail","Телефон","Статус","Класс","Город","Зарегистрирован","UTM");
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