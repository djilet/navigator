<?php

namespace Module\Banner;

require_once(dirname(__FILE__) . "/banner_item.php");

class BannerItemList extends \LocalObjectList
{
    protected $collection = [];
    protected $imageConfig = [];

    public function loadByBanner($id, $imageConfig = BANNERS_IMAGE_CONFIG, $active = true){
        $this->imageConfig = $imageConfig;
        $query = "SELECT * FROM banner_item WHERE BannerID = " . intval($id);
        if ($active == true){
            $query .= " AND Active = 'Y'";
        }
        $this->LoadFromSQL($query);
    }

    public function getCollection(){
        foreach ($this->_items as $index => $item) {
            $bannerItem = new BannerItem($this->imageConfig);
            $bannerItem->LoadFromArray($item);
            $bannerItem->prepare();
            $this->collection[] = $bannerItem;
        }

        return $this->collection;
    }
}