<?php

/**
 * @var $request LocalObject
 * @var $currentSectionTitle string
 * @var $moduleURL string
 */

$urlFilter->LoadFromObject($request);

$styleSheets = array(
    array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
    array("StyleSheetFile" => ADMIN_PATH."template/plugins/timepicker/css/timepicker.min.css"),
    array("StyleSheetFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.css"),
);
$javaScripts = array(
    array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/plugins/timepicker/js/timepicker.min.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
    array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
    array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
);
$header = array(
    "Title" => $currentSectionTitle,
    "Navigation" => $navigation,
    "StyleSheets" => $styleSheets,
    "JavaScripts" => $javaScripts
);

if ($request->IsPropertySet('TagID')){
    $item = ArticleTag::get($request->GetIntProperty('TagID')) ?? new ArticleTag();

    $navigation[] = array(
        'Title' => $item->GetProperty('Title'),
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&TagID=' . $request->IsPropertySet('TagID'),
    );

    $content = $adminPage->Load('article_tag_edit.html', $header);

    //Save
    if ($request->IsPropertySet('Save')){
        $item->LoadFromObject($request);
        //print_r($request);
        //exit();
        if ($item->save()){
            if (!$item->HasErrors()){
                Send302("{$moduleURL}&Section={$urlFilter->GetProperty('Section')}");
            }
        }
    }

    //Template
    $content->LoadFromObject($item);
    $content->SetLoop('ErrorList', $item->GetErrorsAsArray());
}
else{
    $list = new ArticleTagList();
    $list->load();
    $content = $adminPage->Load('article_tag_list.html', $header);
    $content->LoadFromObjectList('ArticleTagList', $list);

    if ($request->GetProperty('Do') == 'Remove') {
        ArticleTagList::remove($request->GetProperty('Ids'));
        $urlFilter->RemoveProperty('Do');
        $urlFilter->RemoveProperty('Ids');
        Send302($moduleURL . '&' . $urlFilter->GetForURL());
    }

    $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL . '&' . $urlFilter->GetForURL()));
}