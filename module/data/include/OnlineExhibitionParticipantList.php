<?php
require_once (__DIR__ . '/OnlineExhibitionParticipant.php');
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

/**
 * Class OnlineExhibitionParticipantList
 */
class OnlineExhibitionParticipantList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;

    public static function getAll(array $filter = null, int $onPage = 0)
    {
        $query = QueryBuilder::init()
            ->select([
                'o_exhibition_part.*',
            ])
            ->from(OnlineExhibitionParticipant::TABLE_NAME . ' AS o_exhibition_part');

        if (!empty($filter)){
            if (!empty($filter['OnlineExhibitionIds'])){
                $ids = implode(", ", Connection::GetSQLArray($filter['OnlineExhibitionIds']));
                $query->addWhere("OnlineExhibitionID IN ({$ids})");
            }
        }

        $query->order(['SortOrder']);

        $item = new static();
        $item->SetItemsOnPage($onPage);
        $item->SetCurrentPage();
        $item->LoadFromSQL($query->getSQL());
        return $item;
    }

    public static function getOnlineEventsIds(int $exhibitionId): array
    {
        $result = [];
        $query = QueryBuilder::init()
            ->select([
                'DISTINCT OnlineEventIDs',
            ])
            ->from(OnlineExhibitionParticipant::TABLE_NAME)
            ->addWhere("OnlineExhibitionID = {$exhibitionId}");
        $rows = GetStatement()->FetchRows($query->getSQL());
        foreach ($rows as $row){
            if ($row){
                $ids = json_decode($row);
                $result = array_merge($result, $ids);
            }
        }

        if (is_array($result)){
            return array_unique($result);
        }

        return [];
    }

    public function prepareForTemplate()
    {
        foreach ($this->_items as $key => $row){
            $this->_items[$key] = OnlineExhibitionParticipant::prepareRow($row);
        }
    }

    public static function remove($ids)
    {
        if (!is_array($ids) || empty($ids)) {
            return false;
        }

        $query = QueryBuilder::init()
            ->delete()
            ->from(OnlineExhibitionParticipant::TABLE_NAME)
            ->addWhere('ID IN (' . implode(',', Connection::GetSQLArray($ids)) . ')');

        return GetStatement()->Execute($query->getSQL());
    }

    public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName('ID', 'Title', $selected, $items);
    }
}