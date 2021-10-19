<?php

$achievement = new Achievement();
if ($request->IsPropertySet("AchievementID") && $request->GetProperty('Do') !== 'Remove') {
	$achievement->loadByID($request->GetProperty('AchievementID'));

	if ($request->GetProperty("AchievementID") > 0)
		$title = GetTranslation("title-achievement-edit", $module);
	else
		$title = GetTranslation("title-achievement-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL . "&" . $urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("achievement_edit.html", $header);

	if ($request->GetProperty("Save")) {
		$achievement->item->LoadFromObject($request);
		if ($achievement->save()) {
			header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
			exit();
		}
		else {
			$content->LoadErrorsFromObject($achievement);
		}
	}

	$content->LoadFromObject($achievement->GetItem());
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

	$content = $adminPage->Load("achievement_list.html", $header);

	if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("AchievementIDs")) {
		if ($achievement->removeItems($request->GetProperty("AchievementIDs"))) {
			$content->LoadMessagesFromObject($achievement);
		}
	}

	$achievement->loadList();
	$content->SetLoop('AchievementList', $achievement->getItems());
}