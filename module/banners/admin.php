<?php

if (!defined('IS_ADMIN')) {
	echo "Incorrect call to admin interface";
	exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/banner.php");
require_once(dirname(__FILE__) . "/include/banner_list.php");
require_once(dirname(__FILE__) . "/include/banner_item.php");
require_once(dirname(__FILE__) . "/include/banner_item_list.php");

es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");
es_include("js_calendar/calendar.php");

use Module\Banner\BannerList;
use Module\Banner\Banner;
use Module\Banner\BannerItem;
use Module\Banner\BannerItemList;

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
//$page = DefineInitialPage($request);
$boxTitle = array('Title' => '', 'replacements' => array());

$urlFilter->LoadFromObject($request, array('PageID'));

//Init
$navigation = array(
	//array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
);

$header = array(
	"Title"       => GetTranslation("module-admin-title", $module),
	"Navigation"  => $navigation,
	"JavaScripts" => array(
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
	),
	"StyleSheets" => array(
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css")
	)
);

$sections = array(
	array(
		'Title' => GetTranslation("module-banner-list-title", $module),
		'Section' => 'BannerList'
	),
);

$selectedSection = ($request->IsPropertySet('Section') ? $request->GetProperty('Section') : $sections[0]['Section']);
$urlFilter->SetProperty('Section', $selectedSection);
$baseUrl = $moduleURL;

foreach ($sections as $key => $section) {
	$section['Link'] = $baseUrl . '&PageID='.$request->GetIntProperty('PageID') . '&Section=' . $section['Section'];

	if ($section['Section'] == $selectedSection){
		$section['Selected'] = 1;
		$navigation[] = array("Title" => $section['Title'], "Link" => $section['Link']);
	}

	$sections[$key] = $section;
}
$content = $adminPage->Load("index.html", $header);
$content->LoadFromObject($request);


//Content
switch ($selectedSection){
    case 'BannerList':
        if ($request->IsPropertySet('ItemID')){
            $bannerItem = new BannerItem();
            if ($request->GetProperty('Action') == 'Save'){
                $bannerItem->LoadFromObject($request);
                if ($bannerItem->save()){
                    BannerItem::resetCountByBannerID($bannerItem->GetIntProperty('BannerID'));
                    Send302($baseUrl . '&' . $urlFilter->GetForURL() . '&' . 'ItemListByBannerID=' . $request->GetProperty('BannerID'));
                }

                $content->LoadFromObject($request);
                $content->LoadErrorsFromObject($bannerItem);
            }
            elseif ($request->GetProperty('Action') == 'Remove'){
                $bannerItem->load($request->GetProperty('ItemID'));
                $bannerItem->remove();
                Send302($baseUrl . '&' . $urlFilter->GetForURL() . '&' . 'ItemListByBannerID=' . $bannerItem->GetProperty('BannerID'));
            }

            $bannerItem->load($request->GetIntProperty('ItemID'));
            $content->LoadFromObject($bannerItem);
            $content->LoadFromObjectList("PageList", $bannerItem->getPageList());

            //print_r($item);

            $content->SetVar('ItemEdit', true);
            $boxTitle['Title'] = 'item-edit';
        }
        elseif ($request->IsPropertySet('ItemListByBannerID')){
            $banner = new Banner();
            $banner->load($request->GetProperty('ItemListByBannerID'));
            $banner->loadItemList(false);
            $content->SetLoop('ItemList', $banner->getItemList());

            $content->SetVar('TemplateItemList', true);
            $boxTitle['Title'] = 'item-list';
        }
        elseif ($request->IsPropertySet('BannerID')){
            $banner = new Banner();
            if ($request->GetProperty('Action') == 'Save'){
                $banner->LoadFromObject($request,['BannerID', 'Name', 'ImageConfig', 'RotateInterval', 'Active', 'StaticPath']);
                if ($banner->save()){
                    Send302($baseUrl . '&' . $urlFilter->GetForURL());
                }
                $content->LoadErrorsFromObject($banner);
            }

            $banner->load($request->GetIntProperty('BannerID'));
            $content->LoadFromObject($banner);

            $content->SetVar('BannerEdit', true);
            $boxTitle['Title'] = 'banner-edit';
        }
        else{
            $bannerList = new BannerList();
            $bannerList->load(false);
            $content->LoadFromObjectList('BannerList', $bannerList);
        }
        break;
}

$content->SetLoop('SectionMenu', $sections);
$content->SetVar('BaseURL', $baseUrl);
//$content->SetVar('PageTitle', $page->GetProperty('Title'));
//$content->SetVar('PageStaticPath', $page->GetProperty('StaticPath'));
$content->SetVar('BoxTitle', GetTranslation($boxTitle['Title'], $module, $boxTitle['replacements']));

$navigation[] = array("Title" => GetTranslation($boxTitle['Title'], $module), "Link" => '');
$content->SetLoop("Navigation", $navigation);
$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
$adminPage->Output($content);