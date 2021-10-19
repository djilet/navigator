<?php

$list = new CollegeList($module);

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

	if ($request->GetProperty("Save")){
		$list->single->LoadFromObject($request);
		$reopen = ($list->single->GetIntProperty("ListID") == 0);
		if ($list->Save())
		{
			if($reopen)
				header("Location: ".$moduleURL."&".$urlFilter->GetForURL()."&ListID=".$list->single->GetIntProperty("ListID"));
			else
				header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($list->single);
		}
	}
	else {
		$list->LoadByID($request->GetProperty("ListID"));
	}

	$content->LoadFromObject($list->single);

	foreach (College::getFilterList() as $key => $item) {
		$content->SetLoop('Filter' . $item . 'List', $list->GetFilterObjects($item));
	}
}
else
{
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
	);

	$content = $adminPage->Load("list_list.html", $header);

	if ($request->GetProperty('Do') == 'Remove' && $request->GetProperty("ListIDs"))
	{
		$list->Remove($request->GetProperty("ListIDs"));
		$content->LoadMessagesFromObject($list);
	}

	$list->LoadList();
	$content->LoadFromObjectList("ListList", $list);

	$content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $list->GetItemsRange(), 'Total' => $list->GetCountTotalItems())));
}