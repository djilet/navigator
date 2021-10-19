<?php
$filterParams = array('FilterDeviceID', 'FilterClient');

if($request->GetProperty("DeviceID"))
{
	$javaScripts = array();
	$styleSheets = array();
	$header = array(
			"Title" => GetTranslation("device-edit", $module),
			"Navigation" => $navigation,
			"JavaScripts" => $javaScripts,
			"StyleSheets" => $styleSheets
	);
	
	$content = $adminPage->Load("device_edit.html", $header);
	
	$device = new DataDevice($module);
	$device->LoadByID($request->GetProperty("DeviceID"));
	$content->LoadFromObject($device);
}
else
{
	$urlFilter->AppendFromObject($request, $filterParams);
	$javaScripts = array();
	$styleSheets = array();
	$header = array(
			"Title" => $currentSectionTitle,
			"Navigation" => $navigation,
			"JavaScripts" => $javaScripts,
			"StyleSheets" => $styleSheets
	);
	
	$content = $adminPage->Load("device_list.html", $header);
	
	$deviceList = new DataDeviceList($module);
	
	//load filter data from session and to session
	$session = GetSession();
	foreach ($filterParams as $key)
	{
		if($session->IsPropertySet("Device".$key) && !$request->IsPropertySet($key))
			$request->SetProperty($key, $session->GetProperty("Device".$key));
		else
			$session->SetProperty("Device".$key, $request->GetProperty($key));
	}
	$session->SaveToDB();
	
	$fullList = $request->GetProperty("Output") == "csv" ? true : false;
	$deviceList->LoadDeviceList($request, $fullList);
	
	if($request->GetProperty("Output") == "csv")
	{
		$deviceList->ExportToCSV();
	}
	
	$content->LoadFromObjectList("DeviceList", $deviceList);
	
	$content->SetVar("Paging", $deviceList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $deviceList->GetItemsRange(), 'Total' => $deviceList->GetCountTotalItems())));
	$content->SetVar("ParamsForFilter", $urlFilter->GetForForm(array_merge(array('Page'), $filterParams)));
	$content->LoadFromObject($request, $filterParams);
}
