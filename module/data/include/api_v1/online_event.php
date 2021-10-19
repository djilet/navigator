<?php

class DataOnlineEvent extends LocalObject {
	
	private $module;

	public function __construct($module)
	{
		parent::LocalObject();
		$this->module = $module;
	}

	public function SaveUserStatus($onlineEventID, $userItemID, $request)
	{
		$stmt = GetStatement();
		
		$query = "SELECT Status FROM `data_online_event2user` 
			WHERE OnlineEventID=".Connection::GetSQLString($onlineEventID)." 
			AND UserItemID=".intval($userItemID);
		$currentStatus = $stmt->FetchField();
		if($currentStatus)
		{
			if($currentStatus != $request->GetProperty("Status"))
			{
				$query = "UPDATE `data_online_event2user`
					SET `Status`=".$request->GetPropertyForSQL("Status").", 
					`Source`='mobile',
					Created=".Connection::GetSQLString(GetCurrentDateTime())." 
					WHERE OnlineEventID=".Connection::GetSQLString($onlineEventID)." 
					AND UserItemID=".intval($userItemID);
				$stmt->Execute($query);
			}
		}
		else 
		{
			$query = "INSERT INTO `data_online_event2user`
				SET OnlineEventID=".Connection::GetSQLString($onlineEventID).",
				UserItemID=".intval($userItemID).",
				`Status`=".$request->GetPropertyForSQL("Status").",
				`Source`='mobile',
				Created=".Connection::GetSQLString(GetCurrentDateTime());
			$stmt->Execute($query);
		}
		return true;
	}
}