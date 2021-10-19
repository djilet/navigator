<?php

class ExhibitionList extends LocalObjectList {

    public function load($onPage = 30)
    {
        $query = 'SELECT * FROM data_exhibition ORDER BY DateFrom DESC';
        $this->SetItemsOnPage(intval($onPage));
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }

    public function remove($listId)
    {
        if (! is_array($listId) or empty($listId)) {
            return;
        }

        $stmt = GetStatement();
        $query = 'DELETE FROM `data_exhibition` 
            WHERE ExhibitionID IN ('.implode(',', Connection::GetSQLArray($listId)).')';
        $stmt->Execute($query);
    }

}