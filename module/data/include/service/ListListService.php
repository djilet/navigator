<?php


class ListListService
{
    public static function filterByCity(ListList $listList, City $city)
    {
        $university = new University();
        $filteredListList = [];
        foreach ($listList->GetItems() as $list){
            if ($list['Type'] != 'filter'){
                $filteredListList[] = $list;
                continue;
            }
            $filter = $listList->getFilterArray($list['ListID']);
            $filter['CityID'] = $city->GetIntProperty('ID');
            $university->load(new LocalObject(['UniverFilter' => $filter]), 1, false);
            if ($university->GetCountItems() > 0){
                $filteredListList[] = $list;
            }
        }

        return $filteredListList;
    }
}