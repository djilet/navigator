<?php
namespace Import;

class CollegeImport extends BaseImport{
	protected $map = [
		'ImportID'               => 0, //id колледжа
		'District'          	 => 1, //Федеральный округ - NULL
		'Region' 				 => 2, //Регион
		'City'					 => 3, //Город
		'Title'                  => 4, //Название ВУЗа
		'Type'            		 => 5, //Тип колледжа
		'AtUniversity'           => 6, //Колледж при вузе
		'CollegeBigDirectionID'  => 7, //Направление колледжа
		'Website'                => 8, //Официальный сайт
		'Address'    			 => 9, //Адрес головного корпуса
		'PhoneSelectionCommittee'=> 10, //Телефон приемной комиссии
		'AccreditationValidity'  => 11, //Срок действия аккредитации
		'Achievements'           => 12, //Достижения колледжа, факты и цифры
		'Hostel' 				 => 13, //Общежитие
		'HostelPrice'            => 14, //Стоимость общежития (минимальная в месяц)
		'Scholarship'            => 15, //Государственная академическая стипендия (мес.)
		'ScholarshipSocial'      => 16, //Стипендия по социальным льготам
		'VideoURL'				 => 17, //Ссылка на ютуб-ролик о колледже

		'WORLDSKILLS HI-TECH 2017' 			 => 18,  //WORLDSKILLS HI-TECH 2017
		'DIGITALSKILLS 2017'				 => 19, //DIGITALSKILLS 2017
		'EUROSKILLS 2018'				 => 20, //EUROSKILLS 2018
		'ТОП-100'				 => 21, //ТОП-100
	];

	private $importId_collegeID;
	private $region;
	private $imageDir;
	private $collegeBigDirection;
	protected $awards;
	protected $awards_titles = array('WORLDSKILLS HI-TECH 2017', 'DIGITALSKILLS 2017', 'EUROSKILLS 2018', 'ТОП-100');

	public function __construct(){
		parent::__construct();

		$this->imageDir = PROJECT_DIR.'import/source/college/';
		$this->region = new Tools\Region($this->stmt);
		$this->collegeBigDirection = new Tools\CollegeBigDirection($this->stmt);
		$this->awards = new Tools\CollegeAwards($this->stmt);

		$this->importId_collegeID = $this->stmt->FetchIndexedAssocList(
			"SELECT `ImportID`, `CollegeID` FROM `college_college` WHERE `ImportID`<>''",
			'ImportID'
		);
	}

	public function findCollegeByImportID($importId){
		if (empty($importId)) {
			return 0;
		}

		if (!isset($this->importId_collegeID[$importId])) {
			return 0;
		}

		return $this->importId_collegeID[$importId]['CollegeID'];
	}

	public function findCollegeByTitle($title){
		$id = $this->stmt->FetchField(
			'SELECT CollegeID FROM `college_college` 
                WHERE `Title`=' . \Connection::GetSQLString($title));
		if ($id) {
			return intval($id);
		}

		return 0;
	}

	public function insert(){

	}

	public function update($id = null){
		$regionId = $this->region->getId(
			$this->value('Region'),
			$this->value('District')
		);
		$collegeBigDirectionID = $this->collegeBigDirection->getId($this->value('CollegeBigDirectionID','self::prepareOnlyText'));
		$location = Tools\Location::getCoordinateByAddress($this->value('Address'));

		$this->prepare();

		if ($id !== null){
			$op = "UPDATE `college_college` SET";
			$where =  " WHERE CollegeID = " . intval($id);
		}
		else{
			$op = "INSERT INTO `college_college` SET";
			$where = '';
		}

		$staticPath = RuToStaticPath($this->field('Title'));
		if (strlen($staticPath) > 150){
			$staticPath = substr($staticPath, 0, 150);
		}

		$query = $op . "
				  `ImportID` = " . $this->field('ImportID') . ",
				  `Title` = " . $this->field('Title') . ",
				  `StaticPath` = " . \Connection::GetSQLString($staticPath) . ",
				  `RegionID` = " . intval($regionId) . ",
				  `City` = " . $this->field('City') . ",
				  `Type` = " . \Connection::GetSQLString('State') . ",
				  `AtUniversity` = " . $this->field('AtUniversity', 'self::parseEnumTF') . ",
				  `CollegeBigDirectionID` = " . intval($collegeBigDirectionID) . ",
				  `Website` = " . $this->field('Website') . ",
				  `Address` = " . $this->field('Address') . ",
				  `Latitude` = " . \Connection::GetSQLString($location->latitude) . ",
				  `Longitude` = " . \Connection::GetSQLString($location->longitude) . ",
				  `PhoneSelectionCommittee` = " . $this->field('PhoneSelectionCommittee') . ",
				  `AccreditationValidity` = " . $this->field('AccreditationValidity') . ",
				  `Achievements` = " . $this->field('Achievements') . ",
				  `Hostel` = " . $this->field('Hostel') . ",
				  `HostelPrice` = " . $this->field('HostelPrice') . ",
				  `Scholarship` = " . $this->field('Scholarship') . ",
				  `ScholarshipSocial` = " . $this->field('ScholarshipSocial') . ",
				  `VideoURL` = " . $this->field('VideoURL') . $where;
		if ($this->stmt->Execute($query)) {
			if ($id == null){
				$id = $this->stmt->GetLastInsertID();
			}
			$this->saveImage($id, $this->value('ImportID'));
			$this->saveLogo($id, $this->value('ImportID'));
			$this->saveAwards($id);
		}
	}

	/**
	 * Save image
	 * @param $id //collegeID
	 * @param $value //ImportID
	 */
	private function saveImage($id, $value){
		$rows = $this->stmt->FetchList('SELECT * FROM `college_image` WHERE CollegeID='.intval($id));

		$i = 0;
		while (file_exists($this->imageDir.$value.'_'.(++$i).'.jpg')) {
			$filepath =  $this->imageDir.$value.'_'.$i.'.jpg';

			if (isset($rows[$i-1])) {
				copy($filepath, COLLEGE_IMAGE_DIR.$rows[$i-1]['ItemImage']);
			} else {
				$fileSys = new \FileSys();

				$filename = $fileSys->GenerateUniqueName(COLLEGE_IMAGE_DIR, 'jpg');
				$fileSys->Move($filepath, COLLEGE_IMAGE_DIR.$filename);

				$this->stmt->Execute('INSERT INTO `college_image` SET 
                    `ItemImage`='.\Connection::GetSQLString($filename).',
                    `CollegeID`='.intval($id).',
                    `SortOrder`='.$i);
			}
		}

		if ($i < count($rows)) {
			while (isset($rows[$i-1])) {

				if (file_exists(COLLEGE_IMAGE_DIR.$rows[$i-1]['ItemImage'])) {
					@unlink(COLLEGE_IMAGE_DIR.$rows[$i-1]['ItemImage']);
				}

				$this->stmt->Execute('DELETE FROM `college_image` WHERE ImageID='.$rows[$i-1]['ImageID']);

				++$i;
			}
		}
	}

	/**
	 * @param $id
	 * @param $value (ImportID)
	 * @return bool
	 */
	protected function saveLogo($id, $value){
		$logo = $this->stmt->FetchField('SELECT CollegeLogo FROM `college_college` WHERE CollegeID='.intval($id));
		$types = array('jpg', 'png');

		foreach ($types as $index => $type) {
			if (file_exists($this->imageDir . 'logotypes/' . $value . '.' . $type)){
				$filePath = $this->imageDir . 'logotypes/' . $value . '.' . $type;
				$extension = $type;
				break;
			}
		}
		if (!isset($filePath)){
			return false;
		}

		if (!empty($logo)){
			copy($filePath, COLLEGE_IMAGE_DIR . $logo);
		}
		else{
			$fileSys = new \FileSys();

			$logo = $fileSys->GenerateUniqueName(COLLEGE_IMAGE_DIR, $extension);
			$fileSys->Move($filePath, COLLEGE_IMAGE_DIR . $logo);
		}

		if (file_exists(COLLEGE_IMAGE_DIR . $logo)){
			$info = @getimagesize(COLLEGE_IMAGE_DIR . $logo);
			$image['Width'] = $info[0];
			$image['Height'] = $info[1];

			$query = 'UPDATE college_college SET 
                    `CollegeLogo`= '.\Connection::GetSQLString($logo).',
                    `CollegeLogoConfig` = ' . \Connection::GetSQLString(json_encode($image)) . '
                    WHERE CollegeID = ' . intval($id);
			$this->stmt->Execute($query);
		}
	}

	protected function saveAwards($collegeId){
        foreach ($this->awards_titles as $key => $item) {
            if ( !empty($this->field($item,false, false))) {
                $id = $this->awards->getId($item);
                $awardsIds[] = '(' . $collegeId . ',' . $id . ')';
            }
		}
		$this->stmt->Execute("DELETE FROM college_college2award WHERE CollegeID = " . intval($collegeId));
        $query = "INSERT INTO college_college2award (CollegeID, AwardsID) VALUES " . implode(',', $awardsIds);
        $this->stmt->Execute($query);
	}

	protected function prepare(){
		//Type
		switch (mb_strtolower($this->row[$this->map['Type']])){
			case "государственный":
				$this->row[$this->map['Type']] = 'State';
				break;
			case "негосударственный":
				$this->row[$this->map['Type']] = 'NotState';
				break;
			default:
				break;
		}
	}
}