<?php

class PriceList extends LocalObjectList
{
    private $module;
    
    public function __construct($module)
    {
        parent::__construct($data);
        $this->module = $module;
    }

    public function load()
    {
        $this->SetItemsOnPage(0);
		
		$where = array();
		
		$query = "SELECT p.* FROM `document_price` p
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "")."
			ORDER BY p.SortOrder ASC";
		
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}
}