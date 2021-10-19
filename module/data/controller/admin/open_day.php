<?php

es_include('GeoHelper.php');

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
    array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
    array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
    array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
);
$header = array(
    "Title" => $currentSectionTitle,
    "Navigation" => $navigation,
    "StyleSheets" => $styleSheets,
    "JavaScripts" => $javaScripts
);

$user = new User();
$user->LoadBySession();

if ($user->getRole() === ROLE_UNIVERSITY){
    $agent = UniversityAgent::getByUserID($user->GetIntProperty('UserID'));
}

if ($request->IsPropertySet('ID')){
    $openDay = OpenDay::load($request->GetIntProperty('ID')) ?? new OpenDay();

    $navigation[] = array(
        'Title' => $openDay->GetProperty('Title'),
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ID=' . $request->IsPropertySet('ID'),
    );

    $content = $adminPage->Load('open_day_edit.html', $header);

    //Save
    if ($request->IsPropertySet('Save')){
        $openDay->LoadFromObject($request);
        if ($agent){
            if(!$openDay->ValidateNotEmpty('ButtonText') || !$openDay->ValidateNotEmpty('ButtonLink')){
                $openDay->AddError('Empty button');
            }

            $openDay->SetProperty('Type', $openDay->GetProperty('Title'));
            $openDay->SetProperty('DateFrom', $openDay->GetProperty('Date'));
            $openDay->SetProperty('DateTo', $openDay->GetProperty('Date'));
        }
        else{
            $fileSys = new FileSys();
            $uploads = $fileSys->Upload('infoFiles', DATA_OPEN_DAY_IMAGE_DIR);
            $openDay->setInfo($uploads);
        }

        //coords
        GeoHelper::setCoordsByAddressIfNeed($openDay);

        if (!$openDay->HasErrors() && $openDay->save()){
            //image
            if(\ImageManager::SaveImage($openDay, DATA_OPEN_DAY_IMAGE_DIR, $openDay->GetProperty("SavedMainImage"), 'Main')){
                $openDay->save();
            }

            //partners
            if (!$agent){
                $partners = [
                    OpenDayPartner::TYPE_MAIN => $request->GetProperty('mainpartners'),
                    OpenDayPartner::TYPE_COMMON => $request->GetProperty('commonpartners'),
                ];
                foreach ($partners as $type => $typeGroup){
                    if (!empty($typeGroup)) {
                        $newImages = $fileSys->Upload("{$type}partnersImages", DATA_OPEN_DAY_IMAGE_DIR);
                        $oldImages = $request->GetProperty("{$type}partnersImagesOld");
                        $partnerIDs = $request->GetProperty("{$type}partnersIDs");

                        foreach ($typeGroup as $key => $title){
                            $oldImage = $oldImages[$key] ?? false;
                            $newImage = $newImages[$key] ?? false;
                            $partnerId = $partnerIDs[$key] ?? false;
                            $partner = $partnerId ? OpenDayPartner::load($partnerId) : new OpenDayPartner();

                            if (is_array($newImage)){
                                if ($newImage['error'] != 4 && isset($newImage['ErrorInfo'])){
                                    $openDay->AddError($newImage['ErrorInfo']);
                                }
                                else{
                                    $partner->SetProperty('Image', OpenDayPartner::replaceImage($oldImage, $newImage));
                                }
                            }

                            $partner->SetProperty('OpenDayID', $openDay->GetIntProperty('ID'));
                            $partner->SetProperty('Type', $type);
                            $partner->SetProperty('Title', $title);
                            $partner->save();
                        }

                        $openDay->AppendErrorsFromObject($partner);
                    }
                }

                //Slider
                $newImages = $fileSys->Upload("slideImages", DATA_OPEN_DAY_SLIDER_IMAGE_DIR);
                $oldImages = $request->GetProperty("slideImagesOld");
                $ids = $request->GetProperty("slideIDs");
                $slides = $request->GetProperty('slide');
                foreach ($slides as $key => $title){
                    $oldImage = $oldImages[$key] ?? false;
                    $newImage = $newImages[$key] ?? false;
                    $slideId = $ids[$key] ?? false;
                    $slide = $slideId ? OpenDaySlide::load($slideId) : new OpenDaySlide();

                    if (is_array($newImage)){
                        if ($newImage['error'] != 4 && isset($newImage['ErrorInfo'])){
                            $slide->AddError($newImage['ErrorInfo']);
                        }
                        else{
                            $slide->SetProperty('Image', OpenDaySlide::replaceImage($oldImage, $newImage));
                        }
                    }

                    $slide->SetProperty('OpenDayID', $openDay->GetIntProperty('ID'));
                    $slide->SetProperty('Title', $title);
                    $slide->save();

                    $openDay->AppendErrorsFromObject($slide);
                }


                //Schedule
                if(isset($_FILES['ScheduleFile']) && $_FILES['ScheduleFile']['size'] > 0){
                    if(!$openDay->uploadSchedule($_FILES['ScheduleFile'])){
                        return false;
                    }
                }
                else {
                    $openDay->updateSchedule($request->GetProperty('Schedule'));
                }

                //Properties
                $openDay->propertyList = new OpenDayPropertyList();
                if ($request->IsPropertySet('Properties')){
                    foreach ($request->GetProperty('Properties') as $property => $val) {
                        $openDay->propertyList->_items[] = ['Property' => $property, 'Value' => $val];
                    }
                }
                $openDay->propertyList->saveForOpenDay($openDay->GetIntProperty('ID'));
            }

            //Univers
            OpenDay::removeLinkedUniversity($openDay->GetIntProperty('ID'));
            if ($agent){
                $request->SetProperty('LinkedUniversity', [$agent->UniversityID]);
            }
            if ($request->GetProperty('LinkedUniversity')){
                OpenDay::saveLinkedUniversity($openDay->GetIntProperty('ID'), $request->GetProperty('LinkedUniversity'));
            }

            if (!$openDay->HasErrors()){
                Send302("{$moduleURL}&Section={$urlFilter->GetProperty('Section')}");
            }
        }
    }

    //Registrations
    $registrationList = new OpenDayRegistrationList();
    if($request->GetProperty("Output") == "csv")
    {
        $registrationList->load(0, true, $openDay->GetIntProperty('ID'));
        $registrationList->exportToCSV();
    }
    elseif($request->GetProperty("Output") == "csv-group")
    {
        $registrationList->exportToCSVGroup($openDay->GetIntProperty('ID'));
    }
    elseif($request->GetProperty("OutputVisit") == "csv")
    {
        $registrationList->exportVisitsByUserToCSV($openDay->GetIntProperty('ID'));
    }
    elseif($request->GetProperty("OutputVisitFlat") == "csv")
    {
        $registrationList->exportVisitsToCSV($openDay->GetIntProperty('ID'));
    }
    elseif($request->GetProperty("Import") == "csv")
    {
        if (!$registrationList->iniVisitsFromCSV($_FILES['importFile'])){
            $content->LoadErrorsFromObject($registrationList);
            return false;
        }

        $registrationList->importFromCSV($openDay->GetIntProperty('ID'));

        if ($registrationList->HasErrors()){
            $content->SetLoop('ImportErrorList', $registrationList->getErrorList());
        }
        else{
            $content->SetVar('ImportResult', true);
            $registrationList->load(0, true, $openDay->GetIntProperty('ID'));
            $content->LoadFromObjectList('RegistrationList', $registrationList);
            $content->SetVar("Paging", $registrationList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL(), null, '#tab-9'));
        }
    }
    else
    {
        $registrationList->load(0, true, $openDay->GetIntProperty('ID'));
        $content->LoadFromObjectList('RegistrationList', $registrationList);
        $content->SetVar("Paging", $registrationList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL(), null, '#tab-9'));
    }

    //Template
    $content->LoadFromObject($openDay);
    $content->SetLoop('ErrorList', $openDay->GetErrorsAsArray());
    $content->SetLoop('TypeList', OpenDay::getTypesListEvent());
    $cityList = CityList::getAll(null, 0);
    $content->SetLoop('CityList', $cityList->getListForTemplate([$openDay->GetProperty('CityID')]));

    if ($openDay->ValidateNotEmpty('ID')){
        $mainPartnerList = OpenDayPartner::getAll(['OpenDayID' => $openDay->GetIntProperty('ID'), 'Type' => OpenDayPartner::TYPE_MAIN]);
        $commonPartnerList = OpenDayPartner::getAll(['OpenDayID' => $openDay->GetIntProperty('ID'), 'Type' => OpenDayPartner::TYPE_COMMON]);
        $slideList = OpenDaySlide::getAll($openDay->GetIntProperty('ID'));
        $content->LoadFromObjectList('MainPartnerList', $mainPartnerList);
        $content->LoadFromObjectList('CommonPartnerList', $commonPartnerList);
        $content->LoadFromObjectList('SlideList', $slideList);
        $content->SetLoop('LinkedUniversityList', OpenDay::getUniversityMap($openDay->GetIntProperty('ID')));
    }
}
else{
    $filter = null;
    if ($agent){
        $filter['UniversityID'] = $agent->UniversityID;
    }
    $list = OpenDayList::getAll($filter);
    $content = $adminPage->Load('open_day_list.html', $header);
    $content->LoadFromObjectList('OpenDayList', $list);

    if ($request->GetProperty('Do') == 'Remove') {
        OpenDayList::remove($request->GetProperty('ListIDs'));
        $urlFilter->RemoveProperty('Do');
        $urlFilter->RemoveProperty('ListIDs');
        Send302($moduleURL . '&' . $urlFilter->GetForURL());
    }

    $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL . '&' . $urlFilter->GetForURL()));
}

if ($agent){
    $content->SetVar('TemplateWithoutInfo', true);
    $content->SetVar('TemplateWithoutType', true);
    $content->SetVar('TemplateWithoutRegistration', true);
    $content->SetVar('TemplateWithoutPeriod', true);
    $content->SetVar('TemplateHiddenStaticPath', true);
    $content->SetVar('TemplateHiddenOtherInfo', true);
    $content->SetVar('TemplateUmaxEditorToolset', true);
    $content->SetVar('TemplateCustomButton', true);
}