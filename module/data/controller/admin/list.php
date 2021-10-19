<?php

if ($request->IsPropertySet("ListID"))
{
	$urlFilter->AppendFromObject($request, array("Page"));
	if ($request->GetProperty("ListID") > 0)
		$title = GetTranslation("title-list-edit", $module);
	else
		$title = GetTranslation("title-list-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL()."&ListID=".$request->GetProperty("ListID"));
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("list_edit.html", $header);

	$list = new DataList($module);

	if ($request->GetProperty("Save"))
	{
		$list->LoadFromObject($request);
		$reopen = ($list->GetIntProperty("ListID") == 0);
		if ($list->Save())
		{
			if($reopen)
				header("Location: ".$moduleURL."&".$urlFilter->GetForURL()."&ListID=".$list->GetIntProperty("ListID"));
			else
				header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($list);
		}
	}
	else
	{
		$list->LoadByID($request->GetProperty("ListID"));
	}
	
	$content->LoadFromObject($list);
	
	$content->SetLoop('LinkedUniversityList', $list->GetLinkedObjects('university'));
	$content->SetLoop('LinkedSpecialityList', $list->GetLinkedObjects('speciality'));
	$content->SetLoop('LinkedProfessionList', $list->GetLinkedObjects('profession'));
	
	$content->SetLoop('FilterRegionList', $list->GetFilterObjects('Region'));
	$content->SetLoop('FilterBigDirectionList', $list->GetFilterObjects('BigDirection'));
	
	$regionList = new DataRegionList($module);
	$regionList->LoadForSelection();
	$content->LoadFromObjectList("RegionList", $regionList);
	
	$typeList = new DataTypeList($module);
	$typeList->LoadForSelection();
	$content->LoadFromObjectList("TypeList", $typeList);
}
else
{
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
	);
	
	$content = $adminPage->Load("list_list.html", $header);

	$listList = new DataListList($module);

	if ($request->GetProperty('Do') == 'Remove' && $request->GetProperty("ListIDs"))
	{
		$listList->Remove($request->GetProperty("ListIDs"));
		$content->LoadMessagesFromObject($listList);
	}

	$listList->LoadListList($request);
	$content->LoadFromObjectList("ListList", $listList);

	$content->SetVar("Paging", $listList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $listList->GetItemsRange(), 'Total' => $listList->GetCountTotalItems())));
	
}