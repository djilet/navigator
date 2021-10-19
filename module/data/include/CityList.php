<?php
require_once (__DIR__ . '/City.php');
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');
/**
 * Class CityList
 */
class CityList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

    const TABLE_NAME = 'data_city';

    public static function getAll(array $filter = null, int $onPage = 40)
    {
        $query = QueryBuilder::init()
            ->select([
                'city.*',
            ])
            ->from(City::TABLE_NAME . ' AS city');

        if (!empty($filter)){
            if (!empty($filter['RegionIDs'])){
                $regionIDs = implode(", ", Connection::GetSQLArray($filter['RegionIDs']));
                $query->addWhere("RegionID IN ({$regionIDs})");
            }
        }

        $query->order(['Title']);

        //echo $query->getSQL();exit();

        $item = new static();
        $item->SetItemsOnPage($onPage);
        $item->SetCurrentPage();
        $item->LoadFromSQL($query->getSQL());
        return $item;
    }

    public static function remove($ids)
    {
        if (!is_array($ids) || empty($ids)) {
            return false;
        }

        $query = QueryBuilder::init()
            ->delete()
            ->from(City::TABLE_NAME)
            ->addWhere('ID IN (' . implode(',', Connection::GetSQLArray($ids)) . ')');

        return GetStatement()->Execute($query->getSQL());
    }

    public function getListForTemplate(array $selected = array(), $items = null)
    {
        $list = [];

        if (is_null($items) && !empty($this->_items)){
            $items = $this->_items;
        }

        foreach ($items as $index => $item) {
            if (!empty($selected)){
                $fields['Selected'] = (in_array($item['ID'], $selected) ? 1 : 0);
            }
            $fields['StaticPath'] = $item['StaticPath'];

            $this->addItemInTemplateList($item['ID'], $item['Title'], $list, $fields);
        }

        return $list;
    }
}