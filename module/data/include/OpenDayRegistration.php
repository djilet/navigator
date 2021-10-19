<?php
es_include("swagger.php");
class OpenDayRegistration extends LocalObject
{
    const MODULE_NAME = 'data';

    public function registration(LocalObject $request, OpenDay $openDay, UserItem $user, $staticPath, $createAccount = true, callable $callback = null)
    {
        $stmt = GetStatement();

        $forms = [];
        foreach ($request->GetProperty('RegisterForm') as $name => $fields) {
            foreach ($fields as $key => $field) {
                $forms[$key][$name] = $field;
            }
        }

        $errors = false;
        foreach ($forms as $fk => $form) {
            $forms[$fk]['ErrorList'] = [];

            if (empty($form['UserName'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-user-name-empty',
                    self::MODULE_NAME
                );
            }
            if (isset($form['UserEmail']) && empty($form['UserEmail'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-user-email-empty',
                    self::MODULE_NAME
                );
            } elseif (isset($form['UserEmail']) && !filter_var($form['UserEmail'], FILTER_VALIDATE_EMAIL)) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-user-email-incorrect',
                    self::MODULE_NAME
                );
            }
            if (empty($form['UserPhone'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-user-phone-empty',
                    self::MODULE_NAME
                );
            }
            if (empty($form['UserWho'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-user-who-empty',
                    self::MODULE_NAME
                );
            } elseif (!in_array($form['UserWho'], ['child', 'parent', 'cparent', 'student', 'specialist', 'other'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-user-who-incorrect',
                    self::MODULE_NAME
                );
            }
            if ($form['UserWho'] == 'child' || $form['UserWho'] == 'parent') {
                if (empty($form['UserClassNumber']) || intval($form['UserClassNumber']) < 1) {
                    $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                        'open-day-register-user-class-name-empty',
                        self::MODULE_NAME
                    );
                }
            }
            if (isset($form['RegistrationID']) && empty($form['RegistrationID'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-registration-id-empty',
                    self::MODULE_NAME
                );
            } else if(isset($form['RegistrationID']) && intval($form['RegistrationID']) < 100000000) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'open-day-register-registration-id-incorrect',
                    self::MODULE_NAME
                );
            }
            else if(isset($form['RegistrationID'])) {
                $query = "SELECT count(*) FROM `data_open_day_registration` WHERE RegistrationID=".intval($form['RegistrationID']);
                if($stmt->FetchField($query) > 0){
                    $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                        'open-day-register-registration-id-exists',
                        self::MODULE_NAME
                    );
                }
            }

            if (! empty($forms[$fk]['ErrorList'])) {
                $errors = true;
            }
        }

        if ($errors) {
            $request->RemoveProperty('RegisterForm');
            $request->SetProperty('RegisterFormList', $forms);
            return false;
        }

        $baseRegistrationID = null;
        foreach ($forms as $form) {
            if ($form['UserWho'] == 'student') {
                $form['UserWhoStr'] = 'Студент';
            } elseif ($form['UserWho'] == 'teacher') {
                $form['UserWhoStr'] = 'Учитель';
            } elseif ($form['UserWho'] == 'parent' || $form['UserWho'] == 'cparent') {
                $form['UserWhoStr'] = 'Родитель';
            } elseif ($form['UserWho'] == 'child') {
                $form['UserWhoStr'] = 'Ученик';
            } elseif ($form['UserWho'] == 'specialist') {
                $form['UserWhoStr'] = 'Молодой специалист';
            } elseif ($form['UserWho'] == 'other') {
                $form['UserWhoStr'] = 'Другое';
            }

            $form['UserInterestStr'] = '';
            if (isset($form['UserInterest'])){
                if ($form['UserInterest'] == 'russia') {
                    $form['UserInterestStr'] = 'Образование в РФ';
                } elseif ($form['UserInterest'] == 'outside') {
                    $form['UserInterestStr'] = 'Образование за рубежом';
                } elseif ($form['UserInterest'] == 'career') {
                    $form['UserInterestStr'] = 'Карьера';
                } elseif ($form['UserInterest'] == 'business') {
                    $form['UserInterestStr'] = 'Бизнес';
                } else {
                    $form['UserInterestStr'] = $form['UserInterest'];
                }
            }

            $query = "INSERT INTO `data_open_day_registration` SET
                  `DeviceID` = '',
                   `EventID` = ".$openDay->GetIntProperty('ID').",
                `StaticPath` = ".Connection::GetSQLString($staticPath).",
                 `FirstName` = ".Connection::GetSQLString($form['UserName']).",
                  `LastName` = ".Connection::GetSQLString($form['UserLastName']).",
                      `City` = ".$openDay->GetPropertyForSQL('CityTitle').",
                       `Who` = ".Connection::GetSQLString($form['UserWhoStr']).",
                     `Class` = ".Connection::GetSQLString($form['UserClassNumber']).",
                     `Phone` = ".Connection::GetSQLString($form['UserPhone']).",
                      `Time` = ".Connection::GetSQLString($form['UserTime']).",
                  `Interest` = ".Connection::GetSQLString($form['UserInterestStr']).",
            		`Source` = 'website'";

            $session =& GetSession();
            if($session->GetProperty('utm_source'))
            {
                $query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
            	utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
            	utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
            	utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
            	utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
            }

            if(isset($form['UserEmail']))
            {
                $query .= ", Email=".Connection::GetSQLString($form['UserEmail']);
            }

            if($user && $user->GetProperty('UserID'))
            {
                $query .= ", UserID=".$user->GetIntProperty('UserID');
            }

            if($baseRegistrationID != null)
            {
                $query .= ", BaseRegistrationID=".$baseRegistrationID;
            }

            if(isset($form['RegistrationID']))
            {
                $query .= ", RegistrationID=".intval($form['RegistrationID']);
            }
            else
            {
                $maxRegistrationID = $stmt->FetchField("SELECT MAX(RegistrationID) FROM `data_open_day_registration` WHERE RegistrationID<100000000");
                $query .= ", RegistrationID=".(intval($maxRegistrationID) + 1);
            }

            if ($stmt->Execute($query)) {
                $registrationID = $stmt->GetLastInsertID();
                if($baseRegistrationID == null) {
                    $baseRegistrationID = $registrationID;
                    $this->SetProperty('RegistrationIDList', $registrationID);
                }
                else {
                    $this->SetProperty('RegistrationIDList', $this->GetProperty('RegistrationIDList').','.$registrationID);
                }

                $this->SetProperty('RegistrationID', $registrationID);
                $this->AddMessage('open-day-register-success', self::MODULE_NAME, ['UserName' => $form['UserName']]);


                //email notification to user
                $content = $openDay->GetProperty('EmailTemplate');
                $content = str_replace("[FirstName]", $form['UserName'], $content);
                $content = str_replace("[LastName]", $form['UserLastName'], $content);
                $content = str_replace("[Time]", $form['UserTime'], $content);
                $content = str_replace("[Phone]", preg_replace("/[^0-9,.]/", "", $form['UserPhone']), $content);
                $content = str_replace("[Address]", $openDay->GetProperty('Address'), $content);
                $language =& GetLanguage();
                $format = $language->GetDateFormat();
                $content = str_replace("[Date]", LocalDate($format, strtotime($openDay->GetProperty('Date'))), $content);
                $mapLink = "https://yandex.ru/maps/?ll=".$openDay->GetProperty('Longitude').",".$openDay->GetProperty('Latitude')."&z=15&pt=".$openDay->GetProperty('Longitude').",".$openDay->GetProperty('Latitude');
                $content = str_replace("[MapLink]", $mapLink, $content);
                $content = str_replace("[TicketNumber]", $registrationID, $content);

                $newUser = new UserItem();
                if ($id = $newUser->getIDByEmail($form['UserEmail'])){
                    $newUser->loadByID($id);
                    $authKey = $newUser->createAuthKey();
                    $content = str_replace("[AuthKey]", $authKey, $content);
                }
                else{
                    //automatic account registration
                    if($createAccount == true && isset($form['UserEmail'])){
                        $user->registrationFromExhibition($form);
                    }
                }

                SendMailFromAdmin($form['UserEmail'], $openDay->GetProperty('EmailTheme'), $content);
                //create short link
                $shortLink = self::getShortRegistrationURL(GetUrlPrefix() . $staticPath, $registrationID);
                if($shortLink)
                {
                    $stmt->Execute("UPDATE `data_open_day_registration` SET ShortLink=".Connection::GetSQLString($shortLink)." WHERE RegistrationID=".$registrationID);
                }

                if (is_callable($callback)){
                    call_user_func_array($callback, array());
                }
            }
        }
        return true;
    }

    private function saveRegistrationId($usersId, $registrationId)
    {
        $stmt = GetStatement();
        foreach ($usersId as $id) {
            $query = "UPDATE data_open_day_registration SET CRMRegistrationId=" . Connection::GetSQLString($registrationId) .
            " WHERE RegistrationID=" . Connection::GetSQLString($id);

            $stmt->Execute($query);
        }
    }

    public static function getRegistrationListInfo($ids)
    {
        $stmt = GetStatement();
        $query = "SELECT r.RegistrationID, r.FirstName, r.LastName
            FROM `data_open_day_registration` r
            WHERE r.RegistrationID IN (" . implode(", ", $ids) . ")
            ORDER BY r.RegistrationID";
        return $stmt->FetchList($query);
    }

    public static function addAdditionalFields(LocalObject $request)
    {
        $forms = [];
        foreach ($request->GetProperty('RegisterForm') as $name => $fields) {
            foreach ($fields as $key => $field) {
                $forms[$key][$name] = $field;
            }
        }

        $stmt = GetStatement();
        foreach ($forms as $form) {
            if(isset($form['RegistrationID'])){
                $updateFields = array();
                if(isset($form['BigDirection'])){
                    $updateFields[] = "`AdditionalBigDirection` = ".Connection::GetSQLString($form['BigDirection']);
                }
                if(isset($form['University'])){
                    $updateFields[] = "`AdditionalUniversity` = ".Connection::GetSQLString($form['University']);
                }
                if(isset($form['Type'])){
                    $updateFields[] = "`AdditionalType` = ".Connection::GetSQLString($form['Type']);
                }
                if(count($updateFields) > 0){
                    $query = "UPDATE `data_open_day_registration` SET ".implode(",", $updateFields)." WHERE RegistrationID=".intval($form['RegistrationID']);
                    $stmt->Execute($query);
                }
            }
        }

        return true;
    }

    public static function getShortRegistrationURL(string $basePath, int $registrationID)
    {
        $longUrl = "{$basePath}/?Registration=".$registrationID;
        if ($url = GetShortURL($longUrl)){
            return $url;
        }
        return false;
    }

    public static function getTicketPage($registrationID)
    {
        $stmt = GetStatement();
        $query = 'SELECT e.UserID, e.FirstName, e.LastName, e.Time, e.Phone, d.Address, d.Date, d.Latitude, d.Longitude, d.EmailTemplate
    		FROM data_open_day_registration e
    		LEFT JOIN data_open_day d ON e.EventID=d.ID
    		WHERE e.RegistrationID='.intval($registrationID);
        $info = $stmt->FetchRow($query);
        if($info)
        {
            $content = $info['EmailTemplate'];
            $content = str_replace("[FirstName]", $info['FirstName'], $content);
            $content = str_replace("[LastName]", $info['LastName'], $content);
            $content = str_replace("[Time]", $info['Time'], $content);
            $content = str_replace("[Phone]", preg_replace("/[^0-9,.]/", "", $info['Phone']), $content);
            $content = str_replace("[Address]", $info['Address'], $content);
            $language =& GetLanguage();
            $format = $language->GetDateFormat();
            $content = str_replace("[Date]", LocalDate($format, strtotime($info['Date'])), $content);
            $mapLink = "https://yandex.ru/maps/?ll=".$info['Longitude'].",".$info['Latitude']."&z=15&pt=".$info['Longitude'].",".$info['Latitude'];
            $content = str_replace("[MapLink]", $mapLink, $content);
            $content = str_replace("[TicketNumber]", $registrationID, $content);
            $content = str_replace("[AuthKey]", '', $content);
            return $content;
        }
        return false;
    }

    public static function checkRegistered(int $openDayID, UserItem $user)
    {
        $stmt = GetStatement();
        $query = "SELECT count(*) FROM data_open_day_registration WHERE 
      		EventID=".$openDayID." AND 
      		UserID=".Connection::GetSQLString($user->GetIntProperty("UserID"));

        return $stmt->FetchField($query);
    }
}