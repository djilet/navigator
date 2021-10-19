<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class DataOnlineEventList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

	var $module;

	function DataOnlineEventList($module = 'data', $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"eventdatetime_asc" => "o.EventDateTime ASC",
			"eventdatetime_desc" => "o.EventDateTime DESC",
		));
		$this->SetOrderBy("eventdatetime_desc");
		$this->SetItemsOnPage(20);
	}

	function LoadOnlineEventList()
	{
		$where = array();
		$query = "SELECT o.OnlineEventID, o.EventType, o.EventDateTime, o.Duration, o.Title, o.Description, o.URL, o.ButtonTitle, o.ButtonURL,  
						o.Active, COUNT(e2u.UserItemID) AS DeviceStatusCount  					
					FROM `data_online_event` AS o	
						LEFT JOIN `data_online_event2user` AS e2u ON e2u.OnlineEventID=o.OnlineEventID 
					".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")." 
					GROUP BY o.OnlineEventID";

		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			
			$query = "DELETE FROM `data_online_event` WHERE OnlineEventID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("online-event-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
			
			$query = "DELETE FROM `data_online_event2user` WHERE OnlineEventID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			$query = "DELETE FROM `data_online_event2direction` WHERE OnlineEventID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			$query = "DELETE FROM `data_online_event2profession` WHERE OnlineEventID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			$query = "DELETE FROM `data_online_event2university` WHERE OnlineEventID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
		}
	}
	
	public function exportRegistrationsToCSV($from, $to)
	{
		$stmt = GetStatement();
		$modifiedTo = new DateTime($to);
		$modifiedTo->modify('+1 day');
		$query = "SELECT e2u.Created AS RegistrationDate, u.UserName, u.UserEmail, u.UserPhone, u.UserWho, u.ClassNumber, u.City, u.Created AS UserCreated, e2u.Status, e2u.Source, e2u.ShortLink,
			CONCAT('source=', e2u.utm_source, CONCAT_WS('',', medium=', e2u.utm_medium, ', campaign=', e2u.utm_campaign, ', term=', e2u.utm_term, ', content=', e2u.utm_content)) as UTM,
			e.EventDateTime, e.Title
			FROM `data_online_event2user` AS e2u
			LEFT JOIN `data_online_event` AS e ON e.OnlineEventID=e2u.OnlineEventID
			LEFT JOIN `users_item` AS u ON u.UserID=e2u.UserItemID
			WHERE e2u.Created>".Connection::GetSQLDateTime($from)." AND e2u.Created<".Connection::GetSQLDateTime($modifiedTo->format('Y-m-d H:i:s'));
		$itemList = $stmt->FetchList($query);
		
		ob_start();
		$f = fopen("php://output", "w");
	
		$row = array("Дата регистрации на вебинар","Имя","E-mail","Телефон","Статус","Класс","Город","Зарегистрирован","Статус","UTM","Дата вебинара","Название вебинара","Источник","Ссылка");
		fputcsv($f, $row, ";");
	
		foreach($itemList as $item)
		{
			$row = array(
					$item["RegistrationDate"],
					$item["UserName"],
					$item["UserEmail"],
					$item["UserPhone"],
					$item["UserWho"],
					$item["ClassNumber"],
					$item["City"],
					$item["UserCreated"],
					$item["Status"],
					$item["UTM"],
					$item["EventDateTime"],
					$item["Title"],
					$item["Source"],
					$item["ShortLink"],
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

    public function getListForTemplate(array $selected = [], $items = null)
    {
        return $this->prepareFromKeysName('OnlineEventID', 'Title', $selected, $items);
    }
}

?>