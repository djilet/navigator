<?php
require_once(dirname(__FILE__) . "/../init.php");
es_include("filesys.php");
es_include("localobject.php");

class College extends LocalObjectList{
	protected $module;
	private $params;
	private $adminParams;
	public $request;
	protected static $admissionBaseSort = array('9 класс', '11 класс');
	protected static $filters = array('Region', 'CollegeBigDirection', 'AdmissionBase', 'Hostel', 'OVZ', 'BestOfBest', 'Text');

	public function __construct($module = 'college'){
		parent::LocalObjectList();
		$this->module = $module;
		$this->params = LoadImageConfig('ItemImage', $this->module, '296x152|8|Thumb,136x80|8|Preview');
		$this->params = LoadImageConfig('ItemLogo', $this->module, '296x152|8|Thumb,136x80|1|Preview');
        //$this->adminParams = LoadImageConfig('CollegeLogo', $this->module, COLLEGE_IMAGE);
        $this->request = new LocalObject();
	}

	public function load(LocalObject $request, $itemsOnPage = 30){
		$where = array();
		$join = array();
		if ($request->IsPropertySet('CollegeFilter')) {
			$filter = $request->GetProperty('CollegeFilter');

			if (isset($filter['Region']) and !empty($filter['Region'])) {
				if (is_array($filter['Region'])) {
					$where[] = ' col.RegionID IN (' . implode(',', Connection::GetSQLArray($filter['Region'])) . ')';
				} else {
					$where[] = ' col.RegionID=' . intval($filter['Region']);
				}
			}

            if (isset($filter['CityID']) and !empty($filter['CityID'])) {
                $where[] = ' col.CityID=' . intval($filter['CityID']);
            }

			if (isset($filter['CollegeBigDirection']) and !empty($filter['CollegeBigDirection'])) {
				if (is_array($filter['CollegeBigDirection'])) {
					$where[] = ' col.CollegeBigDirectionID IN (' . implode(',', Connection::GetSQLArray($filter['CollegeBigDirection'])) . ')';
				} else {
					$where[] = ' col.CollegeBigDirectionID=' . intval($filter['CollegeBigDirection']);
				}
			}

			if (isset($filter['AdmissionBase']) and !empty($filter['AdmissionBase'])) {
				$join[] = 'LEFT JOIN `college_admission_base` AS adm ON spec.AdmissionBaseID = adm.AdmissionBaseID';
				$where[] = ' spec.AdmissionBaseID IN (' . implode(',', Connection::GetSQLArray($filter['AdmissionBase'])) . ')';
			}

			//Other
			if (isset($filter['Hostel']) and $filter['Hostel'] == 1) {
				$where[] = ' col.Hostel="Есть" ';
			}
			if (isset($filter['BestOfBest']) and $filter['BestOfBest'] == 1) {
				$join[] = 'LEFT JOIN `college_college2award` AS col2aw ON col.CollegeID = col2aw.CollegeID';
				$where[] = 'col2aw.AwardsID > 0';
			}
			if (isset($filter['OVZ']) and $filter['OVZ'] == 1) {
				$where[] = 'spec.OVZ != ""';
			}
			if (isset($filter['AtUniversity']) and $filter['AtUniversity'] == 1) {
				$where[] = 'col.AtUniversity = "Y"';
			}
			if (isset($filter['Text']) and strlen($filter['Text']) > 0) {
				$where[] = ' (col.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		col.ShortTitle LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		reg.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR 
            		dir.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR
            		spec.Title LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').' OR
                    col.Synonyms LIKE '.Connection::GetSQLString('%'.$filter['Text'].'%').')';
			}
		}

		$query = "SELECT col.*, city.StaticPath AS CityPath,
        	CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', col.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS CollegeURL,
        	GROUP_CONCAT(spec.CollegeSpecialityID) AS Specialities
            FROM `college_college` AS col
            LEFT JOIN `college_speciality` AS spec ON spec.CollegeID = col.CollegeID 
            LEFT JOIN `data_region` AS reg ON reg.RegionID = col.RegionID 
            LEFT JOIN `data_city` AS city ON col.CityID = city.ID 
            LEFT JOIN `college_bigdirection` AS dir ON dir.CollegeBigDirectionID = spec.CollegeBigDirectionID 
            ".(!empty($join) ? implode(" \n ", $join) : '')."
            ". (!empty($where) ? ' WHERE '.implode(' AND ', $where) : '') ." 
            GROUP BY col.CollegeID
            ORDER BY col.SortOrder DESC, col.Title";

		//echo $query;

		$this->SetPageParam('CollegePager');
		$this->SetItemsOnPage($itemsOnPage);
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
		$this->prepare($request);
	}

	function loadCollegeList(){
		$where = array();

		$query = "SELECT col.CollegeID, col.Title, col.ShortTitle
					FROM `college_college` AS col			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
	}

	function remove($ids){
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();

			$query = "SELECT CollegeSpecialityID from `college_speciality` WHERE CollegeID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$specIDs = $stmt->FetchRows($query);
			if(count($specIDs) > 0){
				$specialityList = new CollegeSpeciality($this->module);
				$specialityList->remove($specIDs);
			}

			$query = "DELETE FROM `college_college` WHERE CollegeID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);

			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("college-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}

	public function loadForOtherList($baseURL, $collegeID ,$collegeBigDirectionID){
		$query = "SELECT col.CollegeID, col.Title, city.StaticPath AS CityPath,
        	CONCAT(".connection::GetSQLString($baseURL).", '/', col.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS CollegeURL
            FROM `college_college` AS col
            LEFT JOIN `data_city` AS city ON col.CityID = city.ID
            WHERE col.CollegeBigDirectionID = " . intval($collegeBigDirectionID) . " AND col.CollegeID != " . $collegeID . " ORDER BY RAND() 
            LIMIT 3";
		$this->LoadFromSQL($query);
		$this->prepare($this->request);
	}

	function LoadListForSelection($collegeID){
		$query = "SELECT col.CollegeID, col.Title, (CASE col.CollegeID WHEN " . intval($collegeID) . " THEN 1 ELSE 0 END) as Selected FROM `college_college` AS col";
		$this->LoadFromSQL($query);
	}


//Service
	protected function prepare($request)
	{
		$stmt = GetStatement();
		for($i=0; $i<count($this->_items); $i++) {
			$specialities = array();

            $this->_items[$i]['CollegeURL'] = URLParser::getPrefixWithSubDomain($this->_items[$i]['CityPath'] ?? null) . $this->_items[$i]['CollegeURL'];

            //prepare image
			$images = $stmt->FetchList('SELECT ItemImage FROM `college_image`
    				WHERE CollegeID='.$this->_items[$i]["CollegeID"].' ORDER BY `SortOrder` ASC LIMIT 1');
			if ($images && count($images) > 0) {
				foreach ($this->params as $param) {
					$this->_items[$i][$param['Name'].'Path'] = $param['Path'].$images[0]["ItemImage"];
				}
			}

			//prepare logo
			if (! empty($this->_items[$i]['CollegeLogo'])) {
				foreach ($this->params as $param) {
					if ($param['Name'] == 'ItemLogoPreview'){
						$this->_items[$i][$param['Name'].'Path'] = $param['Path'].$this->_items[$i]['CollegeLogo'];
						//print_r($this);
						//exit();
					}
				}
			}

			//prepare speciality list
			if (!empty($this->_items[$i]["Specialities"])){
				$where = array();
				$orderby = "spec.GPA DESC";

				$where[] = "spec.CollegeID=" . $this->_items[$i]["CollegeID"];
				$where[] = "spec.CollegeSpecialityID IN (".$this->_items[$i]["Specialities"].")";

				$query = "SELECT spec.CollegeSpecialityID, spec.Title, spec.GPA, adm.Title AS AdmissionBase, spec.OVZ, spec.FullStudBudgetCount, spec.FullStudPaidPrice,
    						CONCAT(".$request->GetPropertyForSQL('BaseURL').",'/".$this->_items[$i]["StaticPath"]."/', spec.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL
						FROM `college_speciality` AS spec
						LEFT JOIN college_admission_base AS adm ON spec.AdmissionBaseID = adm.AdmissionBaseID
						 " . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "") . "
	            		GROUP BY spec.CollegeSpecialityID
	            		ORDER BY ".$orderby;

				$result = $stmt->FetchIndexedAssocList($query,'CollegeSpecialityID');
				$double = array();
				//Group
				foreach ($result as $key => $item) {
					$double[$item['Title']][$item['AdmissionBase']] = $item;
				}
				unset($result);

				//Create distinct list
				foreach ($double as $key => $item) {
					if (count($item) > 1){
						foreach (self::$admissionBaseSort as $index => $sort) {
							if (isset($item[$sort])){
								$select = $item[$sort];
								break;
							}
						}
						if (empty($select)){
							$select = array_shift($item);
						}

					}
					else{
                        $select = array_shift($item);
					}

                    $select['SpecialityURL'] = URLParser::getPrefixWithSubDomain($this->_items[$i]['CityPath'] ?? null) . $select['SpecialityURL'];
                    $specialities[] = $select;
                    //print_r($item);
				}
				unset($double);

				$this->_items[$i]["SpecialityList"] = $specialities;
			}
		}
	}


//Static
	public static function getIDByStaticPath($staticPath){
		$stmt = GetStatement();
		return $stmt->FetchField("SELECT col.CollegeID FROM `college_college` col WHERE col.StaticPath=".Connection::GetSQLString($staticPath));
	}

	public static function getFilterList(){
		return self::$filters;
	}

    public static function getForSiteMap($baseUrl, int $cityID = null){
        $stmt = GetStatement();
        $query = QueryBuilder::init()
            ->select([
                "DISTINCT CONCAT(" . Connection::GetSQLString($baseUrl) . ", '/', StaticPath, '/') AS URL",
            ])
            ->from('`college_college` AS col');
        if ($cityID){
            $query->addWhere("col.CityID = {$cityID}");
        }
        if ($result = $stmt->FetchRows($query->getSQL())){
            return $result;
        }

        return null;
    }

//Single
	//Get
	public function getByID($collegeID, $baseURL=""){
		$collegeID = intval($collegeID);
		$stmt = GetStatement();

		$query = "SELECT col.*, reg.Title AS RegionTitle, city.StaticPath AS CityPath,
            	CONCAT(".Connection::GetSQLString($baseURL).", '/', col.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS CollegeURL
                FROM `college_college` AS col
                LEFT JOIN `data_region` AS reg ON col.RegionID=reg.RegionID
                LEFT JOIN `data_city` AS city ON col.CityID=city.ID
                WHERE col.CollegeID=" . $collegeID;

		if ($row = $stmt->FetchRow($query)) {
            $row['CollegeURL'] = URLParser::getPrefixWithSubDomain($row['CityPath'] ?? null) . $row['CollegeURL'];


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

			$images = $stmt->FetchList('SELECT * FROM `college_image` 
                WHERE CollegeID='.intval($collegeID).' ORDER BY `SortOrder` ASC');
			if ($images) {
				$row['ImagesList'] = [];
				foreach ($images as $image) {
					$image = array('ItemImage' => $image['ItemImage']);
					foreach ($this->params as $param) {
						$image[$param['Name'].'Path'] = $param['Path'].$image['ItemImage'];
					}
					$row['ImagesList'][] = $image;
				}
			}

			//Awards
			$query = "SELECT award.Title, award.AwardsID
				  FROM college_college2award AS col2award
				  LEFT JOIN college_award AS award ON col2award.AwardsID = award.AwardsID
				  WHERE col2award.CollegeID = " . $collegeID;
			$row['Awards'] = $stmt->FetchList($query);

			return $row;
		}
	}

	//Save
	public function save(){
        if (!$this->validate()) {
            return false;
        }
        $stmt = GetStatement();
        //$this->saveItemImage($this->request->GetProperty("SavedCollegeLogo"));
        //$this->request->SetProperty("CollegeLogoConfig", json_encode($this->request->GetProperty("CollegeLogoConfig")));
        $staticPath = RuToStaticPath($this->request->GetProperty("Title"));

        if ($this->request->GetIntProperty('CityID') < 1){
            $this->request->SetProperty('CityID', null);
        }

        if ($this->request->GetIntProperty("CollegeID") > 0){
            $op = "UPDATE `college_college` SET";
            $where =  " WHERE CollegeID = " . $this->request->GetIntProperty("CollegeID");
        }
        else{
            $op = "INSERT INTO `college_college` SET";
            $where = '';
        }

        $query = $op . "
				  `Title` = " . $this->request->GetPropertyForSQL("Title") . ",
				  `ShortTitle` = " . $this->request->GetPropertyForSQL("ShortTitle") . ",
				  `StaticPath` = " . Connection::GetSQLString($staticPath) . ",
				  `RegionID` = " . $this->request->GetIntProperty("RegionID") . ",
				  `CityID` = " . $this->request->GetPropertyForSQL("CityID") . ",
				  `Type` = " . $this->request->GetPropertyForSQL("Type") . ",
				  `AtUniversity` = " . Connection::GetSQLString( ($this->request->GetProperty("AtUniversity") == 'Y' ? 'Y' : 'N') ) . ",
				  `CollegeBigDirectionID` = " . $this->request->GetIntProperty("CollegeBigDirection") . ",
				  `Website` = " . $this->request->GetPropertyForSQL("Website") . ",
				  `Address` = " . $this->request->GetPropertyForSQL("Address") . ",
				  `Latitude` = " . $this->request->GetPropertyForSQL("Latitude") . ",
				  `Longitude` = " . $this->request->GetPropertyForSQL("Longitude") . ",
				  `PhoneSelectionCommittee` = " . $this->request->GetPropertyForSQL("PhoneSelectionCommittee") . ",
				  `AccreditationValidity` = " . $this->request->GetPropertyForSQL("AccreditationValidity") . ",
				  `Achievements` = " . $this->request->GetPropertyForSQL("Achievements") . ",
				  `Hostel` = " . $this->request->GetPropertyForSQL("Hostel") . ",
				  `HostelPrice` = " . $this->request->GetPropertyForSQL("HostelPrice") . ",
				  `Scholarship` = " . $this->request->GetPropertyForSQL("Scholarship") . ",
				  `ScholarshipSocial` = " . $this->request->GetPropertyForSQL("ScholarshipSocial") . ",
				  `VideoURL` = " . $this->request->GetPropertyForSQL("VideoURL") .
                    $where;

        if ($stmt->Execute($query)){
            if (!$this->request->GetIntProperty("CollegeID") > 0){
                $this->request->SetProperty("CollegeID", $stmt->GetLastInsertID());
            }
            $this->saveAwards($this->request->GetProperty('Awards'));
            return true;
        }
        else{
            $this->AddError("sql-error");
            return false;
        }
	}


    /**
     * Not used
     * @param string $savedImage
     * @return bool
     */
    public function saveItemImage($savedImage = ""){
        //TODO save image for college ()
		$fileSys = new FileSys();

		$newItemImage = $fileSys->Upload(
			"College",
			COLLEGE_IMAGE_DIR
		);

		if ($newItemImage) {
			$this->request->SetProperty("College", $newItemImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newItemImage["FileName"]) {
				if (file_exists(COLLEGE_IMAGE_DIR . $savedImage) and
					is_file(COLLEGE_IMAGE_DIR . $savedImage)) {
					@unlink(COLLEGE_IMAGE_DIR . $savedImage);
				}
			}
		} else {
			if ($savedImage) {
				$this->request->SetProperty("College", $savedImage);
			} else {
				$this->request->SetProperty("College", null);
			}
		}

		$this->request->_properties["CollegeConfig"]["Width"] = 0;
		$this->request->_properties["CollegeConfig"]["Height"] = 0;

		if ($this->request->GetProperty('College')) {
			if ($info = @getimagesize(COLLEGE_IMAGE_DIR . $this->request->GetProperty('College'))) {
				$this->request->_properties["CollegeConfig"]["Width"] = $info[0];
				$this->request->_properties["CollegeConfig"]["Height"] = $info[1];
			}
		}

		$this->request->LoadErrorsFromObject($fileSys);

		return !$fileSys->HasErrors();
	}

	public function saveAwards($ids){
        if (!is_array($ids)) {
            return;
        }

        $stmt = GetStatement();
        $stmt->Execute("DELETE FROM `college_college2award` WHERE `CollegeID` = " . $this->request->GetIntProperty('CollegeID'));
        foreach ($ids as $id) {
            $query = 'INSERT INTO `college_college2award` VALUES('.$this->request->GetIntProperty('CollegeID').', '.intval($id).')';
            $stmt->Execute($query);
        }
    }

    //Service
    public function getImageParams(){
        $paramList = array();
        foreach ($this->adminParams as $param) {
            $paramList[] = array(
                "Name" => $param['Name'],
                "SourceName" => $param['SourceName'],
                "Width" => $param['Width'],
                "Height" => $param['Height'],
                "Resize" => $param['Resize'],
                "X1" => $this->request->GetIntProperty("ItemImage".$param['SourceName']."X1"),
                "Y1" => $this->request->GetIntProperty("ItemImage".$param['SourceName']."Y1"),
                "X2" => $this->request->GetIntProperty("ItemImage".$param['SourceName']."X2"),
                "Y2" => $this->request->GetIntProperty("ItemImage".$param['SourceName']."Y2")
            );
        }
        return $paramList;
    }

	protected function validate(){
        if (!$this->request->ValidateNotEmpty("Title")) {
            $this->AddError("college-title-empty", $this->module);
        }
        return !$this->HasErrors();
    }
}