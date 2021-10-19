<?php
$direction = new CollegeBigDirection();
if ($request->IsPropertySet("CollegeBigDirectionID") && $request->GetProperty('Do') !== 'Remove') {
    $direction->loadByID($request->GetProperty('CollegeBigDirectionID'));

    if ($request->GetProperty("CollegeBigDirectionID") > 0)
        $title = GetTranslation("title-bigdirection-edit", $module);
    else
        $title = GetTranslation("title-bigdirection-add", $module);

    $navigation[] = array("Title" => $title, "Link" => $moduleURL . "&" . $urlFilter->GetForURL());
    $styleSheets = array();
    $javaScripts = array();
    $header = array(
        "Title" => $title,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );

    $content = $adminPage->Load("bigdirection_edit.html", $header);

    if ($request->GetProperty("Save")) {
        if ($direction->Save(
        		$request->GetIntProperty('CollegeBigDirectionID'),
				$request->GetProperty('Title'),
				$request->GetProperty('SortOrder')
			)) {
            header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
            exit();
        }
        else {
            $content->LoadErrorsFromObject($direction);
        }
    }

    $content->LoadFromObject($direction->getItem());
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

    $content = $adminPage->Load("bigdirection_list.html", $header);

    if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("CollegeBigDirectionID")) {
        if ($direction->Remove($request->GetProperty("CollegeBigDirectionID"))) {
            $content->LoadMessagesFromObject($direction);
        }
    }

    $direction->Load();
    $content->SetLoop('CollegeBigDirectionList', $direction->getItems());
}