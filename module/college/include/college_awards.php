<?php

class CollegeAwards extends LocalObjectList {
    protected $AwardsID;
    protected $Title;
    protected $module;

    public function __construct($module = 'college'){
        parent::LocalObjectList();
        $this->module = $module;
    }

//List
    public function load(){
        $query = 'SELECT * FROM `college_award` ORDER BY `Title` ASC';
        $this->LoadFromSQL($query);
    }

    public function LoadForSuggest(LocalObject $request){
        $this->_items = array();
        $term = $request->GetPropertyForSQL('term');
        if (empty($term)) {
            return;
        }

        $query = "SELECT `AwardsID` AS `value`, `Title` AS `label` FROM `college_award`
			WHERE INSTR(`Title`, $term)";
        $itemIDs = $request->GetProperty('ItemIDs');
        if($itemIDs) {
            $query .= " AND `AwardsID` NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
        }

        $this->SetItemsOnPage(0);
        $this->LoadFromSQL($query);
    }

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
        	if (!empty($selected)){
				$item['Selected'] = in_array($item['AwardsID'], $selected) ? 1 : 0;
			}
            $result[] = $item;
        }

        return $result;
    }


//Single
    //Load
    public function loadByID($id){
        $stmt = GetStatement();

        $query = "SELECT * FROM college_award WHERE AwardsID = ".Connection::GetSQLString($id);
        if( $result = $stmt->FetchRow($query) ){
            $this->AwardsID = $result['AwardsID'];
            $this->Title = $result['Title'];
            return true;
        }
        return false;
    }

    //Get
    public function getItem(){
        return new LocalObject(array(
            'AwardsID' => $this->AwardsID,
            'Title' => $this->Title
        ));
    }

    //CRUD
    public function save($AwardsID, $Title){
        $stmt = GetStatement();
        //Insert
        if (!empty($Title)){
            if ($AwardsID > 0) {
                $query = "UPDATE `college_award` SET
						Title=" . Connection::GetSQLString($Title) . "
						WHERE AwardsID = " . intval( $AwardsID );
            }
            else{
                $query = "INSERT INTO `college_award` SET
						Title=" . Connection::GetSQLString($Title);
            }

            if( $stmt->Execute($query) ){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            $this->AddError("award-title-empty", $this->module);
        }
    }

    public function remove($AwardsID){
        $stmt = GetStatement();

        $query = "DELETE FROM college_award WHERE AwardsID = " . intval($AwardsID);
        if ($stmt->Execute($query)){
            $this->AddMessage("awards-removed", $this->module);
            return true;
        }
        else{
            return false;
        }
    }
}