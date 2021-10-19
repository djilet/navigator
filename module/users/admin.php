<?php

if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/user.php");
require_once(dirname(__FILE__) . "/include/user_list.php");
es_include("page.php");
es_include("pagelist.php");
es_include("js_calendar/calendar.php");
es_include("urlfilter.php");
es_include("ChatUser.php");
es_include("service/ChatUserService.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
$page = DefineInitialPage($request);
$chatUserService = new ChatUserService();

$urlFilter->LoadFromObject($request, array('PageID'));

if ($request->IsPropertySet("UserID")) {

    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
        array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
    );
    $styleSheets = array(
        array("StyleSheetFile" => PROJECT_PATH . "include/js_calendar/skins/calendar-win2k-1.css"),
    );
    $javaScripts = array(
        array("JavaScriptFile" => PROJECT_PATH . "include/js_http_request/JsHttpRequest.js"),
    );
    $header = array(
        //"Title"       => $title,
        "Navigation"  => $navigation,
        "JavaScripts" => $javaScripts,
        "StyleSheets" => $styleSheets,
    );

    $userItem = new UserItem($module);
    $content = $adminPage->Load("item_edit.html", $header);

    if ($request->GetProperty("Save")) {
        $userItem->LoadFromObject($request);
        if ($userItem->updateData($request)) {
            $chatUser = ChatUser::getByUserID($userItem->GetIntProperty('UserID'));
            if (!$chatUser || $userItem->GetProperty('ChatStatus') != $chatUser->ChatStatus){
                $chatUser = $chatUserService->updateOrCreateByConnection(
                    ChatUser::CONNECTION_TYPE_USER,
                    $userItem->GetIntProperty('UserID'),
                    $userItem->GetProperty('UserName'),
                    $userItem->GetProperty('ChatStatus')
                );
            }

            header("location: " . $moduleURL . "&" . $urlFilter->GetForURL());
            exit();
        } else {
            $content->LoadFromObject($request);
        }
    } else {
        $userItem->LoadByID($request->GetProperty('UserID'));
        $userItem->loadSocialAuth();
        $content->LoadFromObject($userItem);
    }

    //$content->SetLoop('DevicesList', $userItem->getDevices());

    $content->LoadErrorsFromObject($userItem);
    $content->LoadMessagesFromObject($userItem);
} else {
    $navigation = array(
        array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
        array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
    );

    $userItemList = new UserItemList($module);

    if ($request->GetProperty("UserIDs")) {
        if ($request->GetProperty('Do') == 'Remove') {
            $userItemList->removeByItemIDs($request->GetProperty("UserIDs"));
        }
    }
    
    if ($request->GetProperty('Do') == 'ReportCSV')
    {
        $userItemList->exportToCSV();
    }

    $javaScripts = array(
        array("JavaScriptFile" => PROJECT_PATH . "include/js_http_request/JsHttpRequest.js"),
    );
    $header = array(
        "Title"       => GetTranslation("module-admin-title", $module),
        "Navigation"  => $navigation,
        "JavaScripts" => $javaScripts,
    );
    $content = $adminPage->Load("item_list.html", $header);

    $userItemList->load(100, $request);
    $content->LoadFromObjectList('ItemList', $userItemList);
    $content->SetVar("Paging", $userItemList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));

    $content->LoadErrorsFromObject($userItemList);
    $content->LoadMessagesFromObject($userItemList);

    $content->SetVar('BaseURL', $moduleURL.'&PageID='.$request->GetIntProperty('PageID'));
    if ($request->IsPropertySet('Filter')){
		$content->SetVar('Filter', true);
		$content->SetVar('FilterEmail', $request->GetProperty('Filter')['email']);
	}
}

$adminPage->Output($content);