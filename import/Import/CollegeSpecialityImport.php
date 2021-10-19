<?php

namespace Import;

class CollegeSpecialityImport extends BaseImport{
	protected $map = [
		'CollegeID'                     => 0, //id колледжа
		'CollegeTitle'          	    => 1, //Название колледжа
        'CollegeBigDirectionID' 	    => 2, //Крупное направление
		'ImportID'					    => 3, //id специальности
		'Title'                         => 4, //Название специальности
		'NULL'            		        => 5, //Название специализации
		'AdmissionBase'                 => 6, //База для поступления
		'OVZ'                           => 7, //Другие фильтры (ОВЗ)
		'GPA'                           => 8, //Средний балл аттестата
		'FullStudBudgetCount'           => 9, //очная Количество бюджетных мест
		'FullStudPaidCount'    			=> 10, //очная Количество платных мест
		'FullStudPeriod'			    => 11, //очная Срок обучения
		'FullStudPaidPrice'  		    => 12, //очная Стоимость обучения (в год)
		'PartStudBudgetCount'           => 13, //очно-заочная Количество бюджетных мест
		'PartStudPaidCount' 		    => 14, //очно-заочная Количество платных мест
		'PartStudPeriod'                => 15, //очно-заочная Срок обучения
		'PartStudPaidPrice'             => 16, //очно-заочная Стоимость обучения (в год)
		'ExtramuralStudBudgetCount'     => 17, //заочная Количество бюджетных мест
		'ExtramuralStudPaidCount'	    => 18, //заочная Количество платных мест
		'ExtramuralStudPeriod' 			=> 19,  //заочная Срок обучения
		'ExtramuralStudPaidPrice'		=> 20, //заочная Стоимость обучения (в год)
		'RemoteStudBudgetCount'			=> 21, //дистанционная Количество бюджетных мест
		'RemoteStudPaidCount'			=> 22, //дистанционная Количество платных мест
		'RemoteStudPeriod'				=> 23, //дистанционная Срок обучения
		'RemoteStudPaidPrice'		    => 24, //дистанционная Стоимость обучения (в год)
		'Address1'				        => 25, //Адрес обучения 1
		'Address2'				        => 26, //Адрес обучения 2
		'Address3'				        => 27, //Адрес обучения 3
		'Address4'				        => 28, //Адрес обучения 4
		'Qualification'				    => 29, //Квалификация (профессия)
	];

    protected $importIdToCollegeId;
    protected $importIdToSpecId;
    protected $collegeID;
    protected $collegeBigDirection;
    protected $admissionBase;

    protected $oldSpecialityInfo = array();

    public function __construct()
    {
        parent::__construct();

        $this->collegeBigDirection = new Tools\CollegeBigDirection($this->stmt);
        $this->admissionBase = new Tools\AdmissionBase($this->stmt);

        $this->importIdToCollegeId = $this->stmt->FetchIndexedAssocList(
            "SELECT `ImportID`, `CollegeID` FROM `college_college` WHERE `ImportID`<>''",
            'ImportID'
        );
        $this->importIdToSpecId = $this->stmt->FetchIndexedAssocList(
            "SELECT `ImportID`, `CollegeSpecialityID` FROM `college_speciality` WHERE `ImportID`<>''",
            'ImportID'
        );
    }

    public function insert(){
        $directionId = $this->collegeBigDirection->getId($this->value('CollegeBigDirectionID'));
		$admissionBase = $this->admissionBase->getId($this->value('AdmissionBase','self::prepareOnlyText'));

        $oldInfo = null;
        $statusInfo = 'new';
        $statusError="";

        if(isset($this->oldSpecialityInfo[$this->collegeID]['ByImportID'][$this->field('ImportID', null, false)])){
            $oldInfo = $this->oldSpecialityInfo[$this->collegeID]['ByImportID'][$this->field('ImportID', null, false)];
            $statusInfo = 'byID';
        }
        else if(isset($this->oldSpecialityInfo[$this->collegeID]['ByTitle'][$this->field('Title', null, false)])){
            $oldInfo = $this->oldSpecialityInfo[$this->collegeID]['ByTitle'][$this->field('Title', null, false)];
            $statusInfo = 'byTitle';
        }

        $specialityID = null;
        $staticPath = RuToStaticPath($this->field('Title'));
        if($oldInfo){
            $specialityID = $oldInfo['SpecialityID'];
            $staticPath = $oldInfo['StaticPath'];
        }

        $query = "INSERT INTO `college_speciality` SET ";
        if($specialityID != null){
            $query .= "`SpecialityID` = ".intval($specialityID).", ";
        }
        $query .= "`ImportID` = " . $this->field('ImportID') . ",
                    `CollegeID` = " . intval($this->collegeID) .",
                    `CollegeBigDirectionID` = " . intval($directionId) .",
                    `Title` = " . $this->field('Title') .",
                    `StaticPath` = " . \Connection::GetSQLString($staticPath) .",
                    `AdmissionBaseID` = " . intval($admissionBase) .",
                    `OVZ` = " . $this->field('OVZ')  .",
                    `GPA` = " . $this->field('GPA') .",
                    `FullStudBudgetCount` = " . $this->field('FullStudBudgetCount') .",
                    `FullStudPaidCount` = " . $this->field('FullStudPaidCount') .",
                    `FullStudPeriod` = " . $this->field('FullStudPeriod') .",
                    `FullStudPaidPrice` = " . $this->field('FullStudPaidPrice') .",
                    `PartStudBudgetCount` = " . $this->field('PartStudBudgetCount') .",
                    `PartStudPaidCount` = " . $this->field('PartStudPaidCount') .",
                    `PartStudPeriod` = " . $this->field('PartStudPeriod') .",
                    `PartStudPaidPrice` = " . $this->field('PartStudPaidPrice') .",
                    `ExtramuralStudBudgetCount` = " . $this->field('ExtramuralStudBudgetCount') .",
                    `ExtramuralStudPaidCount` = " . $this->field('ExtramuralStudPaidCount') .",
                    `ExtramuralStudPeriod` = " . $this->field('ExtramuralStudPeriod') .",
                    `ExtramuralStudPaidPrice` = " . $this->field('ExtramuralStudPaidPrice') .",
                    `RemoteStudBudgetCount` = " . $this->field('RemoteStudBudgetCount') .",
                    `RemoteStudPaidCount` = " . $this->field('RemoteStudPaidCount') .",
                    `RemoteStudPeriod` = " . $this->field('RemoteStudPeriod') .",
                    `RemoteStudPaidPrice` = " . $this->field('RemoteStudPaidPrice') .",
                    `Address1` = " . $this->field('Address1') .",
                    `Address2` = " . $this->field('Address2') .",
                    `Address3` = " . $this->field('Address3') .",
                    `Address4` = " . $this->field('Address4') .",
                    `Qualification` = " . $this->field('Qualification');

        $existsImportID = $this->stmt->FetchField('SELECT count(spec.CollegeSpecialityID) FROM `college_speciality` AS spec WHERE spec.`ImportID`='.$this->field('ImportID'));
        if($existsImportID > 0){
            $statusInfo = 'double';
        }
        else if($this->stmt->Execute($query)){
            if($specialityID == null){
                $specialityID = $this->stmt->GetLastInsertID();
            }
            //$this->saveSubjects($specialityID);
        }
        else {
            $statusInfo = 'error';
            $statusError = $query."<br/>";
        }
        print_r("SPECIALITY; ".$statusInfo."; ImportCollegeID=".$this->value('CollegeID')."; ImportSpecialityID=".$this->field('ImportID')."; SpecialityID=".$specialityID."; StaticPath=".$staticPath."<br/>".$statusError);
    }

	public function update($id){
		// TODO: Implement update() method.
	}

    public function findCollegeByImportID($importId){
        if (empty($importId)) {
            return 0;
        }

        if (!isset($this->importIdToCollegeId[$importId])) {
            return 0;
        }

        return $this->importIdToCollegeId[$importId]['CollegeID'];

    }

    public function setCollegeID($collegeID){
        $this->collegeID = $collegeID;
    }

    public function deleteByCollegeID(){
        if ($this->collegeID == 0) {
            return;
        }

        $oldSpecialities = $this->stmt->FetchList('SELECT spec.CollegeSpecialityID, spec.Title, spec.ImportID, spec.StaticPath
          FROM `college_speciality` AS spec WHERE spec.`CollegeID`='.$this->collegeID);
        for($i=0; $i<count($oldSpecialities); $i++) {
            if(!isset($this->oldSpecialityInfo[$this->collegeID])){
                $this->oldSpecialityInfo[$this->collegeID] = array(
                    'ByImportID' => array(),
                    'ByTitle' => array()
                );
            }
            $this->oldSpecialityInfo[$this->collegeID]['ByTitle'][$oldSpecialities[$i]['Title']] = $oldSpecialities[$i];
            if($oldSpecialities[$i]['ImportID']){
                $this->oldSpecialityInfo[$this->collegeID]['ByImportID'][$oldSpecialities[$i]['ImportID']] = $oldSpecialities[$i];
            }
        }

        $this->stmt->Execute('DELETE spec
            FROM `college_speciality` AS spec
            WHERE spec.`CollegeID`='.intval($this->collegeID));
    }

    public function reInitSpecImportIDs(){
        $this->importIdToSpecId = $this->stmt->FetchIndexedAssocList(
            "SELECT `ImportID`, `CollegeSpecialityID` FROM `college_speciality` WHERE `ImportID`<>''",
            'ImportID'
        );
    }

	public function uniqStaticPath(){
		if ($result = $this->stmt->FetchList("SELECT CollegeID, GROUP_CONCAT(CollegeSpecialityID) AS SpecialityIds, StaticPath FROM college_speciality GROUP BY CollegeID, StaticPath HAVING COUNT(*) > 1")){
			foreach ($result as $key => $item) {
				$specialties = explode(',', $item['SpecialityIds']);
				foreach ($specialties as $index => $specID) {
					if ($index > 0){
						$staticPath = $item['StaticPath'] . '-' . $index;
						$query = "UPDATE college_speciality
							  SET StaticPath = " . \Connection::GetSQLString($staticPath)
							. " WHERE CollegeSpecialityID = " . intval($specID);
						if (!$this->stmt->Execute($query)){
							echo $query;
							return false;
						}
					}

				}
			}
		}
	}

    public function customCoordinateUpdate($row, \Statement  $stmt, $counter, $i){
        $query = "SELECT Latitude" . $i . " AS Latitude, Longitude" . $i . " AS Longitude FROM college_speciality WHERE Address" . $i . "='".$row['Address']."' AND LENGTH(Latitude" . $i . ") > 0 LIMIT 1";
        $coordinates = $stmt->FetchRow($query);
        if ($coordinates) {
            $query = "UPDATE college_speciality SET `Latitude" . $i . "`='".$coordinates['Latitude']."', `Longitude" . $i . "`='".$coordinates['Longitude']."' WHERE `CollegeSpecialityID` = ".$row['CollegeSpecialityID'];
            $stmt->Execute($query);
            echo "Специальность колледжа с ID=".$row['CollegeSpecialityID']." Обновлена из имеющихся координат в БД ".$counter."<br>";
        } else {
            $location = Tools\Location::getCoordinateByAddress($row['Address']);
            $lat = $location->latitude;
            $long = $location->longitude;
            $query = "UPDATE college_speciality SET `Latitude" . $i . "`='".$lat."', `Longitude" . $i . "`='".$long."' WHERE `CollegeSpecialityID` = ".$row['CollegeSpecialityID'];
            $stmt->Execute($query);
            echo "Специальность колледжа с ID=".$row['CollegeSpecialityID']." Обновлена по запросу ".$counter."<br>";
        }
    }
}