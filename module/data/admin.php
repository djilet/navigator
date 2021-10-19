<?php

if (!defined('IS_ADMIN'))
{
	echo "Incorrect call to admin interface";
	exit();
}
require_once dirname(__FILE__) . '/include/Articles.php';
require_once dirname(__FILE__) . '/include/ArticleTag.php';
require_once dirname(__FILE__) . '/include/ArticleTagList.php';
require_once(dirname(__FILE__) . "/include/WhoWork.php");
require_once(dirname(__FILE__)."/include/WantWork.php");
require_once(dirname(__FILE__)."/include/Operation.php");
require_once(dirname(__FILE__)."/include/Industry.php");
require_once(dirname(__FILE__)."/include/achievement.php");
require_once(dirname(__FILE__)."/include/speciality_study_list.php");
require_once(dirname(__FILE__)."/include/read_later_list.php");
require_once(dirname(__FILE__)."/include/City.php");
require_once(dirname(__FILE__)."/include/CityList.php");
require_once(dirname(__FILE__)."/include/UniversityAgent.php");
require_once(dirname(__FILE__)."/include/UniversityAgentList.php");
require_once(dirname(__FILE__)."/include/OpenDay.php");
require_once(dirname(__FILE__)."/include/OpenDayList.php");
require_once(dirname(__FILE__)."/include/OpenDayPartner.php");
require_once(dirname(__FILE__)."/include/OpenDayPropertyList.php");
require_once(dirname(__FILE__)."/include/OpenDaySlide.php");
require_once(dirname(__FILE__)."/include/OpenDayRegistration.php");
require_once(dirname(__FILE__)."/include/OpenDayRegistrationList.php");
require_once(dirname(__FILE__)."/include/UserUniversityList.php");
require_once(dirname(__FILE__)."/include/OnlineExhibition.php");
require_once(dirname(__FILE__)."/include/OnlineExhibitionList.php");
require_once(dirname(__FILE__)."/include/OnlineExhibitionParticipant.php");
require_once(dirname(__FILE__)."/include/OnlineExhibitionParticipantList.php");
require_once(dirname(__FILE__)."/include/UniversityCategoryList.php");
require_once(dirname(__FILE__)."/include/service/UserUniversityService.php");

require_once(dirname(__FILE__)."/include/admin/device_list.php");
require_once(dirname(__FILE__)."/include/admin/device.php");
require_once(dirname(__FILE__)."/include/admin/area_list.php");
require_once(dirname(__FILE__)."/include/admin/area.php");
require_once(dirname(__FILE__)."/include/admin/region_list.php");
require_once(dirname(__FILE__)."/include/admin/region.php");
require_once(dirname(__FILE__)."/include/admin/type_list.php");
require_once(dirname(__FILE__)."/include/admin/type.php");
require_once(dirname(__FILE__)."/include/admin/bigdirection_list.php");
require_once(dirname(__FILE__)."/include/admin/direction_list.php");
require_once(dirname(__FILE__)."/include/admin/direction.php");
require_once(dirname(__FILE__)."/include/admin/university_list.php");
require_once(dirname(__FILE__)."/include/admin/university.php");
require_once(dirname(__FILE__) . "/include/university_image_list.php");
require_once(dirname(__FILE__)."/include/admin/speciality_list.php");
require_once(dirname(__FILE__)."/include/admin/speciality.php");
require_once(dirname(__FILE__)."/include/admin/subject_list.php");
require_once(dirname(__FILE__)."/include/admin/registration_list.php");
require_once(dirname(__FILE__)."/include/admin/vk_import_list.php");
require_once(dirname(__FILE__)."/include/admin/vk_ads_city.php");
require_once(dirname(__FILE__)."/include/admin/exhibition_class_list.php");
require_once(dirname(__FILE__)."/include/admin/push.php");
require_once(dirname(__FILE__)."/include/admin/online_event_list.php");
require_once(dirname(__FILE__)."/include/admin/online_event.php");
require_once(dirname(__FILE__)."/include/admin/exhibition.php");
require_once(dirname(__FILE__)."/include/admin/exhibitions_list.php");
require_once(dirname(__FILE__)."/include/admin/exhibition_city.php");
require_once(dirname(__FILE__)."/include/admin/exhibition_city_list.php");
require_once(dirname(__FILE__)."/include/admin/article.php");
require_once(dirname(__FILE__)."/include/admin/list.php");
require_once(dirname(__FILE__)."/include/admin/list_list.php");
require_once(dirname(__FILE__)."/include/admin/author_list.php");
require_once(dirname(__FILE__)."/include/admin/author.php");
require_once(dirname(__FILE__)."/../users/include/user_list.php");

require_once(dirname(__FILE__)."/include/public/University.php");
require_once(dirname(__FILE__)."/include/public/Region.php");
es_include("urlfilter.php");

$user = new User();
$user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT, ROLE_UNIVERSITY));

$module = $request->GetProperty("load");
$adminPage = new AdminPage($module);
$navigation = array(array("Title" => GetTranslation("module-title", $module), "Link" => $moduleURL));

$sectionList = array(
	"device" => array(INTEGRATOR, ADMINISTRATOR),
	"area" => array(INTEGRATOR, ADMINISTRATOR),
	"region" => array(INTEGRATOR, ADMINISTRATOR),
	"city" => array(INTEGRATOR, ADMINISTRATOR),
	"type" => array(INTEGRATOR, ADMINISTRATOR),
	"direction" => array(INTEGRATOR, ADMINISTRATOR),
	"university" => array(INTEGRATOR, ADMINISTRATOR, ROLE_UNIVERSITY),
	"push" => array(INTEGRATOR, ADMINISTRATOR),
	"profession" => array(INTEGRATOR, ADMINISTRATOR),
	"industry" => array(INTEGRATOR, ADMINISTRATOR),
	"who_work" => array(INTEGRATOR, ADMINISTRATOR),
	"want_work" => array(INTEGRATOR, ADMINISTRATOR),
	"operation" => array(INTEGRATOR, ADMINISTRATOR),
	"online_event" => array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT),
	"open_day" => array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT, ROLE_UNIVERSITY),
	"exhibition" => array(INTEGRATOR, ADMINISTRATOR),
	"article" => array(INTEGRATOR, ADMINISTRATOR, ROLE_UNIVERSITY),
	"article_tag" => array(INTEGRATOR, ADMINISTRATOR),
	"list" => array(INTEGRATOR, ADMINISTRATOR),
	"achievement" => array(INTEGRATOR, ADMINISTRATOR),
	"read_later" => array(INTEGRATOR, ADMINISTRATOR),
    "author" => array(INTEGRATOR, ADMINISTRATOR),
    "university_agent" => array(INTEGRATOR, ADMINISTRATOR),
    "online_exhibition" => array(INTEGRATOR, ADMINISTRATOR),
);

if ($user->getRole() === ROLE_UNIVERSITY){
    $sectionList['user_university'] = [INTEGRATOR, ADMINISTRATOR, ROLE_UNIVERSITY];
}

if(!$request->GetProperty("Section"))
{
    switch ($user->GetProperty("Role")){
        case ONLINEEVENT:
            $request->SetProperty("Section", "online_event");
            break;
        case ROLE_UNIVERSITY:
            $request->SetProperty("Section", "university");
            break;
        default:
            $request->SetProperty("Section", "device");
            break;
    }
}

$templateSectionList = array();
foreach ($sectionList as $section => $roles)
{
	if(in_array($user->GetProperty("Role"), $roles))
	{
		if(!$request->GetProperty("Section"))
		{
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
	if($request->GetProperty("Section") == $section)
	{
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

?>