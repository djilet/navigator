<?php

class VkAdsCity extends LocalObjectList
{
    public function load($exhibitionId, $onPage = 40)
    {
        $this->_items = array();
        $exhibitionId = intval($exhibitionId);
        if ($exhibitionId == 0) {
            return;
        }
        $query = 'SELECT name FROM `ads_advert` AS vaa
                INNER JOIN ads_campaign as vac 
                ON vaa.ads_campaign_id = vac.id
                WHERE vac.exhibition_id = '.$exhibitionId.'
                GROUP BY name';
        $this->SetItemsOnPage($onPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }
}