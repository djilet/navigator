<?php

namespace Module\Banner;

require_once(dirname(__FILE__)."/banner_item_list.php");
require_once(dirname(__FILE__)."/banner_item.php");

class Banner extends \LocalObject
{
    protected $itemList = null;
    public function load($id){
        $query = "SELECT * FROM banner_banner WHERE BannerID = " . intval($id);
        $this->LoadFromSQL($query);
    }

    public function loadItemList($active = true){
        $this->itemList = new BannerItemList();
        $this->itemList->loadByBanner($this->GetProperty('BannerID'), $this->GetProperty('ImageConfig'), $active);
    }

    public function save(){
        $stmt = GetStatement();
        $query = '';
        $where = [];

        if ($this->GetProperty('BannerID') > 0){
            $query .= "UPDATE banner_banner SET ";
            $where[] = "BannerID = " . $this->GetProperty('BannerID');
        }
        else{
            $query .= "INSERT INTO banner_banner SET ";
        }

        $query .=
        "Name = " . $this->GetPropertyForSQL('Name') . ", 
        ImageConfig = " . $this->GetPropertyForSQL('ImageConfig') . ",
        StaticPath = " . $this->GetPropertyForSQL('StaticPath') . ",
        RotateInterval = " . $this->GetIntProperty('RotateInterval') . ",
        Active = " . \Connection::GetSQLString(($this->GetProperty('Active') == 'Y' ? 'Y' : 'N')) .
        (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "");

        echo $query;

        return $stmt->Execute($query);
    }

    public function getItemList(){
        if (is_null($this->itemList)){
            $this->loadItemList();
        }

        $result = [];
        foreach ($this->itemList->getCollection() as $index => $item) {
            $result[] = $item->GetProperties();
        }

        return $result;
    }

    public function getNextBannerItem(){
        if ($item = BannerItem::getNextItemForBanner($this)){
            $item->addView();
            return $item;
        }

        return false;
    }
}