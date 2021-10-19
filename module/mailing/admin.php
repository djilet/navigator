<?php

if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/mailing.php");
require_once(dirname(__FILE__) . "/include/mailing_list.php");
es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
$page = DefineInitialPage($request);
$javaScripts = array();

$urlFilter->LoadFromObject($request, array('MailingID'));

if ($request->IsPropertySet("MailingID"))
{
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL)
	);
	$javaScripts = array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/timepicker/js/timepicker.min.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
			array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
	);
	$styleSheets = array(
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/timepicker/css/timepicker.min.css")
	);
	$header = array(
			"Title"       => GetTranslation("module-admin-title", $module),
			"Navigation"  => $navigation,
			"StyleSheets" => $styleSheets,
			"JavaScripts" => $javaScripts,
	);
	$content = $adminPage->Load("mailing_edit.html", $header);
	
	$mailing = new Mailing($module);
	
	if ($request->GetProperty("Save"))
	{
		$mailing->LoadFromObject($request);
		if ($mailing->Save())
		{
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($mailing);
		}
	}
	else
	{
		$mailing->LoadByID($request->GetProperty("MailingID"));
	}
	
	$content->LoadFromObject($mailing);
	
	$fromList = array(
			array("From" => "noreply@propostuplenie.ru", "Selected" => 1)
	);
	$content->SetLoop("FromList", $fromList);
}
else 
{
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL)
	);
	
	$header = array(
			"Title"       => GetTranslation("module-admin-title", $module),
			"Navigation"  => $navigation,
			"JavaScripts" => $javaScripts,
	);
	
	$content = $adminPage->Load("mailing_list.html", $header);
	
	$mailingList = new MailingList($module);
	$mailingList->Load($request);
	$content->LoadFromObjectList('MailingList', $mailingList);
	$content->SetVar("Paging", $mailingList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
	
	$content->SetVar('BaseURL', $moduleURL);
	$content->SetVar('FilterStatus', $request->GetProperty('FilterStatus'));
}
    
$adminPage->Output($content);