<?php
require_once(dirname(__FILE__)."/../init.php");

Class Tracker extends LocalObject{
	protected $TrackID;
	protected $UserID;
	protected $stmt;
	protected $filepath;
	protected $session;
	protected $tracking_list;
	protected $tracking_info;
	public $module = 'tracker';

	public function __construct(){
		parent::LocalObject();
		$this->stmt = GetStatement();
		$this->session =& GetSession();
		$this->getTrackUser();
	}

//Init
	protected function getTrackUser(){
		if ( $this->getUserID() ) {
			if ( $this->getTrackID() ){
				$this->editToUserID();
			}
		}
		elseif( !$this->getTrackID() ){
			$this->setTrackID();
		}

	}

	protected function getTrackID(){
		if ( !empty($this->session->GetProperty('TrackID')) ) {
			$this->TrackID = $this->session->GetProperty('TrackID');
			return true;
		}
		else{
			return false;
		}
	}

	protected function setTrackID(){
		if ( empty($this->session->GetProperty('TrackID')) ) {
			$this->session->SetProperty('TrackID', md5(uniqid()));
			$this->session->SaveToDB();
			$this->TrackID = $this->session->GetProperty('TrackID');
		}
	}

	protected function getUserID(){
		$user = $this->session->GetProperty("UserItem");
		if ( !empty($user) && !empty($user['UserID'])) {
			$this->UserID = $user['UserID'];
			return true;
		}
		else{
			return false;
		}
	}

	public function editToUserID(){
		$query = "UPDATE `user_tracking` SET `UserID` = " . Connection::GetSQLString($this->UserID) . ", `TrackID` = NULL WHERE `TrackID` = " . Connection::GetSQLString($this->TrackID);
		if($this->stmt->Execute($query)){
			$this->session->SetProperty('TrackID', '');
			$this->session->SaveToDB();
			return true;
		}

		return false;
	}


//DB
	public function addAction($page = null, $action = null){
		if ( empty($page) ) {
			$page = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
			if ( preg_match('/.*?\./', $page) ) {
				return false;
			}
		}

		if ( !empty($action) ) {
			$result = array();
			foreach ($action as $filterKey => $filter) {
				if (is_array($filter)){
					foreach ($filter as $itemKey => $item) {
						if (!empty($item)){
							$result[$filterKey][$itemKey] = $item;
						}
					}
				}
				else{
					if (!empty($filter)){
						$result[$filterKey] = $filter;
					}
				}
			}

			$action = serialize($result);
		}

		if ( isset($this->UserID) && !empty($this->UserID) ) {
			$track_id = null;
			$user_id = $this->UserID;
		}
		else{
			$track_id = $this->TrackID;
			$user_id = null;
		}

		$query = "INSERT INTO `user_tracking` (`ID`, `Created`, `UserID`, `TrackID`, `Page`, `Action`) VALUES(
		NULL,
		" . Connection::GetSQLString(GetCurrentDateTime()) . ",
		" . Connection::GetSQLString($user_id) . ",
		" . Connection::GetSQLString($track_id) . ",
		" . Connection::GetSQLString($page) . ",
		" . Connection::GetSQLString($action) . "
		)";
		if($this->stmt->Execute($query)){
			return true;
		}
		else{
			return false;
		}
	}

	public function getTrackingList($date_from = null, $date_to = null, $part_count = 0, $last_id = 0, $max_id = null){
		if ( !empty($date_from) && !empty($date_to) ) {
			if ($max_id <= $last_id && $max_id > 0) {
				$answer['Status'] = 'success';
				return $answer;
			}

			$date_to = date('d.m.Y', strtotime($date_to) + 24 * 3600);

			if ($part_count > 0) {
				$limit = " LIMIT " . $part_count;
			}
			else{
				$limit = null;
			}

			if ($max_id < 1) {
				$query = "SELECT MAX(ID) FROM user_tracking WHERE Created > " . Connection::GetSQLDateTime($date_from) . " AND Created < " . Connection::GetSQLDateTime($date_to) . " ORDER BY ID ASC";
				if ( !$max_id = $this->stmt->FetchField($query) ) {
					$this->AddError('empty-data', $this->module);
					return false;
				}
			}

			$query = "SELECT ID, Created, UserID, TrackID, Page, Action
					  FROM user_tracking
					  WHERE Created > " . Connection::GetSQLDateTime($date_from) . "
					  AND Created < " . Connection::GetSQLDateTime($date_to) . "
					  AND ID > " . $last_id . "
					  ORDER BY ID ASC " . $limit;
		}
		else{
			$this->AddError('empty-date', $this->module);
			return false;
		}


		if( $this->tracking_list = $this->stmt->FetchIndexedList($query, 'ID') ) {
			$answer['LastID'] = max($this->tracking_list)['ID'];
			$answer['Status'] = 'work';
			$answer['MaxID'] = $max_id;
			return $answer;
		}
		else{
			$this->AddError('empty-data', $this->module);
			return false;
		}
	}

	public function parseTrackingList(){
		$info = array();
		if ( empty($this->tracking_list) ) {
			$this->AddError('empty-data', $this->module);
			return false;
		}

		foreach ($this->tracking_list as $list_key => $tracking_row) {
			if ( !empty($tracking_row['Action']) ) {
				$this->tracking_list[$list_key]['Action'] = unserialize($tracking_row['Action']);

				foreach ($this->tracking_list[$list_key]['Action'] as $action_key => $action_values) {
					if (is_array($action_values)){
						foreach ($action_values as $key => $value) {
							if ( !empty($value) && $action_key != 'Subject') {
								$info['Action'][$action_key][$value] = $value;
							}
						}
					}
				}
			}

			if ( !empty($tracking_row['UserID']) ) {
				if (!isset($info['UserID'][$tracking_row['UserID']])){
					$info['UserID'][$tracking_row['UserID']] = '';
				}
				if ($info['UserID'][$tracking_row['UserID']] != $tracking_row['UserID']) {
					$info['UserID'][$tracking_row['UserID']] = $tracking_row['UserID'];
				}
			}
		}

		$this->tracking_info['tracking'] = $this->tracking_list;
		unset($this->tracking_list);

		//unique UserID
		$unique_fileds['UserID'] = $this->getStrForQuery($info['UserID']);
		//unique Actions values
		if (!empty($info['Action'])){
			foreach ($info['Action'] as $key => $value) {
				$unique_fileds[$key] = $this->getStrForQuery($value);
			}
		}

		unset($info);

		//Get users info
		$user_query = "SELECT UserID, UserEmail, UserPhone, UserName, UserWho, ClassNumber FROM users_item WHERE UserID IN (" . $unique_fileds['UserID'] . ")";
		if( $users = $this->stmt->FetchList($user_query) ){
			foreach ($users as $key => $value) {
				$this->tracking_info['users'][$value['UserID']] = $value;
			}
		}

		//Get Titles
		if (!empty($unique_fileds['Region'])){
			$all_query['Region'] = "SELECT RegionID, Title FROM data_region WHERE RegionID IN (" . $unique_fileds['Region'] . ")";
		}
		if (!empty($unique_fileds['BigDirection'])){
			$all_query['BigDirection'] = "SELECT BigDirectionID, Title FROM data_bigdirection WHERE BigDirectionID IN (" . $unique_fileds['BigDirection'] . ")";
		}
		if (!empty($unique_fileds['Profession'])){
			$all_query['Profession'] = "SELECT ProfessionID, Title FROM data_profession WHERE ProfessionID IN (" . $unique_fileds['Profession'] . ")";
		}
		$all_query['Subject'] = "SELECT SubjectID, Title FROM data_subject";

		foreach ($all_query as $key => $query) {
			if( $titles = $this->stmt->FetchList($query) ){
				foreach ($titles as $titles_key => $titles_value) {
					$this->tracking_info['titles'][$key][$titles_value[$key . 'ID']] = $titles_value;
				}
			}
		}

		return true;
	}

	//Service for $this->parseTrackingInfo()
	public function getStrForQuery($array){
		$query_items = '';
		$i = 0;
		foreach ($array as $key => $value) {
			$i++;
			if( $i >= count($array) ){
				$query_items .= $value;
			}
			else{
				$query_items .= $value . ',';
			}
		}
		return $query_items;
	}

//CSV
	public function exportToCsv($file_path, $last_id = null){
		//Csv methods
		$this->filepath = $file_path;

		if ( empty($last_id) ) {
			if( !$this->checkPermissionFile() ){
				return false;
			}
		}

		if( !$this->parseTrackingList() ){
			$this->AddError('parse_error', $this->module);
			return false;
		}
		$f = fopen($this->filepath, 'a');

		$logs = $this->tracking_info['tracking'];
		$users = $this->tracking_info['users'];
		$titles = $this->tracking_info['titles'];
		unset($this->tracking_info);

		$filters = array('Region', 'Subject', 'BigDirection', 'Profession');
		$other_filters = array('Military','Delay', 'Hostel');


		foreach ($logs as $key => $log) {
			$row = array();

			$row['Date'] = (isset($log['Created']) ? $log['Created']: '');

			$row['UserName'] = (isset($users[$log['UserID']]['UserName']) ? $users[$log['UserID']]['UserName'] : '');
			$row['UserEmail'] = (isset($users[$log['UserID']]['UserEmail']) ? $users[$log['UserID']]['UserEmail'] : '');
			$row['UserPhone'] = (isset($users[$log['UserID']]['UserPhone']) ? $users[$log['UserID']]['UserPhone'] : '');
			$row['UserWho'] = (isset($users[$log['UserID']]['UserWho']) ? $users[$log['UserID']]['UserWho'] : '');
			$row['ClassNumber'] = (isset($users[$log['UserID']]['ClassNumber']) ? $users[$log['UserID']]['ClassNumber'] : '');

			$row['Page'] = $log['Page'];

			if ( !empty($log['Action']) ) {
				foreach ($filters as $filters_key => $filter) {
					if ( !empty($log['Action'][$filter]) ) {
						foreach ($log['Action'][$filter] as $item_key => $item) {
							if ($filter == 'Subject') {
								if ( !empty($item) ) {
									$row[$filter] = $titles[$filter][$item_key]['Title'] . '=' . $item . ', ';
								}
							}
							elseif ( !empty($titles[$filter][$item]['Title']) ) {
								$row[$filter] = $titles[$filter][$item]['Title'] . ', ';
							}
							else{
								$row[$filter] = '-';
							}
						}
					}
				}
				foreach ($other_filters as $other_key => $other_value) {

					if ( !empty($log['Action'][$other_value]) ) {
						$row['Other'] = GetTranslation($other_value, $this->module) . ', ';
					}
				}
			}

			fputcsv($f, $row, ";");
		}

		fclose($f);
		return true;
	}

	public function getStatistic(){
		$stat = array();
		$result = array();
		$query = "SELECT UserID, Action FROM user_tracking WHERE Action IS NOT NULL ORDER BY ID DESC";
		if ($data = $this->stmt->FetchList($query)){
			//Group by id
			foreach ($data as $key => $item) {
				$filter = unserialize($item['Action']);

				if (!empty($filter['Subject'])){
					foreach ($filter['Subject'] as $id => $val) {
						if (!empty($val)){
							$stat['Subject'][$id][$val]['Score'] = $val;
							if (!isset($stat['Subject'][$id][$val]['Count'])){
								$stat['Subject'][$id][$val]['Count'] = 0;
							}
							$stat['Subject'][$id][$val]['Count']++;
						}
					}
				}
			}

			//Group by period
			$query = "SELECT SubjectID, Title FROM data_subject";
			$subResult = $this->stmt->FetchIndexedAssocList($query, 'SubjectID');
			$titleRow = ['Предмет'];

			foreach ($stat['Subject'] as $id => $val) {
				ksort($val);
				$period = 0;
				$result[$subResult[$id]['Title']]['TotalCount'] = 0;
				$result[$subResult[$id]['Title']]['Period'] = array();
				foreach ($val as $index => $item) {
					$item['Count'] = intval($item['Count']);
					$item['Score'] = intval($item['Score']);

					if ($item['Score'] > 0 && $item['Score'] <= 100){
						while ($period < $item['Score']){
							$period += 10;
							$result[$subResult[$id]['Title']]['Period']['от ' . ($period - 10) . ' до ' . $period] = 0;
							$titleRow['от ' . ($period - 10) . ' до ' . $period] = 'от ' . ($period - 10) . ' до ' . $period;
						}

						$result[$subResult[$id]['Title']]['Period']['от ' . ($period - 10) . ' до ' . $period] += $item['Count'];
						$result[$subResult[$id]['Title']]['TotalCount'] += $item['Count'];
					}
				}
			}

			$f = fopen(TRACKER_EXPORT_DIR . 'statistic.csv', 'w+');
			$titleRow[] = 'Выбран в фильтре';
			fputcsv($f, $titleRow, ';');
			foreach ($result as $subjectName => $subject) {
				$row = array();
				$perc = 100 / $subject['TotalCount'];
				$row[] = $subjectName;
				foreach ($subject['Period'] as $index => $item) {
					$result[$subjectName]['Period'][$index] = round($item * $perc, 1) . '%';
					$row[] = $result[$subjectName]['Period'][$index];
				}
				$row[] = $subject['TotalCount'];
				fputcsv($f, $row, ';');
			}
			return $result;
		}
		return false;
	}

	private function checkPermissionFile(){

        $f = fopen($this->filepath, 'w');
        if (! is_resource($f)) {
            $this->AddError("Не удалось открыть файл {$this->filepath}", $this->module);
            return false;
        }
		$row = array('Дата','Имя','E-mail','Телефон','Статус','Класс','Страница','Регионы','Баллы по предметам','Направления','Профессии','Другие фильтры');
		fputcsv($f, $row, ";");
        fclose($f);

        return true;
    }

    public static function removeInvalidTracking(){
		$stmt = GetStatement();
		$date = new DateTime();
		$date->modify("- 1 day");
		$dateQuery = Connection::GetSQLString($date->format('Y-m-d H:i:s'));

		$query = "DELETE FROM user_tracking WHERE UserID IS NULL AND Created < {$dateQuery}";
		if ($stmt->Execute($query)){
			return true;
		}

		return false;
	}

	public static function getLeadsByUniversity(){
        $stmt = GetStatement();
        $users = array();
        $result = array();

        $query = "SELECT `StaticPath`, Title FROM `data_university`";
        $universityLists = $stmt->FetchIndexedAssocList($query, 'StaticPath');

        foreach ($universityLists as $index => $university) {
            $query = "SELECT UserID, COUNT(Page) AS Visits
                  FROM `user_tracking`
                  WHERE Page LIKE '/university/" . $university['StaticPath'] . "/'
                  AND UserID IS NOT NULL
                  AND Created >= '2018-08-01 00:00:00'
                  GROUP BY UserID
                  ORDER BY Created DESC";
            if ($list = $stmt->FetchList($query)){
                foreach ($list as $key => $user) {
                    $users[$user['UserID']]['UserID'] = $user['UserID'];
                    $users[$user['UserID']]['Univers'][$university['StaticPath']] = $user['Visits'];
                }
            }
        }

        foreach ($users as $id => $user) {
            $i = 0;
            arsort($user['Univers']);
            /*if (count($user['Univers']) > 5){
                $count = 0;
                foreach ($user['Univers'] as $name => $visits) {
                    $count += $visits;
                }

                if (($count / 5) == 1){

                }
            }*/
            foreach ($user['Univers'] as $name => $visits) {
                if ($i >= 5){
                    break;
                }
                $result[$name]++;
                $i++;
            }
        }

        arsort($result);

        //export
        ob_start();

        $f = fopen('php://output', "w");

        $row = ['Вуз', 'Лиды'];
        fputcsv($f, $row, ";");
        foreach ($result as $index => $item) {
            $row = array();
            $row[] = $universityLists[$index]['Title'];
            $row[] = $item;
            fputcsv($f, $row, ";");
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="university-leads.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }

    /*public static function getUniversityLeads(){
        $stmt = GetStatement();
        $result = array();

        $query = "SELECT `StaticPath` FROM `data_list`";
        $lists = $stmt->FetchRows($query);

        $query = "SELECT UserID, GROUP_CONCAT(Page SEPARATOR ';') AS Pages
                  FROM `user_tracking`
                  WHERE Page RLIKE '\/university\/.+'
                  AND UserID IS NOT NULL
                  GROUP BY UserID";
        if (!$users = $stmt->FetchList($query)){
            echo 'false';
            return false;
        }

        foreach ($users as $index => $item) {
            $pages = explode(';', $item['Pages']);
            $path = array();
            foreach ($pages as $key => $page) {
                $path[$key] = explode('/', $page);
                //clear path
                while (empty($path[$key][0]) || $path[$key][0] == 'navigator' || $path[$key][0] == 'test' || $path[$key][0] == 'university'){
                    array_shift($path[$key]);
                }

                if (in_array($path[$key][0], $lists)){
                    unset($path[$key]);
                }

                if (isset($path[$key])){
                    $path[$key] = $path[$key][0];
                }
            }

            //visits count
            $university = array();
            foreach ($path as $key => $pathItem) {
                if (!isset($university[$pathItem])){
                    $university[$pathItem] = 0;
                }

                $university[$pathItem]++;
            }
            asort($university, 'DESC');

            //5 max
            $i = 0;
            foreach ($university as $name => $count) {
                $users[$index]['University'][] = $name;
                if ($i > 4){
                    break;
                }
                $i++;
            }

            //result
            foreach ($users[$index]['University'] as $key => $univer) {
                if (!isset($result[$univer])){
                    $result[$univer] = 0;
                }
                $result[$univer]++;
            }
        }

        print_r($result);
    }*/

	/*protected function saveToCSV($rows){
		ob_start();

		$f = fopen('php://output', "w");
	
		foreach($rows as $row){
			fputcsv($f, $row, ";");
		}

		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition: attachment;filename="tracking.csv"');
		header("Content-Transfer-Encoding: binary");
	
		echo(ob_get_clean());
		exit();
	}*/

}