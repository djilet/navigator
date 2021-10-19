<?php

namespace Module\Banner;

class BannerItemPage extends \LocalObject
{
    protected $module = 'banners';

    public function load($pageId)
    {
        $query = "SELECT * FROM banner_item_page WHERE PageID = " . intval($pageId);
        $this->LoadFromSQL($query);
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $stmt = GetStatement();

        if (!empty($this->GetProperty('PageID'))) {
            $query = "UPDATE banner_item_page SET StaticPath = " . $this->GetPropertyForSQL("StaticPath") .
                " WHERE PageID = " . $this->GetIntProperty("PageID");
        } else {
            $query = "INSERT INTO banner_item_page SET ItemID = " . $this->GetIntProperty("ItemID") . ",
            StaticPath = " . $this->GetPropertyForSQL('StaticPath');
        }

        if ($stmt->Execute($query)) {
            if (empty($this->GetProperty('ItemID'))) {
                $this->SetProperty('PageID', $stmt->GetLastInsertID());
            }

            return true;
        }

        return false;
    }

    public function remove()
    {
        $query = "DELETE FROM banner_item_page WHERE PageID = " . $this->GetProperty('PageID');
        GetStatement()->Execute($query);
    }

    public function validate()
    {
        if ($this->ValidateNotEmpty("StaticPath")) {
            $this->SetProperty("StaticPath", trim(trim($this->GetProperty("StaticPath")), "/"));

            return true;
        }

        return false;
    }
}