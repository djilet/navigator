<?php
require_once (__DIR__ . '/UniversityAgent.php');
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');
/**
 * Class UniversityAgentList
 */
class UniversityAgentList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

    public static function getAll(array $filter = null, int $onPage = 40)
    {
        $query = QueryBuilder::init()
            ->select([
                'agent.*',
                'university.Title AS UniversityTitle',
                'user.Email AS UserEmail',
            ])
            ->addJoin('LEFT JOIN data_university AS university ON agent.UniversityID = university.UniversityID')
            ->addJoin('LEFT JOIN user AS user ON agent.UserID = user.UserID')
            ->from(UniversityAgent::TABLE_NAME . ' AS agent');

        if (!empty($filter)){

        }

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
            ->from(UniversityAgent::TABLE_NAME)
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