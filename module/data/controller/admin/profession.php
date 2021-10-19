<?php

require_once dirname(__FILE__) . '/../../include/profession.php';
require_once dirname(__FILE__).'/../../include/admin/profession_list.php';
require_once dirname(__FILE__).'/../../include/admin/direction_list.php';

if ($request->IsPropertySet("ProfessionID")) {
	
	if ($request->GetIntProperty("ProfessionID") > 0)
		$title = GetTranslation("title-profession-edit", $module);
	else
		$title = GetTranslation("title-profession-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => array(),
		"JavaScripts" => array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
		)
	);
	
	$content = $adminPage->Load('profession_edit.html', $header);
	
	$directionIDs = array();
	$profession = new DataProfession($module);
	
	if ($request->IsPropertySet('Save')) {
		if ($profession->save($request)) {
			header('Location: '.$moduleURL."&".$urlFilter->GetForURL()); exit(0);
		} else {
			$content->LoadFromObject($request);
			$content->LoadErrorsFromObject($profession);
		}
	} else {
		$profession->loadByID($request->GetIntProperty("ProfessionID"));
		$content->LoadFromObject($profession);

		$directionIDs = $profession->getDirectionIDs( $request->GetIntProperty("ProfessionID") );
	}
	
	$directionList = new DataDirectionList($module);
	$directionList->SetOrderBy('title_asc');
	$directionList->LoadDirectionList();

	$directionIDs = $directionIDs ? $directionIDs : $request->GetProperty('directions');
	if (!empty($directionIDs)) {
		foreach ($directionList->_items as $key => $item) {
			$directionList->_items[$key]['Selected'] = in_array($item['DirectionID'], $directionIDs);
		}
	}
	
	$content->LoadFromObjectList('DirectionList', $directionList);
	
	$content->SetLoop('OtherProfessionList', $profession->getOtherProfessions( $request->GetIntProperty("ProfessionID")));
	
} else {
	$styleSheets = array(
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
	);
	$javaScripts = array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
	);
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);
	
	$content = $adminPage->Load('profession_list.html', $header);

	$professionList = new DataProfessionList($module);
	
	if ($request->GetProperty('Do') == 'ReportCSV' && $request->GetProperty("ReportDateFrom") && $request->GetProperty("ReportDateTo"))
	{
		$professionList->exportUsersToCSV($request->GetProperty("ReportDateFrom"), $request->GetProperty("ReportDateTo"));
	}
	
	$professionList->load();
	$content->LoadFromObjectList('ProfessionList', $professionList);
	$content->SetVar("Paging", $professionList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	
	$content->SetVar("NowDate", GetCurrentDateTime());
}