<?php
define("OLYMPIAD_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/olympiad/");
define("OLYMPIAD_DIR_URL", GetUrlPrefix()."website/".WEBSITE_FOLDER."/var/olympiad/");

$GLOBALS['moduleConfig']['olympiad'] = array(
	'AdminMenuIcon' => 'fa fa-flag',
	'ColorA'        => '#ff5500',
	'ColorI'        => '#335500',
	'Config'        => array(),
);