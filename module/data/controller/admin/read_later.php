<?php

$list = new ReadLaterList();
$javaScripts = array();
$styleSheets = array();
$header = array(
    "Title" => $currentSectionTitle,
    "Navigation" => $navigation,
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

$list->loadWithTarget($request->GetProperties());

if ($request->GetProperty('Action') == 'ExportCSV'){
    $list->exportToCSV();
}

$content = $adminPage->Load("read_later_list.html", $header);
$content->LoadFromObject($request);
$content->SetLoop('ReadLaterList', $list->GetItems());
$content->SetVar('ItemsCount', $list->GetCountTotalItems());