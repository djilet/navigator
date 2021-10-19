<?php
define("COLLEGE_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/college/");
define("COLLEGE_IMAGE", "100x100|8|Admin,500x500|0|Full");

define("COLLEGE_COLLEGE_PAGE", "college");
define("COLLEGE_COLLEGE_PAGE_SPECIALITIES", "specialities");
define("COLLEGE_COLLEGE_PAGE_CONTACTS", "contacts");
define("COLLEGE_COLLEGE_PAGES", [DATA_UNIVERSITY_PAGE_SPECIALITIES, DATA_UNIVERSITY_PAGE_CONTACTS]);

$GLOBALS['moduleConfig']['college'] = array(
	'AdminMenuIcon' => 'fa fa-university',
	'ColorA' => '#000', 'ColorI' => '#000',
	'Config' => array()
);