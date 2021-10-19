<?php

$user = new User();
$user->LoadBySession();
$urlFilter = new URLFilter();

$header = array(
    "Title"       => GetTranslation("module-admin-title", $module),
    "Navigation"  => $navigation,
    "JavaScripts" => array(
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
    ),
    "StyleSheets" => array(
        array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css")
    )
);

if ($user->getRole() === ROLE_UNIVERSITY){
    $agent = UniversityAgent::getByUserID($user->GetIntProperty('UserID'));
}

$urlFilter->LoadFromObject($request);

$navigation = array(
    array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
);

$filter = [
    'CreatedGte' => $request->GetProperty('CreatedGte'),
    'CreatedLt' => $request->GetProperty('CreatedLt'),
];

if ($agent){
    $filter['UniversityIds'] = [$agent->UniversityID];
}

$list = UserUniversityList::getAllWithDependents($filter);

if ($request->GetProperty("Action") == 'ExportCSV'){
    UserUniversityService::exportToCsv($list);
}
else{
    $content = $adminPage->Load("user_university_list.html", $header);
    $content->LoadFromObject($request);
    $content->LoadFromObjectList('List', $list);
    $content->SetVar('ItemsCount', $list->GetCountTotalItems());
    $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));

    $content->LoadErrorsFromObject($list);
    $content->LoadMessagesFromObject($list);
}