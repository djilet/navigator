<?php
namespace Import;

require_once(dirname(__FILE__) . "/../../module/data/include/public/Subject.php");
require_once(dirname(__FILE__) . "/../../module/data/include/profession.php");

Class ProfessionImport extends BaseImport{
    protected $delimiter = ';';
    protected $enclosure = '"';
    protected $error = array();

    protected $subject = array();
    protected $directions = array();

    protected $map = [
        'ImportID'                 => 0, //id профессии
        'Industry'                 => 1, //Сфера деятельности
        'Title'                 => 2, //Профессия
        'AreaWork'                 => 3, //Профилизации
        'WhoToWork'                 => 4, //С чем хочу работать
        'WantToWork'                 => 5, //Что хочу делать
        'WageLevel'                 => 6, // Зарплата начинающего специалиста (по России)
        'ProWageLevel'                 => 7, //Зарплата ведущего специалиста (по России)
        'Description'                 => 8, //Трудовые обязанности
        'Employee'                 => 9, //Характеристика сотрудника
        'Books'                 => 10, //Книги для саморазвития
        'Films'                 => 11, //Фильмы, влоги, передачи про профессию (16+)
        'dd.Title'                 => 12, //Рекомендуемые специальности  (data_direction)
        /*'p2p.ItemID'                 => -1, //Похожие профессии*/
        'Schedule'                 => 13, //График работы
        'Operation'                 => 14, //Характер труда

        'Мат' => 15,
        'Рус' => 16,
        'Общ' => 17,
        'Физ' => 18,
        'Био' => 19,
        'Ист' => 20,
        'Иняз' => 21,
        'Хим' => 22,
        'Лит' => 23,
        'Инф' => 24,
        'Гео' => 25,
        'ДВИ' => 26,
    ];

//Init
    public function __construct($delimiter, $enclosure){
        parent::__construct();

        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;

        $this->importedProfession = $this->stmt->FetchIndexedAssocList("SELECT `ProfessionID`, `ImportID` FROM `data_profession` WHERE `ImportID`<>''", 'ImportID');
        $this->directions = $this->stmt->FetchIndexedAssocList('SELECT `DirectionID`,`Title` FROM `data_direction`','Title');

        $this->subject = new \Subject();
        $this->subject->load();
    }

    public function initImport(){
		$logs = '';
        while (($data = $this->getNext()) != false) {
            if ($this->value('ImportID') == 'id профессии' || intval($this->value('ImportID')) == 0) {
                continue;
            }
            
            if ($id = $this->findProfessionByImportID($this->value('ImportID'))) {
                if( $this->update($id) ){
                    $logs .= $this->createItemLink($id, $this->value('ImportID') . ' - update by importID "' . $this->value('Title') . '"<br/>');
                }
                else{
                    return false;
                }
            }
            else {
                $id = $this->findProfessionByTitle( $this->value('Title') );

                if ( !empty($id) ) {
                    if( $this->update($id) ){
                        $logs .= $this->createItemLink($id, ' - update by title "' . $this->value('Title') . '"<br/>');
                    }
                    else{
                        return false;
                    }
                } else {
                    if( $id = $this->insert() ){
                        $logs .= $this->createItemLink($id, ' - insert new  "' . $this->value('Title') . '"<br/>');
                    }
                    else{
                        $this->errors[] = 'добавить профессию';
                        return false;
                    }
                }
            }
            
            if( !$directionsID = $this->parseDirection() ){
                $this->errors[] = 'Получить направляния';
                return false;
            }
            
            if ( !$this->saveDirections($id, $directionsID) ){
                return false;
            }
            if ( !$this->saveWhoToWork($id) ){
                return false;
            }
            if ( !$this->saveWantToWork($id) ){
                return false;
            }
            if ( !$this->saveOperation($id) ){
                return false;
            }
            if ( !$this->saveSubjects($id) ){
                return false;
            }
            $this->saveOtherProfession($id, $directionsID);
        }
        echo 'Список изменений: <br>' . $logs;
        return true;
    }

//CRUD
    public function insert(){
        $sortOrder = $this->getMaxSortOrder('data_profession');
        if ( $sortOrder > 0){
            $sortOrder++;
        }
        else{
            $sortOrder = 1;
        }

        $StaticPath = RuToStaticPath( $this->value('Title') );
        $query = 'INSERT INTO `data_profession`(`ImportID`, `Title`, `StaticPath`, `Industry`, `WageLevel`, `Description`, `Employee`, `AreaWork`, `ProWageLevel`, `Books`, `Films`, `Schedule`, `SortOrder`) VALUES(
					' . $this->field('ImportID') . ',
			       	' . $this->field('Title') . ',
			       	' . \Connection::GetSQLString($StaticPath) . ',
			    	' . $this->getIndustryID() . ',
				   	' . self::parseInt($this->field('WageLevel')) . ',
				 	' . self::parseHtml($this->field('Description')) . ',
				    ' . self::parseHtml($this->field('Employee')) . ',
				    ' . self::parseHtml($this->field('AreaWork')) . ',
				    ' . self::parseInt($this->field('ProWageLevel')) . ',
				    ' . self::createTitles($this->field('Books'), '*') . ',
				    ' . self::createYouTubeFrame($this->field('Films'), '*') . ',
				    ' . self::parseHtml($this->field('Schedule')) . ',
					' . \Connection::GetSQLString($sortOrder).'
				)';
        if( !$this->stmt->Execute($query) ){
            print_r($this->stmt->_dbLink->error . '<br>');
            return false;
        }
        return $this->stmt->_dbLink->insert_id;
    }
    //Service for insert
    protected function getMaxSortOrder($table_name){
        $query = "SELECT MAX(SortOrder) FROM " . $table_name;
        return $this->stmt->FetchField($query);
    }

    public function update($id){
        $query = '
    	UPDATE `data_profession` SET
	       	`ImportID` = '.$this->field('ImportID').',
	       	`Title` = '.$this->field('Title').',
	    	`Industry` = '.$this->getIndustryID().',
		   	`WageLevel` = '.self::parseInt($this->field('WageLevel')).',
		 	`Description` = '.self::parseHtml($this->field('Description')).',
		    `Employee` = '.self::parseHtml($this->field('Employee')).',
		    `AreaWork` = '.self::parseHtml($this->field('AreaWork')).',
		    `ProWageLevel` = '.self::parseInt($this->field('ProWageLevel')).',
		    `Books` = '.self::createTitles($this->field('Books'),'*').',
		    `Films` = '.self::createYouTubeFrame($this->field('Films'), '*').',
		    `Schedule` = '.self::parseHtml($this->field('Schedule')).'
		WHERE
			`ProfessionID` = '. $id;
        if( !$this->stmt->Execute($query) ){
            print_r($this->stmt->_dbLink->error . '<br>');
            return false;
        }
        return true;
    }

    public function getIndustryID(){
        $query = 'SELECT IndustryID FROM `data_profession_industry` WHERE IndustryTitle = ' . $this->field('Industry');
        if( !$IndustryID = $this->stmt->FetchField($query) ){
            $query = 'INSERT INTO `data_profession_industry` (IndustryTitle) VALUES(' . $this->field('Industry') . ')';
            if( $this->stmt->Execute($query) ){
                $IndustryID = $this->stmt->GetLastInsertID();
            }
        }
        return \Connection::GetSQLString($IndustryID);
    }

    //Copy from /module/data/admin/DataProfession::saveDirections()
    public function saveDirections($id, $directionsID){
        if (!is_array($directionsID)) {
            return false;
        }

        $del_query = 'DELETE FROM `data_profession2direction` WHERE `ProfessionID`=' . $id;
        if (!$this->stmt->Execute($del_query)) {
            print_r($this->stmt->_dbLink->error . '<br>');
            return false;
        }
        foreach ($directionsID as $dir_id) {
            $query = 'INSERT INTO `data_profession2direction` VALUES('.$id.', '.intval($dir_id).')';
            if( !$this->stmt->Execute($query) ){
                //print_r($this->stmt->_dbLink->error . '<br>');
                return false;
            }
        }
        return true;
    }
    //Service for saveDirections
    public function parseDirection(){
        $result = self::parseValueFromWrap($this->field('dd.Title'), '<p>', '<\/p>');

        foreach ($result as $key => $title) {
            if ( isset($this->directions[$title]) && !empty($this->directions[$title]) ) {
                $directionsID[] = $this->directions[$title]['DirectionID'];
            }
        }
        if ( empty($directionsID) ) {
            return false;
        }
        return array_unique($directionsID);
    }

    public function saveWhoToWork($id){
        $this->stmt->Execute("DELETE FROM `data_profession2who` WHERE `ProfessionID` = ".$id);

        $str = preg_replace('/[\']/', '', $this->field('WhoToWork'));
        $str = str_ireplace([','], ';', $str);
        $result = explode(';', $str);

        foreach ($result as $key => $who_work_title) {
			$who_work_title = self::prepareOnlyText($who_work_title, true);

            $query = 'SELECT WhoWorkID FROM `data_profession_who_work` WHERE WhoWorkTitle = ' . $who_work_title;
            if( !$WhoWorkID = $this->stmt->FetchField($query) ){
                $query = 'INSERT INTO `data_profession_who_work` (WhoWorkTitle) VALUES(' . $who_work_title . ')';
                if( $this->stmt->Execute($query) ){
                    $WhoWorkID = $this->stmt->GetLastInsertID();
                }
            }

            $query = 'INSERT INTO `data_profession2who` VALUES('.$id.', '.intval($WhoWorkID).')';
            if( !$this->stmt->Execute($query) ){
                $this->errors[] = 'Сохранить "С чем хочу работать"';
            }
        }
        return true;
    }

    public function saveWantToWork($id){
        $this->stmt->Execute("DELETE FROM `data_profession2want` WHERE `ProfessionID` = ".$id);

        $str = preg_replace('/[\']/', '', $this->field('WantToWork'));
        $str = str_ireplace([','], ';', $str);
        $result = explode(';', $str);

        foreach ($result as $key => $want_work_title) {
			$want_work_title = self::prepareOnlyText($want_work_title, true);

            $query = 'SELECT WantWorkID FROM `data_profession_want_work` WHERE WantWorkTitle = ' . $want_work_title;
            if( !$WantWorkID = $this->stmt->FetchField($query) ){
                $query = 'INSERT INTO `data_profession_want_work` (WantWorkTitle) VALUES(' . $want_work_title . ')';
                if( $this->stmt->Execute($query) ){
                    $WantWorkID = $this->stmt->GetLastInsertID();
                }
            }

            $query = 'INSERT INTO `data_profession2want` VALUES('.$id.', '.intval($WantWorkID).')';
            if( !$this->stmt->Execute($query) ){
                $this->errors[] = 'Сохранить "Чем хочу заниматься"';
            }
        }
        return true;
    }

    public function saveOperation($id){
        $this->stmt->Execute("DELETE FROM `data_profession2operation` WHERE `ProfessionID` = ".$id);

        $str = preg_replace('/[\']/', '', $this->field('Operation'));
        $str = str_ireplace([','], ';', $str);
        $result = explode(';', $str);

        foreach ($result as $key => $operation_title) {
			$operation_title = self::prepareOnlyText($operation_title, true);

            $query = 'SELECT OperationID FROM `data_profession_operation` WHERE OperationTitle = ' . $operation_title;
            if( !$OperationID = $this->stmt->FetchField($query) ){
                $query = 'INSERT INTO `data_profession_operation` (OperationTitle) VALUES(' . $operation_title . ')';
                if( $this->stmt->Execute($query) ){
                    $OperationID = $this->stmt->GetLastInsertID();
                }
            }

            $query = 'INSERT INTO `data_profession2operation` VALUES('.$id.', '.intval($OperationID).')';
            if( !$this->stmt->Execute($query) ){
                $this->errors[] = 'Сохранить "Характер труда"';
            }
        }
        return true;
    }

    //Copy from /module/data/admin/DataProfession::saveOtherProfession()
    public function saveOtherProfession($id, $directionsID){
        if( !$professions = $this->getOtherProfession($id, $directionsID)){
            $this->errors[] = 'Получить профессии';
            return false;
        }

        if ( isset($professions[$id]) ) {
            unset($professions[$id]);
        }

        if (!is_array($professions)) {
            return false;
        }

        $this->stmt->Execute("DELETE FROM `data_profession2profession` WHERE `ProfessionID` = ".$id);
        foreach ($professions as $prof_id) {
            $query = 'INSERT INTO `data_profession2profession` VALUES('.$id.', '.intval($prof_id).')';
            if( !$this->stmt->Execute($query) ){
                print_r($this->stmt->_dbLink->error . '<br>');
            }
        }
        return true;
    }
    //Service for saveOtherProfession
    public function getOtherProfession($id, $directionsID){
        $query = 'SELECT ProfessionID FROM `data_profession2direction` WHERE `DirectionID` IN(' . $this->getStrForQuery($directionsID) . ') AND ProfessionID != ' . \Connection::GetSQLString($id);
        if( $professions = $this->stmt->FetchRows($query) ){
            return array_unique($professions);
        }
        return false;
    }
    
    public function saveSubjects($id){
        $ids = array();
        $subjectList = $this->subject->getIndexedItems('ShortTitle');

        $subjectShortTitles = [
            'Мат',
            'Рус',
            'Общ',
            'Физ',
            'Био',
            'Ист',
            'Иняз',
            'Хим',
            'Лит',
            'Инф',
            'Гео',
        ];

        foreach ($subjectShortTitles as $index => $title) {
            if ($this->value($title) > 0){
                $ids[] = $subjectList[$title]['SubjectID'];
            }
        }

        if (!empty($ids)){
            $profession = new \DataProfession();
            $profession->loadByID($id);
            $profession->saveSubject($ids);
            return true;
        }

        return false;
    }

//Check
    public function findProfessionByImportID($importID){
        if ( empty($importID) || !isset($this->importedProfession[$importID]) ){
            return false;
        }
        return $this->importedProfession[$importID]['ProfessionID'];
    }

    public function findProfessionByTitle($title){
        $id = $this->stmt->FetchField('SELECT ProfessionID FROM `data_profession` WHERE `Title`=' . \Connection::GetSQLString($title));
        if (isset($id)) {
            return intval($id);
        }
        return false;
    }

//Industry
    public function migratetIndustry(){
        $query = "
		SELECT DISTINCT Industry FROM `data_profession` WHERE
			Industry NOT IN(
				SELECT DISTINCT data_profession.Industry FROM `data_profession`,`data_profession_industry` WHERE data_profession.Industry = data_profession_industry.IndustryTitle
			) AND Industry REGEXP '^[^0-9]'";

        if($result = $this->stmt->FetchRows($query) ){
            foreach ($result as $key => $prof) {
                $query = "INSERT INTO `data_profession_industry` (IndustryTitle) VALUES (" . \Connection::GetSQLString($prof) . ")";
                if( $this->stmt->Execute($query) ){
                    $query = "UPDATE `data_profession` SET Industry =" . $this->stmt->GetLastInsertID() . " WHERE Industry = " . \Connection::GetSQLString($prof);
                    $this->stmt->Execute($query);
                    echo $query . "<br>";
                }
            }
            //print_r($result);
        }
        else{
            if (!empty($this->stmt->_dbLink->error_list)) {
                print_r($this->stmt->_dbLink->error_list);
            }
        }
    }

	public function createItemLink($id, $text){
		return '<a href="' . PROJECT_PATH . 'profession/?ProfessionID=' . $id . '">' . $text . '</a>';
	}

}
?>