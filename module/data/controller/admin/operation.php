<?php

$operation = new Operation();
if ($request->IsPropertySet("OperationID") && $request->GetProperty('Do') !== 'Remove') {
	$operation->loadByID($request->GetProperty('OperationID'));

	if ($request->GetProperty("OperationID") > 0)
		$title = GetTranslation("title-operation-edit", $module);
	else
		$title = GetTranslation("title-operation-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL . "&" . $urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("operation_edit.html", $header);

	if ($request->GetProperty("Save")) {
		if ($operation->Save($request->GetIntProperty('OperationID'), $request->GetProperty('OperationTitle'))) {
			header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
			exit();
		}
		else {
			$content->LoadErrorsFromObject($operation);
		}
	}

	$content->LoadFromObject($operation->GetItem());
}
else {
	$javaScripts = array();
	$styleSheets = array();
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts,
		"StyleSheets" => $styleSheets
	);

	$content = $adminPage->Load("operation_list.html", $header);

	if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("OperationID")) {
		if ($operation->Remove($request->GetProperty("OperationID"))) {
			$content->LoadMessagesFromObject($operation);
		}
	}

	$operation->Load();
	$content->SetLoop('OperationList', $operation->getItems());
}