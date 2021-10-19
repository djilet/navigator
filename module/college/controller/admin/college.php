<?php
$college = new College($module);
if ($request->IsPropertySet("CollegeSpecialityID")) {
	$urlFilter->SetProperty("CollegeIDSpecialityList", $request->GetProperty('CollegeIDSpecialityList'));
	//$urlFilter->SetProperty("CollegeSpecialityID", $request->GetProperty("CollegeSpecialityID"));

	$collegeInfo = $college->getByID($request->GetProperty("CollegeIDSpecialityList"));
    $navigation[] = array("Title" => $collegeInfo["Title"], "Link" => $moduleURL."&".$urlFilter->GetForURL());
    $styleSheets = array();
    $javaScripts = array();
    $header = array(
        "Title" => $title,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );

    $content = $adminPage->Load("speciality_edit.html", $header);

    $speciality = new CollegeSpeciality($module);

	if ($request->GetProperty("Save")){
		$speciality->request->LoadFromObject($request);
		if ($speciality->Save()){
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else{
			$content->LoadFromObject($speciality->request);
			$content->LoadErrorsFromObject($speciality);
		}
	}
	else{
		$specialityInfo = $speciality->getByID($request->GetProperty("CollegeSpecialityID"),'',false);
	}

    $content->LoadFromArray($specialityInfo);

    $collegeList = new College($module);
	if($specialityInfo["CollegeID"]){
        $collegeList->LoadListForSelection($specialityInfo["CollegeID"]);
    }
    else {
        $collegeList->LoadListForSelection($request->GetProperty("CollegeIDSpecialityList"));
    }
    $content->LoadFromObjectList("CollegeList", $collegeList);

	//CollegeBigDirection
	$directionList = new CollegeBigDirection();
	$directionList->load();
	$content->SetLoop("CollegeBigDirectionList", $directionList->getItems(array($specialityInfo['CollegeBigDirectionID'])));
}
elseif($request->IsPropertySet("CollegeIDSpecialityList")) {
	$urlFilter->SetProperty("CollegeIDSpecialityList", $request->GetProperty("CollegeIDSpecialityList"));
	$request->SetProperty('CollegeID', $request->GetProperty("CollegeIDSpecialityList"));

	$collegeInfo = $college->getByID($request->GetProperty("CollegeID"));

	$javaScripts = array();
	$styleSheets = array();
	$navigation[] = array("Title" => $collegeInfo["Title"], "Link" => $moduleURL."&".$urlFilter->GetForURL());
	$header = array(
        "Title" => $currentSectionTitle,
        "Navigation" => $navigation,
        "JavaScripts" => $javaScripts,
        "StyleSheets" => $styleSheets
    );

	$content = $adminPage->Load("speciality_list.html", $header);

	$specialityList = new CollegeSpeciality();

    if ($request->GetProperty('Do') == 'RemoveSpeciality' && $request->GetProperty("CollegeSpecialityIDs")) {
        $specialityList->remove($request->GetProperty("CollegeSpecialityIDs"));
        $content->LoadMessagesFromObject($specialityList);
        $content->LoadErrorsFromObject($specialityList);
    }

    $specialityList->loadListByCollegeID($request);
    $content->LoadFromObjectList("SpecialityList", $specialityList);
    $content->SetVar("CollegeID", $collegeInfo['CollegeID']);
}
else if ($request->IsPropertySet("CollegeID")) {
	$urlFilter->SetProperty("CollegeID", $request->GetProperty('CollegeID'));

    if ($request->GetProperty("CollegeID") > 0)
        $title = GetTranslation("title-college-edit", $module);
    else
        $title = GetTranslation("title-college-add", $module);

    $navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL());
    $styleSheets = array();
    $javaScripts = array(
        array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
        array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
    );
    $header = array(
        "Title" => $title,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );

    $content = $adminPage->Load("college_edit.html", $header);

    if ($request->GetProperty("Save")){
        $college->request->LoadFromObject($request);
        if ($college->Save()){
            header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
            exit();
        }
        else{
			$content->LoadFromObject($college->request);
            $content->LoadErrorsFromObject($college);
        }
    }
    else{
        $collegeInfo = $college->getByID($request->GetProperty("CollegeID"));
		$content->LoadFromArray($collegeInfo);
	}

	//$content->SetLoop("CollegeLogoParamList", $college->getImageParams());

    //Regions
    $regionList = new DataRegionList($module);
    $regionList->LoadForSelection($collegeInfo['RegionID']);
    $content->LoadFromObjectList("RegionList", $regionList);

    //City list
    $cityList = CityList::getAll(null, 0);
    $content->SetLoop("CityList", $cityList->getListForTemplate([$collegeInfo['CityID']]));

    //CollegeBigDirection
    $directionList = new CollegeBigDirection();
    $directionList->load();
    $content->SetLoop("CollegeBigDirectionList", $directionList->getItems(array($collegeInfo['CollegeBigDirectionID'])));

    //UserItemList
    $userList = new UserItemList($module);
    if (isset($collegeInfo['QuestionUserID'])){
		$userList->LoadForSelection($collegeInfo['QuestionUserID']);
	}

    $content->LoadFromObjectList("UserList", $userList);
}
else {
    $styleSheets = array(
        //array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
    );
    $javaScripts = array(
        //array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
        //array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
    );
    $header = array(
        "Title" => $currentSectionTitle,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );

    $content = $adminPage->Load("college_list.html", $header);

    if ($request->GetProperty('Do') == 'RemoveCollege' && $request->GetProperty("CollegeIDs")) {
        $college->remove($request->GetProperty("CollegeIDs"));
        $content->LoadMessagesFromObject($college);
        $content->LoadErrorsFromObject($college);
    }

    //TODO export college
    /*else if ($request->GetProperty('Do') == 'ReportCSV' && $request->GetProperty("ReportDateFrom") && $request->GetProperty("ReportDateTo") && $request->GetProperty("ReportType"))
    {
        $college->exportUsersToCSV($request->GetProperty("ReportDateFrom"), $request->GetProperty("ReportDateTo"), $request->GetProperty("ReportType"));
    }*/

    $college->loadCollegeList();
    $content->LoadFromObjectList("CollegeList", $college);

    $content->SetVar("NowDate", GetCurrentDateTime());
}