<?php
define("MARATHON_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/marathon/");

define("MARATHON_PDF_DIR", MARATHON_DIR . "pdf/");
define("MARATHON_MAP_ICONS_DIR", MARATHON_DIR . "map/");
define("MARATHON_IMAGE_URL_PREFIX", GetUrlPrefix()."website/".WEBSITE_FOLDER."/var/marathon/");

$GLOBALS['moduleConfig']['marathon'] = array(
	'AdminMenuIcon' => 'fa fa-flag',
	'ColorA'        => '#ff5500',
	'ColorI'        => '#335500',
	'Config'        => array(),
);