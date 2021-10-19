<?php

class MarathonUser extends LocalObject 
{	
	private $module;

	public function __construct($module = 'marathon')
	{
		parent::LocalObject();
		$this->module = $module;
	}
	
	public function loadByID($marathonUserID)
	{
	    $query = "SELECT mu.MarathonUserID, mu.UserID, mu.XP
            FROM `marathon_user` mu
            WHERE mu.MarathonUserID=".intval($marathonUserID);
	    $this->LoadFromSQL($query);
	}
	
	public function load($userID)
	{
	    $query = "SELECT mu.MarathonUserID, mu.XP
            FROM `marathon_user` mu
            WHERE mu.UserID=".intval($userID);
	    $this->LoadFromSQL($query);
	    
	    //create marathon user if not init yet
	    if(!$this->IsPropertySet("MarathonUserID")){
	        if($this->initMaraphoneUser($userID)){
	            $this->load($userID);
	        }
	    }
	}
	
	public function initMaraphoneUser($userID)
	{
	    $stmt = GetStatement();
	    $query = "INSERT INTO `marathon_user` SET
            UserID=".intval($userID).",
            XP=0,
            Created=".Connection::GetSQLString(GetCurrentDateTime());

		$session =& GetSession();
		if($session->GetProperty('utm_source'))
		{
			$query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
    			utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
    			utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
    			utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
    			utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
		}

	    if($stmt->Execute($query)) {
	        return true;
	    }
	    return false;
	}


//UserInfo
	public function isSetUserInfo($items){
		$stmt = GetStatement();

		$query = "SELECT COUNT(InfoID) FROM marathon_user_info WHERE MarathonUserID = "
		. intval($this->GetProperty('MarathonUserID')) . " AND Item IN ("
		. implode(',', Connection::GetSQLArray($items)) . ")";
		if($count = $stmt->FetchField($query)) {
			if ($count == count($items)){
				return true;
			}
		}
		return false;
	}

	public static function getUserAttachmentName($userID, $extension, $name = null){
		return md5(($name !== null ? $name : '') . '_' . $userID) . '.' . $extension;
	}
}

?>
