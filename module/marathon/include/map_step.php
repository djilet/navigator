<?php
require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/MarathonPrepare.php");
require_once(dirname(__FILE__)."/map.php");

class MarathonMapStep extends LocalObject{
	use MarathonPrepare;

	protected $data;
	protected $step_id;
	protected $marathon_user_id;
	protected $user_answers = array();
	protected $content;
	protected $stmt;
	protected $module;

	const STATUS_AVAILABLE = "available";
	const STATUS_COMPLETED = "completed";

	public function __construct($stepID, $marathonUserID){
		parent::LocalObject();
		$this->stmt = GetStatement();
		$this->module = 'marathon';

		$this->step_id = $stepID;
		$this->marathon_user_id = $marathonUserID;

		$this->loadByID();
		return true;
	}

	public function getDataTable(){
		return $this->GetProperty('DataTable');
	}

	//Init
	public function loadByID(){
		$query = "SELECT *,
		CONCAT(" . Connection::GetSQLString(MARATHON_IMAGE_URL_PREFIX . 'map/') . ", Icon) AS ImagePath
		FROM marathon_map WHERE StepID = " . $this->step_id;
		$this->LoadFromSQL($query);

		$user_answers = $this->getAnswersOnStepForUser($this->GetProperty('StepID'));
		if (!empty($user_answers) && is_array($user_answers)){
			$this->user_answers = $user_answers;
			unset($user_answers);
		}

		//Load data
		switch ($this->GetProperty('DataTable')) {
			case 'Industry':
				$this->data = $this->loadIndustry();
				break;

			case 'Subject':
				$this->data = $this->loadSubject();
				break;

			case 'WhoWork':
				$this->data = $this->loadWhoToWork();
				break;

			case 'WantWork':
				$this->data = $this->loadWantToWork();
				break;

			case 'Profession':
				$this->data = $this->loadProfession();
				break;

			case 'Region':
				$this->data = $this->loadRegion();
				break;

			case 'Direction':
				$this->data = $this->loadDirection();
				break;

			case 'University':
				$this->data = $this->loadUniversity();
				break;
		}

		//Prepare Content
		$content = $this->prepareContentByType($this->GetProperty('Type'));

		if ( !empty($content) ){
			$this->SetProperty('Content', $content);
			return true;
		}

	}

//Load content
	public function loadIndustry(){
		require_once(dirname(__FILE__) . "/../../data/include/Industry.php");
		$industry = new Industry();
		$industry->load();
		return $industry->getItems($this->user_answers);
	}
	public function loadSubject(){
		require_once (dirname(__FILE__)."/../../data/include/public/Subject.php");
		$subject = new Subject();
		$subject->load();
		$result = array();

		//get answers
		$answers = $this->getAnswersByData( array('Direction', 'University') );
		if (!empty($answers['Direction']) && !empty($answers['University'])){
			foreach ($answers as $key => $answer) {
				if ($key == 'Direction'){
					$request['SpecialFilter']['Direction'] = $answer;
				}
				elseif ($key == 'University'){
					$request['universityID'] = $answer;
				}
			}
			$request = new LocalObject($request);

			//get speciality
			require_once (dirname(__FILE__)."/../../data/include/public/Specialities.php");
			$speciality = new Specialities($this->module);
			$speciality->load($request);
			foreach ($speciality->_items as $key => $item) {
				$speciality_id[] = $item['SpecialityID'];
			}
			$result = $subject->getItems(Subject::GetItemsOnSpeciality($speciality_id));

			foreach ($result as $index => $item) {
				if($item['Title'] == 'Математика'){
					$result[$index]['Title'] = 'Математика профильная';
					$result[$index]['Selected'] = 1;
				}
			}
			return $result;
		}
	}
	public function loadWhoToWork(){
		require_once(dirname(__FILE__) . "/../../data/include/WhoWork.php");
		$who_work = new WhoWork();
		$who_work->load();
		return $who_work->getItems($this->user_answers);
	}
	public function loadWantToWork(){
		require_once(dirname(__FILE__) . "/../../data/include/WantWork.php");
		$want_work = new WantWork();
		$want_work->load();
		return $want_work->getItems($this->user_answers);
	}
	public function loadProfession(){
		$result = array();
		$answers = $this->getAnswersByData( array('Industry', 'WhoWork', 'WantWork') );

		require_once (dirname(__FILE__)."/../../data/include/public/Professions.php");
		$professions = new Professions($this->module);
		$professions->LoadItemsInFields(new LocalObject($answers));
		foreach ($professions->getItems($this->user_answers) as $key => $item) {
			$result[$item['Industry']]['Title'] = $item['IndustryTitle'];
			$result[$item['Industry']]['Values'][] = $item;
		}
		return $result;
	}
	public function loadRegion(){
		require_once (dirname(__FILE__)."/../../data/include/public/Region.php");
		$region = new Region();
		$region->load();
		return $region->getItems($this->user_answers);
	}
	public function loadDirection(){
		$profession_answers = $this->getAnswers($this->getStepByData('Profession')['AnswerID']);

		require_once (dirname(__FILE__)."/../../data/include/public/Direction.php");
		$direction = new Direction();
		$direction->LoadItemsOnProfessions($profession_answers);
		return $direction->getItems($this->user_answers);
	}
	public function loadUniversity(){
		$request = array();

		$answers = $this->getAnswersByData( array('Direction', 'Region') );
		foreach ($answers as $key => $answer) {
			if ($key == 'Direction'){
				$request['UniverFilter']['Direction'] = $answer;
			}
			elseif ($key == 'Region'){
				$request['UniverFilter']['Region'] = $answer;
			}
		}
		$request = new LocalObject($request);

		require_once (dirname(__FILE__)."/../../data/include/public/University.php");
		$university = new University($this->module);
		$university->load($request);

		foreach ($university->_items as $key => $item) {
			if (!empty($item['SpecialityList'])){
				foreach ($item['SpecialityList'] as $index => $speciality) {
					//TODO init other years
					$budget = '';
					if ( !empty($speciality['BudgetNext']) ){
						$budget = $speciality['BudgetNext'];
					}

					if ( intval($budget) > 1 ){
						$university->_items[$key]['SpecialityList'][$index]['BudgetNext'] = $budget;
					}
					elseif($budget !== '0'){
						$university->_items[$key]['SpecialityList'][$index]['BudgetText'] = $budget;
					}

					if ( !empty($speciality['BudgetCountNext']) ){
						if ( intval($speciality['BudgetCountNext']) < 0){
							unset($university->_items[$key]['SpecialityList'][$index]['BudgetCountNext']);
						}
					}
				}
			}
			if(in_array($item['UniversityID'], $this->user_answers)){
				$university->_items[$key]['Selected'] = 1;
			}
			if ( !empty($item['ShortTitle']) ){
				$university->_items[$key]['Title'] = $item['Title'] . ' (' . $item['ShortTitle'] . ')';
			}
		}
		return $university->_items;
	}

//Service
	public function prepareToUniversityList(){
		return $this->data;
	}

	public function getAnswersOnStepForUser($step_id){
		if( $this->getStepForUser($step_id) ){
			return $this->getAnswers( $this->getAnswerIdOnStepForUser($step_id) );
		}
		return false;
	}

	public static function validateAnswers(Array $answers){
		if (empty($answers)){
			return false;
		}
		return true;
	}
	public function getAnswersByData($data_tables){
		$answers = array();

		if (is_array($data_tables)){
			foreach ($data_tables as $key => $item) {
				$data = $this->getStepByData($item);
				if ( !empty($data['AnswerID']) ){
					$answers[$item] = $this->getAnswers($data['AnswerID']);
				}
			}
			return $answers;
		}
	}

//Validate
	public function validate(){
		//print_r($this);
	    if($this->GetProperty('XP') > MarathonMap::getUserXP($this->marathon_user_id)){
			return false;
		}
		elseif ( $this->GetProperty('SortOrder') > (int) MarathonMap::getLastCompleted($this->marathon_user_id)['SortOrder'] + 1){
			return false;
		}
		else{
			return true;
		}
	}

//Get
	public function getStepForUser($step_id){
		$query = "SELECT * FROM marathon_map2user WHERE MarathonUserID = " . $this->marathon_user_id . " AND StepID = " . $step_id;
		if($result = $this->stmt->FetchRow($query)){
			return $result;
		}
		else{
			return false;
		}
	}
	public function getStepByData($data_table){
		$query = "SELECT StepID FROM marathon_map WHERE DataTable = " . connection::GetSQLString($data_table);

		if($step_id = $this->stmt->FetchField($query)){
			return $this->getStepForUser($step_id);
		}
		else{
			return false;
		}
	}

	public function getDependentSteps($steps_id = null){
		$result = array();
		if ($steps_id == null){
			$steps_id = array($this->step_id);
		}

		while($temp = $this->getDependSteps($steps_id)){
			foreach ($temp as $index => $item) {
				$result[] = $item;
			}
			$steps_id = $temp;
		}
		return $result;
	}
		//service
		public function getDependSteps($steps_id){
			$query = "SELECT StepID FROM marathon_map_depends WHERE DependsStep IN (" . implode(',', Connection::GetSQLArray($steps_id)) . ")";
			if($steps = $this->stmt->FetchRows($query)){
				return $steps;
			}
			else{
				return false;
			}
		}

	public function cleanStepForUser($answer_id){
		$query = "UPDATE marathon_map2user SET Status=" . connection::GetSQLString(self::STATUS_AVAILABLE) . " WHERE AnswerID=" . intval($answer_id);
		if( $this->stmt->Execute($query) ){
			$this->deleteAnswers($answer_id);
		}
	}

	public function getAnswerIdOnStepForUser($step_id){
		$query = "SELECT AnswerID FROM marathon_map2user WHERE StepID = " . $step_id . " AND MarathonUserID = " . $this->marathon_user_id;
		if($answer_id = $this->stmt->FetchField($query)){
			return $answer_id;
		}
		else{
			return false;
		}
	}
	//for delete
	public function getAnswersIdsOnStepsForUser($steps_id){
		$query = "SELECT AnswerID FROM marathon_map2user WHERE StepID IN (
		" . implode(',', Connection::GetSQLArray($steps_id)) . ") AND MarathonUserID = " . $this->marathon_user_id . " ORDER BY StepID ASC";
		if($answers = $this->stmt->FetchRows($query)){
			return $answers;
		}
		else{
			return false;
		}
	}
	public function getAnswers($AnswerID){
		$query = "SELECT ValueID FROM marathon_map2user_answer WHERE AnswerID IN (" . connection::GetSQLString($AnswerID) . ")";

		if($answers = $this->stmt->FetchRows($query)){
			return $answers;
		}
		else{
			return false;
		}
	}

//CRUD
	public function addStepForUser(){
	    if ( !$this->getAnswersIdsOnStepsForUser(array($this->step_id)) ){
	        $query = "INSERT INTO marathon_map2user (MarathonUserID, StepID, Status)
	           VALUES (" . $this->marathon_user_id . ", " . $this->step_id . ", " . connection::GetSQLString(self::STATUS_AVAILABLE) . ")";
	        $this->stmt->Execute($query);
	    }
	}
	
	public function saveStepForUser(Array $answers){
	    $answer_id = null;
		if ($result = $this->getStepForUser($this->step_id)){
			$answer_id = $this->getAnswerIdOnStepForUser($this->step_id);
			$this->deleteAnswers($answer_id);
		}
		else {
			$query = "INSERT INTO marathon_map2user (MarathonUserID, StepID, Status)
				    VALUES (" . $this->marathon_user_id . ", " . $this->step_id . ", " . connection::GetSQLString(self::STATUS_AVAILABLE) . ")";
			if( $this->stmt->Execute($query) ){
				$answer_id = $this->stmt->_lastInsertID;
			}
		}

		if($answer_id){
		    $query = "UPDATE marathon_map2user SET Status=" . connection::GetSQLString(self::STATUS_COMPLETED) . " WHERE AnswerID=".$answer_id;
			if( $this->stmt->Execute($query) ){
			    $this->saveAnswers($answer_id, $answers);
		    }
		}
	}

	public function skipStepForUser(){
		$answer_id = $this->getAnswerIdOnStepForUser($this->step_id);
		$query = "UPDATE marathon_map2user SET Status= " . connection::GetSQLString(self::STATUS_COMPLETED) . " WHERE AnswerID = " . $answer_id;
		if( $this->stmt->Execute($query) ){
			return true;
		}
	}
	
	public function saveAnswers($answer_id, $answers){
		$query_answers = '';
		foreach ($answers as $key => $answer){
			$query_answers .= '(' . $answer_id . ', ' . $answer . ')';
			if (count($answers)-1 > $key){
				$query_answers .= ', ';
			}
		}
		$query = "INSERT INTO marathon_map2user_answer (AnswerID, ValueID) VALUES " . $query_answers;
		if( $this->stmt->Execute($query) ){
			return true;
		}
	}
	public function deleteAnswers($answer_id){
	    $query = "DELETE FROM marathon_map2user_answer WHERE AnswerID=".$answer_id;
		if( !$this->stmt->Execute($query) ){
			//print_r($this->stmt);
		}
	}
}