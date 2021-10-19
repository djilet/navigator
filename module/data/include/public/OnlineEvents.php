<?php
setlocale(LC_ALL, 'ru_RU.UTF-8');

class OnlineEvents extends LocalObjectList
{
    private $now;
    private $module;

    /**
     * OnlineEvents constructor.
     */
    public function __construct($module = 'data')
    {
        $this->module = $module;
        $this->now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $this->params['HeadImage'] = LoadImageConfig('HeadImage', $this->module, DATA_ONLINEEVENTHEAD_IMAGE);
    }

    public function load($daysLimit = 0, $inDayLimit = 0, LocalObject $request = null)
    {
        $session = GetSession();
        $where = array();
        $join = array();
        
        $onlyInFuture = true;
        $where[] = "Active='Y'";
        $where[] = "ShowInList='Y'";
        $orderby = "e.EventDateTime ASC";
        
        if($request != null)
        {
            if ($request->IsPropertySet('Ids')){
                $ids = implode(", ", Connection::GetSQLArray($request->GetProperty('Ids')));
                $where[] = "e.OnlineEventID IN ($ids)";
            }

        	if($request->IsPropertySet("universityID"))
        	{
        		$join[] = "LEFT JOIN data_online_event2university e2un ON e.OnlineEventID=e2un.OnlineEventID";
        		$where[] = "e2un.UniversityID=".$request->GetIntProperty("universityID");
        		$onlyInFuture = false;
        	}
        	elseif($request->IsPropertySet("specialityID"))
        	{
        		$join[] = "LEFT JOIN data_online_event2direction e2d ON e.OnlineEventID=e2d.OnlineEventID";
        		$join[] = "LEFT JOIN data_speciality sp ON e2d.DirectionID=sp.DirectionID";
        		$where[] = "sp.SpecialityID=".$request->GetIntProperty("specialityID");
        		$onlyInFuture = false;
        	}
        	elseif($request->IsPropertySet("ProfessionID"))
        	{
        		$join[] = "LEFT JOIN data_online_event2profession e2p ON e.OnlineEventID=e2p.OnlineEventID";
        		$where[] = "e2p.ProfessionID=".$request->GetIntProperty("ProfessionID");
        		$onlyInFuture = false;
        	}
        	
        	if($request->IsPropertySet("OrderDesc"))
        	{
        		$orderby = "e.EventDateTime DESC";
        	}
        }
        	
        if($onlyInFuture)
        {
        	$where[] = "ADDTIME(EventDateTime,Duration) > " . Connection::GetSQLString($this->now->format('Y-m-d H:i:s'));
        }
        
        $baseURLPrefix = '/events';
        $userInfo = $session->GetProperty('UserItem');
        if (!empty($userInfo['UserID'])) {
            $query = "SELECT e.*, e2u.Status,
            	CONCAT('".$baseURLPrefix."', '/', e.OnlineEventID, '-', e.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS OnlineEventURL 
            	FROM data_online_event AS e
                LEFT JOIN data_online_event2user AS e2u ON e.OnlineEventID=e2u.OnlineEventID 
                    AND e2u.UserItemID=".intval($userInfo['UserID'])."
                ".(!empty($join) ? implode(' ', $join) : '')."
        		".((count($where) > 0)?" WHERE ".implode(" AND ", $where):"")."
                ORDER BY ".$orderby;
        } else {
            $query = "SELECT e.*,
            	CONCAT('".$baseURLPrefix."', '/', e.OnlineEventID, '-', e.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS OnlineEventURL 
            	FROM data_online_event e
                ".(!empty($join) ? implode(' ', $join) : '')."
        		".((count($where) > 0)?" WHERE ".implode(" AND ", $where):"")."
                ORDER BY ".$orderby;
        }
        
        $this->SetItemsOnPage(0);
        $this->LoadFromSQL($query);
        $this->prepare($daysLimit, $inDayLimit);
    }
    
    public function loadArchive(LocalObject $request = null)
    {
        $query = QueryBuilder::init()->select([
            'e.*',
    		"CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', e.OnlineEventID, '-', e.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS OnlineEventURL" 
        ])->from('data_online_event AS e')
        ->where([
            "ADDTIME(e.EventDateTime,e.Duration) < " . Connection::GetSQLString($this->now->format('Y-m-d H:i:s')),
            "e.Active='Y'",
            "e.ShowInList='Y'"
        ])
        ->order(["e.EventDateTime DESC"]);

        if ($request->IsPropertySet('Ids')){
            $ids = implode(", ", Connection::GetSQLArray($request->GetProperty('Ids')));
            $query->addWhere("e.OnlineEventID IN ($ids)");
        }

    	$this->SetItemsOnPage(0);
    	$this->LoadFromSQL($query->getSQL());
    	$this->prepare();
    }
    
    public function loadForUser($userID, $request)
    {
    	$query = "SELECT e.*, e2u.Status, 
    		CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', e.OnlineEventID, '-', e.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS OnlineEventURL 
    		FROM data_online_event AS e
    		LEFT JOIN data_online_event2user AS e2u ON e.OnlineEventID=e2u.OnlineEventID
    		WHERE e2u.UserItemID=".intval($userID)."
    		AND Active='Y'
    		ORDER BY EventDateTime ASC";
    	$this->SetItemsOnPage(0);
    	$this->LoadFromSQL($query);
    	$this->prepare();
    }

    private function prepare($daysLimit = 0, $inDayLimit = 0)
    {
        if (!empty($this->_items)) {
            $result = array();
            $fmt = new IntlDateFormatter(
                'ru_RU',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Europe/Moscow',
                IntlDateFormatter::GREGORIAN
            );

            foreach ($this->_items as $item) {
                $date = new DateTime($item['EventDateTime'], new DateTimeZone('Europe/Moscow'));
                $day = $date->format('d-m-Y');
                if (!isset($result[$day])) {
                	if($daysLimit && count($result) >= $daysLimit) break;
                	$fmt->setPattern('EEEE');
                	$weekStr = $fmt->format($date);
                	$fmt->setPattern('d MMMM');
                	$dateStr = $fmt->format($date);
                	$result[$day] = array(
                        'Date'         => $dateStr,
                        'DayOfTheWeek' => $weekStr,
                    	'Day'          => $date->format('d'),
                    	'Month'        => GetTranslation("date-".$date->format('F')),
                        'Children'     => array(),
                    	'__ROWNUM__'   => count($result) + 1
                    );
                }
                
                if($inDayLimit && count($result[$day]['Children']) >= $inDayLimit) continue;

                $item['StartDateUTC'] = $date->format("Y-m-d H:i:s e");
                if ($date < $this->now) {
                    if ($duration = explode(':', $item['Duration']) and count($duration) == 3) {
                        $duration = $duration[0] * 3600 + $duration[1] * 60 + $duration[2];
                        $end = clone $date;
                        $end->modify('+' . $duration . ' second');
                        if ($end > $this->now) {
                            $item['inProgress'] = 1;
                            $item['Progress'] = (($this->now->format('U') - $date->format('U')) * 100) / $duration;
                            $item['Progress'] = number_format($item['Progress'], 2, '.', '');
                            $fmt->setPattern('d MMMM в HH:mm');
                            $item['ProgressDate'] = $fmt->format($date);

                            $diff = $this->now->diff($date);
                            $item['ProgressTime'] = $diff->format('%H:%I:%S');
                        } else {
                            $item['isFinished'] = 1;
                            $fmt->setPattern('d MMMM Y');
                            $item['FinishedDate'] = $fmt->format($date);
                        }
                        $item['EndDateUTC'] = $end->format("Y-m-d H:i:s e");
                    } else {
                        $item['isFinished'] = 1;
                    }
                } else {
                    $fmt->setPattern('d MMMM в HH:mm');
                    $item['OnlineEventDateTitle'] = $fmt->format($date);
                }
                $item['TypeTitle'] = GetTranslation('online-event-' . $item['EventType'], $this->module);
                $item['TimePrefix'] = $this->getDayPrefix($date);

                $result[$day]['Children'][] = $item;
            }

            $this->_items = array_values($result);
        }
    }

    public function getByID($eventID, $createChat = false)
    {
    	$session = GetSession();
    	$userInfo = $session->GetProperty('UserItem');
    	if (empty($userInfo['UserID']))
    	{
    		$query = 'SELECT e.*, cg.GroupID AS ChatGroupID
    			FROM data_online_event e
    			LEFT JOIN chat_group AS cg ON e.OnlineEventID=cg.AttachID AND cg.Type="online_event"
    			WHERE e.OnlineEventID=' . intval($eventID) . ' AND e.Active="Y"';
    	}
    	else 
    	{
    		$query = 'SELECT e.*, e2u.Status as Status, cg.GroupID AS ChatGroupID, u.ChatStatus
    			FROM data_online_event e
    			LEFT JOIN data_online_event2user AS e2u ON e.OnlineEventID=e2u.OnlineEventID AND e2u.UserItemID=' . intval($userInfo['UserID']). '
    			LEFT JOIN users_item AS u ON e2u.UserItemID=u.UserID
    			LEFT JOIN chat_group AS cg ON e.OnlineEventID=cg.AttachID AND cg.Type="online_event"
    			WHERE e.OnlineEventID=' . intval($eventID) . ' AND e.Active="Y"';
    	}
        
        $this->SetItemsOnPage(0);
        $this->LoadFromSQL($query);
        $this->prepare();
        
        $row = $this->_items[0]['Children'][0];
        $this->_items = array();
        
        //init chat group if necessary
        if($row["Chat"] == 'Y')
        {
        	if(!isset($row["ChatGroupID"]))
        	{
        		$stmt = GetStatement();
        		$query = "INSERT INTO chat_group(Type, AttachID, Created) VALUES('online_event', ".intval($eventID).", ".Connection::GetSQLString(GetCurrentDateTime()).")";
        		if($stmt->Execute($query))
        		{
        			$row["ChatGroupID"] = $stmt->GetLastInsertID();
        		}
        	}
        }
        else 
        {
        	unset($row["ChatGroupID"]);
        }
        
        //prepare image
        if ($row && !empty($row['HeadImage'])) {
        	foreach ($this->params['HeadImage'] as $param) {
        		$row[$param['Name'].'Path'] = $param['Path'].'onlineevent/'.$row['HeadImage'];
        	}
        }

        $stmt = GetStatement();
        $links = $stmt->FetchList("SELECT * FROM `data_online_event_link` WHERE OnlineEventID=" . $row["OnlineEventID"]);
        if (count($links) > 0) {
            $row["LinkList"] = $links;
        }

        return $row;
    }

    public function loadFirstEvent($count = 3, $request)
    {
        $date = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $count = intval($count) == 0 ? 3 : intval($count);
        $query = "SELECT e.OnlineEventID, e.Title, e.EventDateTime, e.EventType, 
        	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', e.OnlineEventID, '-', e.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS OnlineEventURL 
			FROM data_online_event e 
			WHERE e.EventDateTime > " . Connection::GetSQLString($date->format('Y-m-d H:i:s')) . " AND e.ShowInList='Y'
			ORDER BY e.EventDateTime ASC";
        $this->SetItemsOnPage($count);
        $this->LoadFromSQL($query);

        $fmt = new IntlDateFormatter(
            'ru_RU',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Moscow',
            IntlDateFormatter::GREGORIAN,
            'dd MMMM в HH:mm'
        );
        foreach ($this->_items as $key => $item) {
            $date = new DateTime($item['EventDateTime'], new DateTimeZone('Europe/Moscow'));
            $this->_items[$key]['EventDate'] = $fmt->format($date);
            $this->_items[$key]['TypeTitle'] = GetTranslation('online-event-' . $item['EventType'], $this->module);
        }
    }
    
    public function signUser($eventID, $userID)
    {
    	if(!$this->checkSigned($eventID, $userID))
    	{
    		$stmt = GetStatement();
    		$query = "INSERT INTO `data_online_event2user`  SET
    		OnlineEventID=".intval($eventID).",
    		UserItemID=".intval($userID).",
    		Created=NOW(),
    		Status='signed',
    		Source='website'";
    		
    		$session =& GetSession();
    		if($session->GetProperty('utm_source'))
    		{
    			$query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
    			utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
    			utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
    			utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
    			utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
    		}

    		$result = $stmt->Execute($query);

    		if ($result) {
                //create short link
                $shortLink = $this->getShortSignURL($eventID, $userID);

                if($shortLink)
                {
                    $stmt->Execute("UPDATE `data_online_event2user` 
                        SET ShortLink=".Connection::GetSQLString($shortLink)." 
                        WHERE OnlineEventID=".intval($eventID)." AND UserItemID=".intval($userID));
                }
            }

    		return $result;
    	}
    	return false;
    }
    
    public function unsignUser($eventID, $userID)
    {
    	$stmt = GetStatement();
    	$query = "DELETE FROM `data_online_event2user` WHERE
	    		OnlineEventID=".intval($eventID)." AND 
	    		UserItemID=".intval($userID);
    	$stmt->Execute($query);
    }
    
    public function checkSigned($eventID, $userID)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT count(*) FROM `data_online_event2user` WHERE OnlineEventID=".intval($eventID)." AND UserItemID=".intval($userID));
    }
    
    public function setWatchedUser($eventID, $userID)
    {
    	$stmt = GetStatement();
    	if($this->checkSigned($eventID, $userID))
    	{
    		$query = "UPDATE `data_online_event2user` SET Status='watched'
    			WHERE OnlineEventID=".intval($eventID)." AND UserItemID=".intval($userID);
    	}
    	else 
    	{
    		$query = "INSERT INTO `data_online_event2user` SET Status='watched', Source='website', OnlineEventID=".intval($eventID).", UserItemID=".intval($userID).", Created=NOW()";
    		$session =& GetSession();
    		if($session->GetProperty('utm_source'))
    		{
    			$query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
    			utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
    			utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
    			utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
    			utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
    		}
    	}

    	$result = $stmt->Execute($query);

    	// Отправка посещения в CRM. Если есть связка с выставкой, то данные выбираются из нее.
        $user = new UserItem(null);
        $exhibition = new PublicExhibition('data');

    	if ($result && $user->loadByID($userID)) {
            $onlineEvents = new OnlineEvents('data');
            $onlineEventVisit = $onlineEvents->getVisitByEventAndUser($eventID, $userID);
            $onlineEvent = $onlineEvents->getByID($eventID);
            $eventGuid = $onlineEvent['GUID'];

            $exhibitionCities = $exhibition->getCityListInfoByOnlineEventID($eventID);

            if (count($exhibitionCities) > 0) {
                foreach ($exhibitionCities as $exhibitionCity) {
                    $guid = !empty($exhibitionCity['GUID']) ? $exhibitionCity['GUID'] : $eventGuid;

                    if (!empty($guid)) {
                        $exhibitionRegistration = $exhibition->getRegistrationOfUserWhoWatchEvent(
                            $exhibitionCity['ExhibitionID'],
                            $exhibitionCity['CityTitle'],
                            $user->GetProperty('UserEmail')
                        );

                        if ($exhibitionRegistration) {
                            $preparedData = $this::prepareDataForCRM($exhibitionRegistration, $guid, 'exhibition');
                        } else {
                            $preparedData = $this::prepareDataForCRM($user, $guid, 'event', $onlineEventVisit);
                        }

                        if ($preparedData) {
                            $this::sendVisitToCRM($preparedData);
                        }
                    }
                }
            } else {
                if (!empty($eventGuid)) {
                    $preparedData = $this::prepareDataForCRM($user, $eventGuid, 'event', $onlineEventVisit);
                    $this::sendVisitToCRM($preparedData);

                    if ($preparedData) {
                        $this::sendVisitToCRM($preparedData);
                    }
                }
            }
        }

    	return $result;
    }
    
    public function getIDByStaticPath($staticPath)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT o.OnlineEventID FROM `data_online_event` o WHERE o.StaticPath=".Connection::GetSQLString($staticPath));
    }
    
    public function getStaticPathByID($onlineEventID)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT o.StaticPath FROM `data_online_event` o WHERE o.OnlineEventID=".intval($onlineEventID));
    }
    
    protected function getDayPrefix($match_date)
    {
    	$match_date->setTime(0, 0);
    	$date = new DateTime("now", new DateTimeZone('Europe/Moscow'));
    	$date->setTime(0, 0);
    	$interval = $date->diff($match_date);
    	
    	if($interval->days == 0) 
    	{
    		return "сегодня";
    	} 
    	elseif($interval->days == 1) 
    	{
    		if($interval->invert == 0) 
    		{
    			return "завтра";
    		} 
    		else 
    		{
    			return "вчера";
    		}
    	} 
    	return "";
    }

    public function getVisitByEventAndUser($onlineEventID, $userID)
    {
        if (empty($onlineEventID) || empty($userID)) {
            return false;
        }

        $stmt = GetStatement();

        $query = "SELECT * FROM `data_online_event2user`
					WHERE OnlineEventID=".intval($onlineEventID)." AND UserItemID=".intval($userID);

        $visits = $stmt->FetchList($query);

        return $visits[count($visits) - 1];
    }

    public static function sendVisitToCRM($data){
        $hookUrl = 'https://prod-35.westeurope.logic.azure.com:443/workflows/4568705c03fe417493f24aa2289bee44/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=nnTH-C-Zv5eCK5o5NIOLQgvN_lQ42u40-etW8AJY_bQ';

        $logFile = fopen(PROJECT_DIR . 'var/log/crm.log', 'a+');

        $curlInit = curl_init($hookUrl);
        curl_setopt($curlInit, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlInit, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit, CURLOPT_VERBOSE, true);
        curl_setopt($curlInit, CURLOPT_STDERR, $logFile);
        curl_setopt($curlInit, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        fwrite($logFile, date('Y-m-d H:i:s') . ' START REQUEST WITH PARAMETERS:' . PHP_EOL .
            json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL);
        $result = curl_exec($curlInit);
        fwrite($logFile, date('Y-m-d H:i:s') . ' RESPONSE CONTENT: ' . $result . PHP_EOL);
        fwrite($logFile, date('Y-m-d H:i:s') . ' FINISH REQUEST' . PHP_EOL);
        curl_close ($curlInit);
    }

    public static function prepareDataForCRM($data, $guid, $type, $visit = null)
    {
        if ($type === 'exhibition') {
            $who = $data['Who'] === 'Ученик' ? 'Школьник' : $data['Who'];

            return [
                'CRMName' => $data['LastName'] . ' ' . $data['FirstName'],
                'CRMPhone' => $data['Phone'],
                'CRMEmail' => $data['Email'] ?? '',
                'CRMType' => $who,
                'CRMCity' => $data['City'],
                'CRMYear' => $data['Class'] ?? '',
                'CRMComment' => 'выставка',
                'formname' => $guid,
                'utm_source' => $data['utm_source'] ?? '',
                'utm_medium' => $data['utm_medium'] ?? '',
                'utm_campaign' => $data['utm_campaign'] ?? '',
            ];
        } elseif ($type === 'event') {
            $userWho = $data->GetProperty('UserWho');

            switch ($userWho) {
                case 'parent':
                    $who = 'Родитель';
                    break;
                case 'child':
                    $who = 'Школьник';
                    break;
                case 'student':
                    $who = 'Студент';
                    break;
                default:
                    $who = '';
            }

            return [
                'CRMName' => $data->GetProperty('UserName'),
                'CRMPhone' => $data->GetProperty('UserPhone'),
                'CRMEmail' => $data->GetProperty('UserEmail'),
                'CRMType' => $who,
                'CRMCity' => 'Онлайн',
                'CRMYear' => $data->GetProperty('ClassNumber') ?? '',
                'CRMComment' => 'выставка',
                'formname' => $guid,
                'utm_source' => $visit['utm_source'] ?? '',
                'utm_medium' => $visit['utm_medium'] ?? '',
                'utm_campaign' => $visit['utm_campaign'] ?? '',
            ];
        }

        return null;
    }

    public function getShortSignURL($eventID, $userID)
    {
        if ($eventID && $userID) {
            $longUrl = GetUrlPrefix() . 'events?ShowRecord=' . $eventID . '&UserID=' . $userID;

            if ($url = GetShortURL($longUrl)){
                return $url;
            }
        }

        return false;
    }
}