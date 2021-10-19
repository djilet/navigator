<?php
if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/user_event_list.php");
es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);

$urlFilter = new URLFilter();
$urlFilter->LoadFromObject($request, array("FilterDateFrom", "FilterDateTo", "FilterLeadTo", "FilterEvent"));

$navigation = array(
	array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
);
$styleSheets = array(
	array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
);
$javaScripts = array(
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
);

$header = array(
	"Title" => GetTranslation("module-admin-title", $module),
	"StyleSheets" => $styleSheets,
	"JavaScripts" => $javaScripts,
	"Navigation" => $navigation,
);

if ($request->GetProperty("Action") == 'ExportCSV'){
    $eventList = new UserEventList();
    $eventList->loadForLeadAfterBlog($request);
    
    $eventList->exportToCSV();
}
else {
    $content = $adminPage->Load("user_event_list.html", $header);
    
    $eventList = new UserEventList();
    $eventList->loadForLeadAfterBlog($request);
    
    $content->SetVar("Paging", $eventList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
    $content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $eventList->GetItemsRange(), 'Total' => $eventList->GetCountTotalItems())));
    $content->SetVar("ParamsForFilter", $urlFilter->GetForForm(array_merge(array('Page'), $filterParams)));
    $content->LoadFromObjectList('EventList', $eventList);
    
    $content->SetVar("FilterDateFrom", $request->GetProperty("FilterDateFrom"));
    $content->SetVar("FilterDateTo", $request->GetProperty("FilterDateTo"));
    $content->SetVar("FilterLeadTo", $request->GetProperty("FilterLeadTo"));
    $content->SetVar("FilterEvent", $request->GetProperty("FilterEvent"));
    
    $adminPage->Output($content);
}
