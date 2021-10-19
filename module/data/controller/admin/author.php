<?php
if ($request->IsPropertySet("AuthorID"))
{
	if ($request->GetProperty("AuthorID") > 0)
		$title = GetTranslation("title-author-edit", $module);
	else
		$title = GetTranslation("title-author-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$styleSheets = array();
	$javaScripts = array();
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("author_edit.html", $header);

	$author = new DataAuthor($module);

	if ($request->GetProperty("Save"))
	{
	    $author->LoadFromObject($request);
	    if ($author->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
		    $content->LoadErrorsFromObject($author);
		}
	}
	else
	{
	    $author->LoadByID($request->GetProperty("AuthorID"));
	}

	$content->LoadFromObject($author);
	$content->SetLoop("AuthorImageParamList", $author->GetImageParams("Author"));
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
	
	$content = $adminPage->Load("author_list.html", $header);

	$authorList = new DataAuthorList($module);

	if ($request->GetProperty('Do') == 'RemoveAuthor' && $request->GetProperty("AuthorIDs"))
	{
	    $authorList->Remove($request->GetProperty("AuthorIDs"));
	    $content->LoadMessagesFromObject($authorList);
	}

	$authorList->LoadAuthorList();
	$content->LoadFromObjectList("AuthorList", $authorList);
}