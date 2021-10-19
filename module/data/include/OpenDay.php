<?php
require_once(dirname(__FILE__) . "/OpenDayPropertyList.php");
/**
 * @property $ID
 * @property $Title
 * @property $CityID
 * @property $StaticPath
 * @property $Type
 * @property $DateFrom
 * @property $DateTo
 * @property $Date
 * @property $Phone
 * @property $Email
 * @property $Address
 * @property $Latitude
 * @property $Longitude
 * @property $InfoList (crud)
 * @property $Description
 * @property $TitleSchedule
 * @property $TitleRegister
 * @property $GUID
 * @property $Active
 * @property $EmailTemplate (crud)
 * @property $EmailTheme (crud)
 */
class OpenDay extends LocalObject
{
    const TABLE_NAME = 'data_open_day';
    const MODULE_NAME = 'data';
    const BASE_PAGE_URL = 'dod';

    /**
     * @var array
     */
    protected static $params;
    /**
     * @var OpenDayPropertyList
     */
    public $propertyList;

    /**
     * @param int $id
     * @return OpenDay|null
     * @throws Exception
     */
    public static function load(int $id)
    {
        return self::loadBy(["open_day.ID = {$id}"]);
    }

    /**
     * @param string $staticPath
     * @return OpenDay
     * @throws Exception
     */
    public static function loadByStaticPath(string $staticPath)
    {
        return self::loadBy(["open_day.StaticPath = '{$staticPath}'"]);
    }

    protected static function loadBy(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                'open_day.*',
                'city.Title AS CityTitle',
                'city.StaticPath AS CityPath',
            ])
            ->from(self::TABLE_NAME . ' AS open_day')
            ->addJoin("LEFT JOIN data_city AS city ON open_day.CityID = city.ID")
            ->where($where);

        $item = new static();
        $item->LoadFromSQL($query->getSQL());
        if ($item->GetIntProperty('ID') > 0){
            self::prepare($item);
            return $item;
        }

        return null;
    }

    /**
     * @param OpenDay $openDay
     * @throws Exception
     */
    public static function prepare(OpenDay $openDay)
    {
        //Image
        foreach (self::getParams()['MainImage'] as $param){
            if (!empty($openDay->GetProperty('MainImage'))){
                $openDay->SetProperty($param['Name'].'Path', $param['Path'].'openday/'.$openDay->GetProperty('MainImage'));
            }
        }

        //Schedule
        $schedule = self::loadScheduleByID($openDay->GetIntProperty('ID'));
        if ($schedule) {
            foreach ($schedule as $key => $item) {
                foreach ($item['ActionList'] as $key2 => $action) {
                    $schedule[$key]['ActionList'][$key2]['TypeList'] = self::getTypesListEvent($action['Type']);
                }
            }
            $openDay->SetProperty('Schedule', $schedule);
        }

        //Info list
        if ($openDay->IsPropertySet('InfoList')) {
            $info = $openDay->GetProperty('InfoList');
            if (!empty($info)) {
                $info = json_decode($info, true);
                foreach ($info as $key => $item) {
                    if (!empty($item['Image'])) {
                        foreach (self::getParams()['Image'] as $param) {
                            $info[$key][$param['Name'].'Path'] = $param['Path'].'openday/'.$item['Image'];
                        }
                    }
                }

                $openDay->SetProperty('InfoList', $info);
            }
        }

        //Date
        $dateFrom = new DateTime($openDay->GetProperty('DateFrom'), new DateTimeZone('Europe/Moscow'));
        $openDay->SetProperty('StartDateUTC', $dateFrom->format("Y-m-d H:i:s e"));
        $dateTo = new DateTime($openDay->GetProperty('DateTo'), new DateTimeZone('Europe/Moscow'));
        $openDay->SetProperty('EndDateUTC', $dateTo->format("Y-m-d H:i:s e"));

        //Properties
        $openDay->propertyList = new OpenDayPropertyList();
        $openDay->propertyList->loadByOpenDay($openDay->GetIntProperty('ID'));
        foreach ($openDay->propertyList->GetItems() as $index => $property) {
            $openDay->SetProperty('Property' . $property['Property'], $property['Value']);
        }
    }

    /**
     * @return array
     */
    public static function getParams(): array
    {
        if (empty(self::$params)){
            self::$params['Image'] = LoadImageConfig('Image', self::MODULE_NAME, DATA_OPEN_DAY_IMAGE);
            self::$params['MainImage'] = LoadImageConfig('MainImage', self::MODULE_NAME, DATA_OPEN_DAY_MAIN_IMAGE);
        }

        return self::$params;
    }


    /**
     * @return bool|mixed|null
     */
    public static function getMaxSortOrder()
    {
        return GetStatement()->FetchField(QueryBuilder::init()->select(['MAX(SortOrder)'])->from(self::TABLE_NAME)->getSQL());
    }

    /**
     * @param null $active
     * @return array
     */
    public static function getTypesListEvent($active = null)
    {
        return [
            [
                'Key' => 'experts',
                'Title' => GetTranslation('exhibition-event-type-experts', self::MODULE_NAME),
                'Selected' => (!empty($active) and $active == 'experts') ? 1 : 0
            ],
            [
                'Key' => 'parent',
                'Title' => GetTranslation('exhibition-event-type-parent', self::MODULE_NAME),
                'Selected' => (!empty($active) and $active == 'parent') ? 1 : 0
            ],
            [
                'Key' => 'abroad',
                'Title' => GetTranslation('exhibition-event-type-abroad', self::MODULE_NAME),
                'Selected' => (!empty($active) and $active == 'abroad') ? 1 : 0
            ],
            [
                'Key' => 'prof',
                'Title' => GetTranslation('exhibition-event-type-prof', self::MODULE_NAME),
                'Selected' => (!empty($active) and $active == 'prof') ? 1 : 0
            ],
            [
                'Key' => 'univer',
                'Title' => GetTranslation('exhibition-event-type-univer', self::MODULE_NAME),
                'Selected' => (!empty($active) and $active == 'univer') ? 1 : 0
            ],
            [
                'Key' => 'toelf',
                'Title' => GetTranslation('exhibition-event-type-toelf', self::MODULE_NAME),
                'Selected' => (!empty($active) and $active == 'toelf') ? 1 : 0
            ],
        ];
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function validate(): bool
    {
        if (!$this->ValidateNotEmpty('Title')) {
            $this->AddError('open-day-save-title-empty', self::MODULE_NAME);
        }
        if (!$this->ValidateNotEmpty('Type')) {
            $this->AddError('open-day-save-type-empty', self::MODULE_NAME);
        }
        if (!$this->ValidateNotEmpty('DateFrom')) {
            $this->AddError('open-day-save-date-from-empty', self::MODULE_NAME);
        }
        if (!$this->ValidateNotEmpty('DateTo')) {
            $this->AddError('open-day-save-date-to-empty', self::MODULE_NAME);
        }
        if (!$this->ValidateNotEmpty('Date')) {
            $this->AddError('open-day-save-date-empty', self::MODULE_NAME);
        }
        if (!$this->ValidateNotEmpty('StaticPath')) {
            $this->AddError('open-day-save-static-path-empty', self::MODULE_NAME);
        }

        $item = self::loadByStaticPath($this->GetProperty('StaticPath'));

        if ($item && $item->GetIntProperty('ID') !== $this->GetIntProperty('ID')){
            $this->AddError('open-day-save-static-already-exist', self::MODULE_NAME);
        }

        return !$this->HasErrors();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        if (!$this->validate()){
            return false;
        }

        $query = QueryBuilder::init();
        if ($this->GetIntProperty('ID') > 0){
            $query->update(self::TABLE_NAME);
            $query->addWhere("ID = {$this->GetIntProperty('ID')}");
        }
        else{
            $query->insert(self::TABLE_NAME);
        }

        if ($this->GetIntProperty('CityID') < 1){
            $this->SetProperty('CityID', null);
        }

        //ButtonLink
        if ($this->ValidateNotEmpty('ButtonLink')){
            $link = $this->GetProperty('ButtonLink');
            $parsedUrl = parse_url($link);
            if (!isset($parsedUrl['scheme'])){
                $link = "http://{$link}";
            }
            $this->SetProperty('ButtonLink', $link);
        }

        $query->setValue('Title', $this->GetPropertyForSQL('Title'));
        $query->setValue('CityID', $this->GetPropertyForSQL('CityID'));
        $query->setValue('StaticPath', $this->GetPropertyForSQL('StaticPath'));
        $query->setValue('Type', $this->GetPropertyForSQL('Type'));
        $query->setValue('DateFrom', Connection::GetSQLDate($this->GetProperty('DateFrom')));
        $query->setValue('DateTo', Connection::GetSQLDate($this->GetProperty('DateTo')));
        $query->setValue('Date', Connection::GetSQLDateTime($this->GetProperty('Date')));
        $query->setValue('Phone', $this->GetPropertyForSQL('Phone'));
        $query->setValue('Email', $this->GetPropertyForSQL('Email'));
        $query->setValue('Address', $this->GetPropertyForSQL('Address'));
        $query->setValue('Latitude', $this->GetPropertyForSQL('Latitude'));
        $query->setValue('Longitude', $this->GetPropertyForSQL('Longitude'));
        $query->setValue('InfoList', Connection::GetSQLString(json_encode($this->GetProperty('InfoList'))));
        $query->setValue('MainImage', $this->GetPropertyForSQL('MainImage'));
        $query->setValue('Description', $this->GetPropertyForSQL('Description'));
        $query->setValue('TitleSchedule', $this->GetPropertyForSQL('TitleSchedule'));
        $query->setValue('TitleRegister', $this->GetPropertyForSQL('TitleRegister'));
        $query->setValue('GUID', $this->GetPropertyForSQL('GUID'));
        $query->setValue('Active', Connection::GetSQLString($this->GetProperty('Active') == 'Y' ? 'Y' : 'N'));
        $query->setValue('EmailTemplate', $this->GetPropertyForSQL('EmailTemplate'));
        $query->setValue('EmailTheme', $this->GetPropertyForSQL('EmailTheme'));
        $query->setValue('SortOrder', intval(self::getMaxSortOrder()) + 1);
        $query->setValue('ButtonText', $this->GetPropertyForSQL('ButtonText'));
        $query->setValue('ButtonLink', $this->GetPropertyForSQL('ButtonLink'));

        $stmt = GetStatement();
        //echo $query->getSQL();exit();
        if ($stmt->Execute($query->getSQL())){
            if ($this->GetIntProperty('ID') < 1){
                $this->SetProperty('ID', $stmt->GetLastInsertID());
            }
            return true;
        }

        $this->AddError('sql-error');
        return false;
    }

    /**
     * @return bool
     */
    public function remove(): bool
    {
        $id = $this->GetProperty('ID');
        $query = QueryBuilder::init()->delete()->from(self::TABLE_NAME)->where(["ID = {$id}"]);
        if (GetStatement()->Execute($query->getSQL())){
            self::removeLinkedUniversity($id);
            return true;
        }
    }

//Service
    /**
     * @param $uploads
     */
    public function setInfo($uploads)
    {
        $info = $this->GetProperty('info');
        $infoList = array();

        if (is_array($info) and !empty($info)) {
            foreach ($info['title'] as $k => $item) {
                $imgOld = $info['img'][$k];
                if (isset($info['text'][$k]) and !empty($info['text'][$k]) and !empty($item)) {
                    $image = '';

                    if (is_array($uploads) && $uploads[$k]['error'] == 0 and $uploads[$k]['size'] > 0) {
                        $image =  $uploads[$k]['FileName'];

                        if (! empty($imgOld)) {
                            if (file_exists(DATA_OPEN_DAY_IMAGE_DIR.$imgOld) and
                                is_file(DATA_OPEN_DAY_IMAGE_DIR.$imgOld)) {
                                unlink(DATA_OPEN_DAY_IMAGE_DIR.$imgOld);
                            }
                        }
                    } elseif (! empty($imgOld)) {
                        $image =  $imgOld;
                    }

                    $infoList[] = array(
                        'Title' => $item,
                        'Image' => $image,
                        'Description' => $info['text'][$k]
                    );
                } elseif (! empty($imgOld)) {
                    if (file_exists(DATA_OPEN_DAY_IMAGE_DIR.$imgOld) and
                        is_file(DATA_OPEN_DAY_IMAGE_DIR.$imgOld)) {
                        unlink(DATA_OPEN_DAY_IMAGE_DIR.$imgOld);
                    }
                }
            }
        }

        $this->SetProperty('InfoList', $infoList);
    }

    /**
     * @param $fileInfo
     * @return bool
     * @todo move to individual model
     */
    public function uploadSchedule($fileInfo): bool
    {
        $openDayId = $this->GetIntProperty('ID');
        $file = fopen($fileInfo['tmp_name'], 'r');
        if($file){
            $delimiter = ',';
            $enclosure = '"';
            $stmt = GetStatement();
            $data = fgetcsv($file, 0, $delimiter, $enclosure);
            if($data !== false && trim($data[0]) == 'Зал'){
                $query = 'SELECT r.Title, r.RoomID FROM data_open_day_room r WHERE r.OpenDayID='.intval($openDayId);
                $oldRoomList = $stmt->FetchIndexedList($query);
                foreach($oldRoomList as $title=>$room){
                    $query = 'SELECT a.Title, a.ActionID FROM data_open_day_action a WHERE a.RoomID='.intval($room['RoomID']);
                    $oldRoomList[$title]['ActionList'] = $stmt->FetchIndexedList($query);
                    $stmt->Execute('DELETE FROM data_open_day_room WHERE RoomID='.intval($room['RoomID']));
                    $stmt->Execute('DELETE FROM data_open_day_action WHERE RoomID='.intval($room['RoomID']));
                }
                $newRoomList = array();
                while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
                    $roomTitle = trim($data[0]);
                    if(strlen($roomTitle) == 0 || $roomTitle == 'Зал'){
                        continue;
                    }

                    $oldRoomInfo = null;
                    if(isset($oldRoomList[$roomTitle])){
                        $oldRoomInfo = $oldRoomList[$roomTitle];
                    }

                    $roomID = $newRoomList[$roomTitle];
                    if(!$roomID){
                        $query = 'INSERT INTO data_open_day_room SET ';
                        if($oldRoomInfo != null){
                            $query .= 'RoomID='.intval($oldRoomInfo['RoomID']).', ';
                        }
                        $query .= 'OpenDayID='.intval($openDayId).',
                            Title='.Connection::GetSQLString($roomTitle);
                        if($stmt->Execute($query)){
                            $roomID = $stmt->GetLastInsertID();
                            $newRoomList[$roomTitle] = $roomID;
                        }
                    }

                    $actionTitle = trim($data[6]);
                    $query = 'INSERT INTO data_open_day_action SET ';
                    if($oldRoomInfo != null && isset($oldRoomInfo['ActionList'][$actionTitle])){
                        $query .= 'ActionID='.intval($oldRoomInfo['ActionList'][$actionTitle]['ActionID']).',';
                    }
                    $type = '';
                    if($data[5] == 'ЕГЭ и ОГЭ') {$type='experts';}
                    elseif($data[5] == 'Для родителей') {$type='parent';}
                    elseif($data[5] == 'Обучение за рубежом') {$type='abroad';}
                    elseif($data[5] == 'Профориентация') {$type='prof';}
                    elseif($data[5] == 'Лекция от вуза') {$type='univer';}
                    elseif($data[5] == 'English') {$type='toefl';}
                    $query .= 'RoomID='.intval($roomID).',
                        TimeFrom='.Connection::GetSQLString($data[2]).',
                        TimeTo='.Connection::GetSQLString($data[3]).',
                        Type='.Connection::GetSQLString($type).',
                        Title='.Connection::GetSQLString($actionTitle).',
                        Name='.Connection::GetSQLString($data[7]).',
                        Post='.Connection::GetSQLString($data[8]).',
                        Description='.Connection::GetSQLString($data[9]);
                    if($stmt->Execute($query)){
                        $actionID = $stmt->GetLastInsertID();
                    }
                }
                fclose($file);
                return true;
            }
            else {
                $this->AddError('exhibition-city-save-schedule-file-notcsv', self::MODULE_NAME);
                fclose($file);
                return false;
            }
        }
        else {
            $this->AddError('exhibition-city-save-schedule-file-incorrect', self::MODULE_NAME);
        }
        return false;
    }

    /**
     * @param $schedule
     */
    public function updateSchedule($schedule)
    {
        $openDayId = $this->GetIntProperty('ID');
        $stmt = GetStatement();
        $query = 'SELECT RoomID FROM data_open_day_room WHERE OpenDayID='.intval($openDayId);
        $oldRoomList = $stmt->FetchIndexedList($query);
        foreach($schedule as $roomInfo){
            $query = 'INSERT INTO data_open_day_room SET ';
            $roomID = $roomInfo['RoomID'];
            if($roomID){
                $roomID = $roomInfo['RoomID'];
                $stmt->Execute('DELETE FROM data_open_day_action WHERE RoomID='.intval($roomID));
                $stmt->Execute('DELETE FROM data_open_day_room WHERE RoomID='.intval($roomID));
                unset($oldRoomList[$roomID]);
                $query .= 'RoomID='.intval($roomID).', ';
            }
            $query .= 'OpenDayID='.intval($openDayId).', 
                Title='.Connection::GetSQLString($roomInfo['Title']);
            if($stmt->Execute($query)){
                if(!$roomID){
                    $roomID = $stmt->GetLastInsertID();
                }
                foreach($roomInfo['ActionList'] as $actionInfo){
                    $query = 'INSERT INTO data_open_day_action SET ';
                    if(isset($actionInfo['ActionID'])){
                        $query .= 'ActionID='.intval($actionInfo['ActionID']).', ';
                    }
                    $query .= 'RoomID='.intval($roomID).', 
                        TimeFrom='.Connection::GetSQLString($actionInfo['TimeFrom']).', 
                        TimeTo='.Connection::GetSQLString($actionInfo['TimeTo']).', 
                        Type='.Connection::GetSQLString($actionInfo['Type']).', 
                        Title='.Connection::GetSQLString($actionInfo['Title']).', 
                        Name='.Connection::GetSQLString($actionInfo['Name']).', 
                        Post='.Connection::GetSQLString($actionInfo['Post']).', 
                        Description='.Connection::GetSQLString($actionInfo['Description']);
                    $stmt->Execute($query);
                }
            }
        }
        foreach($oldRoomList as $roomID=>$room){
            $stmt->Execute('DELETE FROM data_open_day_action WHERE RoomID='.intval($roomID));
            $stmt->Execute('DELETE FROM data_open_day_room WHERE RoomID='.intval($roomID));
        }
    }

    /**
     * @param $openDayID
     * @return array|bool|null
     */
    public static function getUniversityMap($openDayID)
    {
        $stmt = GetStatement();
        $query = "SELECT u.UniversityID, u.Title FROM `data_university` AS u
			INNER JOIN `data_open_day2university` AS o2u ON u.UniversityID=o2u.UniversityID
			WHERE o2u.OpenDayID=".intval($openDayID);
        return $stmt->FetchList($query);
    }

    public static function removeLinkedUniversity($openDayId)
    {
        $stmt = GetStatement();
        $stmt->Execute("DELETE FROM `data_open_day2university` WHERE `OpenDayID` = ".$openDayId);
    }

    /**
     * @param int $openDayId
     * @param array $universityIDs
     */
    public static function saveLinkedUniversity(int $openDayId, array $universityIDs)
    {
        $stmt = GetStatement();
        foreach ($universityIDs as $id) {
            $query = 'INSERT INTO `data_open_day2university` (OpenDayID, UniversityID) VALUES('.$openDayId.', '.intval($id).')';
            $stmt->Execute($query);
        }
    }

    /**
     * @param int $openDayID
     * @return array
     */
    public static function loadScheduleByID(int $openDayID)
    {
        $stmt = GetStatement();
        $query = 'SELECT r.RoomID, r.Title AS RoomTitle, a.*
            FROM data_open_day_action a
            LEFT JOIN data_open_day_room r ON r.RoomID=a.RoomID
            WHERE r.OpenDayID='.intval($openDayID).'
            ORDER BY r.Title, a.TimeFrom';
        $actionList = $stmt->FetchList($query);
        $roomList = array();
        foreach($actionList as $action) {
            if(!isset($roomList[$action['RoomID']])) {
                $roomList[$action['RoomID']] = array(
                    'RoomID' => $action['RoomID'],
                    'Title' => $action['RoomTitle'],
                    'ActionList' => array()
                );
            }
            $roomList[$action['RoomID']]['ActionList'][] = $action;
        }
        $result = array();
        foreach($roomList as $roomID=>$room) {
            $result[] = $room;
        }
        return $result;
    }

    public function getScheduleList(LocalObject $filter = null)
    {
        $schedule = $this->GetProperty('Schedule');
        if ($schedule) {
            $allRoom = [];
            if (is_array($schedule) and !empty($schedule)) {
                foreach ($schedule as $k2 => $room) {

                    if (empty($room['Title'])) {
                        unset($schedule[$k2]);
                        continue;
                    }

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
                                'Title'    => $hour . ':00',
                                'ItemList' => array()
                            );
                        }

                        if (!isset($allRoom[$hour])) {
                            $allRoom[$hour] = array(
                                'Title'    => $hour . ':00',
                                'ItemList' => array()
                            );
                        }

                        $result[$hour]['ItemList'][] = $action;
                        $allRoom[$hour]['ItemList'][] = $action;
                    }

                    $schedule[$k2]['ActionList'] = array_values($result);
                }

                //order for full list
                usort($allRoom, function($a, $b){
                    return strcmp($a['Title'], $b['Title']);
                });
                for($i=0; $i<count($allRoom); $i++) {
                    usort($allRoom[$i]['ItemList'], function($a, $b){
                        return strcmp($a['TimeFrom'], $b['TimeFrom']);
                    });
                }

                return $schedule;

            } else {
                return false;
            }
        }
        return false;
    }

    public function removeMainImage()
    {
        if (ImageManager::RemoveImage(DATA_OPEN_DAY_IMAGE_DIR . $this->GetProperty('MainImage'))){
            $this->SetProperty('MainImage', null);
            return $this->save();
        }

        return false;
    }

    public static function addVisit(int $registrationID, int $openDayID, $room = 'Зона регистрации')
    {
        $stmt = GetStatement();

        $query = 'INSERT INTO `data_open_day_visit`
            SET RegistrationID='.$registrationID.',
                VisitTime='.Connection::GetSQLString(GetCurrentDateTime()).',
                LoadedTime='.Connection::GetSQLString(GetCurrentDateTime()).',
                ScannerOpenDayID='.$openDayID.',
                ScannerRoom='.Connection::GetSQLString($room);

        if ($stmt->Execute($query)) {
            return true;
        }

        return false;
    }
}