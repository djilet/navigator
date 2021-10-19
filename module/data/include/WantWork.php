<?php
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class WantWork extends LocalObjectList implements TemplateListInterface
{
	use TemplateListMethods;
	protected $module = 'data';
	protected $dataTable = 'WantWork';
	public $item;

//List
	//Load
	public function __construct(){
		parent::LocalObjectList();
		$this->item = new LocalObject();
	}

	public function load()
    {
        $query = 'SELECT * FROM `data_profession_want_work` ORDER BY `WantWorkTitle` ASC';
        $this->LoadFromSQL($query);
    }

	public function LoadForSuggest(LocalObject $request){
		$this->_items = array();
		$term = $request->GetPropertyForSQL('term');
		if (empty($term)) {
			return;
		}

		$query = "SELECT `WantWorkID` AS `value`, `WantWorkTitle` AS `label` FROM `data_profession_want_work`
			WHERE INSTR(`WantWorkTitle`, $term)";
		$itemIDs = $request->GetProperty('ItemIDs');
		if($itemIDs) {
			$query .= " AND `WantWorkID` NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
		}

		$this->SetItemsOnPage(0);
		$this->LoadFromSQL($query);
	}

	//Get
    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
        	if (!empty($selected)){
				$item['Selected'] = in_array($item['WantWorkID'], $selected) ? 1 : 0;
			}
            $result[] = $item;
        }

        return $result;
    }
    
    public function getForFilter($max_sub_list, $selected = array()){
        $stmt = GetStatement();
        $query = "	SELECT
						DISTINCT GROUP_CONCAT(prof2want.WantWorkID SEPARATOR ',') AS Filter,
						GROUP_CONCAT(want.WantWorkTitle SEPARATOR ',') AS Title
					FROM
						data_profession2want AS prof2want
					LEFT JOIN
						data_profession_want_work AS want ON prof2want.WantWorkID = want.WantWorkID
					GROUP BY prof2want.ProfessionID
					HAVING COUNT(prof2want.WantWorkID) <= " . intval($max_sub_list) . "
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
                    if (isset($titleMap[$filters[$i]])){
						$titleStr .= $titleMap[$filters[$i]];
					}
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

	public function getItemsNameConcatAlias($selected = array()){
	    foreach ($this->_items as $item) {
			if ( !empty($item['Alias']) ){
				$item['WantWorkTitle'] = $item['WantWorkTitle'] . ' (' . $item['Alias'] . ')';
			}
			$item['Selected'] = in_array($item['WantWorkID'], $selected) ? 1 : 0;
			$result[] = $item;
		}
		return $result;
	}

//Single
	//Load
	public function loadByID($id){
		$query = "SELECT * FROM data_profession_want_work WHERE WantWorkID = ".Connection::GetSQLString($id);
		$this->item->LoadFromSQL($query);
		return true;
	}

	//Get
	public function getItem(){
		return $this->item;
	}

	//CRUD
	public function save($WantWorkID, LocalObject $request){
		$stmt = GetStatement();
		//Insert
		$WantWorkTitle = $request->GetProperty('WantWorkTitle');
		if (!empty($WantWorkTitle)){
			if ($WantWorkID > 0) {
				$query = "UPDATE `data_profession_want_work` SET
						WantWorkTitle=" . Connection::GetSQLString($WantWorkTitle) . ",
						Alias=" . $request->GetPropertyForSQL('Alias') . ",
						Description=" . $request->GetPropertyForSQL('Description') . "
						WHERE WantWorkID = " . intval( $WantWorkID );
			}
			else{
				$query = "INSERT INTO `data_profession_want_work` SET
						WantWorkTitle=" . Connection::GetSQLString($WantWorkTitle) . ",
						Alias=" . $request->GetPropertyForSQL('Alias') . ",
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
			$this->AddError("want-work-title-empty", $this->module);
		}
	}

	public function remove($WantWorkID){
		$stmt = GetStatement();

		$query = "DELETE FROM data_profession_want_work WHERE WantWorkID = " . intval($WantWorkID);
		if ($stmt->Execute($query)){
			$this->AddMessage("want-work-removed", $this->module);
			return true;
		}
		else{
			return false;
		}
	}
}