<?php

class MailingList extends LocalObjectList
{
    private $module;
    
    public function __construct($module)
    {
        parent::LocalObjectList($data = array());
        $this->module = $module;
    }

    public function Load($request)
    {
        $this->SetItemsOnPage(0);
		
		$where = array();
		if($request->GetProperty("FilterStatus"))
			$where[] = "m.Status=".$request->GetPropertyForSQL("FilterStatus");
		
		$query = "SELECT m.MailingID, m.From, m.Theme, m.Time, m.Status, m.Created
			FROM `mailing_mailing` m
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")." 
			ORDER BY m.Created DESC";
		
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}
}