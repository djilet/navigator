<?php
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once dirname(__FILE__) . '/include/Articles.php';
require_once dirname(__FILE__) . '/include/City.php';
require_once dirname(__FILE__) . '/include/profession.php';
require_once dirname(__FILE__) . '/include/public/University.php';
require_once dirname(__FILE__) . '/include/public/Professions.php';
require_once dirname(__FILE__) . '/include/public/PublicExhibition.php';
require_once(dirname(__FILE__) . "/../users/include/user.php");
require_once(dirname(__FILE__) . "/../tracker/include/tracker.php");
require_once(dirname(__FILE__) . "/include/read_later_list.php");
es_include("localpage.php");
es_include("urlfilter.php");
require_once (__DIR__ . '/../share/include/share.php');

$module = "data";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');
$urlParser = GetURLParser();
$currentCityPath = $urlParser->GetSubDomain();

switch ($post->GetProperty("Action")) {
	case "loadArticles":
		$articles = new Articles($module);
		$result['status'] = 'success';

		$page = new Page();
		$page->LoadByID($post->GetIntProperty('PageID'));
		$url = $page->GetPageURL(false);

		$post->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));
		$articles->load($post, 18);

		$popupPage = new PopupPage($module, false);
		$tpl = $popupPage->Load('_data/article_list.html');
		$tpl->LoadFromObjectList('ArticleList', $articles);
		$paging = $articles->GetPagingAsArray($url, $url);
		$tpl->SetLoop('ArticlePager', $paging);
        foreach ($paging as $index => $articlePage) {
            if ($articlePage['Selected'] && !$articlePage['Last'] && !$paging[$index + 1]['Selected']) {
                $tpl->SetVar('ArticleNextPage', $paging[$index + 1]['Page']);
                $tpl->SetVar('ArticleNextPageURL', $paging[$index + 1]['URL']);
                break;
            }
        }
		$result['html'] = $popupPage->Grab($tpl);

		break;

	case "loadUniversity":
		$university = new University($module);
		$result['status'] = 'success';
	
		$page = new Page();
		$page->LoadByID($post->GetIntProperty('PageID'));
		$url = $page->GetPageURL(false);
	
		$post->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));

        if ($currentCityPath && $city = City::getByStaticPath($urlParser->GetSubDomain())){
            $filter = $post->GetProperty('UniverFilter');
            $filter['CityID'] = $city->GetIntProperty('ID');
            $post->SetProperty('UniverFilter', $filter);
        }

		$university->load($post);
	
		$popupPage = new PopupPage($module, false);
		$tpl = $popupPage->Load('_data/university_list.html');
		$tpl->LoadFromObjectList('UniversityList', $university);
		$tpl->SetLoop('UniversityPager', $university->GetPagingAsArray($url, $url));
		$result['html'] = $popupPage->Grab($tpl);

    //$tracker = new Tracker;
    //$tracker->addAction(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH), $post->GetProperty('UniverFilter'));
	
		break;
        
    case 'loadProfession':
    	$professions = new Professions("data");
       	$result['status'] = 'success';
        
       	$urlFilter = new URLFilter();
       	$urlFilter->LoadFromObject($post, array("TextSearch", "ProfessionFilter", "SortOrder"));
       	
       	$page = new Page();
       	$page->LoadByID($post->GetIntProperty('PageID'));
       	$url = $page->GetPageURL(false).'?'.$urlFilter->GetForURL();

       	$post->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));
        if ($currentCityPath && $city = City::getByStaticPath($currentCityPath)){
            $post->SetProperty('CityIDs', [$city->GetProperty('ID')]);
        }
       	$professions->load($post, 30, 'AjaxPager');
        
       	$popupPage = new PopupPage($module, false);
       	$tpl = $popupPage->Load('_data/profession_list.html');
       	$tpl->LoadFromObjectList('ProfessionList', $professions);
       	$tpl->SetVar('ProfessionPager', $professions->GetPagingAsHTML($url, $url));

       	$tpl->SetVar('PageID', $post->GetIntProperty('PageID'));
       	$tpl->SetVar('TextSearch', $post->GetProperty('TextSearch'));
       	$tpl->SetVar('ProfessionFilter', $post->GetProperty('ProfessionFilter'));
       	$tpl->SetVar('SortOrder', $post->GetProperty('SortOrder'));
       	
       	$result['html'] = $popupPage->Grab($tpl);
        
       	break;

    case 'GetProfessionInfo':
        $profession = new DataProfession();
        $id = $post->GetIntProperty('ProfessionID');
        if ($id > 0){
            $info = $profession->getItemInfo($id,'',false,false);
            $result = $info;
            $result['status'] = 'success';
        }
        break;
       
    case "loadExhibitionSchedule":
        $exhibition = new PublicExhibition($module);
        
        $city = $exhibition->loadCityInfo($post->GetProperty('CityID'));
        if($city){
            $scheduleInfo = $exhibition->getCitySchedule($city, $post);
            
            $popupPage = new PopupPage($module, false);
            $tpl = $popupPage->Load('_data/exhibition_landing_schedule.html');
            $tpl->SetLoop('Schedule', $scheduleInfo['TimeList']);
            $tpl->SetVar('ScheduleShowMore', $scheduleInfo['ShowMore']);
            $tpl->SetVar('ScheduleLineLimit', $scheduleInfo['LineLimit']);
            $result['status'] = 'success';
            $result['html'] = $popupPage->Grab($tpl);
        }
        else {
            $result['status'] = 'error';
        }
        
        break;
        
    case "registerExhibition":
        $user = new UserItem(null);
        $user->loadBySession();
        
        $page = new Page();
        $page->LoadByID($post->GetIntProperty('PageID'));
        $exhibition = new PublicExhibition($module);
        $city = $exhibition->loadCityInfo($post->GetProperty('CityID'));
        if($city){
            $post->SetProperty('city', $city['CityTitle']);
            $post->SetProperty('ExhibitionID', $city['ExhibitionID']);
            if ($exhibition->registration($post, $user, $city, $page->GetProperty('StaticPath'))) {
                $result['status'] = 'success';
                $result['nextPage'] = 'RegistrationSuccess2';
                $result['registrationID'] = $exhibition->GetProperty('RegistrationIDList');
                $result['messageList'] = $exhibition->GetMessagesAsArray();
            } else {
                $result['status'] = 'error';
                $result['formList'] = $post->GetProperty('RegisterFormList');
            }
        }
        else {
            $result['status'] = 'error';
        }
        
        break;

	case "saveRegisterExhibitionForm":
		$result['status'] = 'success';

		$forms = [];
		foreach ($post->GetProperty('RegisterForm') as $name => $fields) {
			foreach ($fields as $key => $field) {
				$forms[$key][$name] = $field;
			}
		}

		if (!empty($forms)){
			$session = GetSession();
			$session->SetProperty('RegisterFormList', $forms);
			$session->SaveToDB();
		}
		break;

    case 'AddArticleLike':
        Articles::addLike($post->GetIntProperty('ItemID'));
        break;


    case 'NewShare':
        //TODO normal init
        $result['status'] = 'success';
        Share::changeCount(
            $post->GetProperty('ItemID'),
            $post->GetProperty('ItemType'),
            $post->GetProperty('ShareItem'),
            $post->GetProperty('Value')
        );
        break;

    case 'SendReadLater':
        $captchaResponse = getReCaptcha()->verify($post->GetProperty('g-recaptcha-response'), getClientIP());
        if (!$captchaResponse->isSuccess()){
            $result['errors'] = ['Name' => GetTranslation('validate-invalid-captcha')];
        }
        elseif (!$post->IsPropertySet('Terms')){
            $result['errors'] = ['Terms' => GetTranslation('validate-terms-required')];
        }
        else{
            $result['status'] = 'success';
            $articles = new Articles();
            $content = $articles->getReadLaterTemplate($post->GetIntProperty('ItemID'));

            SendMailFromAdmin($post->GetProperty('Email'), 'Прочитать позже', $content);
            ReadLaterList::add($post->GetProperty('ItemID'), $post->GetProperty('Email'), $post->GetProperty('Name'));
        }
        break;
}

echo json_encode($result);
