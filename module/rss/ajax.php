<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");

$module = "rss";
$result = array();

$request = new LocalObject(array_merge($_GET, $_POST));

switch ($request->GetProperty("Action")) {

}

echo json_encode($result);