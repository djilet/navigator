<?php

namespace Module\Banner;

require_once(dirname(__FILE__)."/banner.php");


class BannerList extends \LocalObjectList
{
    protected $collection = [];
    public function load($active = true){
        $query = "SELECT * FROM banner_banner" . ($active == true ? " WHERE Active = 'Y'" : "");
        $this->LoadFromSQL($query);
    }

    public function getCollection(){
        foreach ($this->_items as $index => $item) {
            $banner = new Banner();
            $banner->AppendFromArray($item);
            $this->collection[] = $banner;
        }

        return $this->collection;
    }
}