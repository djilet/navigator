<?php

require_once(dirname(__FILE__)."/include/init.php");

$request = new LocalObject($_GET);
header("Content-Type: text/plain");
$fileName = "robots.txt";
$filePath = PROJECT_DIR."website/".WEBSITE_FOLDER."/".$fileName;

$str = file_get_contents($filePath);
$str = str_replace("[host]", GetUrlPrefix(), $str);
echo $str;