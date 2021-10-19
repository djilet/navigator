<?php

namespace Module\Banner;

es_include("image_manager.php");
require_once(dirname(__FILE__)."/banner_item_page_list.php");
require_once(dirname(__FILE__)."/banner_item_page.php");

class BannerItem extends \LocalObject
{
    public $_acceptMimeTypes = array(
        'image/png',
        'image/x-png',
        'image/gif',
        'image/jpeg',
        'image/pjpeg'
    );

    protected $module = 'banners';
    protected $params = [];
    protected $pageList = null;

    public static function getNextItemForBanner(Banner $banner){
        $stmt = GetStatement();
        $dateTime = GetCurrentDateTime();

        $urlParser = GetURLParser();
        $fullPath = trim($urlParser->GetFullPathAsString(), "/");
        $fullPath = ltrim($fullPath, "test");

        $pageList = new BannerItemPageList();
        $itemIds = $pageList->getItemIdsByStaticPath($fullPath);

        $query = \QueryBuilder::init()
            ->select(['*'])
            ->from('banner_item')
            ->where([
                "ViewCount = (
                    SELECT MIN(ViewCount)
                    FROM `banner_item`
                    WHERE Active = 'Y'
                    AND (IF(PeriodFrom, PeriodFrom <= '{$dateTime}', 1))
                    AND (IF(PeriodTo, PeriodTo >= '{$dateTime}', 1))
                    AND BannerID = " . intval($banner->GetIntProperty('BannerID')) . "
                    AND ItemID IN (" . implode(",", $itemIds) . "))",
                "Active = 'Y'",
                "(IF(PeriodFrom, PeriodFrom <= '{$dateTime}', 1))",
                "(IF(PeriodTo, PeriodTo >= '{$dateTime}', 1))",
                "BannerID = " . $banner->GetIntProperty('BannerID'),
            ])
            ->limit(1);

        $result = $stmt->FetchRow($query->getSQL());

        if (!$result) {
            $query = \QueryBuilder::init()
                ->select(['*'])
                ->from('banner_item')
                ->where([
                    "ViewCount = (
                    SELECT MIN(ViewCount)
                    FROM `banner_item`
                    WHERE Active = 'Y'
                    AND (IF(PeriodFrom, PeriodFrom <= '{$dateTime}', 1))
                    AND (IF(PeriodTo, PeriodTo >= '{$dateTime}', 1))
                    AND BannerID = " . intval($banner->GetIntProperty('BannerID')) . ")",
                    "Active = 'Y'",
                    "(IF(PeriodFrom, PeriodFrom <= '{$dateTime}', 1))",
                    "(IF(PeriodTo, PeriodTo >= '{$dateTime}', 1))",
                    "BannerID = " . $banner->GetIntProperty('BannerID'),
                ])
                ->limit(1);

            $result = $stmt->FetchRow($query->getSQL());
        }

       if ($result) {
           $item = new self($banner->GetProperty('ImageConfig'));
           $item->AppendFromArray($result);
           $item->prepare();
           return $item;
       }

       return false;
    }

    public static function resetCountByBannerID($bannerID){
        $query = "UPDATE banner_item SET ViewCount = 0 WHERE BannerID = " . intval($bannerID);
        GetStatement()->Execute($query);
    }

    public function __construct($imageConfig = BANNERS_IMAGE_CONFIG)
    {
        $this->params["Item"] = LoadImageConfig("ItemImage", $this->module, $imageConfig);
        parent::LocalObject();
    }

    public function load($id){
        $query = "SELECT item.*, ban.ImageConfig FROM banner_item AS item
                  LEFT JOIN banner_banner AS ban ON item.BannerID = ban.BannerID
                  WHERE ItemID = " . intval($id);
        $this->LoadFromSQL($query);
        $this->loadPageList();
        $this->params["Item"] = LoadImageConfig("ItemImage", $this->module, $this->GetProperty('ImageConfig'));
        $this->prepare();
    }

    public function loadPageList(){
        $this->pageList = new BannerItemPageList();
        $this->pageList->loadByItemId($this->GetProperty('ItemID'));
    }

    public function getPageList(){
        return $this->pageList;
    }

    public function addView(){
        $this->SetProperty('ViewCount', $this->GetProperty('ViewCount') + 1);
        $query = "UPDATE banner_item SET ViewCount = ViewCount + 1 WHERE ItemID = " . $this->GetIntProperty('ItemID');
        GetStatement()->Execute($query);
    }

    public function save(){
        \ImageManager::SaveImage($this, BANNERS_IMAGE_DIR, $this->GetProperty("SavedItemImage"), 'Item');
        $this->validate();

        if ($this->HasErrors()){
            return false;
        }

        $stmt = GetStatement();
        $query = '';
        $where = [];

        $this->SetProperty('Active', ($this->GetProperty('Active') == 'Y' ? 'Y' : 'N'));

        if ($this->GetProperty('ItemID') > 0){
            $query .= "UPDATE banner_item SET ";
            $where[] = 'ItemID = ' . $this->GetIntProperty('ItemID');
        }
        else{
            $query .= "INSERT INTO banner_item SET ";
        }

        $query .= "BannerID = " . $this->GetIntProperty('BannerID') . ", 
        Link = " . $this->GetPropertyForSQL('Link') . ", 
        Name = " . $this->GetPropertyForSQL('Name') . ", 
        ItemImage = " . $this->GetPropertyForSQL('ItemImage') . ",
        Active = " . $this->GetPropertyForSQL('Active') . ",
        PeriodFrom = " . \Connection::GetSQLDateTime($this->GetProperty('PeriodFrom')) . ",
        PeriodTo = " . \Connection::GetSQLDateTime($this->GetProperty('PeriodTo')) .
        (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "");

        if ($stmt->Execute($query)){
            if (empty($this->GetProperty('ItemID'))) {
                $this->SetProperty('ItemID', $stmt->GetLastInsertID());
            }

            $this->savePageList();
            return true;
        }

        return false;
    }

    public function savePageList(){
        $this->loadPageList();
        $newPageIds = [];

        if ($this->IsPropertySet("PageListStaticPath") && $this->IsPropertySet("PageListPageId")) {
            $staticPathList = $this->GetProperty("PageListStaticPath");
            $pageIdList = $this->GetProperty("PageListPageId");

            foreach ($staticPathList as $key => $staticPath) {
                $itemPage = new BannerItemPage();
                $itemPage->SetProperty("StaticPath", $staticPath);
                $itemPage->SetProperty("PageID", $pageIdList[$key]);
                $itemPage->SetProperty("ItemID", $this->GetProperty("ItemID"));
                if ($itemPage->save()) {
                    $newPageIds[] = $itemPage->GetProperty("PageID");
                }
            }
        }

        foreach ($this->pageList->getCollection() as $page) {
            if (!in_array($page->GetProperty("PageID"), $newPageIds)) {
                $page->remove();
            }
        }
    }

    public function remove(){
        \ImageManager::RemoveImage(BANNERS_IMAGE_DIR . $this->GetProperty('ItemImage'));
        $this->removePageList();

        $query = "DELETE FROM banner_item WHERE ItemID = " . $this->GetProperty('ItemID');
        GetStatement()->Execute($query);
    }

    public function removePageList(){
        $this->loadPageList();
        foreach ($this->pageList->getCollection()  as $page) {
            $page->remove();
        }
    }

    public function removeImage(){
        \ImageManager::RemoveImage(BANNERS_IMAGE_DIR . $this->GetProperty('ItemImage'));
        $query = "UPDATE banner_item SET ItemImage = NULL WHERE ItemID = " . $this->GetProperty('ItemID');
        GetStatement()->Execute($query);
    }

    public function validate(){
        if (!$this->ValidateNotEmpty('ItemImage')){
            $this->AddError('empty-image', $this->module);
        }

        if (!$this->ValidateNotEmpty('Name')){
            $this->AddError('empty-name', $this->module);
        }

        if (!$this->ValidateNotEmpty('Link')){
            $this->AddError('empty-link', $this->module);
        }
    }

    public function prepare(){
        PrepareImagePath($this->_properties, 'Item', $this->params['Item']);
        $this->SetProperty('ItemImageFullPath', \ImageManager::getImageUrl('banners', $this->GetProperty('ItemImage'), '1612x90', 0));
    }
}