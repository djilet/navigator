<?php 
require_once(dirname(__FILE__)."/../init.php");
es_include("localobject.php");

class Proftest extends LocalObject {
    protected $stmt;

    public function __construct($module = 'proftest'){
    	parent::LocalObject();
    	$this->stmt = GetStatement();
		$this->module = $module;
	}

	public function loadByPage($pageID)
    {
        $query = "SELECT p.ProftestID, p.Text AS ProftestText
            FROM `proftest_item` p
            WHERE p.PageID=".intval($pageID);
        $this->LoadFromSQL($query);
        if (!$this->IsPropertySet('ProftestID')){
        	$this->initProftestItem($pageID);
		}
    }

    public function initProftestItem($pageID){
		$query = "INSERT INTO proftest_item SET PageID = " . intval($pageID);
		if ($this->stmt->Execute($query)){
			$this->SetProperty('ProftestID', $this->stmt->GetLastInsertID());
		}
	}

	public function save(LocalObject $request){
    	if (!$this->validate($request)){
    		return false;
		}

		if ($request->GetIntProperty("ProftestID") > 0){
			$op = "UPDATE `proftest_item` SET";
			$where =  " WHERE ProftestID = " . $request->GetIntProperty("ProftestID");
		}
		else{
			$op = "INSERT INTO `proftest_item` SET";
			$where = '';
		}

		$query = $op .
			"`Text` = " . $request->GetPropertyForSQL("Text") .
			$where;

		if (!$this->stmt->Execute($query)){
			$this->AddError('save-error', $this->module);
		}
		return true;
	}

	public function validate(LocalObject $request){
		$this->ClearErrors();
		if(empty($request->GetProperty('Text'))){
			$this->AddError('empty-text', $this->module);
		}

		if ($this->HasErrors()){
			return false;
		}
		return true;
	}
    
}
 ?>