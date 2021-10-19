<?php

use Cake\Database\Connection;

require_once(dirname(__FILE__) . '/../../include/public/PublicExhibition.php');
require_once(dirname(__FILE__) . '/../../../users/include/user.php');

/** @var \LocalObject $request */

$exhibition = new Exhibition($module);

if ($request->IsPropertySet('CityList')) {
    /*
     * Список городов для выставки
     */

    $urlFilter->LoadFromObject($request, array('Section', 'ExhibitionID', 'CityList'));

    $exhibitionId = $request->GetIntProperty('ExhibitionID');
    if ($exhibitionId > 0) {
        $exhibition->loadByID($exhibitionId);
    }
    
    $navigation[] = array(
        'Title' => $exhibition->IsPropertySet('Title') ? $exhibition->GetProperty('Title') : '',
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID='.
            ($exhibitionId > 0 ? $exhibitionId : '')
    );
    $navigation[] = array(
        'Title' => 'Список городов',
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID='.
            intval($exhibitionId).'&CityList=1'
    );
    $header = array(
        "Title" => $currentSectionTitle,
        "Navigation" => $navigation,
        "StyleSheets" => array(
            array("StyleSheetFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.css"),
            ),
        "JavaScripts" => array(
            array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        )
    );
    
    $exhibitionCityList = new ExhibitionCityList();
    
    if ($request->GetProperty('Do') == 'Remove') {
        $exhibitionCityList->remove($request->GetProperty('ListIDs'));
        header('Location: '.$moduleURL.'&'.$urlFilter->GetForURL());
        exit;
    }
    
    $exhibitionCityList->load($exhibitionId);
    $content = $adminPage->Load('exhibition_city_list.html', $header);
    $content->LoadFromObjectList('ExhibitionCityList', $exhibitionCityList);
    
    $content->SetVar('ExhibitionID', $exhibitionId);
    $content->SetVar('ParamsForURL', $urlFilter->GetForURL());
    $content->SetVar('ParamsForLinkURL', $urlFilter->GetForURL(array('CityList')));
    
} elseif ($request->IsPropertySet('CityID')) {
    /*
     * Редактирование города
     */

    $exhibitionId = $request->GetIntProperty('ExhibitionID');
    if ($exhibitionId > 0) {
        $exhibition->loadByID($exhibitionId);
    }

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
    $navigation[] = array(
        'Title' => $exhibition->IsPropertySet('Title') ? $exhibition->GetProperty('Title') : '',
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID='.
            ($exhibitionId > 0 ? $exhibitionId : '')
    );
    $navigation[] = array(
        'Title' => 'Список городов',
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID='.
            intval($exhibitionId).'&CityList=1'
    );
    $header = array(
        "Title" => $currentSectionTitle,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );
    $content = $adminPage->Load('exhibition_city_edit.html', $header);
    
    $exhibitionCity = new ExhibitionCity($module);
    if ($request->IsPropertySet('Save')) {
        if ($exhibitionCity->save($request)) {
            header(
                'Location: ' . $moduleURL .
                '&Section=' . $request->GetProperty('Section').
                '&ExhibitionID=' . $exhibitionId.
                '&CityList=1'
            );
            exit;
        } else {
            $content->LoadFromObject($request);
            $content->LoadErrorsFromObject($exhibitionCity);
        }
    } else {
        $exhibitionCity->loadByID($request->GetIntProperty('CityID'));
        $content->LoadFromObject($exhibitionCity);
    }

    $selectedUniversitiesList = $exhibitionCity->getUniversities();
    $content->SetLoop('SelectedUniversitiesList', $selectedUniversitiesList);
    
    $partnerList = $exhibitionCity->getMainPartners();
    $content->SetLoop('MainPartnerList', $partnerList);

    $partnerList = $exhibitionCity->getPartners();
    $content->SetLoop('PartnerList', $partnerList);
    
    $univerList = new DataUniversityList($module);
    $univerList->LoadForSelection(0);
    $content->SetLoop('UniversitiesList', $univerList->GetItems());

    $content->SetVar('ExhibitionID', $exhibitionId);
    $content->SetLoop('TypeList', $exhibitionCity->getTypesListEvent());
    
} elseif ($request->IsPropertySet('ExhibitionID')) {
    
    $exhibitionId = $request->GetIntProperty('ExhibitionID');
    if ($exhibitionId > 0) {
        $exhibition->loadByID($exhibitionId);
    }

    $styleSheets = array(
        array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
    );
    $javaScripts = array(
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js")
    );
    $navigation[] = array(
        'Title' => $exhibition->IsPropertySet('Title') ? $exhibition->GetProperty('Title') : '',
        'Link' => $moduleURL . '&Section=' . $request->GetProperty('Section').'&ExhibitionID='.
            ($exhibitionId > 0 ? $exhibitionId : '')
    );
    $header = array(
        "Title" => $currentSectionTitle,
        "Navigation" => $navigation,
        "StyleSheets" => $styleSheets,
        "JavaScripts" => $javaScripts
    );
    $content = $adminPage->Load('exhibition.html', $header);

    if ($request->IsPropertySet('Save')) {
        if ($exhibition->save($request)) {
            header('Location: ' . $moduleURL . '&Section=' . $request->GetProperty('Section'));
            exit;
        } else {
            $content->LoadFromObject($request);
            $content->LoadErrorsFromObject($exhibition);
        }
    } elseif ($exhibitionId > 0) {
        $content->LoadFromObject($exhibition);
    }

    $exhibitionTemplates = [
        "page-exhibition.html",
        "page-exhibition2.html",
        "page-exhibition3.html",
        "page-exhibition4.html",
        "page-exhibition4_online.html",
        "page-exhibition5_online.html",
        "page-exhibition-landing.html"
    ];
    $pageList = new PageList();
    $pageList->LoadPageListForSelection($exhibitionTemplates, $exhibition->GetProperty("PageID"));
    $content->LoadFromObjectList("PageList", $pageList);
    $page2List = new PageList();
    $page2List->LoadPageListForSelection($exhibitionTemplates, $exhibition->GetProperty("Page2ID"));
    $content->LoadFromObjectList("Page2List", $page2List);

    if($exhibitionId)
    {
    	$registrationList = new DataRegistrationList($module);
    	if($request->GetProperty("Output") == "csv")
    	{
    		$registrationList->load(0, true, $exhibitionId);
    		$registrationList->exportToCSV();
    	}
    	elseif($request->GetProperty("Output") == "csv-group")
    	{
    	    $registrationList->exportToCSVGroup($exhibitionId);
    	}
    	elseif($request->GetProperty("OutputVisit") == "csv")
    	{
    	    $registrationList->exportVisitsByUserToCSV($exhibitionId, $request->GetProperty("ExportCityID"));
    	}
    	elseif($request->GetProperty("OutputVisitFlat") == "csv")
    	{
    	    $registrationList->exportVisitsToCSV($exhibitionId, $request->GetProperty("ExportCityID"));
    	}
    	elseif($request->GetProperty("Import") == "csv")
		{
		    if (!$registrationList->iniVisitsFromCSV($_FILES['importFile'])){
				$content->LoadErrorsFromObject($registrationList);
				return false;
			}
			
			$session =& GetSession();
			$session->SetProperty("utm_source", "import");
			$session->SetProperty("utm_medium", "form");
			$session->SetProperty("utm_campaign", preg_replace("/\.[^.]+$/", "", $_FILES['importFile']['name']));
			$session->SaveToDB();

			$publicExhibition = New PublicExhibition('data');
			$publicExhibition->loadByID($exhibitionId);
			$publicExhibition->loadCityList();
			$staticPath = 'import';
			$errorList = array();
			$i = 0;
			
			while ($row = $registrationList->nextVisitsRow()){
				$i++;
				if ($row[0] == 'Email'){
					continue;
				}

				$formRequest = new LocalObject();
				$formRequest->SetProperty('ExhibitionID',$exhibitionId);
				$currentCityInfo = null;
				$error = array();

				foreach ((array)$publicExhibition->GetProperty('CityList') as $city) {
					if ($city['CityTitle'] == $row[4]) {
						$currentCityInfo = $city;
						$publicExhibition->SetProperty('CityID', $city['CityID']);
						$formRequest->SetProperty('city',$city['CityTitle']);

						break;
					}
				}

                $stmt = GetStatement();
                $query = "SELECT GUID FROM data_exhibition_city WHERE 
                            CityID=" . intval($city['CityID']);
                $guid = $stmt->FetchRow($query)['GUID'];


				if ($currentCityInfo == null){
					$error['ErrorList'][]['Message'] = GetTranslation('exhibition-register-city-empty', 'data');
				}

				$user = new UserItem();
				if ($id = $user->getIDByEmail($row[0])){
					$user->loadByID($id);
				}

				$form['UserEmail'] = array($row[0]);
				$form['UserName'] = array($row[2]);
				$form['UserLastName'] = array($row[3]);
				$form['UserWho'] = array($row[5]);
				$form['UserClassNumber'] = array($row[6]);
				$form['UserPhone'] = array($row[1]);
				$form['UserInterest'] = array($row[7]);
				$form['UserTime'] = array($row[8]);
                
                $formRequest->SetProperty('GUID', $guid);

				$formRequest->SetProperty('RegisterForm', $form);

				if (empty($error['ErrorList'])){
					$publicExhibition->registration($formRequest, $user, $currentCityInfo, $staticPath, false);
					if (isset($formRequest->GetProperty('RegisterFormList')[0]['ErrorList'])){
						$error['ErrorList'] = $formRequest->GetProperty('RegisterFormList')[0]['ErrorList'];
					}
				}
				
				if (!empty($error['ErrorList'])){
					$error['Line'] = $i;
					$error['Email'] = $row[0];
					$error['Name'] = $row[2];
					$errorList[] = $error;
				}
			}

			if (!empty($errorList)){
				$content->SetLoop('ImportErrorList', $errorList);
			}
			else{
				$content->SetVar('ImportResult', true);
				$registrationList->load(5000, false, $exhibitionId);
				$content->LoadFromObjectList('RegistrationList', $registrationList);
				$content->SetVar("Paging", $registrationList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL().'&ExhibitionID='.$exhibitionId, null, '#tab-2'));
			}

		}
    	else 
    	{
    		$registrationList->load(5000, false, $exhibitionId);
    		$content->LoadFromObjectList('RegistrationList', $registrationList);
    		$content->SetVar("Paging", $registrationList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL().'&ExhibitionID='.$exhibitionId, null, '#tab-2'));
    	}

        $vkAdsImport = new DataVkImportList($module);
    	if ($request->GetProperty('Do') == 'ImportVK')
        {
            $vkAdsImport->load(5000, false, $exhibitionId, $request);
            $content->LoadFromObjectList('VkImportList', $vkAdsImport);
            $content->SetVar("VkPaging", $vkAdsImport->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL().'&ExhibitionID='.$exhibitionId, null, '#tab-3'));
        }

        $VkReportFilter = $request->GetProperty("VkReportFilter");
        $content->SetVar("VkReportDateFrom", $VkReportFilter['VkReportDateFrom']);
        $content->SetVar("VkReportDateTo", $VkReportFilter['VkReportDateTo']);
        $content->SetVar("Family", $VkReportFilter['Family']);
        $content->SetVar("Source", $VkReportFilter['Source']);
        $content->SetVar("Ads", $VkReportFilter['Ads']);
        $content->SetVar("VKReportSource", $VkReportFilter['VKReportSource']);
        $content->SetVar("VKReportCity", $VkReportFilter['VKReportCity']);
        $sort = $request->GetProperty('Sort');
        if ($sort['clicksDesc'])
        {
            $content->SetVar("OrderBy", "ClicksDesc");
        }
        if ($sort['spentDesc'])
        {
            $content->SetVar("OrderBy", "SpentDesc");
        }
        if ($sort['regDesc'])
        {
            $content->SetVar("OrderBy", "RegDesc");
        }
        if ($sort['impressionsDesc'])
        {
            $content->SetVar("OrderBy", "ImpressionsDesc");
        }
    	$exhibitionCityList = new ExhibitionCityList();
    	$exhibitionCityList->load($exhibitionId);
    	$content->LoadFromObjectList("CityList", $exhibitionCityList);

    	$exhibitionClassList = new ExhibitionClassList();
        $exhibitionClassList->load($exhibitionId);
        $vkReportClassList = array();
        $vkExcludeClassList = array();
        foreach ($exhibitionClassList as $key => $value)
        {
            foreach ($value as $id)
            {
                $vkReportClassList[] = [
                    'title' => $id['Class'],
                    'selected' => in_array($id['Class'], array($VkReportFilter['VKReportClass']))
                ];
                $vkExcludeClassList[] = [
                    'title' => $id['Class'],
                    'selected' => in_array($id['Class'], $VkReportFilter['VKExcludeClass'])
                ];
            }
        }
        $content->SetLoop("VkReportClassList", $vkReportClassList);
        $content->SetLoop('VkExcludeClassList', $vkExcludeClassList);

        //получение городов для фильтрации
        $vkReportCityList = array();
    	foreach ($exhibitionCityList as $key => $value)
        {
            foreach ($value as $id)
            {
                $vkReportCityList[] = [
                    'title' => $id['CityTitle'],
                    'static_path' => $id['StaticPath'],
                    'selected' => in_array($id['StaticPath'], array($VkReportFilter['VKReportCity']))
                ];
            }
        }
        $content->SetLoop("VkReportCityList", $vkReportCityList);
    }
} else {

    $header = array(
        "Title" => $currentSectionTitle,
        "Navigation" => $navigation,
        "StyleSheets" => array(),
        "JavaScripts" => array()
    );
    
    $content = $adminPage->Load('exhibition_list.html', $header);

    $exhibitionList = new ExhibitionList($module);
    
    if ($request->GetProperty('Do') == 'Remove') {
        $exhibitionList->remove($request->GetProperty('ListIDs'));
        header('Location: '.$moduleURL.'&'.$urlFilter->GetForURL());
        exit;
    }
    
    $exhibitionList->load();
    $content->LoadFromObjectList('ExhibitionList', $exhibitionList);
    $content->SetVar("Paging", $exhibitionList->GetPagingAsHTML($moduleURL . '&' . $urlFilter->GetForURL()));
}
