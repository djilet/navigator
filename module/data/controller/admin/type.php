<?php
if ($request->IsPropertySet("TypeID"))
{
	if ($request->GetProperty("TypeID") > 0)
		$title = GetTranslation("title-type-edit", $module);
	else
		$title = GetTranslation("title-type-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("type_edit.html", $header);

	$type = new DataType($module);

	if ($request->GetProperty("Save"))
	{
		$type->LoadFromObject($request);
		if ($type->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($type);
		}
	}
	else
	{
		$type->LoadByID($request->GetProperty("TypeID"));
	}

	$content->LoadFromObject($type);
	$content->SetLoop("TypeImageParamList", $type->GetImageParams("Type"));
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
	
	$content = $adminPage->Load("type_list.html", $header);

	$typeList = new DataTypeList($module);

	if ($request->GetProperty('Do') == 'RemoveType' && $request->GetProperty("TypeIDs"))
	{
		$typeList->Remove($request->GetProperty("TypeIDs"));
		$content->LoadMessagesFromObject($typeList);
	}

	$typeList->LoadTypeList();
	$content->LoadFromObjectList("TypeList", $typeList);
}