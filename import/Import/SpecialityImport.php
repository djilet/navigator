<?php
/**
 * Date:    26.12.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace Import;

class SpecialityImport extends BaseImport
{
    private $importIdToUniverId;
    private $univerID;
    private $direction;
    private $achievement;
    private $subjects;

    private $oldSpecialityInfo = array();

    protected $map = [
		'Город' => 0,
        'ИдВуза' => 1,
		'АббревиатураВуза' => 2,
		'Направление' => 3,
		'НаправлениеПодготовки' => 4,
        'ИдСпециальности' => 5,
        'Наименование' => 6,

        'СодержаниеПрограммы' => 47,
        'ВариантыПрофРазвития' => 48,
        'Адрес' => 49,

		'МинМат' => 50,
		'МинРус' => 51,
		'МинОбщ' => 52,
		'МинФиз' => 53,
		'МинБио' => 54,
		'МинИст' => 55,
		'МинИнЯз' => 56,
		'МинХим' => 57,
		'МинЛит' => 58,
		'МинИнф' => 59,
		'МинГео' => 60,

        'Мат' => 61,
        'Рус' => 62,
        'Общ' => 63,
        'Физ' => 64,
        'Био' => 65,
        'Ист' => 66,
        'ИнЯз' => 67,
        'Хим' => 68,
        'Лит' => 69,
        'Инф' => 70,
        'Гео' => 71,

        'КоличествоПредметовПоВыбору' => 72,

        'дви1' => 73,
        'дви2' => 74,
        'дви3' => 75,
        'дви4' => 76,
        'дви5' => 77,
        'дви6' => 78,
        'дви7' => 79,

		'ЗолотаяМедаль' => 80,
		'ЗначокГто' => 81,
		'ПеречневаяОлимпиада' => 82,
		'Волонтерство' => 83,
		'ИтоговоеСочинение' => 84,
    ];

    // обновляем только 2020 и 2021 года
    protected $studyDelete = '(2021, 2020)';

	protected $studyMap = array(
        '2021' => [
                    'Full'=>		['from' => 9, 'to' => 17],
                    'Part'=>		['from' => 18, 'to' => 22],
                    'Extramural'=>	['from' => 23, 'to' => 27],
        ],
        '2020' => [
                    'Full'=>		['from' => 28, 'to' => 36],
                    'Part'=>		['from' => 37, 'to' => 41],
                    'Extramural'=>	['from' => 42, 'to' => 46],
        ],
		/*'2019' => [
					'Full'=>		['from' => 28, 'to' => 36],
					'Part'=>		['from' => 37, 'to' => 41],
					'Extramural'=>	['from' => 42, 'to' => 46],
		],
		'2018' => [
					'Full'=>		['from' => 47, 'to' => 55],
					'Part'=>		['from' => 56, 'to' => 60],
					'Extramural'=>	['from' => 61, 'to' => 65],
		],
		'2017' => [
					'Full'=>		['from' => 66, 'to' => 74],
					'Part'=>		['from' => 75, 'to' => 79],
					'Extramural'=>	['from' => 80, 'to' => 84],
		],
        '2016' => [
            'Full'=>		['from' => 85, 'to' => 93],
            'Part'=>		['from' => 94, 'to' => 98],
            'Extramural'=>	['from' => 99, 'to' => 103],
        ],*/

	);

    /**
     * SpecialityImport constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->direction = new Tools\Direction($this->stmt);
        $this->achievement = new Tools\Achievements($this->stmt);

        $this->importIdToUniverId = $this->stmt->FetchIndexedAssocList(
            "SELECT `ImportID`, `UniversityID` FROM `data_university` WHERE `ImportID`<>''",
            'ImportID'
        );
        $this->subjects = $this->stmt->FetchIndexedAssocList(
            "SELECT SubjectID, Title FROM `data_subject`",
            'Title'
        );
    }
    
    public function reInitSpecImportIDs()
    {

    }

    public function deleteByUniversityID()
    {
        if ($this->univerID == 0) {
            return;
        }
        
        $oldSpecialities = $this->stmt->FetchList('SELECT s.SpecialityID, s.Title, s.ImportID, s.StaticPath FROM `data_speciality` s WHERE s.`UniversityID`='.$this->univerID);
        for($i=0; $i<count($oldSpecialities); $i++) {
            if(!isset($this->oldSpecialityInfo[$this->univerID])){
                $this->oldSpecialityInfo[$this->univerID] = array(
                    'ByImportID' => array(),
                    'ByTitle' => array()
                );
            }
            $this->oldSpecialityInfo[$this->univerID]['ByTitle'][$oldSpecialities[$i]['Title']] = $oldSpecialities[$i];
            if($oldSpecialities[$i]['ImportID']){
                $this->oldSpecialityInfo[$this->univerID]['ByImportID'][$oldSpecialities[$i]['ImportID']] = $oldSpecialities[$i];
            }
        }
      
        $this->stmt->Execute('DELETE s,e
            FROM `data_speciality` AS s
            LEFT JOIN `data_ege` AS e ON s.SpecialityID=e.SpecialityID
            WHERE s.`UniversityID`='.intval($this->univerID));
    }

    public function insert()
    {
        $directionId = $this->direction->getId($this->value('НаправлениеПодготовки', [$this, 'rmDoubleSpace']), $this->value('Направление', [$this, 'rmDoubleSpace']));
        
        $oldInfo = null;
        $statusInfo = 'new';
        $statusError="";
        $importID = $this->field('ИдСпециальности', 'intval', false);
        $specialityTitle = $this->field('Наименование', null, false);

        if(isset($this->oldSpecialityInfo[$this->univerID]['ByImportID'][$importID])){
            $oldInfo = $this->oldSpecialityInfo[$this->univerID]['ByImportID'][$importID];
            $statusInfo = 'byID';
        }
        else if($importID < 1 && isset($this->oldSpecialityInfo[$this->univerID]['ByTitle'][$specialityTitle])){
            $oldInfo = $this->oldSpecialityInfo[$this->univerID]['ByTitle'][$specialityTitle];
            $statusInfo = 'byTitle';
        }
        
        $specialityID = null;
        $staticPath = RuToStaticPath($this->field('Наименование'));
        if($oldInfo){
            $specialityID = $oldInfo['SpecialityID'];
            $staticPath = $oldInfo['StaticPath'];
        }

        $query = "INSERT INTO `data_speciality` SET ";
        if($specialityID != null){
            $query .= "`SpecialityID` = ".intval($specialityID).", ";
        }
        $query .= "`UniversityID` = {$this->univerID},
                      `DirectionID` = ".intval($directionId).",
                            `Title` = ".$this->field('Наименование').",
                            `Address` = ".$this->field('Адрес').",
                       `StaticPath` = " . \Connection::GetSQLString($staticPath) . ", 
                      `Additional1` = ".$this->field('дви1').",
                      `Additional2` = ".$this->field('дви2').",
                      `Additional3` = ".$this->field('дви3').",
                      `Additional4` = ".$this->field('дви4').",
                      `Additional5` = ".$this->field('дви5').",
                      `Additional6` = ".$this->field('дви6').",
                      `Additional7` = ".$this->field('дви7').",
                        `Score2016` = 0,
                             `Link` = '',
                          `Content` = ".$this->field('СодержаниеПрограммы').",
                         `ImportID` = ".$this->field('ИдСпециальности', 'intval').",
                         `SubjectsCountToChoose` = ".$this->field('КоличествоПредметовПоВыбору', 'intval')." ";

        $existsImportID = $this->stmt->FetchField('SELECT count(s.SpecialityID) FROM `data_speciality` s WHERE s.`ImportID`='.$importID);
        if($existsImportID > 0){
            $statusInfo .= ' - double';
        }
        else if($this->stmt->Execute($query)){
            if($specialityID == null){
                $specialityID = $this->stmt->GetLastInsertID();
            }
            $this->saveSubjects($specialityID);
			$this->saveStudy($specialityID);
			$this->saveAchievement($specialityID);
        }
        else {
            $statusInfo .= ' - error';
            $statusError = $query."<br/>";
        }
        $url = GetUrlPrefix() . "university/?specialityID={$specialityID}";
        echo "SPECIALITY; ".$statusInfo."; ImportUniversityID=".$this->value('ИдВуза')."; ImportSpecialityID=".$this->field('ИдСпециальности')."; SpecialityID=<a href='{$url}'>{$specialityID}</a>".";"."<br/>".$statusError;
    }
    
    public function update($id)
    {
        //nothing to do
    }

    public function findUniversityByImportID($importId)
    {
        if (empty($importId)) {
            return 0;
        }

        if (!isset($this->importIdToUniverId[$importId])) {
            return 0;
        }

        return $this->importIdToUniverId[$importId]['UniversityID'];
    }

    /**
     * @param mixed $univerID
     */
    public function setUniverID($univerID)
    {
        $this->univerID = $univerID;
    }

    private function saveSubjects($id)
    {
        $this->stmt->Execute('DELETE FROM data_ege WHERE SpecialityID='.$id);

        $data = [
            "Математика" => [
            	'Scope' => $this->value('МинМат', 'intval'),
            	'Type' => $this->value('Мат', 'intval')
			],
            "Русский язык" => [
				'Scope' => $this->value('МинРус', 'intval'),
				'Type' => $this->value('Рус', 'intval')
			],
            "Обществознание" => [
				'Scope' => $this->value('МинОбщ', 'intval'),
				'Type' => $this->value('Общ', 'intval')
			],
            "Физика" => [
            	'Scope' => $this->value('МинФиз', 'intval'),
            	'Type' => $this->value('Физ', 'intval')
			],
            "Биология" => [
            	'Scope' => $this->value('МинБио', 'intval'),
            	'Type' => $this->value('Био', 'intval')
			],
            "История" => [
            	'Scope' => $this->value('МинИст', 'intval'),
            	'Type' => $this->value('Ист', 'intval')
			],
            "Иностранный язык" => [
            	'Scope' => $this->value('МинИнЯз', 'intval'),
            	'Type' => $this->value('ИнЯз', 'intval')
			],
            "Химия" => [
            	'Scope' => $this->value('МинХим', 'intval'),
            	'Type' => $this->value('Хим', 'intval')
			],
            "Литература" => [
            	'Scope' => $this->value('МинЛит', 'intval'),
            	'Type' => $this->value('Лит', 'intval')
			],
            "Информатика и ИКТ" => [
            	'Scope' => $this->value('МинИнф', 'intval'),
            	'Type' => $this->value('Инф', 'intval')
			],
            "География" => [
            	'Scope' => $this->value('МинГео', 'intval'),
            	'Type' => $this->value('Гео', 'intval')
			]
        ];
        
        foreach ($data as $subject => $item) {
            if ($item['Type'] > 0) {
                if (! isset($this->subjects[$subject])) {
                    $this->stmt->Execute('INSERT INTO data_subject SET Title='.\Connection::GetSQLString($subject));
                    $this->subjects[$subject] = [
                        'SubjectID' => $this->stmt->GetLastInsertID()
                    ];
                }

                $subjectId = $this->subjects[$subject]['SubjectID'];
				$values[] = "("
					. $id . ","
					. $subjectId . ","
					. $item['Scope'] . ","
					. 0 . ","
					. ($item['Type'] == 2 ? "'Y'" : "'N'") . ","
					. ($item['Type'] == 3 ? "'Y'" : "'N'")
					. ")";
            }
        }

        if (!empty($values)){
			$query = "INSERT INTO `data_ege` (SpecialityID, SubjectID, Score, SortOrder, isProfile, byChoice) VALUES " . implode(", \n", $values);
			$this->stmt->Execute($query);
		}
    }

	protected function saveStudy($id){
		$fields = array();
		$values = array();

		foreach ($this->studyMap as $yearName => $year) {
			foreach ($year as $type => $item) {
				$init = 0;
				$data = array();
				for ($p = $item['from']; $p <= $item['to']; $p++){
					$data[] = ( !empty($this->row[$p]) ? $this->row[$p] : null );

					if ($init < 1 && !empty($this->row[$p])){
						$init = 1;
					}
				}

				if ($init > 0){
					if ($type == 'Full'){
						$fields['BudgetScopeWave1'] = $data[0];
						$fields['BudgetScopeWave2'] = $data[1];
						$fields['PaidScope'] = $data[2];
						$fields['BudgetCount'] = $data[3];
						$fields['PaidCount'] = $data[4];
						$fields['BudgetCompetition'] = $data[5];
						$fields['PaidCompetition'] = $data[6];
						$fields['Period'] = $data[7];
						$fields['PaidPrice'] = $data[8];
					}
					else{
						$fields['BudgetScopeWave1'] = $data[0];
						$fields['BudgetCount'] = $data[1];
						$fields['PaidCount'] = $data[2];
						$fields['Period'] = $data[3];
						$fields['PaidPrice'] = $data[4];

						$fields['PaidScope'] = null;
						$fields['BudgetScopeWave2'] = null;
						$fields['BudgetCompetition'] = null;
						$fields['PaidCompetition'] = null;
					}
					unset($data);

					$delete[] = "(" . intval($id) . ', ' . \Connection::GetSQLString($yearName) . ", " . \Connection::GetSQLString($type) . ")";

					$values[] = "("
						. intval($id) . ','
						. \Connection::GetSQLString($yearName) . ','
						. \Connection::GetSQLString($type) . ','
						. \Connection::GetSQLString($fields['BudgetScopeWave1']) . ','
						. \Connection::GetSQLString($fields['BudgetScopeWave2']) . ','
						. \Connection::GetSQLString($fields['PaidScope']) . ','
						. \Connection::GetSQLString($fields['BudgetCount']) . ','
						. \Connection::GetSQLString($fields['PaidCount']) . ','
						. \Connection::GetSQLString($fields['BudgetCompetition']) . ','
						. \Connection::GetSQLString($fields['PaidCompetition']) . ','
						. \Connection::GetSQLString($fields['Period']) . ','
						. \Connection::GetSQLString($fields['PaidPrice'])
						. ")";
				}

			}
		}

		if (!empty($values)){
			$queryDel = "DELETE FROM data_speciality_study WHERE Year IN " . $this->studyDelete . " AND SpecialityID = " . $id;
			$this->stmt->Execute($queryDel);

			$query = "INSERT INTO `data_speciality_study` (
					`SpecialityID`,
					`Year`,
					`Type`,
					`BudgetScopeWave1`,
					`BudgetScopeWave2`,
					`PaidScope`,
					`BudgetCount`,
					`PaidCount`,
					`BudgetCompetition`,
					`PaidCompetition`,
					`Period`,
					`PaidPrice`) VALUES
					" . implode(", \n", $values);

			if ($this->stmt->Execute($query)){
				return true;
			}
			return false;
		}
	}

	protected function saveAchievement($id){
		$values = array();

		$data = array(
				[
					'Title' => 'Золотая медаль',
					'Scope' => $this->value('ЗолотаяМедаль', 'intval')
				],
				[
					'Title' => 'Значок ГТО',
					'Scope' => $this->value('ЗначокГто', 'intval')
				],
				[
					'Title' => 'Олимпиады',
					'Scope' => $this->value('ПеречневаяОлимпиада', 'intval')
				],
				[
					'Title' => 'Волонтерство',
					'Scope' => $this->value('Волонтерство', 'intval')
				],
				[
					'Title' => 'Итоговое сочинение',
					'Scope' => $this->value('ИтоговоеСочинение', 'intval')
				],

		);

		foreach ($data as $key => $item) {
			if ($item['Scope'] > 0){
				$achievementId = $this->achievement->getId($item['Title']);
				$values[] = '(' . intval($id) . ', ' . intval($achievementId) . ', ' . $item['Scope'] . ')';
			}
		}

		if (!empty($values)){
			$this->stmt->Execute('DELETE FROM data_speciality2achievement WHERE SpecialityID = '.$id);
			$query = "INSERT INTO `data_speciality2achievement` (SpecialityID, AchievementID, Score) VALUES " . implode(", \n", $values);
			$this->stmt->Execute($query);
		}
	}

	public function uniqStaticPath(){
    	if ($result = $this->stmt->FetchList("SELECT UniversityID, GROUP_CONCAT(SpecialityID) AS SpecialityIds, StaticPath FROM data_speciality GROUP BY UniversityID, StaticPath HAVING COUNT(*) > 1")){
			foreach ($result as $key => $item) {
				$specialties = explode(',', $item['SpecialityIds']);
				foreach ($specialties as $index => $specID) {
					if ($index > 0){
						$staticPath = $item['StaticPath'] . '-' . $index;
						$query = "UPDATE data_speciality
							  SET StaticPath = " . \Connection::GetSQLString($staticPath)
							. " WHERE SpecialityID = " . intval($specID);
						if (!$this->stmt->Execute($query)){
							echo $query;
							return false;
						}
					}

				}
			}
		}
	}
//Service
    public function migrateStudy(){
		$query = "SELECT spec.SpecialityID, spec.Budget, spec.BudgetCount, spec.BudgetCompetition, spec.Contract, spec.ContractCompetition,
					spec.BudgetLast,
					spec.ContractLast,
					spec.BudgetNext,
					spec.BudgetCompetitionNext,
					spec.ContractCountNext,
					spec.Period,
					spec.Price
					FROM data_speciality AS spec
				  LEFT JOIN data_speciality_study AS stud ON spec.SpecialityID = stud.SpecialityID
				  WHERE stud.SpecialityID IS NULL";
		$result = $this->stmt->FetchList($query);

		$i = 0;
		foreach ($result as $key => $item) {
			$item['Budget'] = explode('#', $item['Budget']);
			$item['BudgetLast'] = explode('#', $item['BudgetLast']);

			$request_t = array(
				'BudgetScopeWave1' => $item['Budget'][0],
				'BudgetScopeWave2' => $item['Budget'][1],
				'PaidScope' => $item['Contract'],
				'BudgetCount' => $item['BudgetCount'],
				'BudgetCompetition' => $item['BudgetCompetition'],
				'PaidCompetition' => $item['ContractCompetition'],
				'Period' => $item['Period'],
				'PaidPrice' => $item['Price'],
			);

			$request_l = array(
				'BudgetScopeWave1' => $item['BudgetLast'][0],
				'BudgetScopeWave2' => $item['BudgetLast'][1],
				'PaidScope' => $item['ContractLast'],
			);

			$request_n = array(
				'BudgetCount' => $item['BudgetNext'],
				'BudgetCompetition' => $item['BudgetCompetitionNext'],
				'PaidCount' => $item['ContractCountNext'],
			);


			$this->saveStudyForMigrate($item['SpecialityID'],'Full',2016, new \LocalObject($request_l));
			$this->saveStudyForMigrate($item['SpecialityID'],'Full',2017, new \LocalObject($request_t));
			$this->saveStudyForMigrate($item['SpecialityID'],'Full',2018, new \LocalObject($request_n));

			$i++;
		}

		echo 'Обновлено ' . $i . ' специальностей';
	}

	protected function saveStudyForMigrate($id, $type, $year, \LocalObject $request){
		$init = 0;
		foreach ($request->GetProperties() as $key => $property) {
			if (!empty($property)){
				$init = 1;
				break;
			}
		}

		if ($init > 0){
			$this->stmt->Execute("DELETE FROM data_speciality_study WHERE SpecialityID = " . intval($id) .
				" AND Type = " . \Connection::GetSQLString($type) .
				" AND Year = " . \Connection::GetSQLString($year));


			$query = "INSERT INTO data_speciality_study SET
				  SpecialityID = " . intval($id) . ",
				  Year = " . \Connection::GetSQLString($year) . ",
				  Type = " . \Connection::GetSQLString($type) . ",
				  BudgetScopeWave1 = " . $request->GetPropertyForSQL('BudgetScopeWave1') . ",
				  BudgetScopeWave2 = " . $request->GetPropertyForSQL('BudgetScopeWave2') . ",
				  PaidScope = " . $request->GetPropertyForSQL('PaidScope') . ",
				  BudgetCount = " . $request->GetPropertyForSQL('BudgetCount') . ",
				  PaidCount = " . $request->GetPropertyForSQL('PaidCount') . ",
				  BudgetCompetition = " . $request->GetPropertyForSQL('BudgetCompetition') . ",
				  PaidCompetition = " . $request->GetPropertyForSQL('PaidCompetition') . ",
				  Period = " . $request->GetPropertyForSQL('Period') . ",
				  PaidPrice = " . $request->GetPropertyForSQL('PaidPrice');

			if (!$this->stmt->Execute($query)){
				print_r($this->stmt->_dbLink->error_list);
				return false;
			}
		}
		return true;
	}
}