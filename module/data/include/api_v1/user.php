<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobject.php");

class DataUser extends LocalObject
{

    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

	public function auth($email, $pass, $device)
    {
        if (!$email or !$pass) {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $stmt = GetStatement();
        $query = "SELECT UserID FROM users_item WHERE UserEmail='{$email}' AND UserPass=".Connection::GetSQLString(md5($pass));
        if ($userId = $stmt->FetchField($query)) {
			$stmt->Execute("DELETE FROM user_item2device WHERE Device=".Connection::GetSQLString($device));
			$stmt->Execute("INSERT INTO user_item2device VALUE({$userId}, ".Connection::GetSQLString($device).")");
            return true;
        }

        return false;
    }


	public function isAuth($device)
    {
        $stmt = GetStatement();
        $query = "SELECT u.UserEmail, u.UserName, u.UserPhone, u.UserWho, u.ClassNumber
            FROM users_item AS u
            INNER JOIN user_item2device AS i2u ON u.UserID=i2u.ItemID
            WHERE i2u.Device=".Connection::GetSQLString($device);
        if ($row = $stmt->FetchRow($query)) {
            return $row;
        }

        return false;
    }

    public function reg(LocalObject $request)
    {
        $stmt = GetStatement();

        if (!$request->ValidateNotEmpty('UserName')) {
            $this->errorNames[] = "UserName";
            $this->AddError('api-registration-username-empty', $this->module);
        }

        if (!$request->ValidateNotEmpty('Email')) {
            $this->errorNames[] = "Email";
            $this->AddError('api-registration-email-empty', $this->module);
        } elseif (!filter_var($request->GetProperty('Email'), FILTER_VALIDATE_EMAIL)) {
            $this->errorNames[] = "Email";
            $this->AddError('api-registration-email-incorrect', $this->module);
        } else {
            $result = $stmt->FetchField("SELECT COUNT(UserID) FROM `users_item` WHERE UserEmail=".$request->GetPropertyForSql('Email'));
            if ($result > 0) {
                $this->AddError('api-registration-email-already-exists', $this->module);
            }
        }

        if (!$request->ValidateNotEmpty('WhoAmI')) {
            $this->errorNames[] = "WhoAmI";
            $this->AddError('api-registration-whoami-empty', $this->module);
        } elseif (!in_array($request->GetProperty('WhoAmI'), array('child', 'parent', 'student'))) {
            $this->errorNames[] = "WhoAmI";
            $this->AddError('api-registration-whoami-incorrect', $this->module);
        } elseif (in_array($request->GetProperty('WhoAmI'), array('child', 'parent'))) {
            if (!$request->ValidateNotEmpty('ClassNumber')) {
                $this->errorNames[] = "ClassNumber";
                $this->AddError('api-registration-classnumber-empty', $this->module);
            } elseif ($request->GetIntProperty('ClassNumber') < 1 or $request->GetIntProperty('ClassNumber') > 11) {
                $this->errorNames[] = "ClassNumber";
                $this->AddError('api-registration-classnumber-incorrect', $this->module);
            }
        }
        else {
            $request->RemoveProperty('ClassNumber');
        }
        
        if (!$request->ValidateNotEmpty('Pass')) {
            $this->AddError('api-registration-pass-empty', $this->module);
        } elseif (!$request->ValidateNotEmpty('PassRepeat')) {
            $this->AddError('api-registration-pass-repeat-empty', $this->module);
        } elseif ($request->GetProperty('Pass') !== $request->GetProperty('PassRepeat')) {
            $this->AddError('api-registration-pass-do-not-match', $this->module);
        } else {
            $request->SetProperty('Pass', md5($request->GetProperty('Pass')));
        }

        if ($this->HasErrors()) {
            return false;
        }

        $query = "INSERT INTO users_item SET
            UserEmail=".$request->GetPropertyForSQL('Email').",
            UserPass=".$request->GetPropertyForSQL('Pass').",
            UserName=".$request->GetPropertyForSQL('UserName').",
            UserPhone=".$request->GetPropertyForSQL('Phone').",
            UserWho=".$request->GetPropertyForSQL('WhoAmI').",
            ClassNumber=".$request->GetPropertyForSQL('ClassNumber').",
            Created=".Connection::GetSQLString(GetCurrentDateTime());

        if ($stmt->Execute($query)) {
            $userId = $stmt->GetLastInsertID();

            $this->auth(
                $request->GetProperty('Email'),
                $request->GetProperty('PassRepeat'),
                $request->GetProperty("AuthDeviceID")
            );

            if ($request->IsPropertySet('SocialType') && $request->IsPropertySet('SocialID')) {
                $query = 'INSERT INTO social_auth SET SocialType='.$request->GetPropertyForSQL('SocialType').',
                    SocialID='.$request->GetPropertyForSQL('SocialID').',
                    UserItemID='.$userId;
                $stmt->Execute($query);
            }

            return true;
        }

        return false;
    }

    public function authBySocialId(LocalObject $request) {
        $stmt = GetStatement();

        $query = 'SELECT UserItemID FROM social_auth
            WHERE SocialType='.$request->GetPropertyForSQL('SocialType').'
            AND SocialID='.$request->GetPropertyForSQL('SocialID');
        if (($userId = $stmt->FetchField($query)) > 0) {
            $query = 'INSERT IGNORE INTO user_item2device SET ItemID='.intval($userId).',
                Device='.$request->GetPropertyForSQL('AuthDeviceID');
            $stmt->Execute($query);

            $query = "SELECT UserEmail, UserName, UserPhone, UserWho, ClassNumber
                FROM users_item
                WHERE UserID=".$userId;

            return $stmt->FetchRow($query);
        }

        return false;
    }
}
