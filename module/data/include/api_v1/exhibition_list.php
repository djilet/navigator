<?php
require_once(dirname(__FILE__) . '/../common/ExhibitionCityCommon.php');

class DataExhibitionList extends LocalObjectList 
{
    use ExhibitionCityCommon;
    
	private $module;
	private $date;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->date = new DateTime('now', new DateTimeZone('Europe/Moscow'));
		$this->date->modify('-1 day');
		$this->SetSortOrderFields(array(
			"date_asc" => "e.DateFrom ASC"
		));

		$this->SetOrderBy("date_asc");
	}

	public function load()
	{
	    $this->SetItemsOnPage(0);
	    
		$query = "SELECT e.ExhibitionID, e.Title, e.DateFrom, e.DateTo FROM `data_exhibition` AS e
            WHERE e.DateTo >= ".Connection::GetSQLString($this->date->format('Y-m-d H:i:s'));
		//print_r($query);die();
		
		$this->LoadFromSQL($query);
		$this->prepare();
	}
	
	protected function prepare()
	{
	    $stmt = GetStatement();
	    $cityIDs = array();
	    for($i=0; $i<count($this->_items); $i++) {
	        $query = "SELECT c.CityID, c.CityTitle FROM `data_exhibition_city` AS c
                WHERE c.ExhibitionID = ".intval($this->_items[$i]["ExhibitionID"]);
	        $cityList = $stmt->FetchList($query);
	        for($j=0; $j<count($cityList); $j++) {
	            $cityIDs[] = $cityList[$j]["CityID"];
	            $cityList[$j]["Schedule"] = $shedule = $this->loadSchedule($cityList[$j]["CityID"]);
	        }
	        $this->_items[$i]["CityList"] = $cityList;
	    }
	    
	    if(count($cityIDs) > 0){
	        $query = "SELECT c2u.CityID, c2u.UniversityID, u.ShortTitle FROM `data_exhibition_city2univer` AS c2u
                LEFT JOIN data_university u ON c2u.UniversityID=u.UniversityID 
                WHERE c2u.CityID IN (".implode(',', Connection::GetSQLArray($cityIDs)).')';
	        $universityList = $stmt->FetchList($query);
	        for($i=0; $i<count($this->_items); $i++) {
	            for($j=0; $j<count($this->_items[$i]["CityList"]); $j++) {
	                $uList = array();
	                for($k=0; $k<count($universityList); $k++){
	                    if($universityList[$k]["CityID"] == $this->_items[$i]["CityList"][$j]["CityID"]){
	                        $uList[] = $universityList[$k];
	                    }
	                }
	                $this->_items[$i]["CityList"][$j]["UniversityList"] = $uList;
	            }
	        }
	    }
	}
}