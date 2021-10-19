<?php

class DocumentOrderList extends LocalObjectList
{
    private $module;
    
    public function __construct($module)
    {
        parent::LocalObjectList($data = array());
        $this->module = $module;
    }

    public function load($request)
    {
        if ($request->GetProperty('FullList'))
			$this->SetItemsOnPage(0);
		else
			$this->SetItemsOnPage(15);

		if (is_null($request->GetProperty('BaseURL')))
			$request->SetProperty('BaseURL', '');

		$where = array();
		if($request->IsPropertySet('PageID'))
		{
	   	   $where[] = "o.PageID=".$request->GetIntProperty('PageID');
		}
		
		$query = "SELECT o.OrderID, o.Created, o.Name, o.Address, o.Email, o.Phone, o.Date, o.Time, o.Universities, o.City, b.Summ, b.PayDate, p.Title AS PageTitle,
			CONCAT('source=', o.utm_source, CONCAT_WS('',', medium=', o.utm_medium, ', campaign=', o.utm_campaign, ', term=', o.utm_term, ', content=', o.utm_content)) as UTM
			FROM `document_order` o
			LEFT JOIN `payment_bill` b ON o.OrderID=b.TypeID AND b.Type='document'
            LEFT JOIN `page` p ON p.PageID = o.PageID
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "")."
			ORDER BY o.Created DESC";
		
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}
}