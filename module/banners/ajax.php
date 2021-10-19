<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/banner_item.php");

use Module\Banner\BannerItem;

$module = "banner";
$result = array();

$request = new LocalObject(array_merge($_GET, $_POST));


switch ($request->GetProperty("Action")) {
    case "RemoveItemImage":
        $bannerItem = new BannerItem();
        $bannerItem->load($request->GetProperty('ItemID'));
        $bannerItem->removeImage();
        break;
}

echo json_encode($result);