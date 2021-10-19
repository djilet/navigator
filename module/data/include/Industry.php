<?php
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class Industry extends LocalObjectList implements TemplateListInterface
{
	use TemplateListMethods;
	protected $module = 'data';
	protected $dataTable = 'Industry';
	protected $item;

	public function __construct(){
		parent::LocalObjectList();
		$this->item = new LocalObject();
	}

//List
    public function load()
    {
        $query = 'SELECT * FROM `data_profession_industry` ORDER BY `IndustryTitle` ASC';
        $this->LoadFromSQL($query);
    }

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
        	if (!empty($selected)){
				$item['Selected'] = in_array($item['IndustryID'], $selected) ? 1 : 0;
			}
            $result[] = $item;
        }

        return $result;
    }

//Single
	//Load
	public function loadByID($id){
		$query = "SELECT * FROM data_profession_industry WHERE IndustryID = ".Connection::GetSQLString($id);
		$this->item->LoadFromSQL($query);
		return true;
	}

	public function loadByTitle($title){
		$stmt = GetStatement();

		$query = "SELECT * FROM data_profession_industry WHERE IndustryTitle = ".Connection::GetSQLString($title);
		$this->item->LoadFromSQL($query);
		return true;
	}

	//Get
	public function getItem(){
    	return $this->item;
	}

	public function getListForTemplate($selected = array(), $position = array()){
    	return $this->prepareDataForTemplateList($this->dataTable, $selected, $position);
	}

	//CRUD
	public function save($IndustryID, LocalObject $request){
		$stmt = GetStatement();
		//Insert
		$industryTitle = $request->GetProperty('IndustryTitle');
		if (!empty($industryTitle)){
			if ($IndustryID > 0) {
				$query = "UPDATE `data_profession_industry` SET
						IndustryTitle = " . Connection::GetSQLString($industryTitle) . ",
						Description = " . $request->GetPropertyForSQL('Description') . "
						WHERE IndustryID = " . intval( $IndustryID );
			}
			else{
				$query = "INSERT INTO `data_profession_industry` SET
						IndustryTitle=" . Connection::GetSQLString($industryTitle) . ",
						Description = " . $request->GetPropertyForSQL('Description');
			}

			if( $stmt->Execute($query) ){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->AddError("industry-title-empty", $this->module);
		}
	}

	public function remove($IndustryID){
		$stmt = GetStatement();

		$query = "DELETE FROM data_profession_industry WHERE IndustryID = " . intval($IndustryID);
		if ($stmt->Execute($query)){
			$this->AddMessage("industry-removed", $this->module);
			return true;
		}
		else{
			return false;
		}
	}

}