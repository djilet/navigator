<?php

define("DATA_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/data/");
define("DATA_EXHIBITION_IMAGE_DIR", DATA_IMAGE_DIR."exhibition/");
define("DATA_ONLINE_EXHIBITION_IMAGE_DIR", DATA_IMAGE_DIR."online_exhibition/");
define("DATA_OPEN_DAY_IMAGE_DIR", DATA_IMAGE_DIR."openday/");
define("DATA_OPEN_DAY_SLIDER_IMAGE_DIR", DATA_IMAGE_DIR."openday/slider/");
define("DATA_OPEN_DAY_ONLINE_EVENT_TYPE_PATH", "opendays");
define("DATA_UNIVERSITY_IMAGE_DIR", DATA_IMAGE_DIR."univer/");
define("DATA_IMAGE_URL_PREFIX", GetUrlPrefix()."website/".WEBSITE_FOLDER."/var/data/");

define("DATA_AREA_IMAGE", "100x100|8|Admin,100x100|0|Full");
define("DATA_REGION_IMAGE", "100x100|8|Admin,100x100|0|Full");
define("DATA_TYPE_IMAGE", "100x100|8|Admin,100x100|0|Full");
define("DATA_EXHIBITION_INFOITEM_IMAGE", "100x100|8|Admin,936x473|8|Main");
define("DATA_EXHIBITION_PARTNER_IMAGE", "100x100|8|Admin,936x473|8|Main");
define("DATA_EXHIBITION_HEAD_IMAGE", "100x100|8|Admin,936x473|8|Main");
define("DATA_OPEN_DAY_PARTNER_IMAGE", "100x100|8|Admin,936x473|8|Main");
define("DATA_OPEN_DAY_IMAGE", "100x100|8|Admin,936x473|8|Main");
define("DATA_OPEN_DAY_MAIN_IMAGE", "200x67|8|Admin,1312x437|8|Main");
define("DATA_OPEN_DAY_SLIDER_IMAGE", "136x64|1|Thumb,216x200|11|ThumbLanding");
define("DATA_UNIVERSITY_IMAGE", "100x100|8|Admin,500x500|0|Full");
define("DATA_ARTICLE_IMAGE", "100x100|8|Admin,136x80|8|Full,600x350|8|Preview");
define("DATA_ARTICLEMAIN_IMAGE", "100x100|8|Admin,672x390|8|Full,1920x640|8|Head,800x800|8|Best,536x274|8|Insert");
define("DATA_ONLINEEVENTHEAD_IMAGE", "100x100|8|Admin,672x390|8|Full,1920x640|8|Head");
define("DATA_AUTHOR_IMAGE", "100x100|8|Admin,100x100|0|Full,50x50|8|List,100x100|8|Preview");
define("DATA_ONLINE_EXHIBITION_PARTICIPANT_IMAGE", "136x64|1|List");
define("DATA_SPECIALITY_STUDY_CURRENT_YEAR", 2021);

define("DATA_UNIVERSITY_PAGE", "vuzi");
define("DATA_UNIVERSITY_PAGE_SPECIALITIES", "specialities");
define("DATA_SPECIALITIES_PAGE", "specialnosti");
define("DATA_UNIVERSITY_PAGE_CONTACTS", "contacts");
define("DATA_UNIVERSITY_PAGES", [DATA_UNIVERSITY_PAGE_SPECIALITIES, DATA_UNIVERSITY_PAGE_CONTACTS]);

define("DATA_PROFESSION_PAGE_UNIVERSITY", "vuzi");
define("DATA_PROFESSION_PAGES", [DATA_PROFESSION_PAGE_UNIVERSITY]);

define("CLIENT_ANDROID", "android");
define("CLIENT_IOS", "ios");

$GLOBALS['moduleConfig']['data'] = array(
    'AdminMenuIcon' => 'fa fa-list',
    'ColorA' => '#000', 'ColorI' => '#000',
    'Config' => array()
);
