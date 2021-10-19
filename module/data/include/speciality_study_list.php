<?php

class SpecialityStudy extends LocalObjectList {
	//const FULL_STUD = 'Full';
	//const PART_STUD = 'Part';
	//const EXTRAMURAL_STUD = 'Extramural';

	public $studyList;
	protected $module;
	protected $stmt;
	protected static $typeList;

	public function __construct($module = 'data'){
		parent::LocalObjectList();
		$this->module = $module;
		$this->stmt = GetStatement();
	}

//List methods
	public function loadBySpecialityID($id){
		$where = array('stud.SpecialityID = ' . intval($id));
		$join = array();

		/*if ($year !== null){
			$where[] = 'stud.Year <= ' . intval($year);
		}*/

		$query = "SELECT * 
				FROM data_speciality_study AS stud
        		".(!empty($join) ? implode(' ', $join) : '')."
        		".((count($where) > 0)?"WHERE ".implode(" AND ", $where):"") . "
        		ORDER BY Year DESC";

		$this->LoadFromSQL($query);

		//Group by year
		foreach ($this->_items as $key => $item) {
			$this->studyList[$item['Year']][$item['Type']] = $item;
		}

		//echo $query;
		return true;
	}

	/**
	 * FOR GROUP this->studyList
	 * @param null $year
	 * @param bool $addOld
	 * @return array|bool
	 */
	public function getItemNamesByYear($year = null, $addOld = false){
		$max_year = max(array_keys($this->studyList));
		$min_year = min(array_keys($this->studyList));
		$result = array();

		if ($year > 0){
			if (!is_numeric($year)){
				$this->AddError('year-not-numerical');
				return false;
			}
		}
		else{
            $year = $max_year;

            if ($max_year > DATA_SPECIALITY_STUDY_CURRENT_YEAR){
                $year = DATA_SPECIALITY_STUDY_CURRENT_YEAR;
            }
		}

		foreach (self::getTypes() as $typeIndex => $type) {
			if ( !empty($this->studyList[$year][$type]) ){
				foreach ($this->studyList[$year][$type] as $key => $field) {
					$currentYear = '';
					if ( $addOld == true && empty($field) ){
						$currentYear = $year;
						while ( empty($field) && $currentYear > $min_year){
							$currentYear--;
							if (isset($this->studyList[$currentYear][$type][$key])){
								$field = $this->studyList[$currentYear][$type][$key];
							}
						}
					}

					//$result[$type . $key] = $field;
					if ( !empty($field) ){
						$result[$type . $key] = $field;

						if ( !empty($currentYear) ){
							$result[$type . $key . 'OldYear'] = $currentYear;
						}
					}
				}
				//$result[$type . 'Block'] = 1;
			}
		}

		$result['StudyYear'] = $year;
		return $result;
	}

	public function saveForSpeciality($specialityID, $studyList){
        $query = "DELETE FROM data_speciality_study WHERE SpecialityID = " . intval($specialityID);
	    if (!$this->stmt->Execute($query)){
	        return false;
        }

        foreach ($studyList as $index => $study) {
            $query = "INSERT INTO data_speciality_study SET
                          SpecialityID = " . intval($specialityID) . ",
                          Year = " . Connection::GetSQLString($study['Year']) . ",
                          Type = " . Connection::GetSQLString($study['Type']) . ",
                          BudgetScopeWave1 = " . Connection::GetSQLString($study['BudgetScopeWave1']) . ",
                          BudgetScopeWave2 = " . Connection::GetSQLString($study['BudgetScopeWave2']) . ",
                          PaidScope = " . Connection::GetSQLString($study['PaidScope']) . ",
                          BudgetCount = " . Connection::GetSQLString($study['BudgetCount']) . ",
                          PaidCount = " . Connection::GetSQLString($study['PaidCount']) . ",
                          BudgetCompetition = " . Connection::GetSQLString($study['BudgetCompetition']) . ",
                          PaidCompetition = " . Connection::GetSQLString($study['PaidCompetition']) . ",
                          Period = " . Connection::GetSQLString($study['Period']) . ",
                          PaidPrice = " . Connection::GetSQLString($study['PaidPrice']);

            $this->stmt->Execute($query);
	    }
    }

	/**
	 * FOR GROUP this->studyList
	 * @param array $selected
	 * @return array
	 */
	public function getItemsYearList($selected = array()){
		$result = array();
		foreach (array_keys($this->studyList) as $index => $item) {
			$result[$index]['Year'] = $item;
			$result[$index]['Name'] = $item . '/' .  (substr($item, 2) + 1);

			if (!empty($selected) && in_array($item, $selected)){
				$result[$index]['Selected'] = 1;
			}
		}

		return $result;
	}


//Service
	public static function getTypes($list = false, $selected = array()){
		$stmt = GetStatement();
		if (empty(self::$typeList)){
            self::$typeList = $stmt->FetchRows("SELECT DISTINCT Type FROM data_speciality_study ORDER BY Type");
        }

        $result = self::$typeList;

        if ($list == true){
            foreach ($result as $key => $item) {
                $types[$key]['Title'] = $item;
                if (in_array($item, $selected)){
                    $types[$key]['Selected'] = 1;
                }
            }
            return $types;
        }

        return $result;
	}
}