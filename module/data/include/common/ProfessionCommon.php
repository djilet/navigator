<?php
/**
 * Trait ProfessionCommon
 *
 * Common methods for include/profession.php, include/public/ProfessionCommon.php
 */
trait ProfessionCommon{

	public function getItemInfo($id, $baseURL="", $userInfo = true, $concat_names = true){
		$stmt = GetStatement();

		$select = 'SELECT p.*, ind.IndustryTitle,';
		$join = "LEFT JOIN `data_profession_industry` AS ind ON p.Industry=ind.IndustryID";

		if ($userInfo === true){
            require_once(dirname(__FILE__)."/../../../users/include/user.php");
			$user = new UserItem('user');
			$user->loadBySession();

			if ($user->IsPropertySet('UserID')) {
				$select .= 'p2u.ProfessionID as ProfessionSelected,';
				$join .= " LEFT JOIN `data_profession2user` AS p2u ON p2u.ProfessionID=p.ProfessionID AND p2u.UserID=". $user->GetIntProperty('UserID');
			}
		}

		$query = $select . " 
  				CONCAT(".Connection::GetSQLString($baseURL).", '/', p.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS ProfessionURL,
                LOWER(p.Title) AS TitleLower
  				FROM data_profession p
               	" . $join . "
  				WHERE p.ProfessionID=".intval($id);
		$result = $stmt->FetchRow($query);
		
		if ($concat_names === true){
			//WhoWork
			$query = "SELECT who.WhoWorkTitle, who.WhoWorkID
				  FROM data_profession2who AS p2who
				  LEFT JOIN data_profession_who_work AS who ON p2who.WhoWorkID = who.WhoWorkID
				  WHERE p2who.ProfessionID = " . $id;
			$result['WhoWork'] = $stmt->FetchList($query);

			//WantWork
			$query = "SELECT want.WantWorkTitle, want.WantWorkID
				  FROM data_profession2want AS p2want
				  LEFT JOIN data_profession_want_work AS want ON p2want.WantWorkID = want.WantWorkID
				  WHERE p2want.ProfessionID = " . $id;
			$result['WantWork'] = $stmt->FetchList($query);

			//Operation
			$query = "SELECT oper.OperationTitle, oper.OperationID
				  FROM data_profession2operation AS p2oper
				  LEFT JOIN data_profession_operation AS oper ON p2oper.OperationID = oper.OperationID
				  WHERE p2oper.ProfessionID = " . $id;
			$result['Operation'] = $stmt->FetchList($query);

			//Subject
			$query = "SELECT subj.Title, subj.SubjectID
				  FROM data_profession2subject AS p2subj
				  LEFT JOIN data_subject AS subj ON p2subj.SubjectID = subj.SubjectID
				  WHERE p2subj.ProfessionID = " . $id;
			$result['Subject'] = $stmt->FetchList($query);
		}
		else{
			//WhoWork
			$query = "SELECT WhoWorkID
				  FROM data_profession2who
				  WHERE ProfessionID = " . $id;
			$result['WhoWork'] = $stmt->FetchRows($query);

			//WantWork
			$query = "SELECT WantWorkID
				  FROM data_profession2want
				  WHERE ProfessionID = " . $id;
			$result['WantWork'] = $stmt->FetchRows($query);

			//Operation
			$query = "SELECT OperationID
				  FROM data_profession2operation
				  WHERE ProfessionID = " . $id;
			$result['Operation'] = $stmt->FetchRows($query);
		}

		return $result;
	}

	//OtherProfessions
	public function getOtherProfessionList($id, $baseURL=""){
		$stmt = GetStatement();
		$query = "SELECT p.ProfessionID, p.Title, p.WageLevel, CONCAT(".Connection::GetSQLString($baseURL).", '/', p.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS ProfessionURL FROM data_profession2profession p2p LEFT JOIN data_profession p ON p2p.ItemID=p.ProfessionID WHERE p2p.ProfessionID=".intval($id);
		return $stmt->FetchList($query);
	}

	public function getOtherProfessions($profID){
		if (!$profID = intval($profID)) {
			return false;
		}

		$stmt = GetStatement();
		$query = "SELECT p.`ProfessionID`, p.`Title` FROM `data_profession` AS p
              INNER JOIN `data_profession2profession` AS p2p ON p.ProfessionID=p2p.ItemID
              WHERE p2p.ProfessionID=".$profID;
		return $stmt->FetchList($query);
	}

	public static function prepareFilter(array $filter, &$where){
		if (isset($filter['Industry']) and !empty($filter['Industry'])) {
			if (is_array($filter['Industry'])) {
				$where[] = ' ind.IndustryID IN ('.implode(',', Connection::GetSQLArray($filter['Industry'])).')';
			} else {
				$where[] = ' ind.IndustryID=' . intval($filter['Industry']);
			}
		}
		if (isset($filter['WhoWork']) and !empty($filter['WhoWork'])) {
			foreach ($filter['WhoWork'] as $key => $item) {
				$itemsID = explode(',', $item);
				$who_query[] = 'SELECT ProfessionID FROM `data_profession2who`
						  WHERE WhoWorkID IN(' . implode(',', Connection::GetSQLArray($itemsID)) . ')
						  GROUP BY (ProfessionID)
						  HAVING count(*) >= ' . count($itemsID);
			}

			$filter_count = count($filter['WhoWork']);
			if ( count($filter['WhoWork']) > 0) {
				$where[] = 'p.ProfessionID IN (' . implode(' UNION ', $who_query) . ')';
			}
		}
		if (isset($filter['WantWork']) and !empty($filter['WantWork'])) {
			foreach ($filter['WantWork'] as $key => $item) {
				$itemsID = explode(',', $item);
				$want_query[] = 'SELECT ProfessionID FROM `data_profession2want`
						  WHERE WantWorkID IN(' . implode(',', Connection::GetSQLArray($itemsID)) . ')
						  GROUP BY (ProfessionID)
						  HAVING count(*) >= ' . count($itemsID);
			}

			$filter_count = count($filter['WantWork']);
			if ( count($filter['WantWork']) > 0) {
				$where[] = 'p.ProfessionID IN (' . implode(' UNION ', $want_query) . ')';
			}
		}
		if (isset($filter['WageLevel']) and !empty($filter['WageLevel'])) {
			$where[] = 'p.WageLevel >= ' . intval($filter['WageLevel']);
		}
		if (isset($filter['Schedule']) and !empty($filter['Schedule'])) {
			if (is_array($filter['Schedule'])) {
				$where[] = 'p.Schedule IN ('.implode(',', Connection::GetSQLArray($filter['Schedule'])).')';
			} else {
				$where[] = 'p.Schedule=' . intval($filter['Schedule']);
			}
		}
		if (isset($filter['Operation']) and !empty($filter['Operation'])) {
			$where[] = ' p.ProfessionID IN (SELECT DISTINCT ProfessionID FROM data_profession2operation WHERE OperationID IN(' . implode(',', Connection::GetSQLArray($filter['Operation'])) . '))';
		}
	}
}