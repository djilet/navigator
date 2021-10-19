<?php

$javaScripts = array();
$styleSheets = array();

$urlFilter = new URLFilter();
$urlFilter->LoadFromObject($request, array("DateFrom", "DateTo"));

if ($request->IsPropertySet('MarathonUserID')){
	$marathonUserID = $request->GetIntProperty('MarathonUserID');
	$marathon_user = new MarathonUser();
	$marathon_user->loadByID($marathonUserID);
	
	$user = new UserItem($module);
	$user->loadByID($marathon_user->GetIntProperty('UserID'));
	
	$navigation[] = array(
		"Title" => ($user->IsPropertySet('UserName') ? $user->GetProperty('UserName') : $request->GetIntProperty('UserID')),
	    "Link" => $moduleURL."&MarathonUserID=".$marathonUserID
	);
	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts,
		"StyleSheets" => $styleSheets
	);

	if ($request->IsPropertySet('StepID')){
		$content = $adminPage->Load("user_map_info.html", $header);
		$map = new MarathonMapStep($request->GetProperty('StepID'), $marathonUserID);
		$content->LoadFromObject($map);
	}
	elseif ($request->IsPropertySet('PartID')){
		$content = $adminPage->Load("user_stage_info.html", $header);

		$part = new MarathonPart($module);
		$part->loadByID($request->GetProperty('PartID'), $marathonUserID);

		$task = new MarathonTask($module);
		$task->loadForUser($part, $marathonUserID, null, $request->GetProperty('DirectTaskID'));

		foreach ($task->GetProperty('TaskList') as $index => $item) {
			$temp_task = new MarathonTask($module);
			$temp_task->loadForUser($part, $marathonUserID, null, $item['TaskID']);

			$task_list[$index]['TaskID'] = $temp_task->GetProperty('TaskID');
			$task_list[$index]['Title'] = $temp_task->GetProperty('TaskTitle');
			$task_list[$index]['Answer'] = (!empty($temp_task->IsPropertySet('TaskAnswer')) ? $temp_task->GetProperty('TaskAnswer') : '');
			$task_list[$index]['TaskType'] = $temp_task->GetProperty('TaskType');
			$task_list[$index]['TaskStatus'] = $item['Status'];
			$task_list[$index]['Current'] = $item['Current'];
		}

		$content->LoadFromObject($part);
		$content->LoadFromObject($task);
		$content->SetLoop('TaskList', $task_list);

	}
	else{
		$content = $adminPage->Load("user_info.html", $header);
		$content->LoadFromObject($user);
		
		$stage = new MarathonStage('marathon');
		$stageList = $stage->loadListForUser($marathonUserID);
		$content->SetLoop('MarathonUserStages', $stageList);

		$i = 0;
		foreach (UserInfoItem::GetItemsName() as $key => $item) {
			$info_item = new UserInfoItem($item, $marathonUserID);
			$info_item->load();

			if ($info_item->getValues()){
				$result['UserItems'][$i]['Title'] = $info_item->GetProperty('Title');
				$result['UserItems'][$i]['Type'] = $info_item->GetProperty('Type');
				$result['UserItems'][$i]['Content'] = $info_item->GetProperty('Content');
				$i++;
			}
		}
		$content->LoadFromArray($result);
	}

	$content->SetVar('MarathonUserID', $marathonUserID);
}
else{
    $styleSheets = array(
        array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
    );
    $javaScripts = array(
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
    );
    $header = array(
        "JavaScripts" => $javaScripts,
        "StyleSheets" => $styleSheets,
        "Navigation" => $navigation
    );
	$content = $adminPage->Load("user_list.html", $header);
	$marathon_user_list = new MarathonUserList();
	
	if ($request->GetProperty('Do') == 'ExportCSV')
	{
	    $marathon_user_list->exportToCSV($request);
	}
	elseif ($request->GetProperty('Do') == 'ExportCSV2')
	{
	    $marathon_user_list->exportToCSV2($request);
	}
	
	$marathon_user_list->load($request);
	$content->LoadFromObjectList('MarathonUsersList', $marathon_user_list);

    //Stats
	if ($request->IsPropertySet('Filter')){
		$filter = $request->GetProperty('Filter');
		if ( isset($filter['Onboarding']) ){
			$content->SetVar('Filter', 'Onboarding');
		}
	}
	else{
		$filter = array();
		$content->SetVar('Filter', 'All');
	}

	//Common stat
	$common_stat = MarathonUserList::getCommonStat($request);
	$content->SetVar('CountUsers', $common_stat['CountUsers']);
	$content->SetVar('CountOnboarding', $common_stat['CountOnboarding']);

	//Stages stat
	$content->SetLoop('StagesStat', MarathonUserList::getStagesStat( (isset($filter['Stage']) ? $filter : '' ), $request));

	//Map stat
	$content->SetLoop('MapStat', MarathonUserList::getMapStat( (isset($filter['Map']) ? $filter : '' ), $request));
	$content->SetVar('CountMapSteps', MarathonMap::GetStepsCount());

	$content->SetVar("Paging", $marathon_user_list->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	
	$content->SetVar("DateFrom", $request->GetProperty('DateFrom'));
	$content->SetVar("DateTo", $request->GetProperty('DateTo'));
}
$content->SetVar('BaseURL', $moduleURL);