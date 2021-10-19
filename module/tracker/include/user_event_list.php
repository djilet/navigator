<?php

class UserEventList extends LocalObjectList
{
    public function loadForLeadAfterBlog($request){
        $this->SetItemsOnPage(100);
        $this->SetCurrentPage();
        
        $query = QueryBuilder::init()
            ->select([
                "*",
                "Properties->>'$.FirstName' AS FirstName",
                "Properties->>'$.LastName' AS LastName",
                "Properties->>'$.Phone' AS Phone",
                "Properties->>'$.Email' AS Email",
                "Properties->>'$.ContactType' AS ContactType",
                "Properties->>'$.To' AS LeadTo",
                "Properties->>'$.Event' AS Event",
            ])
            ->from('user_event');
            
        if($request->GetProperty("FilterDateFrom")){
            $dateFrom = Connection::GetSQLDateTime($request->GetProperty("FilterDateFrom"));
            $query->addWhere("Created >= {$dateFrom}");
        }
        if($request->GetProperty("FilterDateTo")){
            $dateTo = Connection::GetSQLDateTime($request->GetProperty("FilterDateTo"));
            $query->addWhere("Created <= {$dateTo}");
        }
        if($request->GetProperty("FilterLeadTo")){
            $query->addWhere("Properties->>'$.To' = {$request->GetPropertyForSQL("FilterLeadTo")}");
        }
        if($request->GetProperty("FilterEvent")){
            $query->addWhere("Properties->>'$.Event' = {$request->GetPropertyForSQL("FilterEvent")}");
        }

        $this->LoadFromSQL($query->getSQL());
    }
    
    public function exportToCSV(){
        ob_start();
        $f = fopen("php://output", "w");
        
        $row = array("Дата", "Имя", "Фамилия", "Телефон", "Email", "Тип контакта", "Лид на", "Событие");
        fputcsv($f, $row, ";");
        
        foreach($this->GetItems() as $item)
        {
            $row = array(
                $item["Created"],
                $item["FirstName"],
                $item["LastName"],
                $item["Phone"],
                $item["Email"],
                $item["ContactType"],
                ($item["LeadTo"] == "exhibition") ? "Выставка" : "Консультация",
                ($item["Event"] == "BlogPrevPage") ? "Последняя открытая страница блог" : "Первая страница блог",
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
        header('Content-Disposition: attachment;filename="tracker.csv"');
        header("Content-Transfer-Encoding: binary");
        
        echo(ob_get_clean());
        exit();
    }
}