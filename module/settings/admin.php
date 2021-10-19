<?php
if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
es_include("page.php");
es_include("pagelist.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$navigation = array(
	array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
);
$styleSheets = array(
	array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
);
$javaScripts = array(
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
);

$header = array(
	"Title" => GetTranslation("module-admin-title", $module),
	"StyleSheets" => $styleSheets,
	"JavaScripts" => $javaScripts,
	"Navigation" => $navigation,
);

$content = $adminPage->Load("index.html", $header);
    
$adminPage->Output($content);