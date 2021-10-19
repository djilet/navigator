<?php

if (!defined('IS_ADMIN')) {
	echo "Incorrect call to admin interface";
	exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/test.php");

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
    $config = $page->GetConfig();
    if($user->GetProperty('Role') == CONSULTANT && $config['OpenResult'] != false){
        Send302($moduleURL);
    }

    //Init
    $navigation = array(
        array("Title" => GetTranslation("test-list", $module), "Link" => $moduleURL),
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
            'Title' => GetTranslation("module-user-title", $module),
            'Section' => 'User',
        ),
        array(
            'Title' => GetTranslation("module-statistic-title", $module),
            'Section' => 'Statistic',
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
        case 'User':
            if ($request->GetProperty('Do') == 'ShowTest' && $request->IsPropertySet('TestUserID')){
                $testUser = new BaseTestUser();
                $testUser->load($request->GetProperty('TestUserID'));

                $baseUrl = GetUrlPrefix() . $page->GetProperty('StaticPath') . '/';
                if ($link = $testUser->getShortLink($baseUrl)){
                    Send302($link);
                }
            }
            if($request->GetProperty('Do') == 'ExportSCV'){
                $userList->load($request, 0);
                $userList->exportToCSV();
            }
            else{
                $userList->load($request);
                $pagingUrlFilter = new URLFilter();
                $pagingUrlFilter->LoadFromObject($request);
                $pagingUrlFilter->RemoveProperty('load');

                $content->LoadFromObjectList('UserList', $userList);
                $content->SetVar('UserModuleURL', Module::getAdminModuleUrl('users'));
                $content->SetVar("Paging", $userList->GetPagingAsHTML($moduleURL.'&'.$pagingUrlFilter->GetForURL()));

                $urlFilter->SetProperty('load', $module);
            }
            break;

        case 'Statistic':
            $statistic = array();
            $data = $test->getProfessionStatistic();
            foreach ($data as $point => $items) {
                $statistic[]['Items'] = array_values($items);
            }
            $content->SetLoop('ProfessionPointsList', $statistic);
            $boxTitle['Title'] = 'profession-statistic';
            break;

        default:
    }


    $content->SetLoop('SectionMenu', $sections);
    $content->SetVar('Section', $selectedSection);
    $content->SetVar('BaseURL', $baseUrl);
    $content->SetVar('PageTitle', $page->GetProperty('Title'));
    $content->SetVar('PageStaticPath', $page->GetProperty('StaticPath'));
    $content->SetVar('BoxTitle', GetTranslation($boxTitle['Title'], $module, $boxTitle['replacements']));
}
else{
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

    $templatePageList = array();
    foreach ($pageList->_items as $index => $page) {
        $config = $pageList->GetConfig($module, $page['PageConfig']);

        //Prepare list for CONSULTANT;
        if ($user->GetProperty('Role') == CONSULTANT && $config['OpenResult'] != false){
            continue;
        }

        $templatePageList[] = $page;
    }

    $content->SetLoop('PageList', $templatePageList);

    $content->SetVar('BaseURL', $moduleURL);
}

$navigation[] = array("Title" => GetTranslation($boxTitle['Title'], $module), "Link" => '');
$content->SetLoop("Navigation", $navigation);
$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
$adminPage->Output($content);