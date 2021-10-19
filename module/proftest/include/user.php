<?php

class ProftestUser extends LocalObject 
{	
	private $module;
	public $list;

	public function __construct($module = 'proftest')
	{
		parent::LocalObject();
		$this->module = $module;
		$this->list = new LocalObjectList();
	}
	
	public function load($proftestID, $userID)
	{
	    $query = "SELECT pu.ProftestUserID, pu.LinkID
            FROM `proftest_user` pu
            WHERE pu.ProftestID=".intval($proftestID)." AND pu.UserID=".intval($userID)." AND pu.Status='active'";
	    $this->LoadFromSQL($query);
	    
	    //create proftest user if not init yet
	    if(!$this->IsPropertySet("ProftestUserID")){
	        $this->initProftestUser($proftestID, $userID);
	    }
	}
	
	public function initProftestUser($proftestID, $userID)
	{
	    $stmt = GetStatement();
	    $query = "INSERT INTO `proftest_user` SET
            ProftestID=".intval($proftestID).",
            UserID=".intval($userID).",
            Created=".Connection::GetSQLString(GetCurrentDateTime());

		$session =& GetSession();
		if($session->GetProperty('utm_source'))
		{
			$query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
    			utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
    			utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
    			utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
    			utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
		}

	    if($stmt->Execute($query)) {
			$this->load($proftestID, $userID);
			$this->SetProperty('FirstInit',1);
			return true;
	    }
	    return false;
	}

	public function saveLinkID(){
		$stmt = GetStatement();
		if (!$this->IsPropertySet('LinkID')){
			$linkID = md5(uniqid());

			$query = "UPDATE `proftest_user` pu SET pu.LinkID = " . Connection::GetSQLString($linkID) . "
            WHERE pu.ProftestUserID = ".$this->GetIntProperty('ProftestUserID');

			$stmt->Execute($query);
			$this->SetProperty('LinkID', $linkID);
		}
	}
	
	public function reset()
	{
	    $stmt = GetStatement();
	    $query = "UPDATE `proftest_user` pu SET
            pu.Status='reset'
            WHERE pu.ProftestUserID=".$this->GetIntProperty('ProftestUserID');
	    $stmt->Execute($query);
	}

//Static
	public static function getResult(array $linkIDs, $list = false){
        $stmt = GetStatement();
        $query = "SELECT ans2u.ProftestUserID, cat.*, user_i.UserID, user_i.UserName, user_i.UserEmail, user_i.UserPhone,
				  SUM(ans.Points) AS Points,
				  SUM(max_task_point.MaxPoints) AS MaxPoints,
				  (SUM(ans.Points) * (100/SUM(max_task_point.MaxPoints))) AS Percent
				  FROM proftest_answer2user AS ans2u
                  LEFT JOIN proftest_answer AS ans ON ans2u.AnswerID = ans.AnswerID
                  LEFT JOIN  proftest_task2category AS task2cat ON ans.TaskID = task2cat.TaskID
                  INNER JOIN proftest_category AS cat ON task2cat.CategoryID = cat.CategoryID
                  LEFT JOIN (
                              SELECT pt.TaskID, MAX(ans.Points) AS MaxPoints
                  				FROM proftest_task AS pt
                              	LEFT JOIN proftest_answer AS ans ON pt.TaskID = ans.TaskID
                                GROUP BY pt.TaskID
                              ) AS max_task_point ON ans.TaskID= max_task_point.TaskID
				  LEFT JOIN (
				  				SELECT UserID, ProftestUserID, ProftestID FROM proftest_user WHERE LinkID IN (" . "'" .  implode("', '", $linkIDs) . "'" . ")
							) AS p_user ON ans2u.ProftestUserID = p_user.ProftestUserID
			  	  LEFT JOIN users_item AS user_i ON p_user.UserID = user_i.UserID
                  WHERE ans2u.ProftestUserID = p_user.ProftestUserID
                  GROUP BY p_user.ProftestUserID, task2cat.CategoryID
                  ORDER BY p_user.UserID, Percent DESC";

        //echo $query;
		if (!$userResult = $stmt->FetchList($query)){
			return false;
		}

		if ($list == true){
			//Group
			foreach ($userResult as $key => $item) {
				$groupResult[$item['ProftestUserID']][] = $item;
			}

			//List
			foreach ($groupResult as $key => $item) {
				$result[] = self::createUserCategoryList($item);
			}
		}
		else{
			$result = array(self::createUserCategoryList($userResult));
		}

		return $result;
	}

	/**
	 * FOR self::getResult
	 * @param $userResult
	 * @return mixed
	 */
	public static function createUserCategoryList($userResult){
		$userInfo = false;
		foreach ($userResult as $key => $userCat) {
			if ($userCat['Percent'] > 0){
				$result['CategoryList'][$key]['Title'] = $userCat['Title'];
				$result['CategoryList'][$key]['Percent'] = round($userCat['Percent']);
				$result['CategoryList'][$key]['Profession'] = $userCat['Profession'];
				$result['CategoryList'][$key]['Subjects'] = $userCat['Subjects'];
			}

			if ($userInfo == false){
				$result['UserName'] = $userCat['UserName'];
				$result['UserEmail'] = $userCat['UserEmail'];
				$result['UserPhone'] = $userCat['UserPhone'];
				$userInfo = true;
			}
		}
		return $result;
	}

	public static function getDateRange($proftestID = null){
		$stmt = GetStatement();
		$query = "SELECT MIN(Created) AS Min, MAX(Created) AS Max FROM proftest_user WHERE LinkID IS NOT NULL";
		if ($proftestID !== null){
			$query .= " AND ProftestID = " . intval($proftestID);
		}

		return $stmt->FetchRow($query);
	}

//List
	public function LoadList(LocalObject $request){
		$where = array('p_u.LinkID IS NOT NULL');
		$join = array('LEFT JOIN users_item AS u_i ON p_u.UserID = u_i.UserID');
		$orderBy = 'p_u.Created ASC';

		if ($request->IsPropertySet('ProftestID')){
			$where[] = "p_u.ProftestID = " . $request->GetIntProperty('ProftestID');
		}

		if ($request->IsPropertySet('OnlyLast')){
			$join[] = "LEFT JOIN (
						SELECT UserID, MAX(Created) AS Created FROM proftest_user
						WHERE LinkID IS NOT NULL AND ProftestID = " . $request->GetIntProperty('ProftestID') . " GROUP BY UserID
					) AS max_p_u ON p_u.UserID = max_p_u.UserID";
			$where[] = "p_u.Created = max_p_u.Created";
		}

		if ($request->IsPropertySet('IDs')){
			$IDs = $request->GetProperty('IDs');
			if (!is_array($IDs)){
				return false;
			}
			$where[] = "p_u.UserID IN (" . implode(', ', $IDs) . ")";
		}

		if ($request->IsPropertySet('groupBy')){
			switch ($request->GetProperty('groupBy')){
				case 'UserID':
					$group = 'p_u.UserID';
					break;
			}
		}

		//UserFilter
		if (!empty($request->GetProperty('UserName'))){
			$where[] = "u_i.UserName LIKE " . Connection::GetSQLString('%' . $request->GetProperty('UserName') . '%');
		}

		if (!empty($request->GetProperty('UserEmail'))){
			$where[] = "u_i.UserEmail LIKE " . Connection::GetSQLString('%' . $request->GetProperty('UserEmail') . '%');
		}

		if (!empty($request->GetProperty('UserPhone'))){
			$where[] = "u_i.UserPhone LIKE " . Connection::GetSQLString('%' . $request->GetProperty('UserPhone') . '%');
		}

		if (!empty($request->GetProperty('City'))){
			$where[] = "u_i.City LIKE " . Connection::GetSQLString('%' . $request->GetProperty('City') . '%');
		}

		if (!empty($request->GetProperty('ClassNumber'))){
			$where[] = "u_i.ClassNumber = " . $request->GetPropertyForSQL('ClassNumber');
		}

		if (!empty($request->GetProperty('UserWho'))){
			$where[] = "u_i.UserWho = " . $request->GetPropertyForSQL('UserWho');
		}


		if ($request->IsPropertySet('DateFrom') && $request->IsPropertySet('DateTo')){
			$dateFrom = $request->GetProperty('DateFrom');
			$dateTo = $request->GetProperty('DateTo');
			$where[] = "p_u.Created > " . Connection::GetSQLDateTime($dateFrom);
			$where[] = "p_u.Created < " . Connection::GetSQLDateTime(date('d.m.Y', strtotime($dateTo) + 24 * 3600));
		}

		$query = "SELECT *,
				CONCAT('source=', p_u.utm_source, CONCAT_WS('',', medium=', p_u.utm_medium, ', campaign=', p_u.utm_campaign, ', term=', p_u.utm_term, ', content=', p_u.utm_content)) as UTM
				FROM proftest_user AS p_u
				" .(!empty($join) ? implode(' ', $join) : '') . "
        		" . ((count($where) > 0)?"WHERE ".implode(" AND ", $where):"") .
				(isset($group) ? " GROUP BY " . $group : '') . "
        		ORDER BY " . $orderBy;

		if ($request->GetIntProperty('OnPage') > 0){
			$this->list->SetItemsOnPage($request->GetIntProperty('OnPage'));
			$this->list->SetCurrentPage();
		}
		$this->list->LoadFromSQL($query);
	}

	//Service
	public function getLinkIDsFromList(){
		foreach ($this->list->_items as $key => $item) {
			$result[] = $item['LinkID'];
		}
		if (!empty($result)){
			return $result;
		}

		return false;
	}

	public function exportListToCSV($pageStaticPath){
		ob_start();
		$f = fopen("php://output", "w");

		$row = array("Имя","E-mail","Телефон","Город","Класс","Статус","Ссылка на результат");
		fputcsv($f, $row, ";");

		foreach ($this->list->_items as $index => $item) {
			if (empty($item['ShortLink'])){
				$longUrl = $pageStaticPath . "?TestResult=" . $item["LinkID"];
				//$postData = array('longUrl' => $longUrl);
                $url = GetShortURL($longUrl);
				$this->saveShortLink($item['ProftestUserID'], $url);
			}
			else{
				$url = $item['ShortLink'];
			}

			$row = array(
				$item["UserName"],
				$item["UserEmail"],
				preg_replace("/[^0-9]/", '', $item["UserPhone"]),
				$item["City"],
				$item["ClassNumber"],
				$item["UserWho"],
				$url

			);
			fputcsv($f, $row, ";");
		}

		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition: attachment;filename="proftest_users.csv"');
		header("Content-Transfer-Encoding: binary");

		echo(ob_get_clean());
		exit();
	}

	public function saveShortLink($proftestUserID, $url){
		$stmt = GetStatement();
		$query = "UPDATE `proftest_user` pu SET pu.ShortLink = " . Connection::GetSQLString($url) . "
		WHERE pu.ProftestUserID = " . intval($proftestUserID);
		$stmt->Execute($query);
	}
}
