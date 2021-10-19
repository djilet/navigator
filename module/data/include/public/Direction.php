<?php
/**
 * Date:    08.11.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

class Direction extends LocalObjectList
{

    public static function getData(int $id)
    {
        return self::getBy(["DirectionID = {$id}"]);
    }

    public static function getByStaticPath(string $staticPath)
    {
        return self::getBy(["StaticPath = '{$staticPath}'"]);
    }

    public function load()
    {
        $query = 'SELECT * FROM `data_direction` ORDER BY `Title` ASC';
        $this->LoadFromSQL($query);
    }

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
            $item['Selected'] = in_array($item['DirectionID'], $selected) ? 1 : 0;
            $result[] = $item;
        }
        
        return $result;
    }

	public function LoadItemsOnProfessions($professions){
    	if (!is_array($professions)){
    		return false;
		}
		$query = 'SELECT DirectionID, Title FROM `data_direction` WHERE DirectionID IN (
					SELECT DirectionID FROM data_profession2direction WHERE ProfessionID IN (' . implode(', ', Connection::GetSQLArray($professions)) .'))';
		$this->LoadFromSQL($query);
	}

    protected static function getBy(array $where)
    {
        $query = QueryBuilder::init()
            ->select(['*'])
            ->from('data_direction')
            ->where($where);

        $item = GetStatement()->FetchRow($query->getSQL());

        return $item['DirectionID'] > 0 ? self::prepareItem($item) : null;
    }

    public static function prepareItem($row)
    {
        if (!empty($row['VideoURL'])) {
            $row['VideoID'] = GetVideoIdFromYouTube($row['VideoURL']);
        }

        return $row;
    }
}