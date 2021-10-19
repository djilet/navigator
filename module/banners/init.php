<?php

define("BANNERS_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/banners/");
define("BANNERS_IMAGE_CONFIG", "100x100|8|Admin,500x500|0|Full");

$GLOBALS['moduleConfig']['banners'] = array(
	'AdminMenuIcon' => 'fa fa-puzzle-piece',
	'ColorA'        => '#ff55ff',
	'ColorI'        => '#3355ff',
    'NoPages'		=> true,
	'Config'        => array(),
);