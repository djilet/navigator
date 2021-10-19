<?php

$industry = new Industry();
if ($request->IsPropertySet("IndustryID") && $request->GetProperty('Do') !== 'Remove') {
	$industry->loadByID($request->GetProperty('IndustryID'));

	if ($request->GetProperty("IndustryID") > 0)
		$title = GetTranslation("title-industry-edit", $module);
	else
		$title = GetTranslation("title-industry-add", $module);

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

	$content = $adminPage->Load("industry_edit.html", $header);

	if ($request->GetProperty("Save")) {
		if ($industry->Save($request->GetIntProperty('IndustryID'), $request)){
			header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
			exit();
		}
		else {
			$content->LoadErrorsFromObject($industry);
		}
	}

	$content->LoadFromObject($industry->GetItem());
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

	$content = $adminPage->Load("industry_list.html", $header);

	if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("IndustryID")) {
		if ($industry->Remove($request->GetProperty("IndustryID"))) {
			$content->LoadMessagesFromObject($industry);
		}
	}

	$industry->Load();
	$content->SetLoop('IndustryList', $industry->getItems());
}