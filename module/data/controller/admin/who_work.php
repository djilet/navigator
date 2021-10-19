<?php

$who_work = new WhoWork();
if ($request->IsPropertySet("WhoWorkID") && $request->GetProperty('Do') !== 'Remove') {
	$who_work->loadByID($request->GetProperty('WhoWorkID'));

	if ($request->GetProperty("WhoWorkID") > 0)
		$title = GetTranslation("title-who-work-edit", $module);
	else
		$title = GetTranslation("title-who-work-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL . "&" . $urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
		)
	);

	$content = $adminPage->Load("who_work_edit.html", $header);

	if ($request->GetProperty("Save")) {
		if ($who_work->Save($request->GetIntProperty('WhoWorkID'), $request)) {
			header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
			exit();
		}
		else {
			$content->LoadErrorsFromObject($who_work);
		}
	}

	$content->LoadFromObject($who_work->GetItem());
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

	$content = $adminPage->Load("who_work_list.html", $header);

	if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("WhoWorkID")) {
		if ($who_work->Remove($request->GetProperty("WhoWorkID"))) {
			$content->LoadMessagesFromObject($who_work);
		}
	}

	$who_work->Load();
	$content->SetLoop('WhoWorkList', $who_work->getItems());
}