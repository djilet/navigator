<?php
$awards = new CollegeAwards();
if ($request->IsPropertySet("AwardsID") && $request->GetProperty('Do') !== 'Remove') {
    $awards->loadByID($request->GetProperty('AwardsID'));

    if ($request->GetProperty("AwardsID") > 0)
        $title = GetTranslation("title-awards-edit", $module);
    else
        $title = GetTranslation("title-awards-add", $module);

    $navigation[] = array("Title" => $title, "Link" => $moduleURL . "&" . $urlFilter->GetForURL());
    $styleSheets = array();
    $javaScripts = array();
    $header = array(
        "Title" => $title,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );

    $content = $adminPage->Load("awards_edit.html", $header);

    if ($request->GetProperty("Save")) {
        if ($awards->Save($request->GetIntProperty('AwardsID'), $request->GetProperty('Title'))) {
            header("Location: " . $moduleURL . "&" . $urlFilter->GetForURL());
            exit();
        }
        else {
            $content->LoadErrorsFromObject($awards);
        }
    }

    $content->LoadFromObject($awards->getItem());
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

    $content = $adminPage->Load("awards_list.html", $header);

    if ($request->GetProperty('Do') == 'Remove' && $request->IsPropertySet("AwardsID")) {
        if ($awards->Remove($request->GetProperty("AwardsID"))) {
            $content->LoadMessagesFromObject($awards);
        }
    }

    $awards->Load();
    $content->SetLoop('AwardsList', $awards->getItems());
}