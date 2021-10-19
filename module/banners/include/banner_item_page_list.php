<?php

namespace Module\Banner;

class BannerItemPageList extends \LocalObjectList
{
    protected $collection = [];

    public function loadByItemId($itemId)
    {
        $query = "SELECT * FROM banner_item_page WHERE ItemID = " . intval($itemId);
        $this->LoadFromSQL($query);
    }

    public function getItemIdsByStaticPath($staticPath)
    {
        $query = "SELECT ItemID FROM banner_item_page WHERE StaticPath = " . \Connection::GetSQLString($staticPath);
        return array_column(GetStatement()->FetchList($query), "ItemID");
    }

    public function getCollection(){
        foreach ($this->_items as $index => $item) {
            $page = new BannerItemPage();
            $page->AppendFromArray($item);
            $this->collection[] = $page;
        }

        return $this->collection;
    }
}