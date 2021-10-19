<?php

$want_work = new WantWork();
if ($request->IsPropertySet("WantWorkID") && $request->GetProperty('Do') !== 'Remove') {
	$want_work->loadByID($request->GetProperty('WantWorkID'));

	if ($request->GetProperty("WantWorkID") > 0)
		$title = GetTranslation("title-want-work-edit", $module);
	else
		$title = GetTranslation("title-want-work-add", $module);

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

	$content = $adminPage->Load("want_work_edit.html", $header);

	if ($request->GetProperty("Save")) {
		if ($want_work->Save($request->GetIntProperty('WantWorkID'), $request)) {
			header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
			exit();
		}
		else {
			$content->LoadErrorsFromObject($want_work);
		}
	}

	$content->LoadFromObject($want_work->GetItem());
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

	$content = $adminPage->Load("want_work_list.html", $header);

	if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("WantWorkID")) {
		if ($want_work->Remove($request->GetProperty("WantWorkID"))) {
			$content->LoadMessagesFromObject($want_work);
		}
	}

	$want_work->Load();
	$content->SetLoop('WantWorkList', $want_work->getItems());
}