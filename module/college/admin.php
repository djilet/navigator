<?php

if (!defined('IS_ADMIN'))
{
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__)."/include/college.php");
require_once(dirname(__FILE__)."/include/college_list.php");
require_once(dirname(__FILE__)."/include/college_bigdirection.php");
require_once(dirname(__FILE__)."/include/college_speciality.php");
require_once(dirname(__FILE__)."/include/college_awards.php");
require_once(dirname(__FILE__) . "/../data/include/admin/region_list.php");
require_once(dirname(__FILE__) . "/../data/include/CityList.php");
require_once(dirname(__FILE__)."/../users/include/user.php");
require_once(dirname(__FILE__)."/../users/include/user_list.php");

//es_include("page.php");
//es_include("pagelist.php");
//es_include("js_calendar/calendar.php");
es_include("urlfilter.php");

$user = new User();
$user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT));

$module = $request->GetProperty("load");
$adminPage = new AdminPage($module);
$navigation = array(array("Title" => GetTranslation("module-title", $module), "Link" => $moduleURL));

$sectionList = array(
    "college" => array(INTEGRATOR, ADMINISTRATOR),
    "awards" => array(INTEGRATOR, ADMINISTRATOR),
    "bigdirection" => array(INTEGRATOR, ADMINISTRATOR),
	"list" => array(INTEGRATOR, ADMINISTRATOR),
);

if(!$request->GetProperty("Section")) {
    $request->SetProperty("Section", "college");
}

$templateSectionList = array();
foreach ($sectionList as $section => $roles) {
    if(in_array($user->GetProperty("Role"), $roles))
    {
        if(!$request->GetProperty("Section")) {
            $request->SetProperty("Section", $section);
        }
        $sectionTitle = GetTranslation("section-".$section, $module);
        $templateSectionList[] = array(
            "Section" => $section,
            "Title" => $sectionTitle,
            "Selected" => ($request->GetProperty("Section") == $section ? 1 : 0)
        );
        if($request->GetProperty("Section") == $section)
        {
            $navigation[] = array("Title" => $sectionTitle, "Link" => $moduleURL."&Section=".$section);
            $currentSectionTitle = $sectionTitle;
        }
    }
    if($request->GetProperty("Section") == $section) {
        $user->ValidateAccess($roles);
    }
}

$urlFilter = new URLFilter();
$urlFilter->LoadFromObject($request, array("Section"));

require_once(dirname(__FILE__)."/controller/admin/".$request->GetProperty("Section").".php");

if(isset($content))
{
    $content->SetLoop("Navigation", $navigation);
    $content->SetLoop("SectionList", $templateSectionList);
    $content->SetVar("SectionTitle", $currentSectionTitle);
    $content->SetVar("ParamsForURL", $urlFilter->GetForURL());
    $content->SetVar("ParamsForForm", $urlFilter->GetForForm());
    $adminPage->Output($content);
}