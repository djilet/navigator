<?php

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/banner_list.php");
require_once(dirname(__FILE__)."/include/banner_item_list.php");
//require_once(dirname(__FILE__) . "/../tracker/include/analytic_system/sender.php");
es_include("modulehandler.php");

use Module\Banner\Banner;
use Module\Banner\BannerList;

class BannersHandler extends ModuleHandler{
    public function ProcessHeader($module, Page $page = null)
    {
        $data = array();

        //Prepare banners for content
        //TODO from db
        $excludeTemplates = [
            'page-exhibition.html',
            'page-exhibition2.html',
            'page-exhibition4_online.html',
            'page-exhibition4.html',
            'page-exhibition5_online.html',
            'page-online-events.html',
        ];
        if (!is_null($page) && !in_array($page->GetProperty('Template'), $excludeTemplates) && !$page->GetProperty('WithoutBanners')){
            $bannerList = new BannerList();
            $bannerList->load();
            foreach ($bannerList->getCollection() as $index => $banner) {
                if ($banner instanceof Banner){
                    $bannerName = 'Banner' . $banner->GetProperty('StaticPath');

                    if ($bannerItem = $banner->getNextBannerItem()){
                        $data[$bannerName][] = $bannerItem->GetProperties();
                    }
                }
            }
        }

        return $data;
    }

    function ProcessPublic(){

	}
}