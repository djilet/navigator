<?php

if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/order_list.php");
es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
$page = DefineInitialPage($request);

$urlFilter->LoadFromObject($request, array('PageID'));

if ($request->IsPropertySet("PageID")) 
{
    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
        array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
    );

    $orderList = new ServiceOrderList($module);

    $header = array(
        "Title"       => GetTranslation("module-admin-title", $module),
        "Navigation"  => $navigation,
        "JavaScripts" => $javaScripts,
    );
    $content = $adminPage->Load("order_list.html", $header);

    $orderList->load($request);
    $content->LoadFromObjectList('OrderList', $orderList);
    $content->SetVar("Paging", $orderList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));

    $content->LoadErrorsFromObject($orderList);
    $content->LoadMessagesFromObject($orderList);

    $content->SetVar('BaseURL', $moduleURL.'&PageID='.$request->GetIntProperty('PageID'));
}
else if($request->IsPropertySet("AllOrders"))
{
    $urlFilter->LoadFromObject($request, array('AllOrders'));
    
    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
    );
    
    $orderList = new ServiceOrderList($module);
    
    $header = array(
        "Title"       => GetTranslation("module-admin-title", $module),
        "Navigation"  => $navigation,
        "JavaScripts" => (isset($javaScripts) ? $javaScripts : array()),
    );
    $content = $adminPage->Load("order_list_full.html", $header);
    
    $orderList->load($request);
    $content->LoadFromObjectList('OrderList', $orderList);
    $content->SetVar("Paging", $orderList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
    
    $content->LoadErrorsFromObject($orderList);
    $content->LoadMessagesFromObject($orderList);
    
    $content->SetVar('BaseURL', $moduleURL.'&AllOrders='.$request->GetIntProperty('AllOrders'));
}
else 
{
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL)
	);
	
	$pageList = new PageList();
	
	$header = array(
			"Title"       => GetTranslation("module-admin-title", $module),
			"Navigation"  => $navigation,
			"JavaScripts" => (isset($javaScripts) ? $javaScripts : array()),
	);
	$content = $adminPage->Load("page_list.html", $header);
	
	$pageList->LoadPageListForModule($module);
	$content->LoadFromObjectList('PageList', $pageList);
	
	$content->SetVar('BaseURL', $moduleURL);
}
    
$adminPage->Output($content);