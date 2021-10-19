<?php
require_once (__DIR__ . '/UniversityCategory.php');
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

/**
 * Class UniversityCategoryList
 */
class UniversityCategoryList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

    public static function getAll(array $filter = null, int $onPage = 40)
    {
        $query = QueryBuilder::init()
            ->select([
                'category.*',
            ])
            ->from(UniversityCategory::TABLE_NAME . ' AS category');

        $query->order(['Title']);

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
            ->from(UniversityCategory::TABLE_NAME)
            ->addWhere('ID IN (' . implode(',', Connection::GetSQLArray($ids)) . ')');

        return GetStatement()->Execute($query->getSQL());
    }

    public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName('ID', 'Title', $selected, $items);
    }
}