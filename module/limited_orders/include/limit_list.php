<?php

class LimitList extends LocalObjectList
{
    private $module;
    
    public function __construct($module)
    {
        parent::LocalObjectList($data = array());
        $this->module = $module;
    }

    public function load(LocalObject $request)
    {
		if (is_null($request->GetProperty('BaseURL')))
			$request->SetProperty('BaseURL', '');

		$query = "SELECT *
			FROM `limited_orders_limit`
			WHERE PageID=".$request->GetIntProperty('PageID')."
			ORDER BY Date DESC";

		$this->LoadFromSQL($query);
	}

    public function GetAvailableDateTime($pageID)
    {
        $stmt = GetStatement();

        $query = "SELECT CONCAT(Date, ' ', TimeFrom) AS DateFrom, CONCAT(Date, ' ', TimeTo) AS DateTo, Step, LimitCount
			FROM `limited_orders_limit`
			WHERE PageID=".$pageID."
			ORDER BY Date ASC";

        $limits = $stmt->FetchList($query);

        $dates = array();

        foreach($limits as $limit)
        {
            $date = strtotime($limit['DateFrom']);

            while($date < strtotime($limit['DateTo']))
            {
                $query = "SELECT COUNT(OrderID) FROM limited_orders_order 
                    WHERE PageID=".$pageID." AND DateTime=".Connection::GetSQLDateTime(date('Y-m-d H:i:s', $date));

                if($stmt->FetchField($query) < $limit['LimitCount'])
                    $dates[] = date('Y-m-d H:i:s', $date);

                $date = strtotime('+'.$limit['Step'].' minutes', $date);
            }
        }

        return $dates;
    }
}