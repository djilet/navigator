<?php

if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/limited_order_list.php");
require_once(dirname(__FILE__) . "/include/limit_list.php");
require_once(dirname(__FILE__) . "/include/limit.php");
es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
$page = DefineInitialPage($request);

$urlFilter->LoadFromObject($request);

$header = array(
    "Title"       => GetTranslation("module-admin-title", $module),
    "Navigation"  => $navigation,
    "JavaScripts" => array(
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
    ),
    "StyleSheets" => array(
        array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css")
    )
);

if ($request->GetProperty("Action") == 'ExportCSV'){
    $orderList = new LimitedOrderList($module);
    $orderList->load(new LocalObject([
        'FullList' => true,
        'PageID' => $request->GetProperty('PageID'),
        'DateFrom' => $request->GetProperty('DateFrom'),
        'DateTo' => $request->GetProperty('DateTo'),
    ]));

    $orderList->exportToCSV();
}
elseif ($request->IsPropertySet("PageID"))
{
    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
        array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
    );

    if ($request->IsPropertySet("Section") && $request->GetProperty("Section") == 'limits')
    {
        if ($request->IsPropertySet("LimitID"))
        {
            $limit = new Limit($module);

            if ($request->GetProperty("Save"))
            {
                $limit->LoadFromObject($request);

                if ($limit->Save())
                {
                    header("location: ".$moduleURL.'&PageID='.$request->GetIntProperty('PageID').'&Section=limits');
                    exit();
                }
                else
                {
                    $content->LoadErrorsFromObject($limit);
                }
            }
            elseif ($request->IsPropertySet("Do") && $request->GetProperty("Do") == 'Remove')
            {
                    $limit->Remove($request->GetProperty("LimitID"));
                    header("location: ".$moduleURL.'&PageID='.$request->GetIntProperty('PageID').'&Section=limits');
                    exit();
            }
            else
            {
                $limit->LoadByID($request->GetProperty("LimitID"));
                $content = $adminPage->Load("limit_edit.html", $header);
                $content->LoadFromObject($request);

                $content->LoadFromObject($limit);

                $content->LoadErrorsFromObject($limit);
                $content->LoadMessagesFromObject($limit);

                $content->SetVar('BaseURL', $moduleURL.'&PageID='.$request->GetIntProperty('PageID').'&Section=limits&LimitID='.$request->IsPropertySet("LimitID"));
            }
        }
        else
        {
            $limitList = new LimitList($module);
            $limitList->load($request);
            $content = $adminPage->Load("limit_list.html", $header);
            $content->LoadFromObjectList('LimitList', $limitList);

            $content->LoadFromObject($request);

            $content->LoadErrorsFromObject($limitList);
            $content->LoadMessagesFromObject($limitList);

            $content->SetVar('BaseURL', $moduleURL.'&PageID='.$request->GetIntProperty('PageID').'&Section=limits');
        }
    }
    else
    {
        $orderList = new LimitedOrderList($module);
        $content = $adminPage->Load("limited_order_list_full.html", $header);
        $content->LoadFromObject($request);

        $orderList->load($request);
        $content->LoadFromObjectList('OrderList', $orderList);
        $content->SetVar('ItemsCount', $orderList->GetCountTotalItems());
        $content->SetVar("Paging", $orderList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));

        $content->LoadErrorsFromObject($orderList);
        $content->LoadMessagesFromObject($orderList);

        $content->SetVar('BaseURL', $moduleURL.'&PageID='.$request->GetIntProperty('PageID'));

        //Template settings
        $config = $page->GetConfig();
        foreach ($config as $property => $value) {
            $content->SetVar($property, $value);
        }
    }
}

else if($request->IsPropertySet("AllOrders"))
{
	$urlFilter->LoadFromObject($request);
	
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
	);
	
	$orderList = new LimitedOrderList($module);
	$content = $adminPage->Load("limited_order_list_full.html", $header);
    $content->LoadFromObject($request);
	$orderList->load($request);
	$content->LoadFromObjectList('OrderList', $orderList);
    $content->SetVar('ItemsCount', $orderList->GetCountTotalItems());
	$content->SetVar("Paging", $orderList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	
	$content->LoadErrorsFromObject($orderList);
	$content->LoadMessagesFromObject($orderList);
	
	$content->SetVar('BaseURL', $moduleURL.'&AllOrders='.$request->GetIntProperty('AllOrders'));
	$content->SetVar('AllOrders', true);
}
else 
{
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL)
	);
	
	$pageList = new PageList();
	$content = $adminPage->Load("page_list.html", $header);
    $content->LoadFromObject($request);
	
	$pageList->LoadPageListForModule($module);
	$content->LoadFromObjectList('PageList', $pageList);
	
	$content->SetVar('BaseURL', $moduleURL);
}
    
$adminPage->Output($content);