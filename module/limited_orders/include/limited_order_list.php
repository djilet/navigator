<?php

class LimitedOrderList extends LocalObjectList
{
    private $module;
    
    public function __construct($module)
    {
        parent::LocalObjectList($data = array());
        $this->module = $module;
    }

    public function load(LocalObject $request)
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

		if (!empty($request->GetProperty('DateFrom'))){
		    $where[] = "o.Created >= " . Connection::GetSQLDateTime($request->GetProperty('DateFrom'));
        }

		if (!empty($request->GetProperty('DateTo'))){
		    $where[] = "o.Created <= " . Connection::GetSQLDateTime($request->GetProperty('DateTo') . "+1 days");
        }
		
		$query = "SELECT o.*, p.Title AS PageTitle,
			CONCAT('source=', o.utm_source, CONCAT_WS('',', medium=', o.utm_medium, ', campaign=', o.utm_campaign, ', term=', o.utm_term, ', content=', o.utm_content)) as UTM,
            o.utm_source, o.utm_medium, o.utm_campaign, o.utm_term, o.utm_content, o.DateTime
			FROM `limited_orders_order` o
			LEFT JOIN `page` p ON p.PageID = o.PageID
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "")."
			ORDER BY o.Created DESC";
		
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}

	public function exportToCSV(){
        ob_start();
        $f = fopen("php://output", "w");

        $row = array("Дата заявки", "Имя", "Фамилия", "Телефон", "Статус", "Класс", "Страны", "Город", "Способ связи", "Дополнительно", "Лендинг", "UTM source", "UTM medium", "UTM campaign", "UTM term", "UTM content", "Время регистрации");
        fputcsv($f, $row, ";");

        foreach($this->GetItems() as $item)
        {
            $status = '';
            switch ($item['UserWho']){
                case 'parent':
                    $status = 'Родитель';
                    break;
                case 'child':
                    $status = 'Ученик';
                    break;
                case 'student':
                    $status = 'Студент';
                    break;
            }

            $row = array(
                $item["Created"],
                $item["FirstName"],
                $item["LastName"],
                $item["Phone"],
                $status,
                $item["ClassNumber"],
                $item["Country"],
                $item["City"],
                $item["ContactType"],
                $item["ContactAdditional"],
                $item["PageTitle"],
                $item["utm_source"],
                $item["utm_medium"],
                $item["utm_campaign"],
                $item["utm_term"],
                $item["utm_content"],
                $item["DateTime"],
            );
            fputcsv($f, $row, ";");
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="orders.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }
}