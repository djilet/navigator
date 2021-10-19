<?php

es_include('GeoHelper.php');

$user = new User();
$user->LoadBySession();

if ($user->getRole() === ROLE_UNIVERSITY){
    $agent = UniversityAgent::getByUserID($user->GetIntProperty('UserID'));
}
if($request->GetProperty("UniversitySpecialityID"))
{
    $urlFilter->SetProperty("UniversitySpecialityID", $request->GetProperty("UniversitySpecialityID"));
	$university = new DataUniversity($module);
	$university->LoadByID($request->GetProperty("UniversitySpecialityID"));

	//agent validate
    if ($agent){
        if ($university->GetProperty('UniversityID') !== $agent->UniversityID){
            Send403();
        }
    }

	if ($request->IsPropertySet("SpecialityID"))
	{
		$navigation[] = array("Title" => $university->GetProperty("ShortTitle"), "Link" => $moduleURL."&".$urlFilter->GetForURL());
		$styleSheets = array(
		    array("StyleSheetFile" => PATH2MAIN . 'css/libs/bootstrap-select.css')
        );
		$javaScripts = array(
            array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
            array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
            array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
            array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
            array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
            array("JavaScriptFile" => PATH2MAIN . 'js/bootstrap-select.js')
        );
		$header = array(
				"Title" => (!empty($title) ? $title : ''),
				"Navigation" => $navigation,
				"StyleSheets" => $styleSheets,
				"JavaScripts" => $javaScripts
		);

        $studyList = new SpecialityStudy();

        $content = $adminPage->Load("speciality_edit.html", $header);

        $speciality = new DataSpeciality($module);

        if ($request->GetProperty("Save"))
        {
            //agent validate
            if ($agent){
                if ($request->ValidateNotEmpty('SpecialityID')){
                    $speciality->LoadByID($request->GetIntProperty('SpecialityID'));
                    if ($speciality->GetIntProperty('UniversityID') != $agent->UniversityID){
                        Send403();
                    }
                }

                if ($request->GetIntProperty('UniversityID') != $agent->UniversityID){
                    Send403();
                }
            }

            $speciality->LoadFromObject($request);
            if ($speciality->Save())
            {
                header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
                exit();
            }
            else
            {
                //prepare study type list
                foreach ($speciality->_properties['StudyList'] as $index => $item) {
                    $speciality->_properties['StudyList'][$index]['TypeList'] = $studyList::getTypes(true, [$item['Type']]);
                }

                $content->LoadErrorsFromObject($speciality);
            }
        }
        else
        {
            $speciality->LoadByID($request->GetProperty("SpecialityID"));
            $studyList->loadBySpecialityID($request->GetProperty('SpecialityID'));

            //prepare study type list
            $studyYearList = $studyList->GetItems();
            foreach ($studyYearList as $index => $item) {
                $studyYearList[$index]['TypeList'] = $studyList::getTypes(true, [$item['Type']]);
            }

            $content->SetLoop('StudyList', $studyYearList);
            $content->SetLoop('TypeList', $studyList::getTypes(true));
        }

        $speciality->prepareForTemplate();
        $content->LoadFromObject($speciality);

        $universityList = new DataUniversityList($module);
        if($speciality->GetProperty("UniversityID")){
            $universityList->LoadForSelection($speciality->GetProperty("UniversityID"));
        }
        else {
            $content->SetVar('UniversityID', $university->GetProperty('UniversityID'));
            $universityList->LoadForSelection($request->GetProperty("UniversitySpecialityID"));
        }
        $content->LoadFromObjectList("UniversityList", $universityList);

        $directionList = new DataDirectionList($module);
        $directionList->LoadForSelection($speciality->GetProperty("DirectionID"));
        $content->LoadFromObjectList("DirectionList", $directionList);

        $subjectList = new DataSubjectList($module);
        $subjectList->LoadSubjectList();
        $content->LoadFromObjectList("SubjectList", $subjectList);
	}
	else 
	{
		$javaScripts = array();
		$styleSheets = array();
		$navigation[] = array("Title" => $university->GetProperty("ShortTitle"), "Link" => $moduleURL."&".$urlFilter->GetForURL());
		$header = array(
				"Title" => $currentSectionTitle,
				"Navigation" => $navigation,
				"JavaScripts" => $javaScripts,
				"StyleSheets" => $styleSheets
		);
		
		$content = $adminPage->Load("speciality_list.html", $header);
		
		$specialityList = new DataSpecialityList($module);
		
		if ($request->GetProperty('Do') == 'RemoveSpeciality' && $request->GetProperty("SpecialityIDs"))
		{
			$specialityList->Remove($request->GetProperty("SpecialityIDs"));
			$content->LoadMessagesFromObject($specialityList);
			$content->LoadErrorsFromObject($specialityList);
		}
		
		$specialityList->LoadSpecialityList($request->GetProperty("UniversitySpecialityID"));
		$content->LoadFromObjectList("SpecialityList", $specialityList);

        //agent validate
        if ($agent){
            $urlFilter->RemoveProperty('UniversitySpecialityID');
            Send302($moduleURL."&".$urlFilter->GetForURL() . "&UniversityID={$agent->UniversityID}");
        }
	}

    $content->SetVar('SpecialityListBaseURL', $moduleURL . '&' . $urlFilter->GetForURL());
}
else if ($request->IsPropertySet("UniversityID"))
{
    //agent validate
    if ($agent){
        if ($request->GetProperty('UniversityID') !== $agent->UniversityID){
            Send403();
        }
    }

	if ($request->GetProperty("UniversityID") > 0)
		$title = GetTranslation("title-university-edit", $module);
	else
		$title = GetTranslation("title-university-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$styleSheets = array(
        array("StyleSheetFile" => ADMIN_PATH."template/plugins/prettyphoto/prettyPhoto.css"),
        array("StyleSheetFile" => PATH2MAIN . 'css/libs/bootstrap-select.css')
    );
	$javaScripts = array(
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/prettyphoto/jquery.prettyPhoto.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/js/custom.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
        array("JavaScriptFile" => PATH2MAIN . 'js/bootstrap-select.js')
    );
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("university_edit.html", $header);

	$university = new DataUniversity($module);

	if ($request->GetProperty("Save"))
	{
        $imageList = new UniversityImageList();
        $university->LoadFromObject($request);

        //coords
        GeoHelper::setCoordsByAddressIfNeed($university);

        $stmt->_dbLink->begin_transaction();
        if ($university->Save())
        {
            $university->saveCategories($request->GetProperty('CategoryIds'));
            $imageList->save($request->GetIntProperty('UniversityID'));

            if (!$university->HasErrors() && !$imageList->HasErrors()){

                $stmt->_dbLink->commit();
                header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
                exit();
            }
            else{
                $content->LoadErrorsFromObject($imageList);
            }
		}
		else
		{
			$content->LoadErrorsFromObject($university);
		}
	}
	else
	{
		$university->LoadByID($request->GetProperty("UniversityID"));
	}

    $university->prepareForTemplate();
	$content->LoadFromObject($university);
    $content->SetLoop("UniversityLogoParamList", $university->getImageParams());
	
	$regionList = new DataRegionList($module);
	$regionList->LoadForSelection($university->GetProperty("RegionID"));
	$content->LoadFromObjectList("RegionList", $regionList);

    $specialityList = new DataSpecialityList($module);
    $specialityList->LoadSpecialityList($university->GetProperty('UniversityID'));
    $content->LoadFromObjectList("SpecialityList", $specialityList);

    $cityList = CityList::getAll(null, 0);
    $content->SetLoop('CityList', $cityList->getListForTemplate([$university->GetProperty('CityID')]));

	$typeList = new DataTypeList($module);
	$typeList->LoadForSelection($university->GetProperty("TypeID"));
	$content->LoadFromObjectList("TypeList", $typeList);

    $universityCategoryList = UniversityCategoryList::getAll(null, 0);
	$content->SetLoop("UniversityCategoryList", $universityCategoryList->getListForTemplate($university->getCategoryIds()));

	$userList = new UserItemList($module);
	$userList->LoadForSelection($university->GetProperty("QuestionUserID"));
	$content->LoadFromObjectList("UserList", $userList);

    $imageList = new UniversityImageList();
    $imageList->load($request->GetProperty("UniversityID"));
    $content->SetLoop('ImageList', $imageList->getItemsByParams($university->itemImageParams));
    $specialityListBaseUrl = $moduleURL . '&' . $urlFilter->GetForURL() .
        "&UniversitySpecialityID={$university->GetProperty('UniversityID')}";
    $content->SetVar('SpecialityListBaseURL', $specialityListBaseUrl);

	//print_r($content);
	//exit();
}
else
{
    //agent validate
    if ($agent){
        Send301($moduleURL."&".$urlFilter->GetForURL() . "&UniversityID={$agent->UniversityID}");
    }

	$styleSheets = array(
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
	);
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
	);
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);
	
	$content = $adminPage->Load("university_list.html", $header);

	$universityList = new DataUniversityList($module);

	if ($request->GetProperty('Do') == 'RemoveUniversity' && $request->GetProperty("UniversityIDs"))
	{
		$universityList->Remove($request->GetProperty("UniversityIDs"));
		$content->LoadMessagesFromObject($universityList);
		$content->LoadErrorsFromObject($universityList);
	}
	else if ($request->GetProperty('Do') == 'ReportCSV' && $request->GetProperty("ReportDateFrom") && $request->GetProperty("ReportDateTo") && $request->GetProperty("ReportType"))
	{
		$universityList->exportUsersToCSV($request->GetProperty("ReportDateFrom"), $request->GetProperty("ReportDateTo"), $request->GetProperty("ReportType"));
	}

	$universityList->LoadUniversityList();
	$content->LoadFromObjectList("UniversityList", $universityList);
	
	$content->SetVar("NowDate", GetCurrentDateTime());
}

if ($agent){
    if ($content->GetVar('ErrorList') < 1){
        $content->SetVar('PreviewMode', true);
    }
    $content->SetVar('EditorMode', true);
    $content->setVar('TemplateUmaxEditorToolset', true);
}
