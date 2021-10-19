<?php
require_once (__DIR__ . '/OnlineExhibition.php');
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');
/**
 * Class OnlineExhibitionList
 */
class OnlineExhibitionList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

    public static function getAll(array $filter = null, int $onPage = 40)
    {
        $query = QueryBuilder::init()
            ->select([
                'o_exhibition.*',
            ])
            ->from(OnlineExhibition::TABLE_NAME . ' AS o_exhibition');

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
            ->from(OnlineExhibition::TABLE_NAME)
            ->addWhere('ID IN (' . implode(',', Connection::GetSQLArray($ids)) . ')');

        return GetStatement()->Execute($query->getSQL());
    }

    public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName('ID', 'Title', $selected, $items);
    }
}