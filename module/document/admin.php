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
    $config = $page->GetConfig();
    if($user->Validate(PARTNER) && $config["PartnerID"] != $user->GetIntProperty("UserID") )
    {
        //check partner permission
        Send403();
    }
    
    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
        array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
    );

    $orderList = new DocumentOrderList($module);

    $header = array(
        "Title"       => GetTranslation("module-admin-title", $module),
        "Navigation"  => $navigation,
        "JavaScripts" => (isset($javaScripts) ? $javaScripts : array()),
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
    if($user->Validate(PARTNER))
    {
        //check partner permission
        Send403();
    }
    
    $urlFilter->LoadFromObject($request, array('AllOrders'));
    
    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
    );
    
    $orderList = new DocumentOrderList($module);
    
    $header = array(
        "Title"       => GetTranslation("module-admin-title", $module),
        "Navigation"  => $navigation,
        "JavaScripts" => $javaScripts,
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
	if($user->Validate(PARTNER))
	{
	    //filter by partner permission
	    $pageListItems = $pageList->GetItems();
	    $filteredPageList = array();
	    for($i=0; $i<count($pageListItems); $i++)
	    {
	        $documentPage = new Page();
	        if ($documentPage->LoadByID($pageListItems[$i]['PageID']))
	        {
	            $config = $documentPage->GetConfig();
	            if($config["PartnerID"] == $user->GetIntProperty("UserID"))
	            {
	                $filteredPageList[] = $pageListItems[$i];
	            }
	        }
	    }
	    $content->SetLoop('PageList', $filteredPageList);
	}
	else
	{
	    $content->LoadFromObjectList('PageList', $pageList);
	    $content->SetVar('FullMode', 1);
	}
	
	$content->SetVar('BaseURL', $moduleURL);
}
    
$adminPage->Output($content);