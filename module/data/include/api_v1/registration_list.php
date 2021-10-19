<?php

class DataRegistrationList extends LocalObjectList {
	
	private $module;
	private $now;

	public function __construct($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
		$this->SetSortOrderFields(array(
			"date_asc" => "e.DateFrom ASC"
		));

		$this->SetOrderBy("date_asc");
	}

	public function load()
	{
	    $this->SetItemsOnPage(0);
	    
		$query = "SELECT r.RegistrationID, r.FirstName, r.LastName, r.Who, r.Class, r.Phone, r.Email, r.EventID AS ExhibitionID, r.City
            FROM `event_registrations` AS r
            LEFT JOIN `data_exhibition` AS e ON r.EventID=e.ExhibitionID
            WHERE e.DateTo >= ".Connection::GetSQLString($this->now->format('Y-m-d H:i:s'));
		
		$this->LoadFromSQL($query);
		$this->prepare();
	}
	
	protected function prepare()
	{
	    $stmt = GetStatement();
	    for($i=0; $i<count($this->_items); $i++) {
	        $this->_items[$i]["Phone"] = preg_replace("/[^0-9,.]/", "", $this->_items[$i]["Phone"]);
	    }
	}
}