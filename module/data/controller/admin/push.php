<?php
set_time_limit(3600);

$javaScripts = array();
$styleSheets = array();
$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts,
		"StyleSheets" => $styleSheets
);

$content = $adminPage->Load("push_send.html", $header);

if($request->GetProperty("Action") == "Send")
{
	$push = new DataPush($module);
	$push->LoadFromObject($request);
	
	$push->Send();
	$content->LoadMessagesFromObject($push);
	$content->LoadErrorsFromObject($push);
	
	//convert receiver list for template
	if($push->GetProperty("ReceiverList"))
	{
		$receiverList = array();
		foreach ($push->GetProperty("ReceiverList") as $deviceID)
		{
			$receiverList[] = array("DeviceID" => $deviceID);
		}
		$push->SetProperty("ReceiverList", $receiverList);
	}
	$content->LoadFromObject($push);
}

$clients = array(CLIENT_ANDROID, CLIENT_IOS);
$clientList = array();
foreach ($clients as $client)
{
	$clientList[] = array("Client" => $client, "Selected" => $request->GetProperty("Client") == $client ? 1 : 0);
}
$content->SetLoop("ClientList", $clientList);