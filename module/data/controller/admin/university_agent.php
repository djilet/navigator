<?php
es_include("userlist.php");

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
$stmt = GetStatement();

if ($request->IsPropertySet('ID')){
    $agent = UniversityAgent::get($request->GetIntProperty('ID')) ?? new UniversityAgent();

    $navigation[] = array(
        'Title' => $agent->GetProperty('Title'),
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ID=' . $request->IsPropertySet('ID'),
    );

    $content = $adminPage->Load('university_agent_edit.html', $header);

    //Save
    if ($request->IsPropertySet('Save')){
        $agent->AppendFromObject($request);
        $stmt->_dbLink->begin_transaction();
        if ($agent->save()){
            if (!$agent->HasErrors()){
                //save author
                if ($agent->GetIntProperty('AuthorID') < 1){
                    if ($request->ValidateNotEmpty('AuthorTitle')){
                        $authorTitle = $request->GetProperty('AuthorTitle');
                    }
                    else{
                        $university = new DataUniversity();
                        $university->LoadByID($agent->UniversityID);
                        $authorTitle = !empty($university->GetProperty('ShortTitle')) ?
                            $university->GetProperty('ShortTitle') :
                            $university->GetProperty('Title');
                    }

                    $author = new DataAuthor();
                    $author->SetProperty('Title', $authorTitle);
                    if ($author->Save()){
                        $agent->SetProperty('AuthorID', $author->GetProperty('AuthorID'));
                        $agent->save();
                    }
                    else{
                        $agent->LoadErrorsFromObject($author);
                    }
                }

                if (!$agent->HasErrors()){
                    $stmt->_dbLink->commit();
                    Send302("{$moduleURL}&Section={$urlFilter->GetProperty('Section')}");
                }
            }
        }
    }

    //Template
    $content->LoadFromObject($agent);
    $content->SetLoop('ErrorList', $agent->GetErrorsAsArray());

    $universityList = new DataUniversityList();
    $universityList->LoadForSelection($agent->GetProperty('UniversityID'));
    $content->SetLoop('UniversityList', $universityList->GetItems());

    $userList = new UserList();

    $authorList = new DataAuthorList();
    $authorList->LoadAuthorList();
    $content->SetLoop('AuthorList', $authorList->getListForTemplate([$agent->AuthorID]));

    $userList = new UserList();
    $userList->LoadUserList(new LocalObject([
        'RoleList' => [ROLE_UNIVERSITY]
    ]));
    $content->SetLoop('UserList', $userList->GetItems($agent->UserID));
}
else{
    $list = UniversityAgentList::getAll(null, 0);
    $content = $adminPage->Load('university_agent_list.html', $header);
    $content->LoadFromObjectList('UniversityAgentList', $list);

    if ($request->GetProperty('Do') == 'Remove') {
        UniversityAgentList::remove($request->GetProperty('Ids'));
        $urlFilter->RemoveProperty('Do');
        $urlFilter->RemoveProperty('Ids');
        Send302($moduleURL . '&' . $urlFilter->GetForURL());
    }

    $content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL . '&' . $urlFilter->GetForURL()));
}