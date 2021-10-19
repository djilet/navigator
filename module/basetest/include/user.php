<?php

class BaseTestUser extends LocalObject
{
	private $module;
	private $stmt;
	public $list;

	const STATUS_ACTIVE = 'active';
	const STATUS_RESET = 'reset';

	public function __construct($module = 'basetest')
	{
		parent::LocalObject();
		$this->module = $module;
		$this->list = new LocalObjectList();
		$this->stmt = GetStatement();
	}

//Load
	public function load($testUserID){
        $testUserID = intval($testUserID);
	    if ($testUserID > 0){
            $query = "SELECT * FROM basetest_user WHERE BaseTestUserID = " . $testUserID;
            $this->LoadFromSQL($query);
            return true;
        }

	    return false;
	}

//Init
	public function init($pageID, $userID = null){
		$this->SetProperty('UserID', $userID);
		$this->SetProperty('Created', GetCurrentDateTime());
		$this->SetProperty('Status', self::STATUS_ACTIVE);
		$this->SetProperty('PageID', $pageID);
		if ($id = $this->save()){
			return $id;
		}

		return false;
	}

    public function getShortLink($baseURL = null){
	    if (!$this->ValidateNotEmpty('CompleteDate')){
	        return false;
        }

	    if (is_null($baseURL)){
            $baseURL = GetUrlPrefix() . 'basetest/';
        }
	    if (!$this->ValidateNotEmpty('ShortLink')){
            if (!$this->ValidateNotEmpty('LinkID')){
                $this->setLinkID();
            }

            if ($shortLink = GetShortURL($baseURL . '?ShowTest=' . $this->GetProperty('LinkID'))){
                $this->SetProperty('ShortLink', $shortLink);
                $this->save();
            }
        }

	    return $this->GetProperty('ShortLink');
    }

//Get
	public static function getActiveIDByUserID($userID){
		$stmt = GetStatement();
		$query = "SELECT BaseTestUserID FROM
				  basetest_user
				  WHERE UserID = " . intval($userID) . "
				  AND Status = " . Connection::GetSQLString(self::STATUS_ACTIVE);

		$testUserID = $stmt->FetchField($query);

		if ($testUserID > 0){
			return $testUserID;
		}

		return false;
	}

	public static function getIDByLinkID($linkID){
	    if (empty($linkID)){
	        return false;
        }
        $query = "SELECT BaseTestUserID FROM basetest_user WHERE LinkID = " . Connection::GetSQLString($linkID);
	    return GetStatement()->FetchField($query);
    }

//CRUD
	public function save(){
		$where = '';

		if ($this->GetIntProperty('BaseTestUserID')){
			$query = "UPDATE";
			$where = "\n WHERE BaseTestUserID = " . $this->GetPropertyForSQL('BaseTestUserID');
		}
		else{
			$query = "INSERT INTO";
		}

		$query .= " basetest_user SET
 				UserID 		= " . $this->GetPropertyForSQL('UserID') . ",
 				PageID 		= " . $this->GetIntProperty('PageID') . ",
 				FeedbackRating 		= " . $this->GetPropertyForSQL('FeedbackRating') . ",
 				FeedbackMessage 		= " . $this->GetPropertyForSQL('FeedbackMessage') . ",
 				CompleteDate 		= " . $this->GetPropertyForSQL('CompleteDate') . ",
 				Created 			= " . $this->GetPropertyForSQL('Created') . ",
 				LinkID 			= " . $this->GetPropertyForSQL('LinkID') . ",
 				ShortLink 			= " . $this->GetPropertyForSQL('ShortLink') . ",
 				Status 				= " . $this->GetPropertyForSQL('Status') .
			$where;

		if ($this->stmt->Execute($query)){
            $this->updateToSession();
			return $this->stmt->_lastInsertID;
		}
		else{
			//TODO log
			return false;
		}
	}

	public function updateToSession(){
        $session = GetSession();

        if ($this->GetProperty('BaseTestUserID') > 0 && $this->GetProperty('Status') != self::STATUS_RESET){
            $session->SetProperty('BaseTestUser', $this->GetProperties());
        }
        else{
            $session->RemoveProperty('BaseTestUser');
        }

        $session->SaveToDB();
    }

    protected function setLinkID(){
        $this->SetProperty('LinkID', md5(uniqid()));
    }
}