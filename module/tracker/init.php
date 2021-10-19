<?php
define("TRACKER_FILE_DIR", "/var/tracker/");
define("TRACKER_EXPORT_DIR", PROJECT_DIR."website/" . WEBSITE_FOLDER .  TRACKER_FILE_DIR);

define("ANALYTIC_FILE_DIR", "/var/analytic/");
define("ANALYTIC_LOG_DIR", PROJECT_DIR."website/" . WEBSITE_FOLDER .  ANALYTIC_FILE_DIR);

$GLOBALS['moduleConfig']['tracker'] = array(
	'AdminMenuIcon' => 'fa fa-eye',
    'ColorA' => '#000', 'ColorI' => '#000',
    'NoPages'		=> true,
    'Config' => array());