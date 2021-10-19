<?php
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class WhoWork extends LocalObjectList implements TemplateListInterface
{
	use TemplateListMethods;
	protected $module = 'data';
	protected $dataTable = 'WhoWork';
	protected $item;

	public function __construct(){
		parent::LocalObjectList();
		$this->item = new LocalObject();
	}

//List
    public function load()
    {
        $query = 'SELECT * FROM `data_profession_who_work` ORDER BY `WhoWorkTitle` ASC';
        $this->LoadFromSQL($query);
    }

	public function LoadForSuggest(LocalObject $request){
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}

		$query = "SELECT `WhoWorkID` AS `value`, `WhoWorkTitle` AS `label` FROM `data_profession_who_work`
			WHERE INSTR(`WhoWorkTitle`, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND `WhoWorkID` NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}

		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
        	if (!empty($selected)){
				$item['Selected'] = in_array($item['WhoWorkID'], $selected) ? 1 : 0;
			}

            $result[] = $item;
        }

        return $result;
    }

    public function getForFilter($max_sub_list, $selected = array()){
        $stmt = GetStatement();
		$query = "	SELECT
						DISTINCT GROUP_CONCAT(prof2who.WhoWorkID SEPARATOR ',') AS Filter,
						GROUP_CONCAT(who.WhoWorkTitle SEPARATOR ',') AS Title
					FROM
						data_profession2who AS prof2who
					LEFT JOIN
						data_profession_who_work AS who ON prof2who.WhoWorkID = who.WhoWorkID
					GROUP BY prof2who.ProfessionID
					HAVING COUNT(prof2who.WhoWorkID) <= " . intval($max_sub_list) . "
					ORDER BY Filter ASC";
		$result = $stmt->FetchIndexedAssocList($query,'Filter');
		
		$levels = array();
		foreach ($result as $index => $item) {
		    $ids = explode(',', $item['Filter']);
		    $titles = explode(',', $item['Title']);
		    $titleMap = array();
		    for($i=0; $i<count($ids); $i++){
		        $titleMap[$ids[$i]] = $titles[$i];
		    }
		    foreach (Permutation($ids) as $filters) {
		        $level = &$levels;
		        $idStr = '';
		        $titleStr = '';
		        for($i=0; $i<count($filters); $i++) {
		            if($i>0) {
		                $idStr .= ',';
		                $titleStr .= ' & ';
		            }
		            $idStr .= $filters[$i];
		            $titleStr .= $titleMap[$filters[$i]];
		            if(!isset($level[$filters[$i]])){
		                $item = array(
		                    'Title' => $titleStr,
		                    'Id' => $idStr,
		                    'ChildList' => array()
		                );
		                foreach($selected as $selectedItem){
		                    if($idStr === $selectedItem || substr($selectedItem, 0, strlen($idStr) + 1) === $idStr.','){
		                        $item['Selected'] = 1;
		                    }
		                }
		                $level[$filters[$i]] = $item;
		            }
		            $level = &$level[$filters[$i]]['ChildList'];
		        }
		    }
		}
		return MultilevelMap2ArrayList($levels);
	}

	public function getListForTemplate($selected = array(), $position = array()){
		return $this->prepareDataForTemplateList($this->dataTable, $selected, $position);
	}


//Single
	//Load
	public function loadByID($id){
		$query = "SELECT * FROM data_profession_who_work WHERE WhoWorkID = ".Connection::GetSQLString($id);
		$this->item->LoadFromSQL($query);
	}

	//Get
	public function getItem(){
		return $this->item;
	}

	//CRUD
	public function save($WhoWorkID, LocalObject $request){
		$stmt = GetStatement();
		//Insert
		$WhoWorkTitle = $request->GetProperty('WhoWorkTitle');
		if (!empty($WhoWorkTitle)){
			if ($WhoWorkID > 0) {
				$query = "UPDATE `data_profession_who_work` SET
						WhoWorkTitle=" . Connection::GetSQLString($WhoWorkTitle) . ",
						Description=" . $request->GetPropertyForSQL('Description') . "
						WHERE WhoWorkID = " . intval( $WhoWorkID );
			}
			else{
				$query = "INSERT INTO `data_profession_who_work` SET
						WhoWorkTitle=" . Connection::GetSQLString($WhoWorkTitle) . ",
						Description=" . $request->GetPropertyForSQL('Description');
			}

			if( $stmt->Execute($query) ){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->AddError("who-work-title-empty", $this->module);
		}
	}

	public function remove($WhoWorkID){
		$stmt = GetStatement();

		$query = "DELETE FROM data_profession_who_work WHERE WhoWorkID = " . intval($WhoWorkID);
		if ($stmt->Execute($query)){
			$this->AddMessage("who-work-removed", $this->module);
			return true;
		}
		else{
			return false;
		}
	}
}