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

if ($request->IsPropertySet('ID')){
    $city = City::get($request->GetIntProperty('ID')) ?? new City();

    $navigation[] = array(
        'Title' => $city->GetProperty('Title'),
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ID=' . $request->IsPropertySet('ID'),
    );

    $content = $adminPage->Load('city_edit.html', $header);

    //Save
    if ($request->IsPropertySet('Save')){
        $city->LoadFromObject($request);
        //print_r($request);
        //exit();
        if ($city->save()){
            if (!$city->HasErrors()){
                Send302("{$moduleURL}&Section={$urlFilter->GetProperty('Section')}");
            }
        }
    }

    //Template
    $content->LoadFromObject($city);
    $content->SetLoop('ErrorList', $city->GetErrorsAsArray());

    $region = new Region();
    $region->load();
    $content->SetLoop('RegionMap', $region->getListForTemplate([$city->GetProperty('RegionID')]));
}
else{
    $list = CityList::getAll(null, 0);
    $content = $adminPage->Load('city_list.html', $header);
    $content->LoadFromObjectList('CityList', $list);

    if ($request->GetProperty('Do') == 'Remove') {
        CityList::remove($request->GetProperty('CityIds'));
        $urlFilter->RemoveProperty('Do');
        $urlFilter->RemoveProperty('CityIds');
        Send302($moduleURL . '&' . $urlFilter->GetForURL());
    }

    $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL . '&' . $urlFilter->GetForURL()));
}