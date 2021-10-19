<?php
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once dirname(__FILE__) . '/include/olympiad.php';
require_once dirname(__FILE__) . '/include/main_list.php';
es_include("localpage.php");
es_include("urlfilter.php");

use Module\Olympiad\MainList;

$module = "olympiad";
$request = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');

switch ($request->GetProperty("Action")) {
	case "LoadOlympiad":
		$mainList = new MainList();
		$result['status'] = 'success';

		/*$page = new Page();
		$page->LoadByID($request->GetIntProperty('PageID'));
		$url = $page->GetPageURL(false);*/
        $url = PROJECT_PATH . $module;

        $mainList->loadForFilter($request);

		$popupPage = new PopupPage($module, false);
		$tpl = $popupPage->Load('olympiad-tmpl/olympiad_list.html');
		$tpl->LoadFromObjectList('MainList', $mainList);
		$tpl->SetLoop('MainPager', $mainList->GetPagingAsArray($url, $url));
		$tpl->SetVar('BaseURL', $url);
		$result['html'] = $popupPage->Grab($tpl);

		break;
}

//print_r($result);

echo json_encode($result);
