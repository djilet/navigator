<?php
require_once dirname(__FILE__) . '/../common/ExhibitionCityCommon.php';
require_once dirname(__FILE__) . '/../exhibition_property.php';
es_include("swagger.php");
class PublicExhibition extends LocalObject
{
    use ExhibitionCityCommon;

    private $module;
    private $params = [];
    protected $propertyList;

    /**
     * Exhibition constructor.
     *
     * @param $module
     */
    public function __construct($module)
    {
        $this->module = $module;
        $this->params['ItemImage'] = LoadImageConfig('ItemImage', $this->module, DATA_EXHIBITION_INFOITEM_IMAGE);
        $this->params['UniversityLogo'] = LoadImageConfig('UniversityLogo', $this->module, '136x64|1|Thumb,216x200|11|ThumbLanding');
        $this->params['PartnerImage'] = LoadImageConfig('PartnerImage', $this->module, '176x64|1|Thumb,216x200|11|ThumbLanding');
        $this->params['HeadImage'] = LoadImageConfig('HeadImage', $this->module, DATA_EXHIBITION_HEAD_IMAGE);
    }

    public function loadCurrent($pageID)
    {
        $query = 'SELECT ex.*
            FROM `data_exhibition` AS ex
            WHERE ex.PageID=' . intval($pageID) . ' OR ex.Page2ID=' . intval($pageID) . '
            ORDER BY DateFrom DESC
            LIMIT 1';
        $this->LoadFromSQL($query);
        $this->prepareItem();
    }

    public function loadByID($id)
    {
        $query = 'SELECT ex.*
            FROM `data_exhibition` AS ex
            WHERE ex.ExhibitionID = ' . intval($id) . '
            ORDER BY DateFrom DESC
            LIMIT 1';
        $this->LoadFromSQL($query);
        $this->prepareItem();
    }

    /**
     * Use after load
     */
    public function prepareItem()
    {
        $infoList = $this->GetProperty('InfoList');
        if (!empty($infoList)) {
            $infoList = json_decode($infoList, true);
            if ($infoList) {
                foreach ($infoList as $key => $item) {
                    if (!empty($item['Image'])) {
                        foreach ($this->params['ItemImage'] as $param) {
                            $infoList[$key][$param['Name'] . 'Path'] = $param['Path'] . 'exhibition/' . $item['Image'];
                        }
                    }
                }
                $this->SetProperty('InfoList', $infoList);
            }
        }

        $dateFrom = new DateTime($this->GetProperty('DateFrom'), new DateTimeZone('Europe/Moscow'));
        $this->SetProperty('StartDateUTC', $dateFrom->format("Y-m-d H:i:s e"));
        $dateTo = new DateTime($this->GetProperty('DateTo'), new DateTimeZone('Europe/Moscow'));
        $this->SetProperty('EndDateUTC', $dateTo->format("Y-m-d H:i:s e"));

        //TODO one method for all models
        //Prepare properties
        $this->propertyList = new ExhibitionPropertyList();
        $this->propertyList->loadByExhibition($this->GetIntProperty('ExhibitionID'));

        foreach ($this->propertyList->GetItems() as $index => $property) {
            $this->SetProperty('Property' . $property['Property'], $property['Value']);
        }
    }

    public function checkRegistered($user, $cityTitle)
    {
        $stmt = GetStatement();
        $query = "SELECT count(*) FROM event_registrations WHERE
      		EventID=" . $this->GetIntProperty("ExhibitionID") . " AND
      		UserID=" . Connection::GetSQLString($user->GetIntProperty("UserID")) . " AND
      		City=" . Connection::GetSQLString($cityTitle);
        return $stmt->FetchField($query);
    }

    public function loadCityList($city = '')
    {
        $stmt = GetStatement();
        $query = 'SELECT `CityID`, `ExhibitionID`, `Title`, `CityTitle`, `StaticPath`, `Date`, `Address`,
                `Latitude`, `Longitude`, `InfoList`, `SortOrder`, Description, TitleSchedule, TitleRegister, GUID, Active,
                IF(`StaticPath`=' . Connection::GetSQLString($city) . ', 1, 0) AS Selected, EmailTemplate, EmailTheme, Email as CityEmail, Phone as CityPhone, ManualDate, OnlineEventID, HeadImage
            FROM `data_exhibition_city`
            WHERE ExhibitionID=' . $this->GetIntProperty('ExhibitionID') . '
            ORDER BY SortOrder ASC';
        $list = $stmt->FetchList($query);
        if ($list) {
            foreach ($list as $key => $item) {
                if ($key == 0) {
                    $list[$key]['Default'] = 1;
                    if (empty($city)) {
                        $list[$key]['Selected'] = 1;
                    }
                }

                if (!empty($item['InfoList'])) {
                    $info = json_decode($item['InfoList'], true);
                    if (is_array($info) and !empty($info)) {
                        foreach ($info as $k3 => $i) {
                            if (!empty($i['Image'])) {
                                foreach ($this->params['ItemImage'] as $param) {
                                    $info[$k3][$param['Name'] . 'Path'] = $param['Path'] . 'exhibition/' . $i['Image'];
                                }
                            }
                        }

                        $list[$key]['InfoList'] = $info;

                    } else {
                        $list[$key]['InfoList'] = false;
                    }
                }

                if ($list[$key]['Selected']) {
                    foreach ($this->params['HeadImage'] as $param) {
                        if (!empty($list[$key]['HeadImage'])) {
                            $this->SetProperty($param['Name'] . 'Path', $param['Path'] . 'exhibition/' . $list[$key]['HeadImage']);
                        }
                    }
                }
            }

            $this->SetProperty('CityList', $list);
        }
    }

    public function loadCityInfo($cityID)
    {
        $stmt = GetStatement();
        $query = 'SELECT `CityID`, `ExhibitionID`, `Title`, `CityTitle`, `StaticPath`, `Date`, `Address`,
                `Latitude`, `Longitude`, `InfoList`, `SortOrder`, Description, TitleSchedule, TitleRegister, GUID, Active,
                EmailTemplate, EmailTheme, OnlineEventID
            FROM `data_exhibition_city`
            WHERE CityID=' . intval($cityID);
        return $stmt->FetchRow($query);
    }

    public function getRoomList($item)
    {
        $stmt = GetStatement();
        $query = 'SELECT Title
            FROM `data_exhibition_room`
            WHERE CityID=' . intval($item['CityID']) . '
            ORDER BY Title';
        return $stmt->FetchList($query);
    }

    public function getCityScheduleOld($item, $groupByDates = false)
    {
        $shedule = $this->loadSchedule($item['CityID']);
        if ($shedule) {
            $allRoom = [];
            $allRoomByDate = [];
            if (is_array($shedule) and !empty($shedule)) {
                foreach ($shedule as $k2 => $room) {

                    $result = array();
                    foreach ($room['ActionList'] as $action) {
                        if (empty($action['Title']) or empty($action['TimeFrom'])) {
                            continue;
                        }

                        $time = explode(':', $action['TimeFrom']);
                        $hour = $time[0];

                        $action['RoomTitle'] = $room['Title'];

                        if (!isset($result[$hour])) {
                            $result[$hour] = array(
                                'Title' => $hour . ':00',
                                'ItemList' => array(),
                            );
                        }

                        $result[$hour]['ItemList'][] = $action;

                        if ($groupByDates) {
                            if (!isset($allRoomByDate[$room['Date']][$hour])) {
                                $allRoomByDate[$room['Date']][$hour] = array(
                                    'Title' => $hour . ':00',
                                    'ItemList' => array(),
                                );
                            }
                            $allRoomByDate[$room['Date']][$hour]['ItemList'][] = $action;
                        } else {
                            if (!isset($allRoom[$hour])) {
                                $allRoom[$hour] = array(
                                    'Title' => $hour . ':00',
                                    'ItemList' => array(),
                                );
                            }
                            $allRoom[$hour]['ItemList'][] = $action;
                        }
                    }

                    $shedule[$k2]['ActionList'] = array_values($result);
                }

                if ($groupByDates) {
                    $result = array();

                    uksort($allRoomByDate, function ($a, $b) {
                        if ($a == $b) {
                            return 0;
                        }
                        return (strtotime($a) < strtotime($b)) ? -1 : 1;
                    });

                    $allRoomByDateSort = array();
                    foreach ($allRoomByDate as $date => $allRoom) {
                        usort($allRoom, function ($a, $b) {
                            return strcmp($a['Title'], $b['Title']);
                        });
                        for ($i = 0; $i < count($allRoom); $i++) {
                            usort($allRoom[$i]['ItemList'], function ($a, $b) {
                                return strcmp($a['TimeFrom'], $b['TimeFrom']);
                            });
                        }
                        $allRoomByDateSort[$date] = $allRoom;
                    }

                    $dates = array();
                    foreach ($allRoomByDateSort as $date => $time) {
                        $dates[] = $date;
                    }

                    foreach ($dates as $date) {
                        $result[] = array(
                            'Date' => $date ?? null,
                            'ShortDate' => $date ? strftime("%e %B", strtotime($date)) : null,
                            'RoomList' => array(array(
                                'Title' => GetTranslation('AllRooms', $this->module),
                                'ActionList' => $allRoomByDateSort[$date],
                            )),
                        );
                    }

                    foreach ($shedule as $room) {
                        $dateKey = array_search($room['Date'], $dates);
                        $result[$dateKey]['RoomList'][] = $room;

                        if (!empty($room['Title'])) {
                            $result[$dateKey]['ShowRooms'] = true;
                        }
                    }

                    $shedule = $result;
                } else {
                    //order for full list
                    usort($allRoom, function ($a, $b) {
                        return strcmp($a['Title'], $b['Title']);
                    });
                    for ($i = 0; $i < count($allRoom); $i++) {
                        usort($allRoom[$i]['ItemList'], function ($a, $b) {
                            return strcmp($a['TimeFrom'], $b['TimeFrom']);
                        });
                    }

                    if ($item['StaticPath'] != 'online') //HACK: temporary for all cities
                    {
                        array_unshift($shedule, [
                            'Title' => GetTranslation('AllRooms', $this->module),
                            'ActionList' => array_values($allRoom),
                        ]);
                    }
                }

                return $shedule;

            } else {
                return false;
            }
        }
    }

    public function getCitySchedule($item, $filter = null)
    {
        $shedule = $this->loadSchedule($item['CityID']);
        if ($shedule) {
            $filterType = null;
            $filterTime = null;
            $filterRoom = null;
            $lineLimit = 4;
            if ($filter != null) {
                if ($filter->GetProperty('Filter-type')) {
                    $filterType = explode(';', $filter->GetProperty('Filter-type'));
                }

                if ($filter->GetProperty('Filter-time')) {
                    $filterTime = explode(';', $filter->GetProperty('Filter-time'));
                }

                if ($filter->GetProperty('Filter-room')) {
                    $filterRoom = explode(';', $filter->GetProperty('Filter-room'));
                }

                if ($filter->GetProperty('LineLimit')) {
                    $lineLimit = $filter->GetIntProperty('LineLimit');
                }

            }

            $allRoom = [];
            if (is_array($shedule) and !empty($shedule)) {
                foreach ($shedule as $k2 => $room) {
                    foreach ($room['ActionList'] as $action) {
                        if (empty($action['Title']) or empty($action['TimeFrom'])) {
                            continue;
                        }

                        $time = explode(':', $action['TimeFrom']);
                        $hour = $time[0];

                        $action['RoomTitle'] = $room['Title'];

                        if ($filterType != null && !in_array($action['Type'], $filterType)) {
                            continue;
                        }

                        if ($filterTime != null && !in_array($hour . ':00', $filterTime)) {
                            continue;
                        }

                        if ($filterRoom != null && !in_array($action['RoomTitle'], $filterRoom)) {
                            continue;
                        }

                        if (!isset($allRoom[$hour])) {
                            $allRoom[$hour] = array(
                                'Title' => $hour . ':00',
                                'ItemList' => array(),
                            );
                        }

                        $allRoom[$hour]['ItemList'][] = $action;
                    }
                }

                //order for full list
                usort($allRoom, function ($a, $b) {
                    return strcmp($a['Title'], $b['Title']);
                });

                //sort inline hour and line limits
                $result = array(
                    'LineLimit' => $lineLimit,
                    'TimeList' => array(),
                );
                $itemInRow = 3; //count of items in one line
                for ($i = 0; $i < count($allRoom); $i++) {
                    if ($lineLimit <= 0) {
                        $result['ShowMore'] = 1;
                        break;
                    }
                    usort($allRoom[$i]['ItemList'], function ($a, $b) {
                        return strcmp($a['TimeFrom'], $b['TimeFrom']);
                    });
                    $timeBlock = $allRoom[$i];
                    $eventCount = count($timeBlock['ItemList']);
                    $lineCount = ($eventCount % $itemInRow == 0) ? intval($eventCount / $itemInRow) : (intval($eventCount / $itemInRow) + 1);
                    if ($lineLimit >= $lineCount) {
                        $lineLimit -= $lineCount;
                    } else {
                        $timeBlock['ItemList'] = array_slice($timeBlock['ItemList'], 0, $lineLimit * $itemInRow);
                        $lineLimit = 0;
                    }
                    $result['TimeList'][] = $timeBlock;
                }

                return $result;
            }
        }
        return false;
    }

    /**
     * @param LocalObject $request
     * @param UserItem $user
     * @param $city
     * @param $staticPath
     * @param bool $createAccount (Create account for unregistered users)
     * @param callable|null $callback
     * @return bool
     */
    public function registration(LocalObject $request, UserItem $user, $city, $staticPath, $createAccount = true, callable $callback = null)
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
                    'exhibition-register-user-name-empty',
                    $this->module
                );
            }
            if (isset($form['UserEmail']) && empty($form['UserEmail'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-user-email-empty',
                    $this->module
                );
            } elseif (isset($form['UserEmail']) && !filter_var($form['UserEmail'], FILTER_VALIDATE_EMAIL)) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-user-email-incorrect',
                    $this->module
                );
            }
            if (empty($form['UserPhone'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-user-phone-empty',
                    $this->module
                );
            }
            if (empty($form['UserWho'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-user-who-empty',
                    $this->module
                );
            } elseif (!in_array($form['UserWho'], ['child', 'parent', 'teacher', 'cparent', 'student', 'specialist', 'other'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-user-who-incorrect',
                    $this->module
                );
            }
            if ($form['UserWho'] == 'child' || $form['UserWho'] == 'parent') {
                if (empty($form['UserClassNumber']) || intval($form['UserClassNumber']) < 1) {
                    $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                        'exhibition-register-user-class-name-empty',
                        $this->module
                    );
                }
            }
            if (isset($form['RegistrationID']) && empty($form['RegistrationID'])) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-registration-id-empty',
                    $this->module
                );
            } else if (isset($form['RegistrationID']) && intval($form['RegistrationID']) < 100000000) {
                $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                    'exhibition-register-registration-id-incorrect',
                    $this->module
                );
            } else if (isset($form['RegistrationID'])) {
                $query = "SELECT count(*) FROM `event_registrations` WHERE RegistrationID=" . intval($form['RegistrationID']);
                if ($stmt->FetchField($query) > 0) {
                    $forms[$fk]['ErrorList'][]['Message'] = GetTranslation(
                        'exhibition-register-registration-id-exists',
                        $this->module
                    );
                }
            }

            if (!empty($forms[$fk]['ErrorList'])) {
                $errors = true;
            }
        }

        if ($errors) {
            $request->RemoveProperty('RegisterForm');
            $request->SetProperty('RegisterFormList', $forms);
            return false;
        }

        $baseRegistrationID = null;
        $usersArray = [];
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
            if (isset($form['UserInterest'])) {
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

            $userName = explode(' ', $form['UserName']);

            $query = "INSERT INTO `event_registrations` SET
                  `DeviceID` = '',
                   `EventID` = " . $request->GetIntProperty('ExhibitionID') . ",
                `StaticPath` = " . Connection::GetSQLString($staticPath) . ",
                 `FirstName` = " . Connection::GetSQLString($form['UserName']) . ",
                  `LastName` = " . Connection::GetSQLString($form['UserLastName']) . ",
                      `City` = " . $request->GetPropertyForSQL('city') . ",
                       `Who` = " . Connection::GetSQLString($form['UserWhoStr']) . ",
                     `Class` = " . Connection::GetSQLString($form['UserClassNumber']) . ",
                     `Phone` = " . Connection::GetSQLString($form['UserPhone']) . ",
                      `Time` = " . Connection::GetSQLString($form['UserTime']) . ",
                  `Interest` = " . Connection::GetSQLString($form['UserInterestStr']) . ",
            		`Source` = 'website'";

            if ($form['utm_source']) {
                $query .= ", utm_source=" . Connection::GetSQLString($form['utm_source']) . ",
            	utm_medium=" . Connection::GetSQLString($form['utm_medium']) . ",
            	utm_campaign=" . Connection::GetSQLString($form['utm_campaign']) . ",
            	utm_term=" . Connection::GetSQLString($form['utm_term']) . ",
            	utm_content=" . Connection::GetSQLString($form['utm_content']);
            } else {
                $session = &GetSession();
                if ($session->GetProperty('utm_source')) {
                    $query .= ", utm_source=" . Connection::GetSQLString($session->GetProperty('utm_source')) . ",
            	utm_medium=" . Connection::GetSQLString($session->GetProperty('utm_medium')) . ",
            	utm_campaign=" . Connection::GetSQLString($session->GetProperty('utm_campaign')) . ",
            	utm_term=" . Connection::GetSQLString($session->GetProperty('utm_term')) . ",
            	utm_content=" . Connection::GetSQLString($session->GetProperty('utm_content'));

                    $form['utm_source'] = $session->GetProperty('utm_source');
                    $form['utm_medium'] = $session->GetProperty('utm_medium');
                    $form['utm_campaign'] = $session->GetProperty('utm_campaign');
                    $form['utm_term'] = $session->GetProperty('utm_term');
                    $form['utm_content'] = $session->GetProperty('utm_content');
                }
            }
            if (isset($form['UserEmail'])) {
                $query .= ", Email=" . Connection::GetSQLString($form['UserEmail']);
            }

            if ($user && $user->GetProperty('UserID')) {
                $query .= ", UserID=" . $user->GetIntProperty('UserID');
            }
            if ($baseRegistrationID != null) {
                $query .= ", BaseRegistrationID=" . $baseRegistrationID;
            }

            if (isset($form['RegistrationID'])) {
                $query .= ", RegistrationID=" . intval($form['RegistrationID']);
                $form['RegID'] = $form['RegistrationID'];
            } else {
                $maxRegistrationID = $stmt->FetchField("SELECT MAX(RegistrationID) FROM `event_registrations` WHERE RegistrationID<100000000");
                $query .= ", RegistrationID=" . (intval($maxRegistrationID) + 1);
                $form['RegID'] = intval($maxRegistrationID) + 1;
            }

            $usersArray[] = $form;

            if ($request->IsPropertySet('tranid')) {
                $query .= ", TranId=" . $request->GetPropertyForSQL('tranid');
            }

            if ($stmt->Execute($query)) {
                $registrationID = $stmt->GetLastInsertID();
                if ($baseRegistrationID == null) {
                    $baseRegistrationID = $registrationID;
                    $this->SetProperty('RegistrationIDList', $registrationID);
                } else {
                    $this->SetProperty('RegistrationIDList', $this->GetProperty('RegistrationIDList') . ',' . $registrationID);
                }

                $this->SetProperty('RegistrationID', $registrationID);
                $this->AddMessage('exhibition-register-success', $this->module, ['UserName' => $form['UserName']]);

                //email notification to user
                $content = $city['EmailTemplate'];
                $content = str_replace("[FirstName]", $form['UserName'], $content);
                $content = str_replace("[LastName]", $form['UserLastName'], $content);
                $content = str_replace("[Time]", $form['UserTime'], $content);
                $content = str_replace("[Phone]", preg_replace("/[^0-9,.]/", "", $form['UserPhone']), $content);
                $content = str_replace("[Address]", $city['Address'], $content);
                $language = &GetLanguage();
                $format = $language->GetDateFormat();
                $content = str_replace("[Date]", LocalDate($format, strtotime($city['Date'])), $content);
                $mapLink = "https://yandex.ru/maps/?ll=" . $city['Longitude'] . "," . $city['Latitude'] . "&z=15&pt=" . $city['Longitude'] . "," . $city['Latitude'];
                $content = str_replace("[MapLink]", $mapLink, $content);
                $content = str_replace("[TicketNumber]", $registrationID, $content);

                $newUser = new UserItem();
                if ($id = $newUser->getIDByEmail($form['UserEmail'])) {
                    $newUser->loadByID($id);
                    $authKey = $newUser->createAuthKey();
                    $content = str_replace("[AuthKey]", $authKey, $content);
                } else {
                    //automatic account registration
                    if ($createAccount == true && isset($form['UserEmail'])) {
                        $user->registrationFromExhibition($form);
                        $user->loadBySession();
                    }
                }

                SendMailFromAdmin($form['UserEmail'], $city['EmailTheme'], $content);
                //create short link
                $shortLink = $this->GetShortRegistrationURL($registrationID, $staticPath);
                if ($shortLink) {
                    $stmt->Execute("UPDATE `event_registrations` SET ShortLink=" . Connection::GetSQLString($shortLink) . " WHERE RegistrationID=" . $registrationID);
                }

                //registration for online event
                $onlineEventID = intval($city['OnlineEventID']);
                if ($onlineEventID > 1) {
                    $query = "SELECT * FROM `data_online_event`
                                WHERE `OnlineEventID` = " . $onlineEventID;
                    if ($stmt->FetchRow($query)) {
                        $userID = $user->IsPropertySet('UserID') ? $user->GetProperty('UserID') : $newUser->GetProperty('UserID');

                        $onlineEvents = new OnlineEvents('data');
                        $onlineEvents->signUser($onlineEventID, $userID);
                    }
                }

                //send to CRM
                // if ($request->IsPropertySet('GUID')) {
                //     $guid = $request->GetProperty('GUID');
                //     if (!empty($guid)) {
                //         $this->sendRegistrationToCRM($form, $request->GetPropertyForSQL('city'), $guid);
                //     }
                // }

                if (is_callable($callback)) {
                    call_user_func_array($callback, array());
                }
            }
        }
        if ($request->IsPropertySet('GUID')) {
            $guid = $request->GetProperty('GUID');
            if (!empty($guid)) {
                $parents = [];
                $children = [];
                $extraParents = [];
                foreach ($usersArray as $user) {
                    if ($user['UserWho'] == 'parent' && (count($parents) < 2)) {
                        if (count($parents) == 0) {
                            $parents['first_parent'] = $user;
                        } else {
                            $parents['second_parent'] = $user;
                        }
                    } else {
                        if ($user['UserWho'] == 'parent') {
                            $extraParents[] = $user;
                        } else {
                            $children[] = $user;
                        }
                    }
                }

                $swager = new Swagger();

                // sending for extra parents
                if (count($extraParents) > 0) {
                    foreach ($extraParents as $parent) {
                        $parentData = [];

                        $parentData['first_parent'] = [
                            'email' => $parent['UserEmail'],
                            'phone' => $parent['UserPhone'],
                            'firstName' => $parent['UserName'],
                            'lastname' => $parent['UserLastName'],
                        ];

                        $parentData['ut_mcontent'] = $parent['utm_content'] ? $parent['utm_content'] : null;
                        $parentData['utm_source'] = $parent['utm_source'] ? $parent['utm_source'] : null;
                        $parentData['utm_medium'] = $parent['utm_medium'] ? $parent['utm_medium'] : null;
                        $parentData['utm_campaign'] = $parent['utm_campaign'] ? $parent['utm_campaign'] : null;
                        $parentData['ut_mterm'] = $parent['utm_term'] ? $parent['utm_term'] : null;
                        $parentData['city'] = $request->GetProperty('city') ? $request->GetProperty('city') : null;
                        if ($parent['UserTime'] > date('Y') . '-07-31 10:00') {
                            $parentData['issue_year'] = date('Y') - $parent['UserClassNumber'] + 12;
                        } else {
                            $parentData['issue_year'] = date('Y') - $parent['UserClassNumber'] + 11;
                        }

                        $registrationId = json_decode($swager->sendFamilyToCRM($parentData, [$parent['RegID']], $guid))->registrationId;
                        if ($registrationId) {
                            $this->saveRegistrationId([$parent['RegID']], $registrationId);
                        }
                    }
                }
                // -------------------------------

                // normal family
                if (count($children) > 0) {
                    foreach ($children as $key => $child) {
                        $familyGroup = [];
                        $usersId = [];
                        foreach ($parents as $key => $parent) {
                            $parentData = [];
                            $parentData['email'] = $parent['UserEmail'];
                            $parentData['phone'] = $parent['UserPhone'];
                            $parentData['firstName'] = $parent['UserName'];
                            $parentData['lastname'] = $parent['UserLastName'];
                            $familyGroup[$key] = $parentData;
                            $usersId[] = $parent['RegID'];
                        }
                        $childData = [];
                        $childData['email'] = $child['UserEmail'];
                        $childData['phone'] = $child['UserPhone'];
                        $childData['firstName'] = $child['UserName'];
                        $childData['lastname'] = $child['UserLastName'];
                        $usersId[] = $child['RegID'];
                        $familyGroup['schoolchild'] = $childData;
                        $familyGroup['ut_mcontent'] = $child['utm_content'] ? $child['utm_content'] : null;
                        $familyGroup['utm_source'] = $child['utm_source'] ? $child['utm_source'] : null;
                        $familyGroup['utm_medium'] = $child['utm_medium'] ? $child['utm_medium'] : null;
                        $familyGroup['utm_campaign'] = $child['utm_campaign'] ? $child['utm_campaign'] : null;
                        $familyGroup['ut_mterm'] = $child['utm_term'] ? $child['utm_term'] : null;
                        $familyGroup['city'] = $request->GetProperty('city') ? $request->GetProperty('city') : null;
                        if ($child['UserWho'] == 'student' || $child['UserWho'] == 'teacher') {
                            $familyGroup['issue_year'] = 2020;
                        } else {
                            if ($child['UserTime'] > date('Y') . '-07-31 10:00') {
                                $familyGroup['issue_year'] = date('Y') - $child['UserClassNumber'] + 12;
                            } else {
                                $familyGroup['issue_year'] = date('Y') - $child['UserClassNumber'] + 11;
                            }
                        }
                        $registrationId = json_decode($swager->sendFamilyToCRM($familyGroup, $usersId, $guid))->registrationId;
                        if ($registrationId) {
                            $this->saveRegistrationId($usersId, $registrationId);
                        }
                    }
                } else {
                    $familyGroup = [];
                    $usersId = [];
                    foreach ($parents as $key => $parent) {
                        $parentData = [];
                        $parentData['email'] = $parent['UserEmail'];
                        $parentData['phone'] = $parent['UserPhone'];
                        $parentData['firstName'] = $parent['UserName'];
                        $parentData['lastname'] = $parent['UserLastName'];
                        $familyGroup[$key] = $parentData;
                        if ($key == 'first_parent') {
                            $familyGroup['ut_mcontent'] = $parent['utm_content'] ? $parent['utm_content'] : null;
                            $familyGroup['utm_source'] = $parent['utm_source'] ? $parent['utm_source'] : null;
                            $familyGroup['utm_medium'] = $parent['utm_medium'] ? $parent['utm_medium'] : null;
                            $familyGroup['utm_campaign'] = $parent['utm_campaign'] ? $parent['utm_campaign'] : null;
                            $familyGroup['ut_mterm'] = $parent['utm_term'] ? $parent['utm_term'] : null;
                            $familyGroup['city'] = $request->GetProperty('city') ? $request->GetProperty('city') : null;
                            if ($parent['UserTime'] > date('Y') . '07-31 10:00') {
                                $familyGroup['issue_year'] = date('Y') - $parent['UserClassNumber'] + 12;
                            } else {
                                $familyGroup['issue_year'] = date('Y') - $parent['UserClassNumber'] + 11;
                            }
                        }
                        $usersId[] = $parent['RegID'];
                    }
                    $registrationId = json_decode($swager->sendFamilyToCRM($familyGroup, $usersId, $guid))->registrationId;
                    if ($registrationId) {
                        $this->saveRegistrationId($usersId, $registrationId);
                    }
                }

            }
        }

        return true;
    }

    private function saveRegistrationId($usersId, $registrationId)
    {
        $stmt = GetStatement();
        foreach ($usersId as $id) {
            $query = "UPDATE event_registrations SET CRMRegistrationId=" . Connection::GetSQLString($registrationId) .
            " WHERE RegistrationID=" . Connection::GetSQLString($id);

            $stmt->Execute($query);
        }
    }

    public function getRegistrationListInfo($ids)
    {
        $stmt = GetStatement();
        $query = "SELECT r.RegistrationID, r.FirstName, r.LastName
            FROM `event_registrations` r
            WHERE r.RegistrationID IN (" . implode(", ", $ids) . ")
            ORDER BY r.RegistrationID";
        return $stmt->FetchList($query);
    }

    public function addAdditionalFields(LocalObject $request)
    {
        $forms = [];
        foreach ($request->GetProperty('RegisterForm') as $name => $fields) {
            foreach ($fields as $key => $field) {
                $forms[$key][$name] = $field;
            }
        }

        $stmt = GetStatement();
        foreach ($forms as $form) {
            if (isset($form['RegistrationID'])) {
                $updateFields = array();
                if (isset($form['BigDirection'])) {
                    $updateFields[] = "`AdditionalBigDirection` = " . Connection::GetSQLString($form['BigDirection']);
                }
                if (isset($form['University'])) {
                    $updateFields[] = "`AdditionalUniversity` = " . Connection::GetSQLString($form['University']);
                }
                if (isset($form['Type'])) {
                    $updateFields[] = "`AdditionalType` = " . Connection::GetSQLString($form['Type']);
                }
                if (count($updateFields) > 0) {
                    $query = "UPDATE `event_registrations` SET " . implode(",", $updateFields) . " WHERE RegistrationID=" . intval($form['RegistrationID']);
                    $stmt->Execute($query);
                }
            }
        }

        return true;
    }

    public function addVisit($city, $room)
    {
        $stmt = GetStatement();

        $query = 'INSERT INTO `data_exhibition_visits`
            SET RegistrationID=' . $this->GetIntProperty('RegistrationID') . ',
                VisitTime=' . Connection::GetSQLString(GetCurrentDateTime()) . ',
                LoadedTime=' . Connection::GetSQLString(GetCurrentDateTime()) . ',
                ScannerExhibitionID=' . intval($city['ExhibitionID']) . ',
                ScannerCityID=' . intval($city['CityID']) . ',
                ScannerRoom=' . Connection::GetSQLString($room);

        if ($stmt->Execute($query)) {
            //send to CRM
            $queryCount = "SELECT COUNT(*) as registrationCount FROM data_exhibition_visits
                    WHERE RegistrationID = " . $this->GetIntProperty('RegistrationID');

            if (!empty($city['GUID']) && ($stmt->FetchRow($queryCount)['registrationCount'] == 1)) {
                $querySelect = "SELECT CRMRegistrationId
                    FROM event_registrations
                    WHERE RegistrationID = " . $this->GetIntProperty('RegistrationID');

                if ($registration = $stmt->FetchRow($querySelect)['CRMRegistrationId']) {
                    $swager = new Swagger();
                    $swager->sendVisitToCRM($registration);
                }
            }

            return true;
        }

        return false;
    }

    public function getUniversities()
    {
        $cityId = $this->GetIntProperty('CityID');
        if (empty($cityId)) {
            return array();
        }

        $stmt = GetStatement();
        $query = 'SELECT u.*
            FROM data_university AS u
            INNER JOIN data_exhibition_city2univer AS c2u ON u.UniversityID=c2u.UniversityID
            WHERE c2u.CityID=' . $cityId . '
            ORDER BY c2u.SortOrder ASC';
        $list = $stmt->FetchList($query);
        if ($list) {
            foreach ($list as $key => $item) {
                if (!empty($item['UniversityLogo'])) {
                    foreach ($this->params['UniversityLogo'] as $universityLogo) {
                        $list[$key][$universityLogo['Name'] . 'Path'] = $universityLogo['Path']
                            . 'univer/' . $item['UniversityLogo'];
                    }
                }
            }

            $this->SetProperty('UniversityList', $list);
            return $list;
        }

        return array();
    }

    public function getMainPartners()
    {
        $cityId = $this->GetIntProperty('CityID');
        if (empty($cityId)) {
            return array();
        }

        $stmt = GetStatement();
        $query = 'SELECT *
    	FROM data_exhibition_mainpartners
    	WHERE CityID=' . $cityId . ' AND `PartnerImage` IS NOT NULL AND `PartnerImage`<>""
    	ORDER BY PartnerID ASC';
        $list = $stmt->FetchList($query);
        if ($list) {
            foreach ($list as $key => $item) {
                if (!empty($item['PartnerImage'])) {
                    foreach ($this->params['PartnerImage'] as $universityLogo) {
                        $list[$key][$universityLogo['Name'] . 'Path'] = $universityLogo['Path']
                            . 'exhibition/' . $item['PartnerImage'];
                    }
                }
            }

            $this->SetProperty('MainPartnerList', $list);
            return $list;
        }

        return array();
    }

    public function getPartners()
    {
        $cityId = $this->GetIntProperty('CityID');
        if (empty($cityId)) {
            return array();
        }

        $stmt = GetStatement();
        $query = 'SELECT *
            FROM data_exhibition_partners
            WHERE CityID=' . $cityId . ' AND `PartnerImage` IS NOT NULL AND `PartnerImage`<>""
            ORDER BY PartnerID ASC';
        $list = $stmt->FetchList($query);
        if ($list) {
            foreach ($list as $key => $item) {
                if (!empty($item['PartnerImage'])) {
                    foreach ($this->params['PartnerImage'] as $universityLogo) {
                        $list[$key][$universityLogo['Name'] . 'Path'] = $universityLogo['Path']
                            . 'exhibition/' . $item['PartnerImage'];
                    }
                }
            }

            $this->SetProperty('PartnerList', $list);
            return $list;
        }

        return array();
    }

    public function getTicketPage($registrationID)
    {
        $stmt = GetStatement();
        $query = 'SELECT e.UserID, e.FirstName, e.LastName, e.Time, e.Phone, c.Address, c.Date, c.Latitude, c.Longitude, c.EmailTemplate
    		FROM event_registrations e
    		LEFT JOIN data_exhibition_city c ON e.EventID=c.ExhibitionID AND e.City=c.CityTitle
    		WHERE e.RegistrationID=' . intval($registrationID);
        $info = $stmt->FetchRow($query);
        if ($info) {
            if (preg_match("/\d{4}-\d{2}-\d{2}/", $info['Time'], $match)) {
                $info['Date'] = $match[0];
            }

            if (preg_match("/\d{2}:\d{2}/", $info['Time'], $match)) {
                $info['Time'] = $match[0];
            }

            $content = $info['EmailTemplate'];
            $content = str_replace("[FirstName]", $info['FirstName'], $content);
            $content = str_replace("[LastName]", $info['LastName'], $content);
            $content = str_replace("[Time]", $info['Time'], $content);
            $content = str_replace("[Phone]", preg_replace("/[^0-9,.]/", "", $info['Phone']), $content);
            $content = str_replace("[Address]", $info['Address'], $content);
            $language = &GetLanguage();
            $format = $language->GetDateFormat();
            $content = str_replace("[Date]", LocalDate($format, strtotime($info['Date'])), $content);
            $mapLink = "https://yandex.ru/maps/?ll=" . $info['Longitude'] . "," . $info['Latitude'] . "&z=15&pt=" . $info['Longitude'] . "," . $info['Latitude'];
            $content = str_replace("[MapLink]", $mapLink, $content);
            $content = str_replace("[TicketNumber]", $registrationID, $content);
            $content = str_replace("[AuthKey]", '', $content);
            return $content;
        }
        return false;
    }

    private function GetShortRegistrationURL($registrationID, $staticPath)
    {
        $longUrl = GetUrlPrefix() . $staticPath . "?Registration=" . $registrationID;
        //$postData = array('longUrl' => $longUrl);
        if ($url = GetShortURL($longUrl)) {
            return $url;
        }
        return false;
    }

    protected function sendRegistrationToCRM($form, $city, $guid)
    {
        $hookUrl = 'https://prod-135.westeurope.logic.azure.com:443/workflows/29641e0bf2ee4c49ab48e2a28d18ce6f/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=UHpuIS1TgstWUOKhe29IspQwJLlf034QloaFVkZmndA';

        switch ($form['UserWho']) {
            case 'parent':
                $type = 'Родитель';
                break;
            case 'child':
                $type = 'Школьник';
                break;
            case 'student':
                $type = 'Студент';
                break;
            default:
                $type = '';
        }

        $params = [
            'CRMName' => $form['UserLastName'] . ' ' . $form['UserName'],
            'CRMPhone' => $form['UserPhone'],
            'CRMEmail' => $form['UserEmail'],
            'CRMType' => $type,
            'CRMCity' => $city,
            'CRMYear' => $form['UserClassNumber'] ?? '',
            'CRMComment' => 'выставка',
            'formname' => $guid,
            'utm_source' => $form['utm_source'],
            'utm_medium' => $form['utm_medium'],
            'utm_campaign' => $form['utm_campaign'],
        ];

        $logFile = fopen(PROJECT_DIR . 'var/log/crm.log', 'a+');

        $curlInit = curl_init($hookUrl);
        curl_setopt($curlInit, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlInit, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_VERBOSE, true);
        curl_setopt($curlInit, CURLOPT_STDERR, $logFile);

        fwrite($logFile, date('Y-m-d H:i:s') . ' START REQUEST WITH PARAMETERS:' . PHP_EOL .
            json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL);
        curl_exec($curlInit);
        fwrite($logFile, date('Y-m-d H:i:s') . ' FINISH REQUEST' . PHP_EOL);
        curl_close($curlInit);
    }

    public function getRegistrationOfUserWhoWatchEvent($exhibitionID, $cityTitle, $userEmail)
    {
        $stmt = GetStatement();

        $query = "SELECT `Who`, `LastName`, `FirstName`, `Phone`, `Email`, `Class`, `City`, `utm_source`, `utm_medium`, `utm_campaign`
                            FROM `event_registrations`
                            WHERE `EventID`=" . $exhibitionID . " AND
                            `City`=" . Connection::GetSQLString($cityTitle) . " AND
                            `Email`=" . Connection::GetSQLString($userEmail);
        $exhibitionRegistrations = $stmt->FetchList($query);

        if (count($exhibitionRegistrations) > 0) {
            return $exhibitionRegistrations[count($exhibitionRegistrations) - 1];
        }

        return null;
    }

    public function getCityListInfoByOnlineEventID($onlineEventID)
    {
        if (empty($onlineEventID)) {
            return false;
        }

        $stmt = GetStatement();

        $query = "SELECT `GUID`, `ExhibitionID`, `CityTitle` FROM `data_exhibition_city`
                    WHERE `OnlineEventID`=" . intval($onlineEventID);

        return $stmt->FetchList($query);
    }
}
