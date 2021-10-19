<?php
/**
 * Date:    02.11.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

require_once(dirname(__FILE__) . "/../university_image_list.php");

class University extends LocalObjectList
{
    private $module;
    private $params;
    private $imageList;

    const SORT_KEY_UNIVERSITY_CATEGORY = 'UNIVERSITY_CATEGORY';

    /**
     * University constructor.
     *
     * @param $module
     */
    public function __construct($module = 'data')
    {
    	parent::LocalObjectList();
        $this->module = $module;
        $this->params = LoadImageConfig('ItemImage', $module, UniversityImageList::ITEM_IMAGE_CONFIG);
        $this->imageList = new UniversityImageList();
    }

    public function count(int $cityId = null)
    {
        $query = QueryBuilder::init()->addSelect("COUNT(*)")
            ->from('`data_university` AS u');
        if ($cityId){
            $query->addWhere("u.CityId = {$cityId}");
        }

    	return GetStatement()->FetchField($query->getSQL());
    }

    public function load(LocalObject $request, $itemsOnPage = 30, $prepare = true)
    {
        $where = array();
        $join = array();
        $stmt = GetStatement();
        $beforeQuery = [];

        if ($request->IsPropertySet('UniverFilter')) {
            $countForMultiple = 3;

            $filter = $request->GetProperty('UniverFilter');

        	if (isset($filter['BigDirection']) and !empty($filter['BigDirection'])) {
            	if (is_array($filter['BigDirection'])) {
            		$where[] = ' d.BigDirectionID IN ('.implode(',', Connection::GetSQLArray($filter['BigDirection'])).')';
            	} else {
            		$where[] = ' d.BigDirectionID=' . intval($filter['BigDirection']);
            	}
            }
            if (isset($filter['Direction']) and !empty($filter['Direction'])) {
                if (is_array($filter['Direction'])) {
                    $where[] = ' s.DirectionID IN ('.implode(',', Connection::GetSQLArray($filter['Direction'])).')';
                } else {
                    $where[] = ' s.DirectionID=' . intval($filter['Direction']);
                }
            }
            if (isset($filter['Region']) and !empty($filter['Region'])) {
                if (is_array($filter['Region'])) {
                    $where[] = ' u.RegionID IN (' . implode(',', Connection::GetSQLArray($filter['Region'])) . ')';
                } else {
                    $where[] = ' u.RegionID=' . intval($filter['Region']);
                }
            }
            if (isset($filter['CityID']) and !empty($filter['CityID'])) {
                $where[] = ' u.CityID=' . intval($filter['CityID']);
            }
            if (isset($filter['Military']) and $filter['Military'] == 1) {
                $where[] = ' u.MilitaryDepartment="Есть" ';
            }
            if (isset($filter['Delay']) and $filter['Delay'] == 1) {
                $where[] = ' u.DelayArmy="Есть" ';
            }
            if (isset($filter['Hostel']) and $filter['Hostel'] == 1) {
                $where[] = ' u.Hostel="Есть" ';
            }
            if (isset($filter['Text']) and strlen($filter['Text']) > 0) {
            	$where[] = ' (u.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		u.ShortTitle LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		r.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		t.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR
            		d.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR
            		s.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').')';
            }

            if(isset($filter['Subject'])) {
                $subjectCount = 0;
                $beforeQuery[] = "CREATE TEMPORARY TABLE university_filter (
                    SubjectID INT (5) PRIMARY KEY, 
                    Value INT (3) NOT NULL
                ); \n";

                $subjectFilter = [];
                $subQueryWhere = [];
                foreach ($filter['Subject'] as $subject => $score) {
                    if (empty($subject)){
                        continue;
                    }

                    if ($score > 0){
                        $subQueryWhere[] = "(ege.SubjectID = {$subject} AND Score <= {$score})";
                        $subjectCount++;
                        $subjectFilter[] = "({$subject}, {$score})";
                    }
                    else{
                        $subQueryWhere[] = "(ege.SubjectID = {$subject} AND IF(Score, Score <= 0, Score IS NULL))";
                    }

                }

                if ($subjectCount > 0){
                    if (isset($filter['AdditionalExam'])){
                        $where['AdditionalExam'] = "s.Additional1 IS NOT NULL AND s.Additional1 != ''";
                        $countForMultiple--;
                    }
                    else{
                        $where['AdditionalExam'] = "s.Additional1 = ''";
                    }

                    $beforeQuery[] = "INSERT INTO university_filter VALUES " . implode(",\n ", $subjectFilter) . "; \n";

                    if ($subjectCount > $countForMultiple){
                        $having = "COUNT(ege.SpecialityID) >= {$countForMultiple} AND ege.AllCount >= {$countForMultiple}
                            AND COUNT(ege.SpecialityID) <= {$subjectCount} AND ege.AllCount <= {$subjectCount}
                            AND COUNT(ege.SpecialityID) = ege.AllCount";
                        if (isset($filter['AdditionalExam'])){
                            unset($where['AdditionalExam']);
                        }
                    }
                    else{
                        $having = "COUNT(ege.SpecialityID) = {$subjectCount} AND ege.AllCount = {$subjectCount}";
                    }

                    $join[] = "LEFT JOIN(
                        SELECT ege.SpecialityID, ege.AllCount, SUM(filter.Value) AS FilterSum, ege.GPA
                        FROM tmp_data_ege AS ege
                        LEFT JOIN university_filter AS filter ON ege.SubjectID = filter.SubjectID
                        WHERE " . implode("
                        OR ", $subQueryWhere)
                        . " GROUP BY ege.SpecialityID
                        HAVING (
                            ". $having . "
                        )
                    ) AS spec_info ON s.SpecialityID = spec_info.SpecialityID";

                    $where[] = "spec_info.SpecialityID IS NOT NULL";
                    $where[] = "GPA > 0";
                    $where[] = "IF(s.Additional1 IS NOT NULL AND s.Additional1 != '', (FilterSum) + 100, FilterSum) >= GPA";
                }
            }

            if (isset($filter['Profession']) and !empty($filter['Profession'])) {
                $join[] = "LEFT JOIN data_profession2direction p2d ON p2d.DirectionID=d.DirectionID";
                if (is_array($filter['Profession'])) {
                    $where[] = ' p2d.ProfessionID IN ('.implode(',', Connection::GetSQLArray($filter['Profession'])).')';
                } else {
                    $where[] = ' p2d.ProfessionID=' . intval($filter['Profession']);
                }
            }
            if (isset($filter['Achievement']) and !empty($filter['Achievement'])) {
                $join[] = "LEFT JOIN `data_speciality2achievement` AS spec2ach ON s.SpecialityID = spec2ach.SpecialityID";
                if (is_array($filter['Achievement'])) {
                    $where[] = ' spec2ach.AchievementID IN ('.implode(',', Connection::GetSQLArray($filter['Achievement'])).')';
                } else {
                    $where[] = ' spec2ach.AchievementID=' . intval($filter['Achievement']);
                }
            }
            if (isset($filter['StudType']) and !empty($filter['StudType'])) {
                $where[] = ' s.SpecialityID IN (
								SELECT DISTINCT SpecialityID FROM data_speciality_study WHERE Type IN (' . implode(',', Connection::GetSQLArray($filter['StudType'])) . ')
								)';
        	}
        }
        if($request->IsPropertySet('ListID')){
        	$join[] = "LEFT JOIN `data_list_item` l ON l.TargetID=u.UniversityID AND l.TargetType='university'";
        	$where[] = " l.ListID=".$request->GetIntProperty('ListID');
        }

        //Sorting
        $order = "u.SortOrder DESC,u.Title";
        $sorting = $request->GetProperty('Sorting');

        if(!empty($sorting)){
            if ($sorting['Key'] == self::SORT_KEY_UNIVERSITY_CATEGORY){
                $join['UniversityCategory'] = "
                    LEFT JOIN `data_university2university_category` AS univer_cat2cat
                    ON u.UniversityID = univer_cat2cat.UniversityID
                ";

                $order = "FIELD(univer_cat2cat.UniversityCategoryID, {$sorting['Value']}) DESC, u.UniversityID";
            }
        }

        //Create temp table
        if (isset($subjectCount)){
            foreach ($beforeQuery as $index => $beforeSql) {
                $stmt->Execute($beforeSql);
                //echo $beforeSql;
            }
        }

        GetStatement()->Execute('SET @@group_concat_max_len = 2048;');
        $query = "SELECT u.*, s.Students, city.StaticPath as CityPath,
        	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).",'/') AS UniversityURL,
        	GROUP_CONCAT(s.SpecialityID) AS Specialities
            FROM `data_university` AS u
            LEFT JOIN `data_speciality` AS s ON s.UniversityID=u.UniversityID 
            LEFT JOIN `data_region` AS r ON r.RegionID=u.RegionID 
            LEFT JOIN `data_city` AS city ON city.ID=u.CityID 
            LEFT JOIN `data_type` AS t ON t.TypeID=u.TypeID 
            LEFT JOIN `data_direction` AS d ON d.DirectionID=s.DirectionID 
            ".(!empty($join) ? implode(" \n ", $join) : '')."
            ". (!empty($where) ? "\n WHERE ".implode("\n AND ", $where) : '') .
            " GROUP BY u.UniversityID
            ORDER BY {$order}";

        $this->SetPageParam('UniverPager');
        $this->SetItemsOnPage($itemsOnPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
        if ($prepare){
            $this->prepare($request);
        }
        //echo $query;
    }

    protected function prepare(LocalObject $request)
    {
    	$stmt = GetStatement();
    	for($i=0; $i<count($this->_items); $i++) {

    		//prepare image
    		$images = $stmt->FetchList('SELECT ItemImage FROM `data_university_image`
    				WHERE UniversityID='.$this->_items[$i]["UniversityID"].' ORDER BY `SortOrder` ASC LIMIT 1');
    		if ($images && count($images) > 0) {
    			foreach ($this->params as $param) {
    				$this->_items[$i][$param['Name'].'Path'] = $param['Path'].'univer/'.$images[0]["ItemImage"];
    			}
    		}

            $this->_items[$i]['UniversityURL'] = URLParser::getPrefixWithSubDomain($this->_items[$i]['CityPath'] ?? null) . $this->_items[$i]['UniversityURL'];

    		//prepare speciality list
			if (!empty($this->_items[$i]["Specialities"])){
				$where = array();

				$orderby = $request->GetProperty('SpecialitiesOrder') ? "sp." . $request->GetProperty('SpecialitiesOrder') : "(IF(BudgetNext,BudgetNext, Budget)) * 1 DESC,sp.Title ASC";

				$where[] = "sp.UniversityID=" . $this->_items[$i]["UniversityID"];
				$where[] = "sp.SpecialityID IN (".$this->_items[$i]["Specialities"].")";

				$query = "SELECT sp.SpecialityID, sp.Title, 
    			GROUP_CONCAT(s.ShortTitle SEPARATOR ';') AS SubjectsTitle, GROUP_CONCAT(e.isProfile SEPARATOR ';') AS SubjectsProfile, 
    			sp.Additional1, sp.Additional2, sp.Additional3, sp.Additional4,  sp.Additional5,  sp.Additional6,  sp.Additional7,  sp.Additional8,  sp.Additional9,  sp.Additional10, 
    			CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/".$this->_items[$i]["StaticPath"]."/', sp.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL,
    			
    			tmp_stud.*
                
                FROM `data_speciality` AS sp
	            LEFT JOIN data_ege e ON sp.SpecialityID=e.SpecialityID
	            LEFT JOIN data_subject s ON s.SubjectID=e.SubjectID
	            LEFT JOIN tmp_data_speciality_study tmp_stud ON sp.SpecialityID=tmp_stud.SpecialityID
	            
	            " . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "") . "
	            GROUP BY sp.SpecialityID
	            ORDER BY ".$orderby;

				//echo $query . '<br>';
				$specialities = $stmt->FetchList($query);

				for($j=0; $j<count($specialities); $j++) {

					$subjectList = array();
					$subjectTitles = explode(';', $specialities[$j]["SubjectsTitle"]);
					$subjectProfiles = explode(';', $specialities[$j]["SubjectsProfile"]);
					for($k=0; $k<count($subjectTitles); $k++) {
						$subjectList[] = array(
							"Title" => $subjectTitles[$k],
							"IsProfile" => $subjectProfiles[$k]
						);
					}
					$additionalSubjects = "";
					if($specialities[$j]["Additional1"]) $additionalSubjects .= $specialities[$j]["Additional1"] . ", ";
					if($specialities[$j]["Additional2"]) $additionalSubjects .= $specialities[$j]["Additional2"] . ", ";
					if($specialities[$j]["Additional3"]) $additionalSubjects .= $specialities[$j]["Additional3"] . ", ";
					if($specialities[$j]["Additional4"]) $additionalSubjects .= $specialities[$j]["Additional4"] . ", ";
					if($specialities[$j]["Additional5"]) $additionalSubjects .= $specialities[$j]["Additional5"] . ", ";
					if($specialities[$j]["Additional6"]) $additionalSubjects .= $specialities[$j]["Additional6"] . ", ";
					if($specialities[$j]["Additional7"]) $additionalSubjects .= $specialities[$j]["Additional7"] . ", ";
					if($specialities[$j]["Additional8"]) $additionalSubjects .= $specialities[$j]["Additional8"] . ", ";
					if($specialities[$j]["Additional9"]) $additionalSubjects .= $specialities[$j]["Additional9"] . ", ";
					if($specialities[$j]["Additional10"]) $additionalSubjects .= $specialities[$j]["Additional10"] . ", ";
					if(strlen($additionalSubjects) > 0){
						$additionalSubjects = substr($additionalSubjects, 0, -2);
						$subjectList[] = array(
							"Title" => "ДВИ",
							"Tooltip" => $additionalSubjects
						);
					}
					$specialities[$j]['SubjectList'] = $subjectList;

					$specialities[$j]['Opened'] = $this->_items[$i]["Opened"];

					//subdomain
                    $specialities[$j]['SpecialityURL'] = URLParser::getPrefixWithSubDomain($this->_items[$i]['CityPath'] ?? null) . $specialities[$j]['SpecialityURL'];
				}
				$this->_items[$i]["SpecialityList"] = $specialities;
			}
    	}
    }

    public function getByID($univerID, $baseURL="")
    {
        $univerID = intval($univerID);
        $stmt = GetStatement();
        $user = new UserItem('user');
        $user->loadBySession();

        if ($user->IsPropertySet('UserID')) {
            $query = "SELECT u.*, r.Title AS RegionTitle, t.Title AS TypeTitle, u2u.UniversityID AS IsEnrollee, city.StaticPath as CityPath,
            	CONCAT(".Connection::GetSQLString($baseURL).", '/', u.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).",'/') AS UniversityURL
                FROM `data_university` AS u
                LEFT JOIN `data_region` AS r ON u.RegionID=r.RegionID
                LEFT JOIN `data_type` AS t ON u.TypeID=t.TypeID
                LEFT JOIN `data_city` AS city ON city.ID=u.CityID 
                LEFT JOIN `data_user_university` AS u2u ON u2u.UniversityID=u.UniversityID 
                    AND u2u.UserID=".$user->GetIntProperty('UserID')."
                WHERE u.UniversityID=" . $univerID;
        } else {
            $query = "SELECT u.*, r.Title AS RegionTitle, t.Title AS TypeTitle, city.StaticPath as CityPath,
            	CONCAT(".Connection::GetSQLString($baseURL).", '/', u.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).",'/') AS UniversityURL
                FROM `data_university` AS u
                LEFT JOIN `data_region` AS r ON u.RegionID=r.RegionID
                LEFT JOIN `data_type` AS t ON u.TypeID=t.TypeID
                LEFT JOIN `data_city` AS city ON city.ID=u.CityID 
                WHERE UniversityID=" . $univerID;
        }

        if ($row = $stmt->FetchRow($query)) {

            if (!empty($row['InternationalPrograms']) or !empty($row['DoubleDiploma'])) {
                $row['EducationBlock'] = 1;
            }
            if (!empty($row['MilitaryDepartment']) or !empty($row['DelayArmy'])) {
                $row['MilitaryBlock'] = 1;
            }
            if (!empty($row['Hostel']) or !empty($row['HostelPriceBudget']) or !empty($row['HostelPriceContract'])) {
                $row['HostelBlock'] = 1;
            }
            if (!empty($row['Scholarship']) or !empty($row['ScholarshipSpecialAcademic'])
                or !empty($row['ScholarshipSocial'])
            ) {
                $row['ScholarshipBlock'] = 1;
            }

            if (!empty($row['VideoURL'])) {
            	$row['VideoID'] = GetVideoIdFromYouTube($row['VideoURL']);
            }

            $imageList = $this->getImageList($univerID);
            $row['ImagesList'] = $imageList->getItemsByParams($this->params);

            //subdomain
            $row['UniversityURL'] = URLParser::getPrefixWithSubDomain($row['CityPath']) . $row['UniversityURL'];

            return $row;
        }

        return false;
    }

    public function getImageList($universityID){
        if ($this->imageList->GetCountItems() < 1){
            $this->imageList->load($universityID);
        }

        return $this->imageList;
    }


    public function loadRandom(LocalObject $request)
    {
        $query = "SELECT u.UniversityID, u.Title, s.Score2016 AS Score, city.StaticPath as CityPath,
        	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).",'/') AS UniversityURL
            FROM `data_university` AS u
            LEFT JOIN `data_speciality` AS s ON s.UniversityID=u.UniversityID
            LEFT JOIN `data_city` AS city ON city.ID=u.CityID
            ORDER BY RAND() 
            LIMIT 3";
        $this->LoadFromSQL($query);

        //prepare subdomain
        foreach ($this->_items as $key => $item){
            $this->_items[$key]['UniversityURL'] = URLParser::getPrefixWithSubDomain($item['CityPath']) . $item['UniversityURL'];
        }
    }

    public function loadForUser(LocalObject $request)
    {
    	$query = "SELECT u.UniversityID, u.Title, s.Score2016 AS Score, r.Title as Region, city.StaticPath as CityPath,
    	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', u.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).",'/') AS UniversityURL
    	FROM `data_university` AS u
    	LEFT JOIN `data_speciality` AS s ON s.UniversityID=u.UniversityID
    	LEFT JOIN `data_region` AS r ON r.RegionID=u.RegionID
    	LEFT JOIN `data_city` AS city ON city.ID=u.CityID
    	LEFT JOIN `data_user_university` AS uu ON uu.UniversityID=u.UniversityID
    	WHERE uu.UserID=".$request->GetIntProperty("UserID")."
    	GROUP BY u.UniversityID
    	ORDER BY u.Title";

    	$this->SetItemsOnPage(0);
    	$this->LoadFromSQL($query);

    	//prepare subdomain
        foreach ($this->_items as $key => $item){
            $this->_items[$key]['UniversityURL'] = URLParser::getPrefixWithSubDomain($item['CityPath']) . $item['UniversityURL'];
        }
    }

    public function loadForSelect()
    {
        $query = "SELECT u.UniversityID, u.Title, u.ShortTitle
    	FROM `data_university` AS u
    	ORDER BY u.ShortTitle";

        $this->SetItemsOnPage(0);
        $this->LoadFromSQL($query);
    }

    public function becomeAnEntrant($userId, $univerId, $specialityId, $state)
    {
        $stmt = GetStatement();
        if ($state) {
            $query = "INSERT INTO `data_user_university` 
                SET UserID=".intval($userId).", UniversityID=".intval($univerId).($specialityId?(", SpecialityID=".intval($specialityId)):"").", Created=".Connection::GetSQLString(GetCurrentDateTime());

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
            $query = "DELETE FROM `data_user_university` 
                WHERE UserID=".intval($userId)." AND UniversityID=".intval($univerId).($specialityId?(" AND SpecialityID=".intval($specialityId)):"");
        }
        $stmt->Execute($query);
    }

    public function getItems_nameConcatShortTitle($selected){
		foreach ($this->_items as $item) {
			if (!empty($selected)){
				if(in_array($item['UniversityID'], $selected)){
					$item['Selected'] = 1;
				}
			}
			if ( !empty($item['ShortTitle']) ){
				$item['Title'] = $item['Title'] . ' (' . $item['ShortTitle'] . ')';
			}
			$result[] = $item;
		}
		return $result;
	}

    public function getStaticPathByID($universityID)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT u.StaticPath FROM `data_university` u WHERE u.UniversityID=".intval($universityID));
    }

    public static function getIDByStaticPath($staticPath)
    {
        $stmt = GetStatement();
        return $stmt->FetchField("SELECT u.UniversityID FROM `data_university` u WHERE u.StaticPath=".Connection::GetSQLString($staticPath));
    }

    public static function getForSiteMap($baseUrl, int $cityID = null){
        $stmt = GetStatement();
        $query = QueryBuilder::init()
            ->select([
                "DISTINCT CONCAT(" . Connection::GetSQLString($baseUrl) . ", '/', u.StaticPath, '/') AS URL",
            ])
            ->from('`data_university` AS u');
        if ($cityID){
            $query->addWhere("u.CityID = {$cityID}");
        }
        if ($result = $stmt->FetchRows($query->getSQL())){
            return $result;
        }

        return null;
    }

}