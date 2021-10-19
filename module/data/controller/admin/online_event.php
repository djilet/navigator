<?php

if ($request->IsPropertySet("OnlineEventID"))
{
	$urlFilter->AppendFromObject($request, array("Page"));
	if ($request->GetProperty("OnlineEventID") > 0)
		$title = GetTranslation("title-online-event-edit", $module);
	else
		$title = GetTranslation("title-online-event-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL()."&OnlineEventID=".$request->GetProperty("OnlineEventID"));
	$styleSheets = array(
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/timepicker/css/timepicker.min.css")
	);
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/timepicker/js/timepicker.min.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("online_event_edit.html", $header);

	$onlineEvent = new DataOnlineEvent($module);

	if ($request->GetProperty("Save"))
	{
		$onlineEvent->LoadFromObject($request);
		if ($onlineEvent->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($onlineEvent);
		}
	}
	else
	{
		$onlineEvent->LoadByID($request->GetProperty("OnlineEventID"));
	}
	
	$content->LoadFromObject($onlineEvent);
	
	$userStatusList = $onlineEvent->GetUserStatusList($request->GetProperty("OnlineEventID"));
	$content->SetLoop("UserStatusList", $userStatusList);
	$content->SetVar("UserStatusCount", count($userStatusList));
	
	$content->SetLoop('LinkedUniversityList', $onlineEvent->GetLinkedUniversity($request->GetProperty("OnlineEventID")));
	$content->SetLoop('LinkedDirectionList', $onlineEvent->GetLinkedDirection($request->GetProperty("OnlineEventID")));
	$content->SetLoop('LinkedProfessionList', $onlineEvent->GetLinkedProfession($request->GetProperty("OnlineEventID")));
	$content->SetLoop('LinkList', $onlineEvent->GetLinks($request->GetProperty("OnlineEventID")));

	if($user->GetProperty("Role") == "integrator" || $user->GetProperty("Role") == "onlineevent"){
		$content->SetVar("FullAccess", 1);
	}
}
else
{
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
	
	$content = $adminPage->Load("online_event_list.html", $header);

	$onlineEventList = new DataOnlineEventList($module);

	if ($request->GetProperty('Do') == 'Remove' && $request->GetProperty("OnlineEventIDs"))
	{
		$onlineEventList->Remove($request->GetProperty("OnlineEventIDs"));
		$content->LoadMessagesFromObject($onlineEventList);
	}
	else if ($request->GetProperty('Do') == 'ReportCSV' && $request->GetProperty("ReportDateFrom") && $request->GetProperty("ReportDateTo"))
	{
		$onlineEventList->exportRegistrationsToCSV($request->GetProperty("ReportDateFrom"), $request->GetProperty("ReportDateTo"));
	}

	$onlineEventList->LoadOnlineEventList($request);
	$content->LoadFromObjectList("OnlineEventList", $onlineEventList);

	$content->SetVar("Paging", $onlineEventList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $onlineEventList->GetItemsRange(), 'Total' => $onlineEventList->GetCountTotalItems())));
	$urlFilter->SetProperty('Page', $onlineEventList->GetCurrentPage());
	
	$content->SetVar("NowDate", GetCurrentDateTime());
}