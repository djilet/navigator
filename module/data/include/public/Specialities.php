<?php
/**
 * Date:    02.11.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */
require_once(dirname(__FILE__) . "/../speciality_study_list.php");

class Specialities extends LocalObjectList
{
    private $module;
    protected $study;

    /**
     * Specialities constructor.
     *
     * @param $module
     */
    public function __construct($module)
    {
    	parent::LocalObjectList();
        $this->module = $module;
        $this->study = new SpecialityStudy($module);
    }
    
    public function count(int $cityId = null)
    {
        $query = QueryBuilder::init()->addSelect("COUNT(spec.SpecialityID)")
            ->from('data_speciality AS spec');
        if ($cityId){
            $query->addJoin('LEFT JOIN data_university AS univer ON spec.UniversityID = univer.UniversityID');
            $query->addWhere("univer.CityID = {$cityId}");
        }

        return GetStatement()->FetchField($query->getSQL());
    }

    public function load(LocalObject $request, $onPage = 40)
    {
        $where = array();
        $join = array();
        $orderby = "sp.SortOrder DESC,sp.Title ASC";

        if ($request->IsPropertySet('SpecialFilter')) {
            $filter = $request->GetProperty('SpecialFilter');

            if (isset($filter['Region']) and !empty($filter['Region'])) {
                if (is_array($filter['Region'])) {
                    $where[] = "u.RegionID IN (" . implode(", ", $filter['Region']) . ")";
                } else {
                    $where[] = "u.RegionID=" . intval($filter['Region']);
                }
            }

            if (isset($filter['CityIDs'])){
                $cityIDs = implode(", ", Connection::GetSQLArray($filter['CityIDs']));
                $where[] = "u.CityID IN ({$cityIDs})";
            }

            if (isset($filter['BigDirection']) and !empty($filter['BigDirection'])) {
            	if (is_array($filter['BigDirection'])) {
            		$where[] = ' d.BigDirectionID IN ('.implode(',', Connection::GetSQLArray($filter['BigDirection'])).')';
            	} else {
            		$where[] = ' d.BigDirectionID=' . intval($filter['BigDirection']);
            	}
            }
            
            if (isset($filter['Direction']) and !empty($filter['Direction'])) {
                if (is_array($filter['Direction'])) {
                    $where[] = "sp.DirectionID IN (" . implode(", ", $filter['Direction']) . ")";
                } else {
                    $where[] = "sp.DirectionID=" . intval($filter['Direction']);
                }
            }

            if (isset($filter['Military']) and $filter['Military'] == 1) {
                $where[] = "sp.Military='Да'";
            }

            if (isset($filter['Delay']) and $filter['Delay'] == 1) {
                $where[] = "sp.Delay='Да'";
            }

            if (isset($filter['Hostel']) and $filter['Hostel'] == 1) {
                $where[] = "sp.Hostel='Да'";
            }

            if (isset($filter['Subject'])) {
                $i = 1;
                foreach ($filter['Subject'] as $subject => $score) {
                    if (empty($subject) or empty($score)) {
                        continue;
                    }
                    $join[] = 'INNER JOIN data_ege e'.$i.' ON sp.SpecialityID=e'.$i.'.SpecialityID
                        AND e'.$i.'.SubjectID='.intval($subject).' AND e'.$i.'.Score<='.intval($score);
                    ++$i;
                }
            }
            
            if (isset($filter['Text']) and strlen($filter['Text']) > 0) {
            	$where[] = ' (u.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		u.ShortTitle LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		d.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		sp.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR
            		r.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		t.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR
            		d.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').')';
            }
        }

        $universityID = $request->GetProperty('universityID');
        if ($universityID) {
            if (is_array($universityID)) {
                $where[] = "sp.UniversityID IN (" . implode(", ", $universityID) . ")";
            } else {
                $where[] = "sp.UniversityID=" . intval($universityID);
            }
        }
        
        if($request->IsPropertySet('ProfessionID'))
        {
        	$join[] = "LEFT JOIN data_profession2direction p2d ON d.DirectionID=p2d.DirectionID";
        	$where[] = "p2d.ProfessionID=".$request->GetIntProperty('ProfessionID');
        	if($request->IsPropertySet('SpecSortOrder'))
        	{
        		if($request->GetProperty('SpecSortOrder') == 'score')
        		{
        			$orderby = "BudgetNext, Budget, BudgetLast DESC";
        		}
        	}
        }

        //exclude dublicates
        $where[] = "RIGHT(sp.StaticPath, 2) <> '-1'";
        
        $query = "SELECT sp.SpecialityID, sp.Title, u.UniversityID, u.ShortTitle, u.Title AS UniversityTitle,
                count(e.EgeID) AS SubjectsCount,
                GROUP_CONCAT(e.SubjectID) AS Subjects, GROUP_CONCAT(e.Score) AS SubjectsScore,
                sp.Additional1, sp.Additional2, sp.Additional3, sp.Additional4, sp.Additional5, sp.Additional6, sp.Additional7, sp.Additional8, sp.Additional9, sp.Additional10,
                d.Title AS DirectionTitle,
                sp.Students,
                city.StaticPath AS CityPath,
                CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, '/', sp.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL,
                CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, '/') AS UniversityURL,
                
                IF(stud_n.BudgetScopeWave1 > 0, stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2 ) AS BudgetNext,
                IF(stud_c.BudgetScopeWave1 > 0, stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2 ) AS Budget,
                IF(stud_l.BudgetScopeWave1 > 0, stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2 ) AS BudgetLast,
                
                IF(stud_n.BudgetScopeWave1 > 0 AND stud_n.BudgetScopeWave2 > 0, 
                   LEAST(stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2), 
                   GREATEST(stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2)) AS BudgetMinNext,
                IF(stud_c.BudgetScopeWave1 > 0 AND stud_c.BudgetScopeWave2 > 0, 
                   LEAST(stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2), 
                   GREATEST(stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2)) AS BudgetMin,
                IF(stud_l.BudgetScopeWave1 > 0 AND stud_l.BudgetScopeWave2 > 0, 
                   LEAST(stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2), 
                   GREATEST(stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2)) AS BudgetMinLast,
                
                stud_n.BudgetCount AS BudgetCountNext,
                stud_c.BudgetCount AS BudgetCount,
                stud_l.BudgetCount AS BudgetCountLast,
                
                stud_n.PaidPrice AS PaidPriceNext,
                stud_c.PaidPrice AS PaidPrice,
                stud_l.PaidPrice AS PaidPriceLast,
                
                stud_n.Period AS PeriodNext,
                stud_c.Period AS Period,
                stud_l.Period AS PeriodLast
                
            FROM `data_speciality` AS sp
            INNER JOIN data_university u ON sp.UniversityID=u.UniversityID
            LEFT JOIN data_direction d ON d.DirectionID=sp.DirectionID
            LEFT JOIN data_ege e ON sp.SpecialityID=e.SpecialityID
            LEFT JOIN `data_region` AS r ON r.RegionID=u.RegionID 
            LEFT JOIN `data_type` AS t ON t.TypeID=u.TypeID
            LEFT JOIN `data_city` AS city ON u.CityID=city.ID
            
            LEFT JOIN (SELECT SpecialityID, MAX(Year) AS Year FROM data_speciality_study WHERE Type = 'Full' GROUP BY SpecialityID) AS sp_year ON sp.SpecialityID = sp_year.SpecialityID
			LEFT JOIN data_speciality_study AS stud_n ON sp.SpecialityID = stud_n.SpecialityID AND sp_year.Year = stud_n.Year AND stud_n.Type = 'Full'
			LEFT JOIN data_speciality_study AS stud_c ON sp.SpecialityID = stud_c.SpecialityID AND sp_year.Year - 1 = stud_c.Year AND stud_c.Type = 'Full'
			LEFT JOIN data_speciality_study AS stud_l ON sp.SpecialityID = stud_l.SpecialityID AND sp_year.Year - 2 = stud_l.Year AND stud_l.Type = 'Full'
            ".(!empty($join) ? implode(' ', $join) : '')."
            " . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "") . "
            GROUP BY sp.SpecialityID
            ORDER BY ".$orderby;
        $this->SetPageParam('SpecPager');
        $this->SetItemsOnPage(intval($onPage));
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }

    public function getByID($specialityID, $baseURL="", $studyYear = null)
    {
        $specialityID = intval($specialityID);
        $stmt = GetStatement();
        $query = "SELECT sp.*, d.Title AS DirectionTitle, u2u.SpecialityID AS IsEnrollee, d.VideoURL, 
        	CONCAT(".Connection::GetSQLString($baseURL).", '/', u.StaticPath, '/', sp.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL, sp.Address as SpecialityAddress, sp.Latitude as SpecialityLatitude, sp.Longitude as SpecialityLongitude  
            FROM `data_speciality` AS sp
            INNER JOIN data_university u ON sp.UniversityID=u.UniversityID
            LEFT JOIN `data_direction` AS d ON sp.DirectionID=d.DirectionID
            LEFT JOIN `data_user_university` AS u2u ON u2u.SpecialityID=sp.SpecialityID 
            WHERE sp.SpecialityID=" . $specialityID;
        if ($row = $stmt->FetchRow($query)) {

            $subjectsList = $stmt->FetchList("SELECT s.Title, e.Score, e.isProfile, e.byChoice FROM data_ege e
                LEFT JOIN data_subject s ON e.SubjectID=s.SubjectID
                WHERE e.SpecialityID=" . intval($row['SpecialityID']));

            foreach ($subjectsList as $subject) {
                if ($subject["byChoice"] != "Y") {
                    $row["SubjectsList"][] = $subject;
                } else {
                    $row["SubjectsChoiceList"][] = $subject;
                }
            }

            $row["SubjectsCountToChooseStr"] = $row["SubjectsCountToChoose"];
            $row["SubjectsCountToChooseStr"] .= $row["SubjectsCountToChoose"] == 1 ? " предмет" : " предмета";

            $row["AchievementList"] = $this->getAchievementsByID($specialityID);

            $additionalList = array();
            for ($i = 1; $i <= 10; $i++) {
                if (!empty($row["Additional" . $i])) {
                    $additionalList[] = array("Title" => $row["Additional" . $i]);
                }
                unset($row["Additional" . $i]);
            }
            $row["AdditionalList"] = $additionalList;

			if($this->study->loadBySpecialityID($specialityID)){
				$row = array_merge($row, $this->study->getItemNamesByYear($studyYear));
				$row['StudyList'] = 1;
				$row['StudyYearList'] = $this->study->getItemsYearList(array($row['StudyYear']));

				//TODO init all blocks
				if (!empty($row['FullBudgetCompetition']) or !empty($row['FullBudgetScopeWave1'])
					or !empty($row['FullBudgetScopeWave2']) or !empty($row['FullBudgetCount'])
				) {
					$row['BudgetBlock'] = 1;
				}

				if (!empty($row['FullPaidCompetition']) or !empty($row['FullPaidScope']) or !empty($row['FullPaidCount'])) {
					$row['ContractBlock'] = 1;
				}

				if (!empty($row['FullPeriod']) or !empty($row['FullPaidPrice'])) {
					$row['PeriodPriceBlock'] = 1;
				}
			}

			if (!empty($row['VideoURL'])){
			    $row['VideoID'] = GetVideoIdFromYouTube($row['VideoURL']);
            }

            return $row;
        }

        return array();
    }
    
    public function loadForUser(LocalObject $request)
    {
    	$query = "SELECT sp.SpecialityID, sp.Title, u.UniversityID, u.Title AS UniversityTitle, d.Title AS DirectionTitle,
	    	sp.Students, sp.Score2016 as Score,
	    	
	    	IF(stud_n.BudgetScopeWave1 > 0, stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2 ) AS BudgetNext,
			IF(stud_c.BudgetScopeWave1 > 0, stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2 ) AS Budget,
			IF(stud_l.BudgetScopeWave1 > 0, stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2 ) AS BudgetLast,
			
			stud_n.BudgetCount AS BudgetCountNext,
			stud_c.BudgetCount AS BudgetCount,
			stud_l.BudgetCount AS BudgetCountLast,
			
			stud_n.PaidPrice AS PaidPriceNext,
			stud_c.PaidPrice AS PaidPrice,
			stud_l.PaidPrice AS PaidPriceLast,
			
			stud_n.Period AS PeriodNext,
			stud_c.Period AS Period,
			stud_l.Period AS PeriodLast,
			
			stud_n.BudgetCount AS BudgetCountNext,
			stud_c.BudgetCount AS BudgetCount,
			stud_l.BudgetCount AS BudgetCountLast
			
	    	FROM `data_speciality` AS sp
	    	INNER JOIN data_university u ON sp.UniversityID=u.UniversityID
	    	LEFT JOIN data_direction d ON d.DirectionID=sp.DirectionID
	    	LEFT JOIN `data_user_university` AS uu ON uu.SpecialityID=sp.SpecialityID
	    	
	    	LEFT JOIN (SELECT SpecialityID, MAX(Year) AS Year FROM data_speciality_study WHERE Type = 'Full' GROUP BY SpecialityID) AS sp_year ON sp.SpecialityID = sp_year.SpecialityID
			LEFT JOIN data_speciality_study AS stud_n ON sp.SpecialityID = stud_n.SpecialityID AND sp_year.Year = stud_n.Year AND stud_n.Type = 'Full'
			LEFT JOIN data_speciality_study AS stud_c ON sp.SpecialityID = stud_c.SpecialityID AND sp_year.Year - 1 = stud_c.Year AND stud_c.Type = 'Full'
			LEFT JOIN data_speciality_study AS stud_l ON sp.SpecialityID = stud_l.SpecialityID AND sp_year.Year - 2 = stud_l.Year AND stud_l.Type = 'Full'
			
    		WHERE uu.UserID=".$request->GetIntProperty("UserID")."
	    	GROUP BY sp.SpecialityID
	    	ORDER BY sp.Title ASC";
    	
    	$this->SetItemsOnPage(0);
    	$this->LoadFromSQL($query);
    }
    
    public function loadRandom(LocalObject $request)
    {
    	$query = "SELECT sp.SpecialityID, sp.Title,
    		CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, '/', sp.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL
    		FROM `data_speciality` sp
    		INNER JOIN data_university u ON sp.UniversityID=u.UniversityID
    		WHERE RIGHT(sp.StaticPath, 2) <> '-1'
    		ORDER BY RAND() LIMIT 3";
    	$this->LoadFromSQL($query);
    }

    public function loadByUniversityID(LocalObject $request, $universityID, $specialityID)
    {
        $query = "SELECT sp.SpecialityID, sp.Title,
        	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, '/', sp.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL
        	FROM `data_speciality` sp
        	INNER JOIN data_university u ON sp.UniversityID=u.UniversityID
            WHERE sp.UniversityID=".intval($universityID)." AND sp.SpecialityID !=".intval($specialityID)."  AND RIGHT(sp.StaticPath, 2) <> '-1'
            ORDER BY RAND() LIMIT 3";
        $this->LoadFromSQL($query);
    }
    
    public function getIDByStaticPath($universityPath, $specialityPath)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchRow("SELECT sp.UniversityID, sp.SpecialityID 
    		FROM `data_speciality` sp
    		INNER JOIN data_university u ON sp.UniversityID=u.UniversityID 
    		WHERE u.StaticPath=".Connection::GetSQLString($universityPath)." AND sp.StaticPath=".Connection::GetSQLString($specialityPath));
    }
    
    public function getStaticPathByID($specialityID)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT CONCAT(u.StaticPath, '/', sp.StaticPath) 
    		FROM `data_speciality` sp
    		INNER JOIN data_university u ON sp.UniversityID=u.UniversityID 
    		WHERE sp.SpecialityID=".intval($specialityID));
    }

    public function getAchievementsByID($specialityID){
		$stmt = GetStatement();
		$query = "SELECT ach.AchievementID, ach.Title, spec2ach.Score 
				  FROM data_speciality2achievement AS spec2ach
				  LEFT JOIN data_achievement AS ach ON spec2ach.AchievementID = ach.AchievementID
				  WHERE spec2ach.SpecialityID = " . intval($specialityID) . "
				  ORDER BY ach.SortOrder";

		return $stmt->FetchList($query);
	}

	public static function getForSiteMap($baseUrl, int $cityID = null){
        $stmt = GetStatement();
        $query = QueryBuilder::init()
            ->select([
                "DISTINCT CONCAT(" . Connection::GetSQLString($baseUrl) . ", '/', u.StaticPath, '/', sp.StaticPath, '/') AS URL",
            ])
            ->from('`data_speciality` AS sp')
            ->addJoin('LEFT JOIN data_university AS u ON sp.UniversityID = u.UniversityID');

        if ($cityID){
            $query->addWhere("u.CityID = {$cityID}");
        }

        if ($result = $stmt->FetchRows($query->getSQL())){
            return $result;
        }

        return null;
    }

    public function prepare()
    {
        foreach ($this->_items as $key => $item) {
            if ($item['CityPath']){
                $this->_items[$key]['SpecialityURL'] = URLParser::getPrefixWithSubDomain($item['CityPath']) . $item['SpecialityURL'];
                $this->_items[$key]['UniversityURL'] = URLParser::getPrefixWithSubDomain($item['CityPath']) . $item['UniversityURL'];
            }
        }
    }

    /**
     * @deprecated use only for profession speciality list
     * @todo find other method check count of place
     */
    public function prepareBudget(){
        $fields = ['BudgetCountNext', 'BudgetCount' ,'BudgetCountLast'];
        foreach ($this->_items as $index => $item) {
            foreach ($fields as $key => $field) {
                if (intval($item[$field]) < 1){
                    $this->_items[$index][$field . 'Text'] = $item[$field];
                }
            }
        }
    }
}