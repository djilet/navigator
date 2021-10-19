<?php

if (!defined('IS_ADMIN')) {
	echo "Incorrect call to admin interface";
	exit();
}

require_once(dirname(__FILE__) . "/init.php");

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

//Init
$navigation = array(
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

$sections = array(
	array(
		'Title' => GetTranslation("module-title", $module),
		'Section' => 'User',
	),
);

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


//Content
$test = new BaseTest();
$userList = new BaseTestUserList();

switch ($selectedSection){
	default:
    break;
}


$content->SetLoop('SectionMenu', $sections);
$content->SetVar('Section', $selectedSection);
$content->SetVar('BaseURL', $baseUrl);
$content->SetVar('PageTitle', $page->GetProperty('Title'));
$content->SetVar('PageStaticPath', $page->GetProperty('StaticPath'));
$content->SetVar('BoxTitle', GetTranslation($boxTitle['Title'], $module, $boxTitle['replacements']));

$navigation[] = array("Title" => GetTranslation($boxTitle['Title'], $module), "Link" => '');
$content->SetLoop("Navigation", $navigation);
$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
$adminPage->Output($content);