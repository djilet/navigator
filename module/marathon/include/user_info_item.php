<?php
require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/MarathonPrepare.php");

class UserInfoItem extends LocalObject{
	use MarathonPrepare;
	protected $name;
	protected $data;
	protected $data_table;
	protected $values;
	protected $stmt;
	protected $marathon_user_id;
	protected static $items = array('Welcome', 'Industry','Region', 'University', 'Subject');

	public function __construct($item, $marathon_user_id){
		parent::LocalObject();
		$this->stmt = GetStatement();

		$this->name = $item;
		$this->marathon_user_id = $marathon_user_id;
		$this->SetProperty('ItemName',$this->name);
	}

//Object get
	public function getDataTable(){
		return $this->data_table;
	}
	public function getValues(){
		return $this->values;
	}

	public function load(){
		$this->values = $this->getUserInfoValuesByItem($this->name);

		switch ($this->name) {
			case 'Welcome':
				$this->SetProperty('Type','Text');
				$this->SetProperty('Title','Поздравляем!');
				break;

			case 'Industry':
				$this->SetProperty('Type','Checkbox');
				$this->SetProperty('Title','Какие сферы деятельности тебе интересны?');
				$this->data_table = 'Industry';
				require_once(dirname(__FILE__) . "/../../data/include/Industry.php");
				$industry = new Industry();
				$industry->load();
				$this->data = $industry->getItems($this->values);
				break;

			case 'Subject':
				$this->SetProperty('Type','Checkbox');
				$this->SetProperty('Title','Какие предметы скорее всего будешь сдавать?');
				$this->data_table = 'Subject';

				require_once (dirname(__FILE__)."/../../data/include/public/Subject.php");
				$subject = new Subject();
				$subject->load('SortOrder');
				$this->data = $subject->getItems(array(2));

				foreach ($this->data as $index => $item) {
					if ($item['Title'] == 'Математика'){
						$this->data[$index]['Title'] = 'Математика профильная';
						break;
					}
				}
				break;

			case 'Region':
				$this->SetProperty('Type','SearchList');
				$this->SetProperty('Title','В какие регионы планируешь поступать?');
				$this->SetProperty('MaxSelectCount','5');
				$this->data_table = 'Region';

				require_once (dirname(__FILE__)."/../../data/include/public/Region.php");
				$region = new Region();
				$region->load();
				$this->data = $region->getItems($this->values);
				break;

			case 'University':
				$this->SetProperty('Type','SearchList');
				$this->SetProperty('Title','В какие вузы планируешь поступать?');
				$this->SetProperty('MaxSelectCount','5');
				$this->SetProperty('CanSkip', true);
				$this->data_table = 'University';

				$request['UniverFilter']['Region'] = $this->getUserInfoValuesByItem('Region');
				require_once (dirname(__FILE__)."/../../data/include/public/University.php");
				$university = new University('data');
				$university->load(new LocalObject($request), 0);
				$this->data = $university->getItems_nameConcatShortTitle($this->values);
				break;
		}

		$content = $this->prepareContentByType($this->GetProperty('Type'));
		unset($this->data);
		if ( !empty($content) ){
			$this->SetProperty('Content', $content);
			return true;
		}
	}


//Get
	public function getUserInfoValuesByItem($item){
		return self::getUserInfoValuesByInfoID(self::getUserInfoIDByItem($this->marathon_user_id, $item));
	}

	//Service
	public static function getUserInfoIDByItem($marathon_user_id ,$item){
		$stmt = GetStatement();
		$query = "SELECT InfoID FROM marathon_user_info WHERE MarathonUserID = 
		" . intval($marathon_user_id) . " AND Item = " . Connection::GetSQLString($item);
		if($result = $stmt->FetchField($query)) {
			return $result;
		}
		else{
			return false;
		}
	}

	public static function getUserInfoValuesByInfoID($info_id){
		$stmt = GetStatement();
		$query = "SELECT Value FROM marathon_user_info_values WHERE InfoID = " . intval($info_id);
		if($result = $stmt->FetchRows($query)) {
			return $result;
		}
		else{
			return false;
		}
	}


//CRUD
	public function saveInfo($answers, $skip = false){
		if ($skip === false){
			if ( empty($answers) || !is_array($answers) ){
				return false;
			}
		}

		if ( $info_id = self::getUserInfoIDByItem($this->marathon_user_id, $this->name) ){
			$this->deleteInfoByID($info_id);
		}
		$query = "INSERT INTO marathon_user_info (MarathonUserID, Item) VALUES ("
			. intval($this->marathon_user_id) . ", " . Connection::GetSQLString($this->name) . ")";
		if( $this->stmt->Execute($query) ){

			if ($skip === true){
				return true;
			}
			else{
				if($this->saveValues($this->stmt->GetLastInsertID(), $answers)){
					return true;
				}
				else{
					$this->deleteInfoByID($this->stmt->GetLastInsertID());
				}
			}
		}
		return false;
	}

	//Service
	public function saveValues($info_id, $answers){
		$query_answers = '';
		foreach ($answers as $key => $answer){
			$query_answers .= '(' . $info_id . ', ' . $answer . ')';
			if (count($answers)-1 > $key){
				$query_answers .= ', ';
			}
		}
		$query = "INSERT INTO marathon_user_info_values (InfoID, Value) VALUES " . $query_answers;
		if( $this->stmt->Execute($query) ){
			return true;
		}
		return false;
	}

	public function deleteInfoByID($id){
		$query = "DELETE FROM marathon_user_info WHERE InfoID = " . $id;
		if( $this->stmt->Execute($query) ){
			$query = "DELETE FROM marathon_user_info_values WHERE InfoID = " . $id;
			if( $this->stmt->Execute($query) ){
				return true;
			}
		}
		return false;
	}

	//Static
	public static function GetItemsName(){
		return self::$items;
	}
	
	//For user list
	public function loadForList($marathonUserIDs){
	    $join = array();
	    $value = null;
	    $join[] = "LEFT JOIN marathon_user_info_values v ON ui.InfoID=v.InfoID";
	    
	    switch ($this->name) {
	        case 'Industry':
	            $value = "GROUP_CONCAT(t.IndustryTitle separator ', ')";
	            $join[] = "LEFT JOIN data_profession_industry t ON v.Value=t.IndustryID";
	            break;
	        case 'Subject':
	            $value = "GROUP_CONCAT(t.Title separator ', ')";
	            $join[] = "LEFT JOIN data_subject t ON v.Value=t.SubjectID";
	            break;
	        case 'Region':
	            $value = "GROUP_CONCAT(t.Title separator ', ')";
	            $join[] = "LEFT JOIN data_region t ON v.Value=t.RegionID";
	            break;
	        case 'University':
	            $value = "GROUP_CONCAT(t.ShortTitle separator ', ')";
	            $join[] = "LEFT JOIN data_university t ON v.Value=t.UniversityID";
	            break;
	    }
	    
	    $query = "SELECT ui.MarathonUserID, ".$value." AS Value
            FROM marathon_user_info ui
            ".(!empty($join) ? implode(" \n ", $join) : '')."
            WHERE ui.Item=".Connection::GetSQLString($this->name)." AND ui.MarathonUserID IN (".implode(", ", Connection::GetSQLArray($marathonUserIDs)).")
            GROUP BY ui.MarathonUserID";
	    return $this->stmt->FetchIndexedList($query);
	}
}