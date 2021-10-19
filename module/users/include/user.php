<?php

use SocialAuth\ISocialNetwork;
use Module\Tracker\AnalyticSystem;

require_once(dirname(__FILE__) . "/../../tracker/include/analytic_system/sender.php");

class UserItem extends LocalObject
{
	var $_acceptMimeTypes = array(
			'image/png',
			'image/x-png',
			'image/gif',
			'image/jpeg',
			'image/pjpeg'
	);

    const USER_WHO_PARENT = 'parent';
    const USER_WHO_STUDENT = 'student';
    const USER_WHO_CHILD = 'child';

    private $module;
    private $baseURL;
    private $errorNames = array();

    public function __construct($module = 'user', $baseURL = null)
    {
        $this->module = $module;
        $this->baseURL = $baseURL;
    }

    /**
     * Аутентификация пользователя
     *
     * @param string $email E-mail
     * @param string $pass  Пароль
     *
     * @return bool
     */
    public function Authentication($email, $pass)
    {
        if (!$email or !$pass) {
            $this->AddError('users-auth-input-empty', $this->module);
            $this->errorNames[] = "Email";
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->AddError('users-auth-email-uncorrect', $this->module);
            $this->errorNames[] = "Email";
            return false;
        }

        $stmt = GetStatement();
        $query = "SELECT UserID,UserEmail,UserName,UserPhone,UserWho,ClassNumber,UserImage
            FROM users_item WHERE UserEmail='{$email}'
            AND UserPass=".Connection::GetSQLString(md5($pass));
        if ($row = $stmt->FetchRow($query)) {
            $session = GetSession();
            $session->SetProperty('UserItem', $row);
            $session->SaveToDB();

			//AnalyticSystem
			AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_USER_LOGIN);
			//AnalyticSystem end

            return true;
        }

        $this->AddError('users-auth-pass-uncorrect', $this->module);
        $this->errorNames[] = "Email";
        $this->errorNames[] = "Pass";
        return false;
    }

	public function AuthenticationByAuthKey($authKey){
		$stmt = GetStatement();
		$query = "SELECT * FROM users_item WHERE AuthKey = " . Connection::GetSQLString($authKey);

		if ($row = $stmt->FetchRow($query)) {
			$session = GetSession();
			$session->SetProperty('UserItem', $row);
			$session->SaveToDB();

			$stmt->Execute("UPDATE users_item SET AuthKey = NULL WHERE UserID = " . $row['UserID']);

            //AnalyticSystem
            AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_USER_LOGIN);
            //AnalyticSystem end

			return true;
		}

		$this->AddError('auth-key-incorrect', $this->module);
		return false;
	}

    /**
     * Регистрация пользователя
     *
     * @param \LocalObject $post
     *
     * @return bool
     * @internal param array $data Данные для регистрации
     *
     */
    public function registration(LocalObject $post, $auth = true, $requiredConsent = true)
    {
        if (!$post->ValidateNotEmpty('UserName')) {
            $this->errorNames[] = "UserName";
            $this->AddError('registration-username-empty', $this->module);
        }
        if (!$post->ValidateNotEmpty('Email')) {
            $this->errorNames[] = "Email";
            $this->AddError('registration-email-empty', $this->module);
        } elseif (!filter_var($post->GetProperty('Email'), FILTER_VALIDATE_EMAIL)) {
            $this->errorNames[] = "Email";
            $this->AddError('registration-email-incorrect', $this->module);
        }
        if (!$post->ValidateNotEmpty('WhoAmI')) {
            $this->errorNames[] = "WhoAmI";
            $this->AddError('registration-whoami-empty', $this->module);
        } elseif (!in_array($post->GetProperty('WhoAmI'), array('child', 'parent', 'student'))) {
            $this->errorNames[] = "WhoAmI";
            $this->AddError('registration-whoami-incorrect', $this->module);
        } elseif (in_array($post->GetProperty('WhoAmI'), array('child', 'parent'))) {
            if (!$post->ValidateNotEmpty('ClassNumber')) {
                $this->errorNames[] = "ClassNumber";
                $this->AddError('registration-classnumber-empty', $this->module);
            } elseif ($post->GetIntProperty('ClassNumber') < 1 or $post->GetIntProperty('ClassNumber') > 11) {
                $this->errorNames[] = "ClassNumber";
                $this->AddError('registration-classnumber-incorrect', $this->module);
            }
        }
        elseif ($requiredConsent && !$post->ValidateNotEmpty('Rules')){
            $this->errorNames[] = "Rules";
            $this->AddError('rules-not-checked', $this->module);
        }
        elseif ($requiredConsent &&!$post->ValidateNotEmpty('Licence')){
            $this->errorNames[] = "Licence";
            $this->AddError('rules-not-checked', $this->module);
        }

        if (!$post->IsPropertySet('SocialID') && $post->IsPropertySet('RequirePassword')) {
            if (!$post->ValidateNotEmpty('Pass')) {
                $this->errorNames[] = "Pass";
                $this->AddError('registration-pass-empty', $this->module);
            } elseif (!$post->ValidateNotEmpty('PassRepeat')) {
                $this->errorNames[] = "PassRepeat";
                $this->AddError('registration-pass-repeat-empty', $this->module);
            } elseif ($post->GetProperty('Pass') !== $post->GetProperty('PassRepeat')) {
                $this->errorNames[] = "Pass";
                $this->errorNames[] = "PassRepeat";
                $this->AddError('registration-pass-do-not-match', $this->module);
            } else {
                $post->SetProperty('Pass', md5($post->GetProperty('Pass')));
            }
        }

        if ($this->HasErrors()) {
            return false;
        }

        $stmt = GetStatement();
        $query = "SELECT UserID FROM users_item WHERE UserEmail=".$post->GetPropertyForSQL('Email');
        if ($stmt->FetchField($query)) {
            $this->AddError('users-registration-email-exists', $this->module);
            $this->errorNames[] = "Email";
            return false;
        }

        $userName = $post->GetProperty('UserName');
        if ($post->IsPropertySet('UserSurname')){
            $userName .= urldecode("%20") . $post->GetProperty('UserSurname');
        }

        $query = "INSERT INTO users_item SET
				UserEmail=".$post->GetPropertyForSQL('Email').",
				UserPass=".$post->GetPropertyForSQL('Pass').",
				UserName=" . Connection::GetSQLString($userName) . ",
				UserPhone=".$post->GetPropertyForSQL('Phone').",
				UserWho=".$post->GetPropertyForSQL('WhoAmI').",
				ClassNumber=".$post->GetPropertyForSQL('ClassNumber').",
        		University=".$post->GetPropertyForSQL('University').",
        		CourseNumber=".$post->GetPropertyForSQL('CourseNumber').",
        		Created=".Connection::GetSQLString(GetCurrentDateTime());

        $ipinfo = GetIPInfo(getClientIP());
        if($ipinfo && $ipinfo->city)
        {
        	$query .= ",City=".Connection::GetSQLString($ipinfo->city);
        }

        if ($stmt->Execute($query)) {
            if ($auth === false) {
                return true;
            }

            $UserID = $stmt->GetLastInsertID();
            $session = GetSession();

            if ($socialID = $post->GetProperty('SocialID')) {
                $type = $session->GetProperty('SocialType');
                $query = 'INSERT INTO `social_auth` SET
                    UserItemID='.$UserID.',
                    SocialType='.Connection::GetSQLString($type).',
                    SocialID='.Connection::GetSQLString($socialID);
                $stmt->Execute($query);

                $query = "INSERT INTO `social_token` ( `UserItemID`, `Type`, `Token`)
                    VALUES (
                        ".Connection::GetSQLString($UserID).",
                        ".Connection::GetSQLString($type).",
                        ".Connection::GetSQLString($session->GetPropertyForSQL('UserItem'.$type.'Token'))."
                    )
                    ON DUPLICATE KEY UPDATE
                        `Token` = VALUES(`Token`)";
                $stmt->Execute($query);

                $session->RemoveProperty('UserItem'.$type.'Token');
                $session->RemoveProperty('SocialID');
                $session->RemoveProperty('SocialType');
            }

            $session->SetProperty('UserItem', array(
                'UserID' => $UserID,
                'UserEmail' => filter_var($post->GetProperty('Email'), FILTER_SANITIZE_STRING),
                'UserName' => filter_var($post->GetProperty('UserName'), FILTER_SANITIZE_STRING),
                'UserPhone' => filter_var($post->GetProperty('Phone'), FILTER_SANITIZE_STRING),
                'UserWho' => filter_var($post->GetProperty('WhoAmI'), FILTER_SANITIZE_STRING),
                'ClassNumber' => filter_var($post->GetProperty('ClassNumber'), FILTER_SANITIZE_STRING),
                'University' => filter_var($post->GetProperty('University'), FILTER_SANITIZE_STRING),
                'CourseNumber' => filter_var($post->GetProperty('CourseNumber'), FILTER_SANITIZE_STRING),
            ));
            $session->SaveToDB();

            //AnalyticSystem
			AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_USER_SIGN_UP);
			//AnalyticSystem end

            return true;
        }

        return false;
    }

    /**
     * Регистрация пользователей из формы registerExhibition
     *
     * @param \LocalObject $post
     *
     * @return bool
     * @internal param array $data Данные формы
     *
     */
    public function registrationFromExhibition($form){
		$this->ClearErrors();
		$userFields = array();
		$pass = $this->RandStr(10);

		$userFields['ClassNumber'] = $form['UserClassNumber'];
		$userFields['Email'] = $form['UserEmail'];
		$userFields['UserName'] = $form['UserName'] . ' ' . $form['UserLastName'];
		$userFields['Phone'] = $form['UserPhone'];
		$userFields['Pass'] = $pass;
		$userFields['PassRepeat'] = $pass;
		$userFields['WhoAmI'] = $form['UserWho'];
		$userFields['RequirePassword'] = true;

		$post = new LocalObject($userFields);
		if( $this->registration($post, true, false) ){
			$template = new Page();
			if($template->LoadByStaticPath("you-are-registered")){
				$content = $template->GetProperty("Content");
				$content = str_replace("[password]", $pass, $content);
				SendMailFromAdmin($form['UserEmail'], "Навигатор поступления: Теперь у тебя есть аккаунт на сайте propostuplenie.ru", $content);
			}
			return true;
		}

		return false;
    }

    /**
     * Загрузка информации о пользователе
     *
     * @param int $id ID пользователя
     *
     * @return bool
     */
    public function loadByID($id)
    {
        $id = abs(intval($id));
        if (empty($id)) {
            return false;
        }

        $query = "SELECT `UserID`,`UserEmail`,`UserName`,`UserPhone`,`UserWho`, `UserImage`,`ClassNumber`,`Created`,`ChatStatus`,`CommentsStatus`, `EgeStatus`
            FROM users_item WHERE UserID={$id}";
        $this->LoadFromSQL($query);
        if ($this->GetIntProperty('UserID') > 0) {
            return true;
        }

        return false;
    }

    public function loadSocialAuth(){
        $stmt = GetStatement();
        $query = "SELECT * FROM social_auth WHERE UserItemID = " . $this->GetIntProperty('UserID');
        $result = $stmt->FetchList($query);
        $this->SetProperty('SocialAuthList', $result);
    }

    /**
     * Выход =)
     */
    public function logout()
    {
        $session = GetSession();
        $session->RemoveProperty('UserItem');
        $session->SaveToDB();
    }

    /**
     * @return array
     */
    public function getErrorNames()
    {
        return $this->errorNames;
    }

    /**
     * Авторизация через ИД Соцсети
     *
     * @param \SocialAuth\ISocialNetwork $socialNetwork
     *
     * @return bool
     */
    public function authBySocialID(ISocialNetwork $socialNetwork)
    {
    	$userInfo = $socialNetwork->getUserInfo();
    	$userSocID = filter_var($userInfo['user_id'], FILTER_SANITIZE_STRING);
        $type = filter_var($socialNetwork->getSocialType(), FILTER_SANITIZE_STRING);
        if (empty($userSocID)) {
            return false;
        }

        $session = GetSession();
        $stmt = GetStatement();

        $query = 'SELECT u.*, sa.* FROM `social_auth` AS sa
            INNER JOIN `users_item` AS u ON sa.UserItemID=u.UserID
            WHERE sa.SocialID='.Connection::GetSQLString($userSocID).' 
            AND sa.SocialType='.Connection::GetSQLString($type);

        if ($row = $stmt->FetchRow($query)) {
            $session->SetProperty('UserItem', $row);
            $session->SaveToDB();

            $query = "INSERT INTO `social_token` ( `UserItemID`, `Type`, `Token`)
                VALUES (
                    ".Connection::GetSQLString($row['UserItemID']).",
                    ".Connection::GetSQLString($type).",
                    ".Connection::GetSQLString(serialize($socialNetwork->getToken()))."
                )
                ON DUPLICATE KEY UPDATE
                    `Token` = ".Connection::GetSQLString(serialize($socialNetwork->getToken()));
            $stmt->Execute($query);

            return true;
        } else {
            $row = array(
                'SocialType' => $socialNetwork->getSocialType(),
                'SocialID' => $userInfo['user_id'],
                'SocialSurname' => $userInfo['last_name'],
                'SocialFirstName' => $userInfo['first_name'],
                'SocialEmail' => $userInfo['email'],
                'SocialPhone' => "",
                'SocialCity' => $userInfo['city']
            );

            if ($date = $socialNetwork->getBirthDay()) {
                $result = self::getUserWhoStatusBy($date);
                $row['SocialWhoAmI'] = $result['Status'];
                $row['SocialClass'] = $result['Class'];
            }

            $session->SetProperty('UserItem', $row);
            $session->SetProperty('SocialType', $type);
            $session->SetProperty('UserItem'.$type.'Token', serialize($socialNetwork->getToken()));
            $session->SaveToDB();

            return true;
        }

        return false;
    }

    public function loadBySession()
    {
        $session = GetSession();
        if ($info = $session->GetProperty('UserItem')) {
            if (isset($info['UserID']) and $info['UserID'] > 0) {
                $this->loadByID($info['UserID']);
            }
        }
    }

    public function updateData(LocalObject $request)
    {
    	$stmt = GetStatement();
        $query = "UPDATE `users_item` SET
                UserEmail=".$request->GetPropertyForSQL('UserEmail').",
                UserName=".$request->GetPropertyForSQL('UserName').",
                UserPhone=".$request->GetPropertyForSQL('UserPhone').",
                UserWho=".$request->GetPropertyForSQL('UserWho').",
                ChatStatus=".$request->GetPropertyForSQL('ChatStatus').",
                CommentsStatus=".Connection::GetSQLString($request->GetProperty('CommentsStatus') == 'Y' ? 'Y' : 'N').",
                EgeStatus=".Connection::GetSQLString($request->GetProperty('EgeStatus') == 'Y' ? 'Y' : 'N');

        if($request->GetIntProperty('ClassNumber') > 0){
            $query .= ", ClassNumber=".$request->GetPropertyForSQL('ClassNumber');
        }

    	$query .= " WHERE UserID=".$request->GetIntProperty('UserID');

        return $stmt->Execute($query);
    }

	/**
	 * @deprecated sa.DeviceID not found
	 * @return array|bool|null
	 */
	public function getDevices()
    {
        $stmt = GetStatement();
        $query = 'SELECT *
            FROM `social_auth` AS sa
            INNER JOIN `data_device` AS d ON sa.DeviceID=d.DeviceID
            WHERE sa.UserItemID='.$this->GetIntProperty('UserID').'
            GROUP BY d.DeviceID';
        return $stmt->FetchList($query);
    }

    /**
     * Обновление профиля пользователя
     *
     * @param \LocalObject $post
     *
     * @return bool
     * @internal param array $data Данные для регистрации
     *
     */
    public function updatePublic(LocalObject $post)
    {
    	if(!$this->saveUserImage($this->GetProperty('UserImage'))){
    		return false;
    	}

    	if (!$post->ValidateNotEmpty('UserName')) {
    		$this->AddError('registration-username-empty', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('UserEmail')) {
    		$this->AddError('registration-email-empty', $this->module);
    	} elseif (!filter_var($post->GetProperty('UserEmail'), FILTER_VALIDATE_EMAIL)) {
    		$this->AddError('registration-email-incorrect', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('UserWho')) {
    		$this->AddError('registration-whoami-empty', $this->module);
    	} elseif (!in_array($post->GetProperty('UserWho'), array('child', 'parent', 'student'))) {
    		$this->AddError('registration-whoami-incorrect', $this->module);
    	} elseif (in_array($post->GetProperty('UserWho'), array('child', 'parent'))) {
    		if (!$post->ValidateNotEmpty('ClassNumber')) {
    			$this->AddError('registration-classnumber-empty', $this->module);
    		} elseif ($post->GetIntProperty('ClassNumber') < 1 or $post->GetIntProperty('ClassNumber') > 11) {
    			$this->AddError('registration-classnumber-incorrect', $this->module);
    		}
    	}

    	if ($this->HasErrors()) {
    		return false;
    	}

    	$stmt = GetStatement();
    	$query = "SELECT UserID FROM users_item WHERE UserEmail=".$post->GetPropertyForSQL('Email')." AND UserID<>".$post->GetIntProperty('UserID');
    	if ($stmt->FetchField($query)) {
    		$this->AddError('users-registration-email-exists', $this->module);
    		return false;
    	}

    	$query = "UPDATE users_item SET
    		UserEmail=".$post->GetPropertyForSQL('UserEmail').",
    		UserName=".$post->GetPropertyForSQL('UserName').",
    		UserPhone=".$post->GetPropertyForSQL('UserPhone').",
    		UserWho=".$post->GetPropertyForSQL('UserWho').",";
    		
    	if($post->GetIntProperty('ClassNumber') > 0){
    		$query .= "ClassNumber=".$post->GetPropertyForSQL('ClassNumber').",";
    	}
    	
    	$query .= "UserImage=".$this->GetPropertyForSQL('UserImage')."
    		WHERE UserID=".$post->GetIntProperty('UserID');

    	if ($stmt->Execute($query)) {

    		$session = GetSession();
    		$session->SetProperty('UserItem', array(
    				'UserID' => $post->GetIntProperty('UserID'),
    				'UserEmail' => filter_var($post->GetProperty('UserEmail'), FILTER_SANITIZE_STRING),
    				'UserName' => filter_var($post->GetProperty('UserName'), FILTER_SANITIZE_STRING),
    				'UserPhone' => filter_var($post->GetProperty('UserPhone'), FILTER_SANITIZE_STRING),
    				'UserWho' => filter_var($post->GetProperty('UserWho'), FILTER_SANITIZE_STRING),
    				'ClassNumber' => filter_var($post->GetProperty('ClassNumber'), FILTER_SANITIZE_STRING),
    				'UserImage' => $this->GetProperty('UserImage'),
    		));
    		$session->SaveToDB();

    		return true;
    	}

    	return false;
    }

    /**
     * Смена пароля
     *
     * @param \LocalObject $post
     *
     * @return bool
     * @internal param array $data Данные для регистрации
     *
     */
    public function changePassword($post)
    {
    	if (!$post->ValidateNotEmpty('OldPassword')) {
    		$this->AddError('users-oldpassword-empty', $this->module);
    	} elseif (!$post->ValidateNotEmpty('NewPassword')) {
    		$this->AddError('users-newpassword-empty', $this->module);
    	} else {
    		$post->SetProperty('OldPassword', md5($post->GetProperty('OldPassword')));
    		$post->SetProperty('NewPassword', md5($post->GetProperty('NewPassword')));
    	}

    	if ($this->HasErrors()) {
    		return false;
    	}

    	$stmt = GetStatement();
    	$query = "SELECT UserID FROM users_item WHERE UserID=".$post->GetIntProperty('UserID')." AND UserPass=".$post->GetIntProperty('OldPassword');
    	if (!$stmt->FetchField($query)) {
    		$this->AddError('users-oldpassword-incorrect', $this->module);
    		return false;
    	}

    	$query = "UPDATE users_item SET
    		UserPass=".$post->GetPropertyForSQL('NewPassword')."
    	 	WHERE UserID=".$post->GetIntProperty('UserID');

    	if ($stmt->Execute($query)) {
    		return true;
    	}

    	return false;
    }
    
    /**
     * Отправить ссылку на восстановление пароля
     */
    public function SendRestoreLink($email)
    {
        $stmt = GetStatement();
        $query = "SELECT UserID FROM users_item WHERE UserEmail=".Connection::GetSQLString($email);
        $userID = $stmt->FetchField($query);
        if($userID) 
        {
            $code = $this->RandStr(10);
            $query = "INSERT INTO users_restore SET
				Code=".Connection::GetSQLString($code).",
				UserID=".$userID.",
				Email=".Connection::GetSQLString($email).",
				Created=".Connection::GetSQLString(GetCurrentDateTime());
            if($stmt->Execute($query))
            {
                //email notification to user
                $template = new Page();
                if($template->LoadByStaticPath("users-restore"))
                {
                    $content = $template->GetProperty("Content");
                    $content = str_replace("[Code]", $code, $content);
                    SendMailFromAdmin($email, "Навигатор поступления: восстановление пароля", $content);
                }
                return true;   
            }
        }
        return false;
    }
    
    /**
     * Сброс пароля по коду
     */
    public function RestorePassword($code, $newPassword)
    {
        $stmt = GetStatement();
        $query = "SELECT UserID, Email FROM users_restore WHERE Code=".Connection::GetSQLString($code);
        $row = $stmt->FetchRow($query);
        if($row && strlen($newPassword) > 0)
        {
            $query = "UPDATE users_item SET UserPass=".Connection::GetSQLString(md5($newPassword))." WHERE UserID=".intval($row["UserID"]);
            $stmt->Execute($query);
            
            $query = "DELETE FROM users_restore WHERE Email=".Connection::GetSQLString($row["Email"]);
            $stmt->Execute($query);
            
            return true;
        }
        return false;
    }

    protected function saveUserImage($savedImage = "")
    {
    	$fileSys = new FileSys();

    	$newItemImage = $fileSys->Upload("UserImage", USERS_IMAGE_DIR, false, $this->_acceptMimeTypes);
    	if ($newItemImage)
    	{
    		$this->SetProperty("UserImage", $newItemImage["FileName"]);

    		// Remove old image if it has different name
    		if ($savedImage && $savedImage != $newItemImage["FileName"])
    			@unlink(USERS_IMAGE_DIR.$savedImage);
    	}
    	else
    	{
    		if ($savedImage)
    			$this->SetProperty("UserImage", $savedImage);
    		else
    			$this->SetProperty("UserImage", null);
    	}

    	$this->LoadErrorsFromObject($fileSys);
    	return !$fileSys->HasErrors();
    }

    public function createAuthKey(){
        $stmt = GetStatement();

        $userID = $this->GetIntProperty('UserID');
        $key = md5(uniqid($userID));
        $query = "UPDATE users_item SET AuthKey = " . Connection::GetSQLString($key) . " WHERE UserID = " . intval($userID);
        if ($stmt->Execute($query)){
            return $key;
        }

        return false;
    }

    public function getIDByEmail($email){
        $stmt = GetStatement();
        if ($id = $stmt->FetchField("SELECT UserID FROM users_item WHERE UserEmail = " . Connection::GetSQLString($email))){
            return $id;
        }
        else{
            $this->AddError('registration-email-incorrect', $this->module);
            $this->errorNames[] = "Email";
        }
        return false;
    }

	protected function RandStr($size)
	{
	    $feed = "0123456789abcdefghijklmnopqrstuvwxyz";
	    $randStr = "";
	    for ($i = 0; $i < $size; $i++)
	    {
	        $randStr .= substr($feed, rand(0, strlen($feed) - 1), 1);
	    }
	    return $randStr;
	}


    public static function getUserWhoStatusBy(DateTime $date){
        $status = null;
        $now = new DateTime();
        $age = $now->diff($date)->y;

        $class = null;
        $classAge = 7;

        if ($age >= 25) {
            $status = self::USER_WHO_PARENT;
        }
        elseif($age >= 7){
            for ($i=1; $i <= 11; $i++) {
                if ($classAge === $age) {
                    $class = $i;
                    break;
                }
                $classAge++;
            }

            if ($class > 0) {
                $status = self::USER_WHO_CHILD;
            }
            else{
                $status = self::USER_WHO_STUDENT;
            }
        }
        else{
            $status = self::USER_WHO_CHILD;
        }


        if ($status !== null) {
            $result = array(
                'Status' => $status,
                'Class' => $class,
            );

            return $result;
        }

        return false;
    }

	public function switchCommentStatus(){
		$this->SetProperty('CommentsStatus', ($this->GetProperty('CommentsStatus') == 'Y' ? 'N' : 'Y'));
	}
}
