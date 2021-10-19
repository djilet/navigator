<?php

class ExhibitionCityList extends LocalObjectList 
{
    public function load($exhibitionId, $onPage = 40)
    {
        $this->_items = array();
        $exhibitionId = intval($exhibitionId);
        if ($exhibitionId == 0) {
            return;
        }
        
        $query = 'SELECT `CityID`,`Title`,`Address`,`Date`, `CityTitle`, `StaticPath`
            FROM `data_exhibition_city`
            WHERE ExhibitionID='.$exhibitionId.' 
            ORDER BY `SortOrder` ASC';
        $this->SetItemsOnPage($onPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }

    public function remove($listId)
    {
        if (! is_array($listId) or empty($listId)) {
            return;
        }
        
        $stmt = GetStatement();
        $query = 'DELETE FROM `data_exhibition_city` 
            WHERE CityID IN ('.implode(',', Connection::GetSQLArray($listId)).')';
        $stmt->Execute($query);
    }

    public function updateSortOrder($exhibitionID, $cityID, $diff)
    {
        $exhibitionID = intval($exhibitionID);
        $cityID = intval($cityID);
        $diff = intval($diff);
        
        if (empty($exhibitionID) or empty($cityID) or empty($diff)) {
            return false;
        }
        
        $stmt = GetStatement();
        $stmt->Execute('SET @num := 0');
        $stmt->Execute('UPDATE data_exhibition_city 
            SET SortOrder = (SELECT @num := @num + 1)-1
            WHERE ExhibitionID = '.$exhibitionID.'
            ORDER BY SortOrder ASC, Title ASC');
        
        $sort = $stmt->FetchField('SELECT SortOrder FROM data_exhibition_city WHERE CityID='.$cityID);
        $newSort = $sort + $diff;
        
        if ($diff < 0) {
            $stmt->Execute(
                'UPDATE data_exhibition_city 
                SET SortOrder = SortOrder+1 
                WHERE ExhibitionID='.$exhibitionID.' AND 
                    SortOrder>='.$newSort.' AND 
                    SortOrder<'.$sort
            );
        } else {

            $stmt->Execute(
                'UPDATE data_exhibition_city 
                SET SortOrder = SortOrder-1 
                WHERE ExhibitionID='.$exhibitionID.' AND 
                    SortOrder>'.$sort.' AND 
                    SortOrder<='.$newSort
            );
        }

        $stmt->Execute('UPDATE data_exhibition_city SET SortOrder = '.abs($newSort).' WHERE CityID='.$cityID);
        
        return true;
    }
}
