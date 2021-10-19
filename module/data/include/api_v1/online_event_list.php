<?php

class DataOnlineEventList extends LocalObjectList
{
	private $now;
	var $module;

	public function DataOnlineEventList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"eventdatetime_asc" => "o.EventDateTime ASC",
			"eventdatetime_desc" => "o.EventDateTime DESC",
		));
		$this->SetOrderBy("eventdatetime_asc");
		$this->now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
	}

	public function LoadOnlineEventList($request)
	{
		$where = array();
		$join = array();
		$where[] = "o.Active='Y'";
		if($request->IsPropertySet("OnlyFuture"))
		{
			$where[] = "ADDTIME(o.EventDateTime,o.Duration) >= " . Connection::GetSQLString($this->now->format('Y-m-d H:i:s'));
		}
		if($request->IsPropertySet("OnlyArchive"))
		{
			$this->SetOrderBy("eventdatetime_desc");
			$where[] = "ADDTIME(o.EventDateTime,o.Duration) < " . Connection::GetSQLString($this->now->format('Y-m-d H:i:s'));
		}
		if($request->GetProperty("EventType"))
		{
			if(is_array($request->GetProperty("EventType")))
				$where[] = "o.EventType IN(".implode(", ", Connection::GetSQLArray($request->GetProperty("EventType"))).")";
			else
				$where[] = "o.EventType=".$request->GetPropertyForSQL("EventType");
		}	
		if($request->GetProperty("UniversityID"))
		{
			$join[] = "LEFT JOIN data_online_event2university e2u ON e2u.OnlineEventID=o.OnlineEventID";
			$where[] = "e2u.UniversityID=".$request->GetIntProperty("UniversityID");
		}
		$query = "SELECT o.OnlineEventID, o.EventType, o.EventDateTime, o.Duration, o.Title, o.Description, o.URL, o.ButtonTitle, o.ButtonURL 				
					FROM `data_online_event` AS o 
			".implode(" ", $join)."
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");
		$this->LoadFromSQL($query);
	}
}

?>