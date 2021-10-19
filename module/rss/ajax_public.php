<?php
require_once(dirname(__FILE__) . "/../../include/init.php");
es_include("localpage.php");
es_include("urlfilter.php");


$module = "rss";
$request = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'success');


switch ($request->GetProperty("Action")) {

	default:
		$result['Status'] = 'error';
		break;
}

echo json_encode($result);
