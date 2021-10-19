<?php

class DirectionList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

    public static function getAll(array $filter = null, int $onPage = 40)
    {
        $query = QueryBuilder::init()
            ->select([
                'dir.*',
            ])
            ->from('data_direction AS dir');

        if (!empty($filter)){
            if (!empty($filter['CityIDs'])){
                $query->addJoin('LEFT JOIN data_speciality AS spec ON dir.DirectionID = spec.DirectionID');
                $query->addJoin('LEFT JOIN data_university AS univ ON spec.UniversityID = univ.UniversityID');
                $cityIDs = implode(", ", Connection::GetSQLArray($filter['CityIDs']));
                $query->addWhere("univ.CityID IN ({$cityIDs})");
            }
        }

        $query->order(['dir.SortOrder']);
        $query->group(['dir.DirectionID']);

        //echo $query->getSQL();exit();

        $item = new static();
        $item->SetItemsOnPage($onPage);
        $item->SetCurrentPage();
        $item->LoadFromSQL($query->getSQL());
        return $item;
    }


    public function getListForTemplate(array $selected = array(), $items = null)
    {
        $this->prepareFromKeysName('DirectionID', 'Title', $selected, $items);
    }
}