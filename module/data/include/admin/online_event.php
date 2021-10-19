<?php
require_once(dirname(__FILE__)."/../../init.php"); 
 
es_include("localobject.php");

class DataOnlineEvent extends LocalObject
{
	var $_acceptMimeTypes = array(
			'image/png',
			'image/x-png',
			'image/gif',
			'image/jpeg',
			'image/pjpeg'
	);
	var $module;

	function DataOnlineEvent($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		
		$this->params["Head"] = LoadImageConfig("HeadImage", $this->module, DATA_ONLINEEVENTHEAD_IMAGE);
	}

	function LoadByID($id)
	{
		$query = "SELECT o.*			
					FROM `data_online_event` AS o
					WHERE o.OnlineEventID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);
		
		$result = array();
		$typeList = getEnumList('data_online_event', 'EventType');
		foreach ($typeList as $item) {
			$result[] = array(
				'Type' => $item,
				'Title' => GetTranslation('online-event-'.$item, $this->module),
				'Selected' => ($item == $this->GetProperty('EventType'))
			);
		}
		$this->SetProperty('EventTypeList', $result);
			
		if ($this->GetProperty("OnlineEventID"))
		{
			$this->_PrepareContentBeforeShow();
			return true;
		}
		else
		{
			return false;
		}
	}

	function Save()
	{
	    $result1 = $this->Validate();
	    $result2 = $this->SaveImage($this->GetProperty("SavedHeadImage"), "Head");

		if (!$result1 || !$result2)
		{
			$this->_PrepareContentBeforeShow();
			return false;
		}

		$stmt = GetStatement();
		$user = new User();
		$user->LoadBySession();

		$staticPath = RuToStaticPath($this->GetProperty("Title"));
		if ($this->GetIntProperty("OnlineEventID") > 0)
		{
			$query = "UPDATE `data_online_event` SET ";
						
			if($user->GetProperty("Role") == "integrator" || $user->GetProperty("Role") == "onlineevent")
			{
				$query .= "EventDateTime=".Connection::GetSQLDateTime($this->GetProperty("EventDateTime")).",
						Title=".$this->GetPropertyForSQL("Title").", ";
			}
			
			$query .= "EventType=".$this->GetPropertyForSQL("EventType").", 
						Duration=".$this->GetPropertyForSQL("Duration").",
						Description=".$this->GetPropertyForSQL("Description").", 
						URL=".$this->GetPropertyForSQL("URL").",
						ButtonTitle=".$this->GetPropertyForSQL("ButtonTitle").",
						ButtonURL=".$this->GetPropertyForSQL("ButtonURL").",
						Content=".$this->GetPropertyForSQL("Content").",
						Active=".$this->GetPropertyForSQL("Active").",
						Chat=".$this->GetPropertyForSQL("Chat").",
						StaticPath = ".Connection::GetSQLString($staticPath).",
						HeadImage=".$this->GetPropertyForSQL("HeadImage").", 
						HeadImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("HeadImageConfig"))).",
                        Template=".$this->GetPropertyForSQL("Template").",
                        RegistrationRequired=".$this->GetPropertyForSQL("RegistrationRequired").",
                        RegistrationType=".$this->GetPropertyForSQL("RegistrationType").",
                        ShowInList=".$this->GetPropertyForSQL("ShowInList").",
                        GUID = ".$this->GetPropertyForSQL('GUID')."
				WHERE OnlineEventID=".$this->GetIntProperty("OnlineEventID");
		}
		else
		{
 			$query = "INSERT INTO `data_online_event` SET
						Created=".Connection::GetSQLString(date("Y-m-d H:i:s")).", 
						CreatedBy=".$user->GetPropertyForSQL("UserID").", 
						EventType=".$this->GetPropertyForSQL("EventType").", 
						EventDateTime=".Connection::GetSQLDateTime($this->GetProperty("EventDateTime")).", 
						Duration=".$this->GetPropertyForSQL("Duration").",
						Title=".$this->GetPropertyForSQL("Title").", 
						Description=".$this->GetPropertyForSQL("Description").", 
						URL=".$this->GetPropertyForSQL("URL").",
						ButtonTitle=".$this->GetPropertyForSQL("ButtonTitle").",
						ButtonURL=".$this->GetPropertyForSQL("ButtonURL").",
						Content=".$this->GetPropertyForSQL("Content").",
						Active=".$this->GetPropertyForSQL("Active").",
						Chat=".$this->GetPropertyForSQL("Chat").",
						StaticPath = ".Connection::GetSQLString($staticPath).",
						HeadImage=".$this->GetPropertyForSQL("HeadImage").", 
						HeadImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("HeadImageConfig"))).",
                        Template=".$this->GetPropertyForSQL("Template").",
                        RegistrationRequired=".$this->GetPropertyForSQL("RegistrationRequired").",
                        RegistrationType=".$this->GetPropertyForSQL("RegistrationType").",
                        ShowInList=".$this->GetPropertyForSQL("ShowInList").",
                        GUID = ".$this->GetPropertyForSQL('GUID');
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("OnlineEventID") > 0)
				$this->SetProperty("OnlineEventID", $stmt->GetLastInsertID());
			
			$this->SaveLinkedUniversity();
			$this->SaveLinkedDirection();
			$this->SaveLinkedProfession();
			$this->SaveLinks();

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
		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");
		
		if ($this->GetProperty("Chat") != "Y")
			$this->SetProperty("Chat", "N");
		
		if ($this->GetProperty("ShowInList") != "Y")
		    $this->SetProperty("ShowInList", "N");

		if ($this->GetProperty("RegistrationRequired") != "Y")
		    $this->SetProperty("RegistrationRequired", "N");

		if(!preg_match("/^(\d\d?)\.(\d\d?)\.(\d\d\d\d) (\d\d?):(\d\d?)$/i", $this->GetProperty("EventDateTime")))
			$this->AddError("online-event-eventdatetime-incorrect", $this->module);
		
		if(!preg_match("/^(\d\d?):(\d\d?)$/i", $this->GetProperty("Duration")))
			$this->RemoveProperty("Duration");
			
		if(!$this->ValidateNotEmpty("Title"))
			$this->AddError("online-event-title-empty", $this->module);
		
		if(mb_stripos($this->GetProperty("URL"), "<iframe") !== false)
		{
			preg_match("/src=\"(.+)\"/uiU", $this->GetProperty("URL"), $matches);
			$this->SetProperty("URL", $matches[1]);
		}
		if(mb_stripos($this->GetProperty("URL"), "//") === 0)
		{
			$this->SetProperty("URL", "https:".$this->GetProperty("URL"));
		}
			
		return !$this->HasErrors();
	}
	
	function GetUserStatusList($onlineEventID)
	{
		$stmt = GetStatement();
		$query = "SELECT e2u.UserItemID, e2u.Status, e2u.Source, u.UserName, u.UserEmail, u.UserPhone, u.UserWho, u.ClassNumber, u.City, e2u.Created as UserCreated, e2u.ShortLink,
				CONCAT('source=', e2u.utm_source, CONCAT_WS('',', medium=', e2u.utm_medium, ', campaign=', e2u.utm_campaign, ', term=', e2u.utm_term, ', content=', e2u.utm_content)) as UTM
				FROM `data_online_event2user` AS e2u
				LEFT JOIN `users_item` AS u ON e2u.UserItemID=u.UserID
				WHERE e2u.OnlineEventID=".Connection::GetSQLString($onlineEventID);
		return $stmt->FetchList($query);
	}
	
	public function SaveLinkedUniversity()
	{
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_online_event2university` WHERE `OnlineEventID` = ".$this->GetIntProperty('OnlineEventID'));
		$ids = $this->GetProperty('LinkedUniversity');
		if (!is_array($ids)) {
			return;
		}
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_online_event2university` VALUES('.$this->GetIntProperty('OnlineEventID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}
	
	public function GetLinkedUniversity($onlineEventID)
	{
		$stmt = GetStatement();
		$query = "SELECT u.UniversityID, u.Title FROM `data_university` AS u
			INNER JOIN `data_online_event2university` AS e2u ON u.UniversityID=e2u.UniversityID
			WHERE e2u.OnlineEventID=".intval($onlineEventID);
		return $stmt->FetchList($query);
	}
	
	public function SaveLinkedDirection()
	{
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_online_event2direction` WHERE `OnlineEventID` = ".$this->GetIntProperty('OnlineEventID'));
		$ids = $this->GetProperty('LinkedDirection');
		if (!is_array($ids)) {
			return;
		}
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_online_event2direction` VALUES('.$this->GetIntProperty('OnlineEventID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}
	
	public function GetLinkedDirection($onlineEventID)
	{
		$stmt = GetStatement();
		$query = "SELECT d.DirectionID, d.Title FROM `data_direction` AS d
			INNER JOIN `data_online_event2direction` AS e2d ON d.DirectionID=e2d.DirectionID
			WHERE e2d.OnlineEventID=".intval($onlineEventID);
		return $stmt->FetchList($query);
	}
	
	public function SaveLinkedProfession()
	{
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_online_event2profession` WHERE `OnlineEventID` = ".$this->GetIntProperty('OnlineEventID'));
		$ids = $this->GetProperty('LinkedProfession');
		if (!is_array($ids)) {
			return;
		}
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_online_event2profession` VALUES('.$this->GetIntProperty('OnlineEventID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}

    public function SaveLinks()
    {
        $this->_PrepareLinks();

        $stmt = GetStatement();

        foreach ($this->GetProperty('Links') as $link) {
            if (!empty($link["LinkID"])) {
                if (!empty($link["Title"]) && !empty($link["URL"])) {
                    $query = "UPDATE `data_online_event_link` SET
                        Title=" . Connection::GetSQLString($link["Title"]) . ", 
                        URL=" . Connection::GetSQLString($link["URL"]) . ", 
                        Blank=" . Connection::GetSQLString($link["Blank"]) . ",
                        Active=" . Connection::GetSQLString($link["Active"]) . "
                        WHERE LinkID=" . $link["LinkID"];var_dump($query);

                    $stmt->Execute($query);
                } else {
                    $query = "DELETE FROM `data_online_event_link` WHERE LinkID=" . $link["LinkID"];

                    $stmt->Execute($query);
                }
            } else {
                if (!empty($link["Title"]) && !empty($link["URL"])) {
                    $query = "INSERT INTO `data_online_event_link` SET
                        OnlineEventID=" . $this->GetIntProperty("OnlineEventID") . ", 
                        Title=" . Connection::GetSQLString($link["Title"]) . ", 
                        URL=" . Connection::GetSQLString($link["URL"]) . ", 
                        Blank=" . Connection::GetSQLString($link["Blank"]) . ",
                        Active=" . Connection::GetSQLString($link["Active"]);

                    $stmt->Execute($query);
                }
            }
        }
    }
	
	public function GetLinkedProfession($onlineEventID)
	{
		$stmt = GetStatement();
		$query = "SELECT p.ProfessionID, p.Title FROM `data_profession` AS p
			INNER JOIN `data_online_event2profession` AS e2p ON p.ProfessionID=e2p.ProfessionID
			WHERE e2p.OnlineEventID=".intval($onlineEventID);
		return $stmt->FetchList($query);
	}

    public function GetLinks($onlineEventID)
    {
        $stmt = GetStatement();
        $query = "SELECT * FROM `data_online_event_link` WHERE OnlineEventID=".intval($onlineEventID);

        $links = $stmt->FetchList($query);

        while (count($links) < 5) {
            $links[] = [
                'OnlineEventID' => '',
                'Title' => '',
                'URL' => '',
                'Blank' => 'N',
                'Active' => 'N',
            ];
        }

        return $links;
    }
	
	function SaveImage($savedImage = "", $type = "")
	{
		$fileSys = new FileSys();
	
		if ($savedImage)
			$original = $savedImage;
		else
			$original = true;
	
		$newTypeImage = $fileSys->Upload($type . "Image", DATA_IMAGE_DIR."onlineevent/", $original, $this->_acceptMimeTypes);
		if ($newTypeImage)
		{
			$this->SetProperty($type . "Image", $newTypeImage["FileName"]);
	
			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newTypeImage["FileName"])
				@unlink(DATA_IMAGE_DIR."onlineevent/".$savedImage);
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
			if ($info = @getimagesize(DATA_IMAGE_DIR."onlineevent/".$this->GetProperty($type . 'Image')))
			{
				$this->_properties[$type."ImageConfig"]["Width"] = $info[0];
				$this->_properties[$type."ImageConfig"]["Height"] = $info[1];
			}
		}
	
		$this->AppendErrorsFromObject($fileSys);
	
		return !$fileSys->HasErrors();
	}
	
	function RemoveHeadImage($onlineEventID, $savedImage, $type = "")
	{
		if ($savedImage)
		{
			@unlink(DATA_IMAGE_DIR."onlineevent/".$savedImage);
		}
		$key = substr($type, 0, strlen($type) - 5);
		if ($onlineEventID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT HeadImage
					FROM `data_online_event`
					WHERE OnlineEventID=".$onlineEventID);
	
			if ($imageFile)
				@unlink(DATA_IMAGE_DIR."onlineevent/".$imageFile);
	
			$stmt->Execute("UPDATE `data_online_event` SET
					HeadImage=NULL, HeadImageConfig=NULL
					WHERE OnlineEventID=".$onlineEventID);
		}
	}
	
	function _PrepareContentBeforeShow()
	{
		$this->_PrepareImages("Head");
	}
	
	function _PrepareImages($key)
	{
		PrepareImagePath($this->_properties, $key, $this->params[$key], "onlineevent/");
	}

    function _PrepareLinks()
    {
        $linkIds = $this->GetProperty("LinkID");
        $linkTitles = $this->GetProperty("LinkTitle");
        $linkUrls = $this->GetProperty("LinkUrl");
        $linkActives = $this->GetProperty("LinkActive");
        $linkBlanks = $this->GetProperty("LinkBlank");

        $links = [];
        foreach ($linkIds as $key => $linkID) {
            $links[] = [
                'LinkID' => $linkID,
                'Title' => trim($linkTitles[$key]),
                'URL' => trim($linkUrls[$key]),
                'Active' => $linkActives[$key] != 'Y' ? 'N' : 'Y',
                'Blank' => $linkBlanks[$key] != 'Y' ? 'N' : 'Y',
            ];
        }

        $this->SetProperty("Links", $links);
    }
}

