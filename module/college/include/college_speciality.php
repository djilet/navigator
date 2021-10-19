<?php

Class CollegeSpeciality extends LocalObjectList{
	protected $module;
	public $request;

	public function __construct($module = null){
		parent::LocalObjectList();
		($module !== null ? $this->module = $module : $this->module = 'college');
		$this->request = new LocalObject();
	}

    /**
     * @param LocalObject $request
     * @param int $onPage
     */
    public function load(LocalObject $request, $onPage = 40){
		$where = array();
		$join = array();
		$orderby = "spec.SortOrder DESC,spec.Title ASC";

		if ($request->IsPropertySet('SpecialFilter')) {
			$filter = $request->GetProperty('SpecialFilter');
			if ( !empty($filter['CollegeID']) ){
				$where[] = "col.CollegeID = " . intval($filter['CollegeID']);
			}

			if ( !empty($filter['CollegeStaticPath']) ){
				$where[] = "col.StaticPath = '{$filter['CollegeStaticPath']}'";
			}

			if ( !empty($filter['StaticPath']) ){
				$where[] = "spec.StaticPath = '{$filter['StaticPath']}'";
			}

			if ( !empty($filter['AdmissionBaseID']) ){
				$where[] = "spec.AdmissionBaseID = '{$filter['AdmissionBaseID']}'";
			}

		}

		$query = "SELECT spec.*, dir.Title AS BigDirectionTitle,
					CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', col.StaticPath, '/', spec.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL
				FROM `college_speciality` AS spec
				INNER JOIN college_college AS col ON spec.CollegeID = col.CollegeID
				LEFT JOIN college_bigdirection AS dir ON dir.CollegeBigDirectionID = spec.CollegeBigDirectionID
				LEFT JOIN `data_region` AS reg ON reg.RegionID = col.RegionID 
					".(!empty($join) ? implode(' ', $join) : '')."
					" . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "") . "
				GROUP BY spec.CollegeSpecialityID
				ORDER BY ".$orderby;
		$this->SetPageParam('SpecPager');
		$this->SetItemsOnPage(intval($onPage));
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}


    /**
     * @param LocalObject $request
     *  @request param CollegeID
     *  @request param CollegeSpecialityID
     *  @request param BaseURL
     *  @request param Limit
     *
     * @return bool
     */
    public function loadListByCollegeID(LocalObject $request){
        if (!$request->IsPropertySet('CollegeID')){
            $this->AddError('empty-college-id');
            return false;
        }
        $collegeID = $request->GetIntProperty('CollegeID');

        $where = array("spec.CollegeID=".intval($collegeID));
        $baseURL = '';
        $orderBy = Connection::GetSQLString('SortOrder');
		$groupBy = null;
		$limit = null;

        if ($request->IsPropertySet('CollegeSpecialityID')){
            $where[] = "spec.CollegeSpecialityID != ".$request->GetIntProperty('CollegeSpecialityID');
        }
        if ($request->IsPropertySet('CollegeSpecialityTitle')){
            $where[] = "spec.Title != ".$request->GetPropertyForSQL('CollegeSpecialityTitle');
        }
        if ($request->IsPropertySet('BaseURL')){
            $baseURL = $request->GetProperty('BaseURL');
        }
        if ($request->IsPropertySet('OrderBy')){
            $order = $request->GetProperty('OrderBy');
            if ($order == 'Rand'){
                $orderBy = 'RAND()';
            }
        }
        if ($request->IsPropertySet('GroupBy')){
			$group = $request->GetProperty('GroupBy');
			if ($group == 'Title'){
				$groupBy = 'spec.Title';
			}
        }
        if ($request->IsPropertySet('Limit')){
            $limit = $request->GetIntProperty('Limit');
        }

		$query = "SELECT spec.CollegeSpecialityID, spec.Title,
        	CONCAT(".connection::GetSQLString($baseURL).", '/', col.StaticPath, '/', spec.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL
        	FROM `college_speciality` spec
        	INNER JOIN college_college AS col ON spec.CollegeID = col.CollegeID
            " . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "") .
			($groupBy != null ? " GROUP BY " . $groupBy : "") . "
			ORDER BY " . $orderBy . ($limit != null ? " LIMIT " . $limit : '');

		$this->LoadFromSQL($query);
	}
//Get
	public function getByID($specialityID, $baseURL="", $prepare = true){
		$specialityID = intval($specialityID);
		$stmt = GetStatement();

		$query = "SELECT spec.*, dir.Title AS DirectionTitle,
			col.Title AS CollegeTitle,
			CONCAT(".Connection::GetSQLString($baseURL).", '/', col.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS CollegeURL,
        	CONCAT(".Connection::GetSQLString($baseURL).", '/', col.StaticPath, '/', spec.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') AS SpecialityURL,
        	spec.Address1 as SpecialityAddress,
        	spec.Latitude1 as SpecialityLatitude,
        	spec.Longitude1 as SpecialityLongitude  
            FROM `college_speciality` AS spec
            INNER JOIN college_college AS col ON spec.CollegeID=col.CollegeID
            LEFT JOIN `college_bigdirection` AS dir ON spec.CollegeBigDirectionID=dir.CollegeBigDirectionID
            WHERE spec.CollegeSpecialityID=" . $specialityID;
		if ($row = $stmt->FetchRow($query)) {

			if ($prepare == true){
				if (!empty($row['GPA']) ||
					!empty($row['FullStudBudgetCount']) ||
					!empty($row['PartStudBudgetCount']) ||
					!empty($row['ExtramuralStudBudgetCount']) ||
					!empty($row['RemoteStudBudgetCount'])) {
					$row['BudgetBlock'] = 1;
				}

				if (!empty($row['FullStudPaidCount'])) {
					$row['PaidBlock'] = 1;
				}

				if (!empty($row['FullStudPeriod']) or !empty($row['FullStudPaidPrice'])) {
					$row['PeriodPriceBlock'] = 1;
				}

				for ($i=1; $i <= 4; $i++){
					if ( !empty($row['Latitude' . $i]) && !empty($row['Longitude' . $i]) ){
						$row['Coordinates'][] = array('Latitude' => $row['Latitude' . $i], 'Longitude' => $row['Longitude' . $i]);
					}
				}

				if ( !empty($row['Qualification']) ){
					$result = array();
					foreach (explode(';',$row['Qualification']) as $key => $item) {
						if (!empty(trim($item))){
							$result[]['Title'] = trim($item);
						}
					}
					$row['Qualification'] = $result;
				}
			}

			return $row;
		}
	}

	function Remove($ids){
		if (is_array($ids) && count($ids) > 0) {
			$stmt = GetStatement();
			$query = "DELETE FROM `college_speciality` WHERE CollegeSpecialityID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);

			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("speciality-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}

//Static
	public static function GetOtherAdmissionBase($id){
		$stmt = GetStatement();
		$specInfo = $stmt->FetchRow("SELECT Title, CollegeID, AdmissionBaseID FROM college_speciality WHERE CollegeSpecialityID = " . intval($id));
		if ($specInfo['AdmissionBaseID'] > 0){
			$query = "SELECT spec.CollegeSpecialityID, adm.AdmissionBaseID, adm.Title AS AdmissionBaseTitle, spec.StaticPath AS SpecialityStaticPath,
				  (CASE spec.CollegeSpecialityID WHEN " . intval($id) . " THEN 1 ELSE 0 END) as Selected
				  FROM college_speciality AS spec
				  LEFT JOIN college_admission_base AS adm ON spec.AdmissionBaseID = adm.AdmissionBaseID
				  WHERE spec.Title = " . Connection::GetSQLString($specInfo['Title']) . " AND spec.CollegeID = " . intval($specInfo['CollegeID']);
			if($result = $stmt->FetchList($query)){
				return $result;
			}
		}
		return false;
	}

    public static function getForSiteMap($baseUrl, int $cityID = null){
        $stmt = GetStatement();
        $query = QueryBuilder::init()
            ->select([
                "DISTINCT CONCAT(" . Connection::GetSQLString($baseUrl) . ", '/', col.StaticPath, '/', spec.StaticPath) AS URL",
            ])
            ->from('`college_speciality` AS spec')
            ->addJoin("LEFT JOIN college_college AS col ON spec.CollegeID = col.CollegeID");
        if ($cityID){
            $query->addWhere("col.CityID = {$cityID}");
        }

        if ($result = $stmt->FetchRows($query->getSQL())){
            return $result;
        }

        return null;
    }

//Single
	//Save
		public function save(){
			if (!$this->validate()) {
				return false;
			}
			$stmt = GetStatement();
			$staticPath = RuToStaticPath($this->request->GetProperty("Title"));

			if ($this->request->GetIntProperty("CollegeSpecialityID") > 0){
				$op = "UPDATE `college_speciality` SET";
				$where =  " WHERE CollegeSpecialityID = " . $this->request->GetIntProperty("CollegeSpecialityID");
			}
			else{
				$op = "INSERT INTO `college_speciality` SET";
				$where = '';
			}

			$query = $op . "
					  `CollegeID` = " . $this->request->GetIntProperty("CollegeID") . ",
					  `CollegeBigDirectionID` = " . $this->request->GetIntProperty("CollegeBigDirectionID") . ",
					  `Title` = " . $this->request->GetPropertyForSQL("Title") . ",
					  `StaticPath` = " . Connection::GetSQLString($staticPath) . ",
					  `AdmissionBase` = " . $this->request->GetPropertyForSQL("AdmissionBase") . ",
					  `GPA` = " . $this->request->GetPropertyForSQL("GPA") . ",
					  `FullStudBudgetCount` = " . $this->request->GetPropertyForSQL("FullStudBudgetCount") . ",
					  `FullStudPaidCount` = " . $this->request->GetIntProperty("FullStudPaidCount") . ",
					  `FullStudPeriod` = " . $this->request->GetPropertyForSQL("FullStudPeriod") . ",
					  `FullStudPaidPrice` = " . $this->request->GetPropertyForSQL("FullStudPaidPrice") . ",
					  `PartStudBudgetCount` = " . $this->request->GetPropertyForSQL("PartStudBudgetCount") . ",
					  `PartStudPaidCount` = " . $this->request->GetPropertyForSQL("PartStudPaidCount") . ",
					  `PartStudPeriod` = " . $this->request->GetPropertyForSQL("PartStudPeriod") . ",
					  `PartStudPaidPrice` = " . $this->request->GetPropertyForSQL("PartStudPaidPrice") . ",
					  `ExtramuralStudBudgetCount` = " . $this->request->GetPropertyForSQL("ExtramuralStudBudgetCount") . ",
					  `ExtramuralStudPaidCount` = " . $this->request->GetPropertyForSQL("ExtramuralStudPaidCount") . ",
					  `ExtramuralStudPeriod` = " . $this->request->GetPropertyForSQL("ExtramuralStudPeriod") . ",
					  `ExtramuralStudPaidPrice` = " . $this->request->GetPropertyForSQL("ExtramuralStudPaidPrice") . ",
					  `RemoteStudBudgetCount` = " . $this->request->GetPropertyForSQL("RemoteStudBudgetCount") . ",
					  `RemoteStudPaidCount` = " . $this->request->GetPropertyForSQL("RemoteStudPaidCount") . ",
					  `RemoteStudPeriod` = " . $this->request->GetPropertyForSQL("RemoteStudPeriod") . ",
					  `RemoteStudPaidPrice` = " . $this->request->GetPropertyForSQL("RemoteStudPaidPrice") . ",
					  `Address1` = " . $this->request->GetPropertyForSQL("Address1") . ",
					  `Address2` = " . $this->request->GetPropertyForSQL("Address2") . ",
					  `Address3` = " . $this->request->GetPropertyForSQL("Address3") . ",
					  `Address4` = " . $this->request->GetPropertyForSQL("Address4") . ",
					  `Latitude1` = " . $this->request->GetPropertyForSQL("Latitude1") . ",
					  `Longitude1` = " . $this->request->GetPropertyForSQL("Longitude1") . ",
					  `Latitude2` = " . $this->request->GetPropertyForSQL("Latitude2") . ",
					  `Longitude2` = " . $this->request->GetPropertyForSQL("Longitude2") . ",
					  `Latitude3` = " . $this->request->GetPropertyForSQL("Latitude3") . ",
					  `Longitude3` = " . $this->request->GetPropertyForSQL("Longitude3") . ",
					  `Latitude4` = " . $this->request->GetPropertyForSQL("Latitude4") . ",
					  `Longitude4` = " . $this->request->GetPropertyForSQL("Longitude4") . ",
					  `Qualification` = " . $this->request->GetPropertyForSQL("Qualification") . ",
					  `SortOrder` = " . $this->request->GetIntProperty("SortOrder") .
				$where;

			if ($stmt->Execute($query)){
				if (!$this->request->GetIntProperty("CollegeSpecialityID") > 0){
					$this->request->SetProperty("CollegeSpecialityID", $stmt->GetLastInsertID());
				}
			}
			else{
				$this->AddError("sql-error");
				return false;
			}
			return true;
		}

		public function validate(){
    		if (!$this->request->ValidateNotEmpty('Title')){
				$this->AddError('speciality-title-empty');
				return false;
			}
			if (!$this->request->ValidateInt('SortOrder') || $this->request->GetIntProperty('SortOrder') < 0 ){
				$this->AddError('sort-order-not-numerical');
				return false;
			}
			return true;
		}
}