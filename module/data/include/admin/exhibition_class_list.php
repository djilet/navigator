<?php

class ExhibitionClassList extends LocalObjectList
{
    public function load($exhibitionId, $onPage = 40)
    {
        $this->_items = array();
        $exhibitionId = intval($exhibitionId);
        if ($exhibitionId == 0) {
            return;
        }
        $query = 'SELECT Class
            FROM `event_registrations`
            WHERE EventID='.$exhibitionId.'
            AND Class IS NOT NULL 
            GROUP BY Class';
        $this->SetItemsOnPage($onPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }
}