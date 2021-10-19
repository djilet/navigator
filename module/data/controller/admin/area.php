<?php
if ($request->IsPropertySet("AreaID"))
{
	if ($request->GetProperty("AreaID") > 0)
		$title = GetTranslation("title-area-edit", $module);
	else
		$title = GetTranslation("title-area-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("area_edit.html", $header);

	$area = new DataArea($module);

	if ($request->GetProperty("Save"))
	{
		$area->LoadFromObject($request);
		if ($area->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($area);
		}
	}
	else
	{
		$area->LoadByID($request->GetProperty("AreaID"));
	}

	$content->LoadFromObject($area);
	$content->SetLoop("AreaImageParamList", $area->GetImageParams("Area"));
}
else
{
	$javaScripts = array();
	$styleSheets = array();
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts,
		"StyleSheets" => $styleSheets
	);
	
	$content = $adminPage->Load("area_list.html", $header);

	$areaList = new DataAreaList($module);

	if ($request->GetProperty('Do') == 'RemoveArea' && $request->GetProperty("AreaIDs"))
	{
		$areaList->Remove($request->GetProperty("AreaIDs"));
		$content->LoadMessagesFromObject($areaList);
	}

	$areaList->LoadAreaList();
	$content->LoadFromObjectList("AreaList", $areaList);
}