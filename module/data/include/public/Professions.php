<?php
setlocale(LC_ALL, 'ru_RU.UTF-8');
require_once(dirname(__FILE__) . '/../common/ProfessionCommon.php');

class Professions extends LocalObjectList
{
	use ProfessionCommon;

    private $module;
    const FILTER_PARAMS = array('Industry', 'WhoWork', 'WantWork', 'WageLevel', 'Schedule', 'Operation');

    public function __construct($module = 'data')
    {
        $this->module = $module;
    }
    
    public function count()
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT count(*) FROM data_profession p");
    }

    public function load(LocalObject $request, $itemsOnPage = 30, $replacePager = null)
    {
    	$where = array();
    	$join = array();

		if($request->IsPropertySet('ProfessionFilter')){
			$filter = $request->GetProperty('ProfessionFilter');
			self::prepareFilter($filter, $where);
		}
    	
    	$orderBy = "p.Title ASC";
    	if($request->GetProperty('SortOrder') == 'cost'){
    	    $orderBy = "MidWage ASC";
    	}
    	
    	if($request->IsPropertySet('specialityID'))
    	{
    		$join['direction'] = "LEFT JOIN data_profession2direction p2d ON p.ProfessionID=p2d.ProfessionID";
    		$join['speciality'] = "LEFT JOIN data_speciality s ON p2d.DirectionID=s.DirectionID";
    		$where[] = "s.SpecialityID=".$request->GetIntProperty('specialityID');
    	}
    	
    	if($request->IsPropertySet('TextSearch') && strlen($request->GetProperty('TextSearch')) > 0)
    	{
    	    $likeStr = Connection::GetSQLString('%'.$request->GetProperty('TextSearch').'%');
    	    $where[] = "(p.Title LIKE ".$likeStr." OR p.Synonyms LIKE ".$likeStr.")";
    	}

		if($request->IsPropertySet('ProfessionLike')){
			$join[] = "LEFT JOIN data_profession2profession p2p ON p.ProfessionID=p2p.ItemID";
			$where[] = "p2p.ProfessionID=".$request->GetIntProperty('ProfessionLike');
		}

		if ($request->IsPropertySet('CityIDs')){
		    //TODO delete duplicate
		    if (!isset($join['direction']) || !isset($join['speciality'])){
                $join['direction'] = "LEFT JOIN data_profession2direction p2d ON p.ProfessionID=p2d.ProfessionID";
                $join['speciality'] = "LEFT JOIN data_speciality s ON p2d.DirectionID=s.DirectionID";
            }

		    $join[] = "LEFT JOIN data_university AS univer ON s.UniversityID = univer.UniversityID";
            $cityIDs = implode(", ", Connection::GetSQLArray($request->GetProperty('CityIDs')));
            $where[] = "univer.CityID IN ({$cityIDs})";
        }
    	
        $query = "SELECT p.ProfessionID, p.Title, p.WageLevel, p.ProWageLevel, ind.IndustryTitle,
        		CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', p.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS ProfessionURL,
                CONCAT((p.WageLevel + IF(p.ProWageLevel>1, p.ProWageLevel, p.WageLevel + p.WageLevel)) / 2) AS MidWage
        		FROM data_profession p
                LEFT JOIN `data_profession_industry` AS ind ON p.Industry=ind.IndustryID
        		".(!empty($join) ? implode(' ', $join) : '')."
        		".((count($where) > 0)?"WHERE ".implode(" AND ", $where):"")."
        		GROUP BY p.ProfessionID
        		ORDER BY ".$orderBy;
        
        $this->SetPageParam('ProfessionPager');
        if($replacePager){
            $_REQUEST['ProfessionPager'] = $request->GetIntProperty($replacePager);
        }
        $this->SetItemsOnPage($itemsOnPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }

	public function getIDByStaticPath($staticPath)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT p.ProfessionID FROM `data_profession` p WHERE p.StaticPath=".Connection::GetSQLString($staticPath));
    }
    
    public function getStaticPathByID($professionID)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT p.StaticPath FROM `data_profession` p WHERE p.ProfessionID=".intval($professionID));
    }
    public function getTitleByID($professionID){
		$stmt = GetStatement();
		return $stmt->FetchField("SELECT Title FROM data_profession WHERE ProfessionID=" . intval($professionID));
	}
    
    public function loadForUser(LocalObject $request)
    {
    	$query = "SELECT p.ProfessionID, p.Title, p2i.IndustryTitle AS Industry, p.WageLevel,
        	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', p.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS ProfessionURL
        	FROM data_profession p
    		LEFT JOIN data_profession_industry AS p2i ON p.Industry = p2i.IndustryID
    		LEFT JOIN `data_profession2user` AS p2u ON p2u.ProfessionID = p.ProfessionID
    		WHERE p2u.UserID=".$request->GetIntProperty("UserID")."
    		ORDER BY p.Title ASC";
    	
    	$this->SetItemsOnPage(0);
    	$this->LoadFromSQL($query);
    }
    
    public function selectForUser($professionID, $userID, $state)
    {
    	$stmt = GetStatement();
    	if ($state) {
    		$query = "INSERT INTO `data_profession2user`
    			SET ProfessionID=".intval($professionID).", UserID=".intval($userID).", Created=".Connection::GetSQLString(GetCurrentDateTime());
    		
    		$session =& GetSession();
    		if($session->GetProperty('utm_source'))
    		{
    			$query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
    			utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
    			utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
    			utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
    			utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
    		}
    	} else {
    		$query = "DELETE FROM `data_profession2user`
    			WHERE ProfessionID=".intval($professionID)." AND UserID=".intval($userID);
    	}
    	$stmt->Execute($query);
    }
    
    public function getItems($selected = array())
    {
    	$result = array();
    	foreach ($this->_items as $item) {
    		$item['Selected'] = in_array($item['ProfessionID'], $selected) ? 1 : 0;
    		$result[] = $item;
    	}
    
    	return $result;
    }

	public function LoadItemsInFields($fields){
    	if ( empty($fields) ){
    		return false;
		}
		$stmt = GetStatement();
		$join = array();
		$where = array();

		if ( $fields->IsPropertySet('Industry') ){
			$join[] = "LEFT JOIN data_profession_industry ind ON p.Industry = ind.IndustryID";
			$where[] = "p.Industry IN (" . implode(',', Connection::GetSQLArray($fields->GetProperty('Industry'))) . ")";
		}
		if ( $fields->IsPropertySet('WhoWork') ){
			$join[] = "LEFT JOIN data_profession2who p2who ON p.ProfessionID = p2who.ProfessionID";
			$where[] = "p2who.WhoWorkID IN (" . implode(',', Connection::GetSQLArray($fields->GetProperty('WhoWork'))) . ")";
		}
		if ( $fields->IsPropertySet('WantWork') ){
			$join[] = "LEFT JOIN data_profession2want p2want ON p.ProfessionID = p2want.ProfessionID";
			$where[] = "p2want.WantWorkID IN (" . implode(',', Connection::GetSQLArray($fields->GetProperty('WantWork'))) . ")";
		}

		$query = "SELECT DISTINCT p.ProfessionID, p.Title, p.Industry,
			CONCAT(ind.IndustryTitle) as IndustryTitle
			FROM data_profession as p " .
			(!empty($join) ? implode(' ', $join) : '') .
			((count($where) > 0) ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
		return true;
	}

    //Filters
    public function getWageLevel($selected = array()){
        $stmt = GetStatement();
        $query = "SELECT min(WageLevel) FROM data_profession";
        if ( $min_wage = $stmt->FetchField($query) ) {
            for ($i=0; $i <= 3; $i++) {
                if (!$i == 0){
                    $min_wage += 5000;
                }
                $wage_level[$i]['WageLevel'] = $min_wage;
                $wage_level[$i]['Selected'] = ($wage_level[$i]['WageLevel'] == $selected) ? 1 : 0;
            }
            return $wage_level;
        }
    }

    public function getScheduleList($selected = array()){
        $stmt = GetStatement();
        $query = "SELECT DISTINCT Schedule FROM data_profession WHERE Schedule IS NOT NULL";
        if ( $result = $stmt->FetchList($query) ) {
            foreach ($result as $key => $value) {
                if($value['Selected'] = in_array($value['Schedule'], $selected) ? 1 : 0){
                    $result[$key] = $value;
                }
            }
            return $result;
        }
    }
}
