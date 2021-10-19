<?php

class ReadLaterList extends LocalObjectList
{
    public static function prepareQueryByFilter(QueryBuilder $query, array $filter){
        $filter = (object) $filter;
        if (!empty($filter->DateFrom)){
            $dateFrom = Connection::GetSQLDateTime($filter->DateFrom);
            $query->addWhere("r_later.Created >= {$dateFrom}");
        }
        if (!empty($filter->DateTo)){
            $dateTo = Connection::GetSQLDateTime("{$filter->DateTo} + 1 days");
            $query->addWhere("r_later.Created <= {$dateTo}");
        }

        return $query;
    }

    public function load(){
        $query = "SELECT * FROM read_later";
        $this->LoadFromSQL($query);
    }

    public function loadWithTarget($filter){
        $query = QueryBuilder::init()
            ->addSelect('r_later.*, article.Title')
            ->from('read_later AS r_later')
            ->addJoin('LEFT JOIN data_article AS article ON r_later.TargetID = article.ArticleID');
        $this->LoadFromSQL(self::prepareQueryByFilter($query, $filter)->getSql());
    }

    public static function add($targetID, $email, $name){
        $targetID = intval($targetID);
        $date = GetCurrentDateTime();
        $query = "INSERT INTO read_later SET TargetID = {$targetID}, Email = '{$email}', Name = '{$name}', Created = '{$date}'";
        return GetStatement()->Execute($query);
    }

    public function exportToCSV()
    {
        ob_start();
        $f = fopen("php://output", "w");

        $row = array("ID", "Email", "Дата", "Статья",);
        fputcsv($f, $row, ";");

        foreach($this->GetItems() as $item)
        {
            $row = array(
                $item["ID"],
                $item["Email"],
                $item["Created"],
                $item["Title"],
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
        header('Content-Disposition: attachment;filename="read_later.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }
}