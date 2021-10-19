<?php

class BigDirection extends LocalObjectList
{

    public function load()
    {
        $query = 'SELECT * FROM `data_bigdirection` ORDER BY `Title` ASC';
        $this->LoadFromSQL($query);
    }

    public function loadWithStatistic(array $filter = null)
    {
        $query = QueryBuilder::init()
            ->select([
                'big_dir.*',
                'COUNT(DISTINCT dir.DirectionID) AS DirectionsCount',
                'COUNT(spec.SpecialityID) AS SpecialitiesCount',
            ])
            ->from('data_bigdirection AS big_dir')
            ->join([
                'LEFT JOIN data_direction AS dir ON big_dir.BigDirectionID = dir.BigDirectionID',
                'LEFT JOIN data_speciality AS spec ON dir.DirectionID = spec.DirectionID',
            ])
            ->group(['big_dir.BigDirectionID']);

        if ($filter){
            if ($filter['CityIDs']){
                $cityIDs = implode(", ", Connection::GetSQLArray($filter['CityIDs']));
                $query->addJoin('LEFT JOIN data_university AS university ON spec.UniversityID = university.UniversityID');
                $query->addWhere("university.CityID IN ({$cityIDs})");
            }
        }

        $this->LoadFromSQL($query->getSQL());

        foreach ($this->_items as $key => $item){
            $this->_items[$key] = self::prepareRow($item);
        }
    }

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
            $item['Selected'] = in_array($item['BigDirectionID'], $selected) ? 1 : 0;
            $result[] = $item;
        }
        
        return $result;
    }

    public static function prepareRow(array $row)
    {
        if (!empty($row['DirectionsCount'])){
            $row['DirectionsCountTitle'] = morphos\Russian\pluralize($row['DirectionsCount'], 'направление');
        }

        if (!empty($row['SpecialitiesCount'])){
            $row['SpecialitiesCountTitle'] = morphos\Russian\pluralize($row['SpecialitiesCount'], 'специальность');
        }

        return $row;
    }
}