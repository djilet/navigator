<?php
require_once(dirname(__FILE__) . '/../common/ExhibitionCityCommon.php');

class ExhibitionCity extends LocalObject
{
    use ExhibitionCityCommon;
    
    private $module;
    private $params;

    /**
     * ExhibitionCity constructor.
     *
     * @param $module
     */
    public function __construct($module)
    {
        $this->module = $module;
        $this->params['ItemImage'] = LoadImageConfig('ItemImage', $this->module, DATA_EXHIBITION_INFOITEM_IMAGE);
        $this->params['PartnerImage'] = LoadImageConfig('PartnerImage', $this->module, DATA_EXHIBITION_PARTNER_IMAGE);
        $this->params['HeadImage'] = LoadImageConfig('HeadImage', $this->module, DATA_EXHIBITION_HEAD_IMAGE);
    }

    public function loadByID($cityID = 0)
    {
        $cityID = intval($cityID);
        if ($cityID == 0) {
            return;
        }
        
        $query = 'SELECT * FROM `data_exhibition_city` WHERE CityID='.$cityID;
        $this->LoadFromSQL($query);
        
        $schedule = $this->loadSchedule($cityID);
        if ($schedule) {
            foreach ($schedule as $key => $item) {
                foreach ($item['ActionList'] as $key2 => $action) {
                    $schedule[$key]['ActionList'][$key2]['TypeList'] = $this->getTypesListEvent($action['Type']);
                }
            }
            $this->SetProperty('Schedule', $schedule);
        }
        
        if ($this->IsPropertySet('InfoList')) {
            $info = $this->GetProperty('InfoList');
            if (!empty($info)) {
                $info = json_decode($info, true);
                foreach ($info as $key => $item) {
                    if (! empty($item['Image'])) {
                        foreach ($this->params['ItemImage'] as $param) {
                            $info[$key][$param['Name'].'Path'] = $param['Path'].'exhibition/'.$item['Image'];
                        }
                    }
                }
                
                $this->SetProperty('InfoList', $info);
            }
        }

        foreach ($this->params['HeadImage'] as $param){
            if (!empty($this->GetProperty('HeadImage'))){
                $this->SetProperty($param['Name'].'Path', $param['Path'].'exhibition/'.$this->GetProperty('HeadImage'));
            }
        }
    }

    public function save(LocalObject $request)
    {
    	if (! $request->ValidateNotEmpty('Title')) {
            $this->AddError('exhibition-city-save-title-empty', $this->module);
        }
        if (! $request->ValidateNotEmpty('Date')) {
            $this->AddError('exhibition-city-save-date-empty', $this->module);
        }
        if (! $request->ValidateNotEmpty('StaticPath')) {
            $this->AddError('exhibition-city-save-static-path-empty', $this->module);
        }
        if ($this->HasErrors()) {
            return false;
        }
        
        if ($request->GetProperty("Active") != "Y")
        	$request->SetProperty("Active", "N");
        
        $cityId = $request->GetIntProperty('CityID');
        $stmt = GetStatement();
        
        $latitude = $request->GetProperty('Latitude');
        $longitude = $request->GetProperty('Longitude');
        if (empty($latitude) and empty($longitude) and !$request->ValidateNotEmpty('Address')) {
            $params = array(
                'apikey' => GetFromConfig('GeoCodeApiKey', 'yandex'),
                'geocode' => $request->GetProperty('Title').','.$request->GetProperty('Address'),
                'format'  => 'json',
                'results' => 1
            );
            $response = json_decode(
                file_get_contents('http://geocode-maps.yandex.ru/1.x/?' . http_build_query($params, '', '&'))
            );

            if ($response) {
                $point = $response->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos;
                if ($point != null) {
                    $point = explode(" ", $point);
                    $request->SetProperty('Latitude', $point[1]);
                    $request->SetProperty('Longitude', $point[0]);
                }
            }
        }


        $fileSys = new FileSys();
        $infoList = array();
        $info = $request->GetProperty('info');
        $uploads = $fileSys->Upload('infoFiles', DATA_EXHIBITION_IMAGE_DIR);

        if (is_array($info) and !empty($info)) {
            foreach ($info['title'] as $k => $item) {
                $imgOld = $info['img'][$k];
                if (isset($info['text'][$k]) and !empty($info['text'][$k]) and !empty($item)) {

                    $image = '';

                    if ($uploads[$k]['error'] == 0 and $uploads[$k]['size'] > 0) {
                        $image =  $uploads[$k]['FileName'];

                        if (! empty($imgOld)) {
                            if (file_exists(DATA_EXHIBITION_IMAGE_DIR.$imgOld) and
                                is_file(DATA_EXHIBITION_IMAGE_DIR.$imgOld)) {
                                unlink(DATA_EXHIBITION_IMAGE_DIR.$imgOld);
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
                    if (file_exists(DATA_EXHIBITION_IMAGE_DIR.$imgOld) and
                        is_file(DATA_EXHIBITION_IMAGE_DIR.$imgOld)) {
                        unlink(DATA_EXHIBITION_IMAGE_DIR.$imgOld);
                    }
                }
            }
        }
        $request->SetProperty('InfoList', $infoList);

        $onlineEventID = $request->GetIntProperty('OnlineEventID') > 1 ? $request->GetIntProperty('OnlineEventID') : 'NULL';

        $result = ImageManager::SaveImage($this, DATA_EXHIBITION_IMAGE_DIR, $request->GetProperty("SavedHeadImage"), 'Head');

        if (!$result) {
            PrepareImagePath($this->_properties, 'HeadImage', $this->params['HeadImage'], "exhibition/");
            return false;
        }

        if ($cityId > 0) {
            $query = "UPDATE `data_exhibition_city` SET
                   `ExhibitionID` = ".$request->GetIntProperty('ExhibitionID').",
                          `Title` = ".$request->GetPropertyForSQL('Title').",
                      `CityTitle` = ".$request->GetPropertyForSQL('CityTitle').",
                     `StaticPath` = ".$request->GetPropertyForSQL('StaticPath').",
                           `Date` = ".Connection::GetSQLDateTime($request->GetProperty('Date')).",
                        `Address` = ".$request->GetPropertyForSQL('Address').",
                       `Latitude` = ".$request->GetPropertyForSQL('Latitude').",
                      `Longitude` = ".$request->GetPropertyForSQL('Longitude').",
                      `Longitude` = ".$request->GetPropertyForSQL('Longitude').",
                       `InfoList` = ".Connection::GetSQLString(json_encode($request->GetProperty('InfoList'))).",
                    `Description` = ".$request->GetPropertyForSQL('Description').",
                  `TitleSchedule` = ".$request->GetPropertyForSQL('TitleSchedule').",
                  `TitleRegister` = ".$request->GetPropertyForSQL('TitleRegister').",
				           `GUID` = ".$request->GetPropertyForSQL('GUID').",
				         `Active` = ".$request->GetPropertyForSQL('Active').",
                     `EmailTheme` = ".$request->GetPropertyForSQL('EmailTheme').",
                  `EmailTemplate` = ".$request->GetPropertyForSQL('EmailTemplate').",
                          `Phone` = ".$request->GetPropertyForSQL('Phone').",
                          `Email` = ".$request->GetPropertyForSQL('Email').",
                     `ManualDate` = ".$request->GetPropertyForSQL('ManualDate').",
                     `OnlineEventID` = ".$onlineEventID.",
                     `HeadImage` = ".$this->GetPropertyForSQL('HeadImage')."
                WHERE
                     `CityID` = " . $cityId;
        } else {

            $maxSort = $stmt->FetchField('SELECT MAX(SortOrder)+1 FROM `data_exhibition_city` 
                WHERE ExhibitionID='.$request->GetIntProperty('ExhibitionID'));
            if (! $maxSort) {
                $maxSort = 0;
            }
            
            $query = "INSERT INTO `data_exhibition_city` SET
               `ExhibitionID` = ".$request->GetIntProperty('ExhibitionID').",
                  `CityTitle` = ".$request->GetPropertyForSQL('CityTitle').",
                      `Title` = ".$request->GetPropertyForSQL('Title').",
                 `StaticPath` = ".$request->GetPropertyForSQL('StaticPath').",
                       `Date` = ".Connection::GetSQLDateTime($request->GetProperty('Date')).",
                    `Address` = ".$request->GetPropertyForSQL('Address').",
                   `Latitude` = ".$request->GetPropertyForSQL('Latitude').",
                  `Longitude` = ".$request->GetPropertyForSQL('Longitude').",
                   `InfoList` = ".Connection::GetSQLString(json_encode($request->GetProperty('InfoList'))).",
                `Description` = ".$request->GetPropertyForSQL('Description').",
              `TitleSchedule` = ".$request->GetPropertyForSQL('TitleSchedule').",
              `TitleRegister` = ".$request->GetPropertyForSQL('TitleRegister').",
                       `GUID` = ".$request->GetPropertyForSQL('GUID').",
                     `Active` = ".$request->GetPropertyForSQL('Active').",
              	 `EmailTheme` = ".$request->GetPropertyForSQL('EmailTheme').",
              `EmailTemplate` = ".$request->GetPropertyForSQL('EmailTemplate').",
                      `Phone` = ".$request->GetPropertyForSQL('Phone').",
                      `Email` = ".$request->GetPropertyForSQL('Email').",
              	  `SortOrder` = ".$maxSort.",
                 `ManualDate` = ".$request->GetPropertyForSQL('ManualDate').",
                 `OnlineEventID` = ".$onlineEventID.",
                 `HeadImage` = ".$this->GetPropertyForSQL('HeadImage');
        }

        if ($stmt->Execute($query)) {
            if (! $cityId) {
                $cityId = $stmt->GetLastInsertID();
            }

            $stmt->Execute('DELETE FROM data_exhibition_city2univer WHERE CityID='.$cityId);
            if ($request->IsPropertySet('univers')) {
                $sortOrder = 0;
                foreach ($request->GetProperty('univers') as $item) {
                    $query = 'INSERT INTO data_exhibition_city2univer 
                        SET CityID='.$cityId.', 
                            UniversityID='.$item.',
                            SortOrder='.($sortOrder++);
                    $stmt->Execute($query);
                }
            }
            
            $partners = $request->GetProperty('mainpartners');
            if (! empty($partners)) {
            	$uploadsPartners = $fileSys->Upload('mainpartnersImages', DATA_EXHIBITION_IMAGE_DIR);
            	$oldImages = $request->GetProperty('mainpartnersImagesOld');
            	$partnerIDs = $request->GetProperty('mainpartnersIDs');
            	foreach ($partners as $key => $partner) {
            		$oldImage = isset($oldImages[$key]) ? $oldImages[$key] : false;
            		$partnerId = isset($partnerIDs[$key]) ? $partnerIDs[$key] : false;
            
            		if (! empty($uploadsPartners[$key]) and $uploadsPartners[$key]['error'] == 0) {
            			$image = $uploadsPartners[$key]['FileName'];
            
            			if (!empty($oldImage) and file_exists(DATA_EXHIBITION_IMAGE_DIR.$oldImage)
            					and is_file(DATA_EXHIBITION_IMAGE_DIR.$oldImage)) {
            				unlink(DATA_EXHIBITION_IMAGE_DIR.$oldImage);
            			}
            		} else {
            			$image = $oldImage;
            		}
            
            		if ($partnerId) {
            			$query = 'UPDATE `data_exhibition_mainpartners` SET
            			`CityID` = '.Connection::GetSQLString($cityId).',
            			`PartnerTitle` = '.Connection::GetSQLString($partner).',
            			`PartnerImage` = '.Connection::GetSQLString($image).'
            			WHERE
            			`PartnerID` = ' . $partnerId;
            		} else {
            			$query = 'INSERT INTO `data_exhibition_mainpartners` SET
            			`CityID` = '.Connection::GetSQLString($cityId).',
            			`PartnerTitle` = '.Connection::GetSQLString($partner).',
            			`PartnerImage` = '.Connection::GetSQLString($image);
            		}
            		$stmt->Execute($query);
            	}
            }

            $partners = $request->GetProperty('partners');
            if (! empty($partners)) {
                $uploadsPartners = $fileSys->Upload('partnersImages', DATA_EXHIBITION_IMAGE_DIR);
                $oldImages = $request->GetProperty('partnersImagesOld');
                $partnerIDs = $request->GetProperty('partnersIDs');
                foreach ($partners as $key => $partner) {
                    $oldImage = isset($oldImages[$key]) ? $oldImages[$key] : false;
                    $partnerId = isset($partnerIDs[$key]) ? $partnerIDs[$key] : false;
                    
                    if (! empty($uploadsPartners[$key]) and $uploadsPartners[$key]['error'] == 0) {
                        $image = $uploadsPartners[$key]['FileName'];
                        
                        if (!empty($oldImage) and file_exists(DATA_EXHIBITION_IMAGE_DIR.$oldImage)
                            and is_file(DATA_EXHIBITION_IMAGE_DIR.$oldImage)) {
                            unlink(DATA_EXHIBITION_IMAGE_DIR.$oldImage);
                        }
                    } else {
                        $image = $oldImage;
                    }
                    
                    if ($partnerId) {
                        $query = 'UPDATE `data_exhibition_partners` SET
                                      `CityID` = '.Connection::GetSQLString($cityId).',
                                `PartnerTitle` = '.Connection::GetSQLString($partner).',
                                `PartnerImage` = '.Connection::GetSQLString($image).'
                            WHERE
                                   `PartnerID` = ' . $partnerId;
                    } else {
                        $query = 'INSERT INTO `data_exhibition_partners` SET
                                  `CityID` = '.Connection::GetSQLString($cityId).',
                            `PartnerTitle` = '.Connection::GetSQLString($partner).',
                            `PartnerImage` = '.Connection::GetSQLString($image);
                    }
                    $stmt->Execute($query);
                }

            }
            
            if(isset($_FILES['ScheduleFile']) && $_FILES['ScheduleFile']['size'] > 0){
                if(!$this->uploadSchedule($cityId, $_FILES['ScheduleFile'])){
                    return false;
                }
            }
            else {
                $this->updateSchedule($cityId, $request->GetProperty('Schedule'));
            }
            
            return true;
        }
        
        return false;
    }

    public function getTypesListEvent($active = null)
    {
        return [
            [
                'Key' => 'experts',
                'Title' => GetTranslation('exhibition-event-type-experts', $this->module),
                'Selected' => (!empty($active) and $active == 'experts') ? 1 : 0
            ],
            [
                'Key' => 'parent',
                'Title' => GetTranslation('exhibition-event-type-parent', $this->module),
                'Selected' => (!empty($active) and $active == 'parent') ? 1 : 0
            ],
            [
                'Key' => 'abroad',
                'Title' => GetTranslation('exhibition-event-type-abroad', $this->module),
                'Selected' => (!empty($active) and $active == 'abroad') ? 1 : 0
            ],
            [
                'Key' => 'prof',
                'Title' => GetTranslation('exhibition-event-type-prof', $this->module),
                'Selected' => (!empty($active) and $active == 'prof') ? 1 : 0
            ],
            [
                'Key' => 'univer',
                'Title' => GetTranslation('exhibition-event-type-univer', $this->module),
                'Selected' => (!empty($active) and $active == 'univer') ? 1 : 0
            ],
            [
                'Key' => 'toelf',
                'Title' => GetTranslation('exhibition-event-type-toelf', $this->module),
                'Selected' => (!empty($active) and $active == 'toelf') ? 1 : 0
            ],
        ];
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
            WHERE c2u.CityID='.$cityId.' 
            ORDER BY c2u.SortOrder ASC';
        return $stmt->FetchList($query);
    }
    
    public function getMainPartners()
    {
    	$cityId = $this->GetIntProperty('CityID');
    	if (empty($cityId)) {
    		return array();
    	}
    
    	$stmt = GetStatement();
    	$query = 'SELECT * FROM `data_exhibition_mainpartners` WHERE CityID='.$cityId;
    	$list = $stmt->FetchList($query);
    	if ($list) {
    
    		foreach ($list as $key => $item) {
    			if (! empty($item['PartnerImage'])) {
    				foreach ($this->params['PartnerImage'] as $param) {
    					$list[$key][$param['Name'].'Path'] = $param['Path'].'exhibition/'.$item['PartnerImage'];
    				}
    			}
    		}
    
    		return $list;
    	}
    
    	return array();
    }
    
    public function removeMainPartner($id)
    {
    	$id = intval($id);
    	if (empty($id)) {
    		return false;
    	}
    
    	$stmt = GetStatement();
    	$row = $stmt->FetchRow(
    			'SELECT PartnerID, PartnerImage FROM `data_exhibition_mainpartners` WHERE `PartnerID`=' . $id
    	);
    	if (! empty($row)) {
    		if (! empty($row['PartnerImage'])) {
    			$file = DATA_EXHIBITION_IMAGE_DIR.$row['PartnerImage'];
    			if (file_exists($file) and is_file($file)) {
    				unlink($file);
    			}
    		}
    
    		$stmt->FetchRow('DELETE FROM `data_exhibition_mainpartners` WHERE `PartnerID`=' . $id);
    
    		return true;
    	}
    
    	return false;
    }

    public function getPartners()
    {
        $cityId = $this->GetIntProperty('CityID');
        if (empty($cityId)) {
            return array();
        }

        $stmt = GetStatement();
        $query = 'SELECT * FROM `data_exhibition_partners` WHERE CityID='.$cityId;
        $list = $stmt->FetchList($query);
        if ($list) {

            foreach ($list as $key => $item) {
                if (! empty($item['PartnerImage'])) {
                    foreach ($this->params['PartnerImage'] as $param) {
                        $list[$key][$param['Name'].'Path'] = $param['Path'].'exhibition/'.$item['PartnerImage'];
                    }
                }
            }
            
            return $list;
        }
        
        return array();
    }

    public function removePartner($id)
    {
        $id = intval($id);
        if (empty($id)) {
            return false;
        }

        $stmt = GetStatement();
        $row = $stmt->FetchRow(
            'SELECT PartnerID, PartnerImage FROM `data_exhibition_partners` WHERE `PartnerID`=' . $id
        );
        if (! empty($row)) {
            if (! empty($row['PartnerImage'])) {
                $file = DATA_EXHIBITION_IMAGE_DIR.$row['PartnerImage'];
                if (file_exists($file) and is_file($file)) {
                    unlink($file);
                }
            }

            $stmt->FetchRow('DELETE FROM `data_exhibition_partners` WHERE `PartnerID`=' . $id);
            
            return true;
        }
        
        return false;
    }
    
    protected function updateSchedule($cityID, $schedule)
    {
        $stmt = GetStatement();
        $query = 'SELECT RoomID, Title FROM data_exhibition_room WHERE CityID='.intval($cityID);
        $oldRoomList = $stmt->FetchIndexedList($query);

        foreach($oldRoomList as $roomID=>$room)
        {
            $stmt->Execute('DELETE FROM data_exhibition_action WHERE RoomID='.intval($roomID));
            $stmt->Execute('DELETE FROM data_exhibition_room WHERE RoomID='.intval($roomID));
        }

        foreach($schedule as $roomInfo)
        {
            $stmt = GetStatement();
            $query = 'SELECT RoomID FROM data_exhibition_room WHERE CityID='.intval($cityID).' AND Title='.Connection::GetSQLString($roomInfo['Title']);
            $roomID = $stmt->FetchField($query);

            if(!$roomID)
            {
                $query = 'INSERT INTO data_exhibition_room SET ';
                $query .= 'CityID=' . intval($cityID) . ', 
                        Title=' . Connection::GetSQLString($roomInfo['Title']);

                if (!$stmt->Execute($query))
                    return false;

                $roomID = $stmt->GetLastInsertID();
            }

            foreach($roomInfo['ActionList'] as $actionInfo)
            {
                $query = 'INSERT INTO data_exhibition_action SET ';
                $query .= 'RoomID='.intval($roomID).', 
                    Date='.Connection::GetSQLDate($roomInfo['Date']).', 
                    TimeFrom='.Connection::GetSQLString($actionInfo['TimeFrom']).', 
                    TimeTo='.Connection::GetSQLString($actionInfo['TimeTo']).', 
                    Type='.Connection::GetSQLString($actionInfo['Type']).', 
                    Title='.Connection::GetSQLString($actionInfo['Title']).', 
                    Name='.Connection::GetSQLString($actionInfo['Name']).', 
                    Post='.Connection::GetSQLString($actionInfo['Post']).', 
                    Description='.Connection::GetSQLString($actionInfo['Description']);

                if (!$stmt->Execute($query))
                    return false;
            }
        }
    }
    
    protected function uploadSchedule($cityID, $fileInfo)
    {
        $file = fopen($fileInfo['tmp_name'], 'r');
        if($file){
            $delimiter = ',';
            $enclosure = '"';
            $stmt = GetStatement();
            $data = fgetcsv($file, 0, $delimiter, $enclosure);
            if($data !== false && trim($data[1]) == 'Зал'){
                $query = 'SELECT r.Title, r.RoomID FROM data_exhibition_room r WHERE r.CityID='.intval($cityID);
                $oldRoomList = $stmt->FetchIndexedList($query);
                foreach($oldRoomList as $title=>$room){
                    $query = 'SELECT a.Title, a.ActionID, a.Date FROM data_exhibition_action a WHERE a.RoomID='.intval($room['RoomID']);
                    $oldRoomList[$title]['ActionList'] = $stmt->FetchIndexedList($query);
                    $stmt->Execute('DELETE FROM data_exhibition_room WHERE RoomID='.intval($room['RoomID']));
                    $stmt->Execute('DELETE FROM data_exhibition_action WHERE RoomID='.intval($room['RoomID']));
                }
                $newRoomList = array();
                while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
                    $roomTitle = trim($data[1]);
                    if(strlen($roomTitle) == 0 || $roomTitle == 'Зал'){
                        continue;
                    }
                    
                    $oldRoomInfo = null;
                    if(isset($oldRoomList[$roomTitle])){
                        $oldRoomInfo = $oldRoomList[$roomTitle];
                    }
                    
                    $roomID = $newRoomList[$roomTitle];
                    if(!$roomID){
                        $query = 'INSERT INTO data_exhibition_room SET ';
                        if($oldRoomInfo != null){
                            $query .= 'RoomID='.intval($oldRoomInfo['RoomID']).', ';
                        }
                        $query .= 'CityID='.intval($cityID).',
                            Title='.Connection::GetSQLString($roomTitle);
                        if($stmt->Execute($query)){
                            $roomID = $stmt->GetLastInsertID();
                            $newRoomList[$roomTitle] = $roomID;
                        }
                    }
                    
                    $actionTitle = trim($data[7]);
                    $actionDate = trim($data[0]);
                    $query = 'INSERT INTO data_exhibition_action SET ';
                    if($oldRoomInfo != null && isset($oldRoomInfo['ActionList'][$actionTitle]) && isset($oldRoomInfo['ActionList'][$actionDate])){
                        $query .= 'ActionID='.intval($oldRoomInfo['ActionList'][$actionTitle]['ActionID']).',';
                    }
                    $type = '';
                    if($data[6] == 'ЕГЭ и ОГЭ') {$type='experts';}
                    elseif($data[6] == 'Для родителей') {$type='parent';}
                    elseif($data[6] == 'Обучение за рубежом') {$type='abroad';}
                    elseif($data[6] == 'Профориентация') {$type='prof';}
                    elseif($data[6] == 'Лекция от вуза') {$type='univer';}
                    elseif($data[6] == 'English') {$type='toefl';}
                    $query .= 'Date='.Connection::GetSQLDate($actionDate).',
                        RoomID='.intval($roomID).',                        
                        TimeFrom='.Connection::GetSQLString($data[3]).',
                        TimeTo='.Connection::GetSQLString($data[4]).',
                        Type='.Connection::GetSQLString($type).',
                        Title='.Connection::GetSQLString($actionTitle).',
                        Name='.Connection::GetSQLString($data[8]).',
                        Post='.Connection::GetSQLString($data[9]).',
                        Description='.Connection::GetSQLString($data[10]);
                    if($stmt->Execute($query)){
                        $actionID = $stmt->GetLastInsertID();
                    }
                }
                fclose($file);
                return true;
            }
            else {
                $this->AddError('exhibition-city-save-schedule-file-notcsv', $this->module);
                fclose($file);
                return false;
            }
        }
        else {
            $this->AddError('exhibition-city-save-schedule-file-incorrect', $this->module);
        }
        return false;
    }

    public function removeHeadImage()
    {
        if (ImageManager::RemoveImage(DATA_EXHIBITION_IMAGE_DIR . $this->GetProperty('HeadImage'))) {
            $stmt = GetStatement();
            $query = "UPDATE `data_exhibition_city` SET HeadImage=NULL WHERE CityID=".$this->GetIntProperty('CityID');
            return $stmt->Execute($query);
        }

        return false;
    }
}
