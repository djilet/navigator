<?php

if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}
use mikehaertl\wkhtmlto\Pdf;
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/proftest.php");
require_once(dirname(__FILE__) . "/include/user.php");
require_once(dirname(__FILE__) . "/include/task.php");
require_once(dirname(__FILE__) . "/include/answer.php");
require_once(dirname(__FILE__) . "/include/category.php");
es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");
es_include("js_calendar/calendar.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
$page = DefineInitialPage($request);
$boxTitle = array('Title' => '', 'replacements' => array());

$urlFilter->LoadFromObject($request, array('PageID'));

$user = new User();
$user->LoadBySession();

if ($request->IsPropertySet("PageID")) {
	//Init
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
		array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
	);

	$header = array(
		"Title"       => GetTranslation("module-admin-title", $module),
		"Navigation"  => $navigation,
		"JavaScripts" => array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
		),
		"StyleSheets" => array(
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css")
		)
	);
	
	$sections = array();
	if($user->GetProperty('Role') == PROFTEST){
	    $sections[] = array(
	        'Title' => GetTranslation("module-users-title", $module),
	        'Section' => 'User'
	    );
	}
	else {
	    $sections[] = array(
	        'Title' => GetTranslation("module-item-title", $module),
	        'Section' => 'Item'
	    );
	    $sections[] = array(
	        'Title' => GetTranslation("module-task-title", $module),
	        'Section' => 'Task'
	    );
	    $sections[] = array(
	        'Title' => GetTranslation("module-category-title", $module),
	        'Section' => 'Category'
	    );
	    $sections[] = array(
	        'Title' => GetTranslation("module-users-title", $module),
	        'Section' => 'User'
	    );
	}
	
	$selectedSection = ($request->IsPropertySet('Section') ? $request->GetProperty('Section') : $sections[0]['Section']);
	$urlFilter->SetProperty('Section', $selectedSection);
	$baseUrl = $moduleURL;

	foreach ($sections as $key => $section) {
		$section['Link'] = $baseUrl . '&PageID='.$request->GetIntProperty('PageID') . '&Section=' . $section['Section'];

		if ($section['Section'] == $selectedSection){
			$section['Selected'] = 1;
			$navigation[] = array("Title" => $section['Title'], "Link" => $section['Link']);
		}

		$sections[$key] = $section;
	}
	$content = $adminPage->Load("index.html", $header);
	$content->LoadFromObject($request);
	$content->SetVar("Section", $selectedSection);


	//Content
	$proftest = New Proftest();
	$proftestUser = New ProftestUser();
	$taks = New ProftestTask();
	$category = New ProftestCategory();
	$answer = New ProftestAnswer();

	$proftest->loadByPage($request->GetProperty('PageID'));
	$request->SetProperty('ProftestID', $proftest->GetProperty('ProftestID'));

	switch ($selectedSection){
		case 'Task':
			if ($request->IsPropertySet('AnswerID')){
				if ($request->IsPropertySet('Save')){
					$urlFilter->SetProperty('TaskAnswerList',$request->GetIntProperty('TaskID'));

					if (!$answer->save($request)){
						$content->LoadErrorsFromObject($answer);
						$content->LoadFromObject($request);
					}
					else{
						Send302($baseUrl . '&' . $urlFilter->GetForURL());
					}
				}
				elseif($request->GetProperty('Do') == 'Remove'){
					$answer->remove($request->GetIntProperty('AnswerID'));
					Send302($baseUrl . '&' . $urlFilter->GetForURL());
				}
				else{
					$answer->loadByID($request->GetIntProperty('AnswerID'));
					$content->LoadFromObject($answer);
				}

				$content->SetVar('EditAnswer', 1);
				if (!empty($request->GetIntProperty('AnswerID'))){
					$boxTitle['Title'] = 'edit-answer';
				}
				else{
					$boxTitle['Title'] = 'add-answer';
				}
				$navigation[] = array("Title" => GetTranslation('answer-list', $module), "Link" => $moduleURL."&".$urlFilter->GetForURL() . '&TaskAnswerList=1');
			}
			elseif ($request->IsPropertySet('TaskID')){
				if ($request->IsPropertySet('Save')){
					if (!$taks->save($request)){
						$content->LoadErrorsFromObject($taks);
						$content->LoadFromObject($request);
					}
					else{
						Send302($baseUrl . '&' . $urlFilter->GetForURL());
					}
				}
				elseif($request->GetProperty('Do') == 'Remove'){
					$taks->remove($request->GetIntProperty('TaskID'));
					Send302($baseUrl . '&' . $urlFilter->GetForURL());
				}
				else{
					$taks->loadByID($request->GetIntProperty('TaskID'));
					$content->LoadFromObject($taks);
					$category->loadListByTaskID($request->GetIntProperty('TaskID'));
					$content->LoadFromObjectList('CategoryList', $category->getObjectList());
				}

				$content->SetVar('EditTask', 1);
				if (!empty($request->GetIntProperty('TaskID'))){
					$boxTitle['Title'] = 'edit-task';
				}
				else{
					$boxTitle['Title'] = 'add-task';
				}
			}
			elseif($request->IsPropertySet('TaskAnswerList')){
				$answer->loadList($request->GetIntProperty('TaskAnswerList'));
				$content->LoadFromObjectList('AnswerList', $answer->getObjectList());
				$boxTitle['Title'] = 'answer-list';
			}
			else{
				$taks->loadList($proftest->GetProperty('ProftestID'));
				$content->LoadFromObjectList('TaskList', $taks->getObjectList());
				$boxTitle['Title'] = 'task-list';
			}
			break;

		case 'Category':
			if ($request->IsPropertySet('CategoryID')){
				if ($request->IsPropertySet('Save')){
					if (!$category->save($request)){
						$content->LoadErrorsFromObject($category);
						$content->LoadFromObject($request);
					}
					else{
						Send302($baseUrl . '&' . $urlFilter->GetForURL());
					}
				}
				elseif($request->GetProperty('Do') == 'Remove'){
					$category->remove($request->GetIntProperty('CategoryID'));
					Send302($baseUrl . '&' . $urlFilter->GetForURL());
				}
				else{
					$category->loadByID($request->GetIntProperty('CategoryID'));
					$content->LoadFromObject($category);
				}

				$content->SetVar('EditCategory', 1);
				if (!empty($request->GetIntProperty('CategoryID'))){
					$boxTitle['Title'] = 'edit-category';
				}
				else{
					$boxTitle['Title'] = 'add-category';
				}
			}
			else{
				$category->loadList($proftest->GetProperty('ProftestID'));
				$content->LoadFromObjectList('CategoryList', $category->getObjectList());
				$boxTitle['Title'] = 'category-list';
			}
			break;

		case 'User':
			if ($request->IsPropertySet('UserID')){
				$proftestUser->loadList(new LocalObject(array(
					'IDs' => [$request->GetIntProperty('UserID')],
					'ProftestID' => $proftest->GetProperty('ProftestID')
				)));
				$content->LoadFromObjectList('ResultList', $proftestUser->list);
				$boxTitle['Title'] = 'user-result-list';
			}
			else{
				if ($request->GetProperty('Do') == 'ExportPDF'){
					$request->SetProperty('groupBy', 'UserID');
					$request->SetProperty('OnlyLast', true);
					if ($request->IsPropertySet('DateFrom') && $request->IsPropertySet('DateTo')){
						$proftestUser->loadList($request);
					}

					$popupPage = new PopupPage($module, false);
					$template = $popupPage->Load("proftest_pdf.html");
					$template->SetVar('PageTitle', $page->GetProperty('Title'));
					$userResult = ProftestUser::getResult($proftestUser->getLinkIDsFromList(), true);
					
					$template->SetLoop('UserResult', $userResult);
					//$template->SetVar('Print', true);

					if ($request->IsPropertySet('Output')){
						$template->SetVar('TemplatePath', PROJECT_PATH . "website/" . WEBSITE_FOLDER . "/template/");
					}
					else{
						$template->SetVar('TemplatePath', PROJECT_DIR . 'website/' . WEBSITE_FOLDER . '/template/');
					}

					$content = $popupPage->Grab($template);
					
					if ($request->IsPropertySet('Output')){
						echo $content;
						exit();
					}
					$pdf = new Pdf(array(
						'margin-top'=> 5,
						'margin-bottom'=> 0,
						'margin-right' => 10,
						'margin-left' => 10,
					));
					$pdf->addPage($content);
					if (!$pdf->send('proftest.pdf', true)) {
						echo $pdf->getError();
					}

					exit();
				}
				elseif($request->GetProperty('Do') == 'ExportSCV'){
					$request->SetProperty('groupBy', 'UserID');
					$request->SetProperty('OnlyLast', true);
					if ($request->IsPropertySet('DateFrom') && $request->IsPropertySet('DateTo')){
						$proftestUser->loadList($request);
					}

					$proftestUser->exportListToCSV(GetUrlPrefix() . $page->GetProperty('StaticPath'));
				}
				else{
					$request->SetProperty('groupBy', 'UserID');
					$request->SetProperty('OnlyLast', true);
					$proftestUser->loadList($request);

					$urlFilter->SetProperty('load', $module);
					$content->LoadFromObjectList('UserList', $proftestUser->list);
					$boxTitle['Title'] = 'user-list';
					if ($request->IsPropertySet('DateFrom') && $request->IsPropertySet('DateTo')){
						$content->SetVar("DateFrom", $request->GetProperty('DateFrom'));
						$content->SetVar("DateTo", $request->GetProperty('DateTo'));
					}
					else{
						$range = $proftestUser::getDateRange();
						$content->SetVar("DateFrom", date("d.m.Y", strtotime($range['Min'])));
						$content->SetVar("DateTo",  date("d.m.Y", strtotime($range['Max'])));
					}

					$content->SetVar('ProftestID', $request->GetProperty('ProftestID'));
					$urlFilter->SetProperty('OnPage',$request->GetProperty('OnPage'));
					$pagingUrlFilter = new URLFilter();
					$pagingUrlFilter->LoadFromObject($request);
					$pagingUrlFilter->RemoveProperty('load');
					$content->SetVar("Paging", $proftestUser->list->GetPagingAsHTML($moduleURL.'&'.$pagingUrlFilter->GetForURL()));
					$urlFilter->RemoveProperty('OnPage');
				}
			}
			break;

		case 'Item':
			if ($request->IsPropertySet('Save')){
				if (!$proftest->save($request)){
					$content->LoadErrorsFromObject($proftest);
					$content->LoadFromObject($request);
				}
				else{
					Send302($baseUrl . '&' . $urlFilter->GetForURL());
				}
			}
			else{
				$content->LoadFromObject($proftest);
			}
			break;
		default:
	}

	$content->SetLoop('SectionMenu', $sections);
	$content->SetVar('BaseURL', $baseUrl);
	$content->SetVar('PageTitle', $page->GetProperty('Title'));
	$content->SetVar('PageStaticPath', $page->GetProperty('StaticPath'));
	$content->SetVar('BoxTitle', GetTranslation($boxTitle['Title'], $module, $boxTitle['replacements']));
}
else {
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL)
	);
	$pageList = new PageList();
	$header = array(
		"Title"       => GetTranslation("module-admin-title", $module),
		"Navigation"  => $navigation,
		"JavaScripts" => (isset($javaScripts) ? $javaScripts : array()),
	);
	$content = $adminPage->Load("page_list.html", $header);
	
	$pageList->LoadPageListForModule($module);
	$content->LoadFromObjectList('PageList', $pageList);
	
	$content->SetVar('BaseURL', $moduleURL);
}

$navigation[] = array("Title" => GetTranslation($boxTitle['Title'], $module), "Link" => '');
$content->SetLoop("Navigation", $navigation);
$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
$adminPage->Output($content);