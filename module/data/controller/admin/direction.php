<?php
if ($request->IsPropertySet("DirectionID"))
{
	if ($request->GetProperty("DirectionID") > 0)
		$title = GetTranslation("title-direction-edit", $module);
	else
		$title = GetTranslation("title-direction-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("direction_edit.html", $header);

	$direction = new DataDirection($module);

	if ($request->GetProperty("Save"))
	{
		$direction->LoadFromObject($request);
		if ($direction->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($direction);
		}
	}
	else
	{
		$direction->LoadByID($request->GetProperty("DirectionID"));
	}

	$content->LoadFromObject($direction);
	
	$bigDirectionList = new DataBigDirectionList($module);
	$bigDirectionList->LoadForSelection($direction->GetProperty("BigDirectionID"));
	$content->LoadFromObjectList("BigDirectionList", $bigDirectionList);
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
	
	$content = $adminPage->Load("direction_list.html", $header);

	$directionList = new DataDirectionList($module);

	if ($request->GetProperty('Do') == 'RemoveDirection' && $request->GetProperty("DirectionIDs"))
	{
		$directionList->Remove($request->GetProperty("DirectionIDs"));
		$content->LoadMessagesFromObject($directionList);
	}

	$directionList->LoadDirectionList();
	$content->LoadFromObjectList("DirectionList", $directionList);
}