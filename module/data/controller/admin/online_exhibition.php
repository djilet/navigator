<?php

$urlFilter = new URLFilter();
$fileSys = new FileSys();

$header = array(
    "Title"       => GetTranslation("module-admin-title", $module),
    "Navigation"  => $navigation,
    "JavaScripts" => array(
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        array("JavaScriptFile" => PATH2MAIN . 'js/components.js'),
        array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
        array("JavaScriptFile" => PATH2MAIN . 'js/bootstrap-select.js'),
    ),
    "StyleSheets" => array(
        array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
        array("StyleSheetFile" => PATH2MAIN . 'css/libs/bootstrap-select.css'),
    )
);

$urlFilter->LoadFromObject($request);
if ($request->IsPropertySet('ParticipantID')){
    if ($request->ValidateNotEmpty('ExhibitionID')){
        $exhibition = OnlineExhibition::get($request->GetIntProperty('ExhibitionID'));

        $navigation[] = array(
            'Title' => $exhibition->Title,
            'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID=' . $request->GetProperty('ExhibitionID') . "&ParticipantList=true",
        );
    }

    $participant = OnlineExhibitionParticipant::get($request->GetIntProperty('ParticipantID')) ?? new OnlineExhibitionParticipant();

    $navigation[] = array(
        'Title' => $participant->Title ?? GetTranslation('title-online_exhibition_participant-add', 'data'),
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ID=' . $request->IsPropertySet('ID'),
    );

    $content = $adminPage->Load("online_exhibition_participant_edit.html", $header);

    //Save
    if ($request->IsPropertySet('Save')){
        $participant->LoadFromObject($request);
        $participant->SetProperty('OnlineExhibitionID', $request->GetProperty('ExhibitionID'));
        $participant->SetProperty('ID', $request->GetProperty('ParticipantID'));
        if ($participant->save()){
            //image
            if(\ImageManager::SaveImage($participant, DATA_ONLINE_EXHIBITION_IMAGE_DIR, $participant->GetProperty("SavedMainImage"), 'Main')){
                $participant->save();
            }

            if (!$participant->HasErrors()){
                Send302("{$moduleURL}&Section={$urlFilter->GetProperty('Section')}&ExhibitionID=" .
                    $request->GetProperty('ExhibitionID') .
                    "&ParticipantList=true");
            }
        }
    }

    //Template
    $participant->prepareForTemplate();
    $content->LoadFromObject($participant);
    $content->SetVar('ExhibitionID', $request->GetProperty('ExhibitionID'));
    $content->SetLoop('ParticipantImageParamList', OnlineExhibitionParticipant::getParams()['Image']);
    $content->SetLoop('ErrorList', $participant->GetErrorsAsArray());

    $universityList = new DataUniversityList();
    $universityList->LoadForSelection($participant->UniversityID);
    $content->SetLoop('UniversityList', $universityList->GetItems());

    $onlineEventList = new DataOnlineEventList();
    $onlineEventList->LoadOnlineEventList();
    $content->SetLoop('OnlineEventList', $onlineEventList->getListForTemplate($participant->getOnlineEventIDs()));
}
else if ($request->IsPropertySet('ExhibitionID')){
    $exhibition = OnlineExhibition::get($request->GetIntProperty('ExhibitionID')) ?? new OnlineExhibition();
    $navigation[] = array(
        'Title' => $exhibition->Title ?? GetTranslation('title-online_exhibition-add', 'data'),
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID=' .
            $request->GetProperty('ExhibitionID') .
            "&ParticipantList=true",
    );

    if ($request->IsPropertySet('ParticipantList')){
        $navigation[] = array(
            'Title' => GetTranslation('title-online_exhibition-participant-list', 'data'),
            'Link' => $moduleURL . '&Section=' .
                $request->GetProperty('Section').
                '&ExhibitionID=' . $request->GetProperty('ExhibitionID') .
                '&ParticipantList=true',
        );

        $list = OnlineExhibitionParticipantList::getAll(['OnlineExhibitionIds' => [$exhibition->ID]]);
        $content = $adminPage->Load("online_exhibition_participant_list.html", $header);
        $content->LoadFromObject($request);
        $content->LoadFromObjectList('List', $list);
        $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));

        $content->LoadErrorsFromObject($list);
        $content->LoadMessagesFromObject($list);
    }
    else{
        $content = $adminPage->Load("online_exhibition_edit.html", $header);

        //Save
        if ($request->IsPropertySet('Save')){
            $exhibition->LoadFromObject($request);
            $exhibition->SetProperty('ID', $request->GetProperty('ExhibitionID'));
            if ($exhibition->save()){
                if (!$exhibition->HasErrors()){
                    Send302("{$moduleURL}&Section={$urlFilter->GetProperty('Section')}");
                }
            }
        }

        $content->LoadFromObject($exhibition);
        $content->LoadErrorsFromObject($exhibition);
    }
}
else{
    $list = OnlineExhibitionList::getAll();
    $content = $adminPage->Load("online_exhibition_list.html", $header);
    $content->LoadFromObject($request);
    $content->LoadFromObjectList('List', $list);
    $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));

    $content->LoadErrorsFromObject($list);
    $content->LoadMessagesFromObject($list);
}