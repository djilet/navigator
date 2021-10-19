<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/Articles.php");
require_once(dirname(__FILE__) . "/include/WhoWork.php");
require_once(dirname(__FILE__) . "/include/WantWork.php");
require_once(dirname(__FILE__) . "/include/Operation.php");
require_once(dirname(__FILE__) . "/include/OpenDay.php");
require_once(dirname(__FILE__) . "/include/OpenDayPartner.php");
require_once(dirname(__FILE__) . "/include/OpenDaySlide.php");
require_once(dirname(__FILE__) . "/include/OnlineExhibitionParticipant.php");

require_once(dirname(__FILE__) . "/include/admin/area.php");
require_once(dirname(__FILE__) . "/include/admin/region.php");
require_once(dirname(__FILE__) . "/include/admin/type.php");
require_once(dirname(__FILE__) . "/include/admin/profession_list.php");
require_once(dirname(__FILE__) . "/include/admin/online_event.php");
require_once(dirname(__FILE__) . "/include/admin/exhibition_city.php");
require_once(dirname(__FILE__) . "/include/admin/exhibition_city_list.php");
require_once(dirname(__FILE__) . "/include/admin/article.php");
require_once(dirname(__FILE__) . "/include/admin/university_list.php");
require_once(dirname(__FILE__) . "/include/admin/direction_list.php");
require_once(dirname(__FILE__) . "/include/admin/speciality_list.php");
require_once(dirname(__FILE__) . "/include/admin/region_list.php");
require_once(dirname(__FILE__) . "/include/admin/bigdirection_list.php");
require_once(dirname(__FILE__) . "/include/admin/author.php");
require_once(dirname(__FILE__) . "/include/university_image_list.php");
es_include("user.php");

$module = "data";

$result = array();

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT, ROLE_UNIVERSITY))) {
    $result["SessionExpired"] = GetTranslation("your-session-expired");
    exit();
} else {
    $request = new LocalObject(array_merge($_GET, $_POST));

    switch ($request->GetProperty("Action")) {
        case "RemoveAreaImage":
            $course = new DataArea($module);
            $course->RemoveAreaImage(
                $request->GetProperty("ItemID"),
                $request->GetProperty('SavedImage'),
                $request->GetProperty('ImageName')
            );
            $result = true;
            break;
            
        case "RemoveRegionImage":
            $course = new DataRegion($module);
            $course->RemoveRegionImage(
                $request->GetProperty("ItemID"),
                $request->GetProperty('SavedImage'),
                $request->GetProperty('ImageName')
            );
            $result = true;
            break;
            
        case "RemoveTypeImage":
            $course = new DataType($module);
            $course->RemoveTypeImage(
                $request->GetProperty("ItemID"),
                $request->GetProperty('SavedImage'),
                $request->GetProperty('ImageName')
            );
            $result = true;
            break;
            
        case "RemoveArticleImage":
         	$article = new DataArticle($module);
           	$article->RemoveArticleImage(
        		$request->GetProperty("ItemID"),
        		$request->GetProperty('SavedImage'),
        		$request->GetProperty('ImageName')
           	);
           	$result = true;
           	break;

        case "RemoveOpenDayImage":
            $openDay = OpenDay::load($request->GetProperty("ItemID"));
            $result = $openDay->removeMainImage();
           	break;
           	
        case 'RemoveOnlineEventHeadImage':
        	$onlineEvent = new DataOnlineEvent($module);
        	$onlineEvent->RemoveHeadImage(
        		$request->GetProperty("ItemID"),
        		$request->GetProperty('SavedImage'),
        		$request->GetProperty('ImageName')
        	);
        	$result = true;
        	break;

        case 'RemoveUniversityImage':
            $result['status'] = UniversityImageList::removeItemById($request->GetProperty("ImageID"));
            break;

        case 'SetUniversityImageSortOrder':
            $result['status'] = UniversityImageList::SetSortOrder(
                $request->GetProperty("ImageID"),
                $request->GetProperty("Diff")
            );
            break;
            
        case 'SetExhibitionCitySortOrder':
            $exhibitionCityList = new ExhibitionCityList();
            $exhibitionCityList->updateSortOrder(
                $request->GetIntProperty('ExhibitionID'),
                $request->GetIntProperty('CityID'),
                $request->GetIntProperty('Diff')
            );
            break;
            
        case 'RemoveMainPartnerCity':
            $exhibitionCity = new ExhibitionCity($module);
            $result = $exhibitionCity->removeMainPartner($request->GetIntProperty('PartnerID'));
        	break;
        	
        case 'RemovePartnerCity':
        	$exhibitionCity = new ExhibitionCity($module);
        	$result = $exhibitionCity->removePartner($request->GetIntProperty('PartnerID'));
        	break;

        case "RemoveExhibitionCityHeadImage":
            $exhibitionCity = new ExhibitionCity($module);
            $exhibitionCity->loadByID($request->GetProperty("ItemID"));
            $result = $exhibitionCity->removeHeadImage();
            break;

        case 'RemoveOpenDayPartner':
            $result = false;
            $partner = OpenDayPartner::load($request->GetIntProperty('ID'));
            if ($partner){
                $result = $partner->remove();
            }
        	break;

        case 'RemoveOpenDaySlide':
            $result = false;
            $item = OpenDaySlide::load($request->GetIntProperty('ID'));
            if ($item){
                $result = $item->remove();
            }
        	break;
        	
        case "RemoveAuthorImage":
            $author = new DataAuthor($module);
            $author->RemoveAuthorImage(
                $request->GetProperty("AuthorID"),
                $request->GetProperty('SavedImage'),
                $request->GetProperty('ImageName')
                );
            $result = true;
            break;

        case "linked-article":
            $list = new Articles($module);
            $list->LoadForSuggest($request);
            $result = $list->GetItems();
            break;
        	
        case "linked-university":
        	$list = new DataUniversityList($module);
        	$list->LoadForSuggest($request);
        	$result = $list->GetItems();
        	break;
        
        case "linked-direction":
       		$list = new DataDirectionList($module);
        	$list->LoadForSuggest($request);
        	$result = $list->GetItems();
        	break;

        case "linked-profession":
        	$list = new DataProfessionList($module);
        	$list->LoadForSuggest($request);
        	$result = $list->GetItems();
        	break;
        	
        case "linked-speciality":
        	$list = new DataSpecialityList($module);
        	$list->LoadForSuggest($request);
        	$result = $list->GetItems();
        	break;
        
        case "linked-region":
        	$list = new DataRegionList($module);
        	$list->LoadForSuggest($request);
       		$result = $list->GetItems();
       		break;
       		
       	case "linked-bigdirection":
       		$list = new DataBigDirectionList($module);
       		$list->LoadForSuggest($request);
       		$result = $list->GetItems();
       		break;

		case "linked-who-work":
			$list = new WhoWork();
			$list->LoadForSuggest($request);
			$result = $list->GetItems();
       		break;

		case "linked-want-work":
			$list = new WantWork($module);
			$list->LoadForSuggest($request);
			$result = $list->GetItems();
       		break;

		case "linked-operation":
			$list = new Operation($module);
			$list->LoadForSuggest($request);
			$result = $list->GetItems();
       		break;

        case "SaveOnlineExhibitionParticipantSorting":
            $items = $request->GetProperty('Data');
            $result = true;
            if (!empty($items)){
                foreach ($request->GetProperty('Data') as $item){
                    $participant = OnlineExhibitionParticipant::get($item['id']);
                    $participant->SetProperty('SortOrder', $item['sortOrder']);
                    $result = $participant->save();

                    if(!$result){
                        break;
                    }
                }
            }
            break;
    }
}

echo json_encode($result);