<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/category.php");

$module = "proftest";
$result = array();

$request = new LocalObject(array_merge($_GET, $_POST));

switch ($request->GetProperty("Action")) {
	case "linked-category":
		$list = new ProftestCategory();
		$list->LoadListForSuggest($request);
		$result = $list->GetItems();
		break;
}

echo json_encode($result);