<?php
if ($request->IsPropertySet("RegionID"))
{
	if ($request->GetProperty("RegionID") > 0)
		$title = GetTranslation("title-region-edit", $module);
	else
		$title = GetTranslation("title-region-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("region_edit.html", $header);

	$region = new DataRegion($module);

	if ($request->GetProperty("Save"))
	{
		$region->LoadFromObject($request);
		if ($region->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($region);
		}
	}
	else
	{
		$region->LoadByID($request->GetProperty("RegionID"));
	}

	$content->LoadFromObject($region);
	$content->SetLoop("RegionImageParamList", $region->GetImageParams("Region"));
	
	$areaList = new DataAreaList($module);
	$areaList->LoadForSelection($region->GetProperty("AreaID"));
	$content->LoadFromObjectList("AreaList", $areaList);
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
	
	$content = $adminPage->Load("region_list.html", $header);

	$regionList = new DataRegionList($module);

	if ($request->GetProperty('Do') == 'RemoveRegion' && $request->GetProperty("RegionIDs"))
	{
		$regionList->Remove($request->GetProperty("RegionIDs"));
		$content->LoadMessagesFromObject($regionList);
	}

	$regionList->LoadRegionList();
	$content->LoadFromObjectList("RegionList", $regionList);
}