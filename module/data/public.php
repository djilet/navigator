<?php

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/device.php");
require_once(dirname(__FILE__) . "/include/achievement.php");
require_once(dirname(__FILE__) . "/include/Industry.php");
require_once(dirname(__FILE__) . "/include/Operation.php");
require_once(dirname(__FILE__) . "/include/WantWork.php");
require_once(dirname(__FILE__) . "/include/WhoWork.php");
require_once(dirname(__FILE__) . "/include/OpenDay.php");
require_once(dirname(__FILE__) . "/include/OpenDayList.php");
require_once(dirname(__FILE__) . "/include/OpenDaySlide.php");
require_once(dirname(__FILE__) . "/include/OpenDayPartner.php");
require_once(dirname(__FILE__) . "/include/OpenDayRegistration.php");
require_once(dirname(__FILE__) . "/include/OnlineExhibition.php");
require_once(dirname(__FILE__) . "/include/OnlineExhibitionParticipantList.php");
require_once(dirname(__FILE__) . "/include/CityList.php");
require_once(dirname(__FILE__) . "/include/public/OnlineEvents.php");
require_once(dirname(__FILE__) . "/include/public/University.php");
require_once(dirname(__FILE__) . "/include/public/Specialities.php");
require_once(dirname(__FILE__) . "/include/public/BigDirection.php");
require_once(dirname(__FILE__) . "/include/public/Direction.php");
require_once(dirname(__FILE__) . "/include/public/DirectionList.php");
require_once(dirname(__FILE__) . "/include/public/Region.php");
require_once(dirname(__FILE__) . "/include/public/Subject.php");
require_once(dirname(__FILE__) . "/include/public/PublicExhibition.php");
require_once(dirname(__FILE__) . "/include/public/Professions.php");
require_once(dirname(__FILE__) . "/include/admin/author_list.php");
require_once(dirname(__FILE__) . "/include/Articles.php");
require_once(dirname(__FILE__) . "/include/public/ListList.php");
require_once(dirname(__FILE__) . "/include/service/ListListService.php");
require_once(dirname(__FILE__) . "/../users/include/user.php");
require_once(dirname(__FILE__) . "/../question/include/message_list.php");
require_once(dirname(__FILE__) . "/include/api_v1/exhibition_list.php");
require_once(dirname(__FILE__) . "/include/api_v1/registration_list.php");
require_once(dirname(__FILE__) . "/include/api_v1/visit.php");
require_once(dirname(__FILE__) ."/../tracker/include/analytic_system/sender.php");
es_include("modulehandler.php");
es_include("urlfilter.php");
es_include("apiresponse.php");
es_include("user.php");
es_include("ChatUser.php");
es_include("service/ChatUserService.php");

use Module\Tracker\AnalyticSystem;
use morphos\Russian\Cases;
use morphos\Russian\GeographicalNamesInflection;

class DataHandler extends ModuleHandler
{
    protected $chatUserService;

    public function __construct()
    {
        parent::ModuleHandler();
        $this->chatUserService = new ChatUserService();
    }

    function ProcessPublic()
    {
        if (count($this->pathInsideModule) > 0) {
            $apiVersion = $this->pathInsideModule[0];
            if (is_dir(dirname(__FILE__) . "/controller/api_" . $apiVersion) && file_exists(dirname(__FILE__) . "/controller/api_" . $apiVersion . "/index.php")) {
                $request = new LocalObject($_REQUEST);
                $input = file_get_contents("php://input");
                if ($input !== false && strlen($input) > 0) {
                    $json = json_decode($input, true);
                    if ($json !== null) {
                        $request->AppendFromArray($json);
                    }
                }
                require_once(dirname(__FILE__) . "/controller/api_" . $apiVersion . "/index.php");
                $controller = new ApiController($this->module, array_slice($this->pathInsideModule, 1));
                $controller->ProcessRequest($request);
            } else {
                Send404();
            }
        } else {
            Send404();
        }
    }

    public function ProcessHeader($module, Page $page = null)
    {
        $data = [];
        $request = new LocalObject(array_merge($_GET, $_POST));
        $template = empty($page) ? '' : $page->GetProperty('Template');

    	if ($template == 'page-index.html') {

        	$data = $this->indexPage($module, $page, $request);

        } elseif ($template == 'page-online-events.html' || $template == 'page-open-day-events.html') {

        	$data = $this->onlineEventPage($module, $page, $request);

        } elseif ($template == 'page-university.html') {

        	$data = $this->universitiesPage($module, $page, $request);

        } elseif (in_array($template, ['page-exhibition.html', 'page-exhibition2.html', 'page-exhibition3.html'])) {

            $data = $this->exhibitionPage($module, $page, $request, "old");

        } elseif (in_array($template, ['page-exhibition4.html', 'page-exhibition4_online.html', 'page-exhibition5_online.html'])) {

            $data = $this->exhibitionPage($module, $page, $request, "oldWithGroupingByDate");

        } elseif ($template == 'page-online-exhibition.html') {

            $data = $this->onlineExhibitionPage($module, $page, $request, "old");

        } elseif ($template == 'page-open-day.html') {

            $data = $this->openDayPage($module, $page, $request, "old");

        }elseif ($template == 'page-exhibition-landing.html') {
            $data = $this->exhibitionPage($module, $page, $request, "landing");

        } elseif ($template == 'page-profession.html') {

            $data = $this->professionPage($module, $page, $request);

        } elseif ($template == 'page-article.html') {

            $data = $this->articlePage($module, $page, $request);

        }

    	//common
        $urlParser = GetURLParser();
        $urlParser->GetSubDomain();

        //City list
        //todo translate
        $data['HeaderCityList'] = [[
                'StaticPath' => '',
                'Name' => 'Все города',
                'Link' => URLParser::getPrefixWithSubDomain('') . $urlParser->GetRequestURI()
            ],];
        foreach (CityList::getAll(null, 0)->getListForTemplate() as $index => $city){
            if ($city['StaticPath'] == $urlParser->GetSubDomain()){
                $city['Selected'] = true;
                $data['HeaderCityTitleInRodCase'] = GeographicalNamesInflection::getCase($city['Name'], Cases::RODIT);
            }
            $city['Link'] = URLParser::getPrefixWithSubDomain($city['StaticPath']) . $urlParser->GetRequestURI();
            $data['HeaderCityList'][] = $city;
        }

        return $data;
    }

    public function ProcessApi($module, $method, $chunks, $request)
    {
        $response = new ApiResponse();
        if(count($chunks) > 1 && $chunks[1] == "exhibitionlist" && $method == "GET")
        {
            $user = new User();
            if($user->ValidateUserRequest($request, array(API_SCANNER, API_SCANNER_FULL)))
            {
                $exhibitionList = new DataExhibitionList($this->module);
                $exhibitionList->load();
                $response->SetStatus("success");
                $response->SetCode("200");
                $response->SetData($exhibitionList->getItems());
            }
            else
            {
                $response->SetStatus("error");
                $response->SetCode(403);
            }
        }
        else if(count($chunks) > 1 && $chunks[1] == "registrationlist" && $method == "GET")
        {
            $user = new User();
            if($user->ValidateUserRequest($request, array(API_SCANNER, API_SCANNER_FULL)))
            {
                $registrationList = new DataRegistrationList($this->module);
                $registrationList->load();
                $response->SetStatus("success");
                $response->SetCode("200");
                $response->SetData($registrationList->getItems());
            }
            else
            {
                $response->SetStatus("error");
                $response->SetCode(403);
            }
        }
        else if(count($chunks) > 1 && $chunks[1] == "visit" && $method == "POST")
        {
            $user = new User();
            if($user->ValidateUserRequest($request, array(API_SCANNER, API_SCANNER_FULL)))
            {
                $visit = new DataVisit($this->module);
                if($visit->addAllFromRequest($request)){
                    $response->SetStatus("success");
                    $response->SetCode("200");
                }
                else {
                    $response->SetStatus("error");
                    $response->SetCode(400);
                }
            }
            else
            {
                $response->SetStatus("error");
                $response->SetCode(403);
            }
        }
        else if(count($chunks) > 1 && $chunks[1] == "profession" && $method == "GET")
        {
            $device = new DataDevice($module);
            if($device->CheckRequestSign($request) || GetFromConfig("DevMode"))
            {
                if(count($chunks) > 2 && intval($chunks[2]) > 0)
                {
                    $professionID = intval($chunks[2]);
                    if($professionID > 0){
                        $professions = new Professions($module);
                        $info = $professions->getItemInfo($professionID);
                        $response->SetStatus("success");
                        $response->SetCode("200");
                        $response->SetData($info);
                    }
                    else {
                        $response->SetStatus("error");
                        $response->SetCode(404);
                        $response->AddError("api-request-id-incorrect", $module);
                    }
                }
                else
                {
                    $professions = new Professions($module);
                    $professionFilter = $request->GetProperty("ProfessionFilter");
                    if($professionFilter){
                        foreach($professionFilter as $key=>$value){
                            $professionFilter[$key] = json_decode($professionFilter[$key], true);
                        }
                        $request->SetProperty("ProfessionFilter", $professionFilter);
                    }
                    $professions->load($request, 0);
                    $response->SetStatus("success");
                    $response->SetCode("200");
                    $response->SetData($professions->GetItems());
                }
            }
            else
            {
                $response->SetStatus("error");
                $response->SetCode(400);
                $response->AddError("api-request-sign-incorrect", $module);
            }
        }
        else if(count($chunks) > 1 && $chunks[1] == "professionfilter" && $method == "GET"){
            $data = array();

            $industry = new Industry();
            $industry->load();
            $data['IndustryList'] = $industry->getItems([]);

            $whoWork = new WhoWork();
            $data['WhoWorkList'] = $whoWork->getForFilter(3);

            $wantWork = new WantWork();
            $data['WantWorkList'] = $wantWork->getForFilter(3);

            $professions = new Professions($module);
            $data['WageLevelList'] = $professions->getWageLevel([]);
            $data['ScheduleList'] = $professions->getScheduleList([]);

            $operation = new Operation();
            $operation->load();
            $data['OperationList'] = $operation->getItems([]);

            $response->SetStatus("success");
            $response->SetCode("200");
            $response->SetData($data);
        }
        else
        {
            $response->SetStatus("error");
            $response->SetCode(404);
        }
        $response->Output();
        return true;
    }

    /**
     *  Главная страница
     *
     * @param              $module
     * @param \Page        $page
     * @param \LocalObject $request
     *
     * @return array
     */
    protected function indexPage($module, Page $page, LocalObject $request)
    {
    	$data = [];
        $urlParser = new URLParser();
        $currentCityPath = $urlParser->GetSubDomain();
        $city = null;
        $cityId = null;

        if ($currentCityPath){
            $city = City::getByStaticPath($currentCityPath);
            $cityId = $city->GetProperty('ID');
            $cityTitleInPredlojCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::PREDLOJ);
            $cityTitleInRodCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::RODIT);
            $regionTitleInPredlojCase = GeographicalNamesInflection::getCase($city->GetProperty('RegionTitle'), Cases::PREDLOJ);
            $data['CityTitleInRodCase'] = $cityTitleInRodCase;
            $data['CityTitleInPredlojCase'] = $cityTitleInRodCase;
            $data['MetaTitle'] = "Высшее образование в {$cityTitleInPredlojCase}: специальности, программы обучения, списки вузов и колледжей на портале Навигатор поступления в {$regionTitleInPredlojCase}";
        }

    	$university = new University('data');
    	$specialities = new Specialities('data');
    	$bigDirection = new BigDirection('data');
    	$listList = new ListList();
    	$professions = new Professions("data");
    	$onlineEvents = new OnlineEvents("data");
    	$articles = new Articles("data");

    	$data["CountUniversities"] = $university->count($cityId);
    	$data["CountSpecialities"] = $specialities->count($cityId);
    	$data["CountProfesssions"] = $professions->count();

    	$request->SetProperty('BaseURL', PROJECT_PATH . 'events');
    	$onlineEvents->load(3, 1, $request);
    	$data['OnlineEventList'] = $onlineEvents->GetItems();

    	$request->SetProperty('BaseURL', URLParser::getPrefixWithSubDomain('') . '/article');

    	$articles->load($request, 4);
        $articleList = $articles->GetItems();

        $data['ArticleList'] = $articleList;

        //article block
        unset($articleList[0]);

        $blockList = [
            ['ArticleList' => $articleList]
        ];
        $request->SetProperty('ArticleFilter', ['OnMain' => true]);
        $articles->load($request, 20);

        $count = 0;
        $blockIndex = 1;
        foreach ($articles->GetItems() as $article){
            if ($count >= 3){
                $blockIndex++;
                $count = 0;
            }

            $blockList[$blockIndex]['ArticleList'][] = $article;
            $count++;
        }

        $data['OnMainArticleBlockList'] = $blockList;

        if ($city){
            $universityBaseUrl = PROJECT_PATH . DATA_UNIVERSITY_PAGE;

            //universities
            $university->load(new LocalObject([
                'BaseURL' => $universityBaseUrl,
                'UniverFilter' => [
                    'CityID' =>$cityId
                ]
            ]), 5);

            $data['UniversityList'] = $university->GetItems();

            //directions
            $bigDirection->loadWithStatistic(['CityIDs' => [$cityId]]);
            $data['DirectionList'] = $bigDirection->getItems();

            $directionList = DirectionList::getAll(['CityIDs' => [$cityId]], 0);
            $data['DirectionsCountTitle'] = morphos\Russian\pluralize($directionList->GetCountItems(), 'направление');
            $data['SpecialitiesCountTitle'] = morphos\Russian\pluralize($data["CountSpecialities"], 'специальность');

            //lists
            $listList->loadForUniversityList($universityBaseUrl, null);
            $data['ListList'] = ListListService::filterByCity($listList, $city);
        }

    	return $data;
    }

    /**
     *  Онлайн события
     *
     * @param              $module
     * @param \Page        $page
     * @param \LocalObject $request
     *
     * @return array
     */
    protected function onlineEventPage($module, Page $page, LocalObject $request)
    {
        $data = [];
        $onlineEvents = new OnlineEvents("data");
        $urlParser = new URLParser();

        $city = null;
        $currentCityPath = $urlParser->GetSubDomain();
        if ($currentCityPath){
            if ($city = City::getByStaticPath($currentCityPath)){
                $data['CurrentCityID'] = $city->GetIntProperty('ID');
                $cityTitleInRodCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::RODIT);
            }
        }

        //redirect to StaticPath
        if($request->IsPropertySet('OnlineEventID') && !$request->IsPropertySet('SignOnlineEvent') && !$request->IsPropertySet('ShowRecord'))
        {
        	$path = $onlineEvents->getStaticPathByID($request->GetProperty('OnlineEventID'));
        	if($path)
        	{
        		$this->Send302UTM(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$request->GetProperty('OnlineEventID').'-'.$path.HTML_EXTENSION);
        	}
        }
        //load ID by StaticPath
        $urlParser =& GetURLParser();
        if(count($urlParser->fixedPath) > 1)
        {
        	$path = $urlParser->fixedPath[1];
        	$pos = strpos($path, '-');
        	if($pos === false)
        	{
        		if(intval($path) > 0)
        		{
        			$path2 = $onlineEvents->getStaticPathByID(intval($path));
        			if($path2)
        			{
        				$this->Send302UTM(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.intval($path).'-'.$path2.HTML_EXTENSION);
        			}
        		}
        		else
        		{
        			$id = $onlineEvents->getIDByStaticPath($path);
        			if($id)
        			{
        				$this->Send302UTM(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$id.'-'.$path.HTML_EXTENSION);
        			}
        		}
        	}
        	else
        	{
        		$prefix = substr($path, 0, $pos);
        		if(intval($prefix) > 0)
        		{
        			$onlineEventID = intval($prefix);
        		}
        		else
        		{
        			$id = $onlineEvents->getIDByStaticPath($path);
        			if($id)
        			{
        				$this->Send302UTM(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$id.'-'.$path.HTML_EXTENSION);
        			}
        		}
        	}
        	if($onlineEventID)
        	{
        		$request->SetProperty('OnlineEventID', $onlineEventID);
        	}
        	else
        	{
        		Send404();
        	}
        }

        $request->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));

        if ($eventID = $request->GetIntProperty('OnlineEventID')) {
            $showRecordUrl = PROJECT_PATH.$page->GetProperty('StaticPath')."?ShowRecord=".$eventID;
        	$user = new UserItem('user');
        	$user->loadBySession();
        	if ($user->IsPropertySet('UserID')) {
        		if ($request->IsPropertySet('SignOnlineEvent')) {
        			$onlineEvents->signUser($eventID, $user->GetProperty('UserID'));
        		}
        		//redirect for signed in users
        		if($onlineEvents->checkSigned($eventID, $user->GetProperty('UserID')))
        		{
        			Send302($showRecordUrl);
        		}
        	}

            if (!$event = $onlineEvents->getByID($eventID)) {
                Send404();
            }

            if ($event['RegistrationRequired'] !== 'Y'){
                Send302($showRecordUrl);
            }

            $data['MetaTitle'] = "Вебинар " . $event['Title'];
            $data['OnlineEventID'] = [$event];
            $data['Title'] = '';
            $data['TitleH1'] = '';
            $data['HeaderLogotype'] = $event['Template'];
            $data['RegistrationType'] = $event['RegistrationType'];

        } elseif ($eventID = $request->GetIntProperty('ShowRecord')) {

            if (!$event = $onlineEvents->getByID($eventID, true)) {
                Send404();
            }

            $user = new UserItem(null);
            $user->loadBySession();
            $event['RegistrationIsRequired'] = $event['RegistrationRequired'] === 'Y';
            if (!$user->GetIntProperty('UserID') && $event['RegistrationIsRequired']) {
                //redirect for not logged in users
        		Send302(PROJECT_PATH.$page->GetProperty('StaticPath')."?OnlineEventID=".$eventID);
            }

            if(isset($event['inProgress']) || isset($event['isFinished'])){
                if (!$user->ValidateNotEmpty('UserID') && $request->IsPropertySet('UserID')) {
                    $userId = $request->GetIntProperty('UserID');
                } else {
                    $userId = $user->GetIntProperty('UserID');
                }
                if ($userId > 0) {
                    //mark as watched
                    $onlineEvents->setWatchedUser($eventID, $userId);
                }
            }

            //for chat initialization
            if ($user->ValidateNotEmpty('UserID')){
                $chatUser = ChatUser::getByUserID($user->GetIntProperty('UserID'));
                if (!$chatUser){
                    $chatUser = $this->chatUserService->updateOrCreateByConnection(
                        ChatUser::CONNECTION_TYPE_USER,
                        $user->GetIntProperty('UserID'),
                        $user->GetProperty('UserName'),
                        $user->GetProperty('ChatStatus')
                    );
                }
            }
            else{
                $session = GetSession();
                if ($session->getId()){
                    $chatUser = ChatUser::getBySessionID($session->getId());
                }
            }

            if ($chatUser){
                $event['ChatUserID'] = $chatUser->ID;
                $event['UserName'] = $chatUser->UserName;
                $event['ChatStatus'] = $chatUser->ChatStatus;
            }
            $event['ChatURL'] = GetFromConfig('ChatURL', 'chat');

            $data['ShowRecordInfo'] = [$event];
            $data['MetaTitle'] = $event['Title'];
            $data['TitleH1'] = $event['Title'];
            $data['HeaderLogotype'] = $event['Template'];
        } else {
            $openDayFilter = ['Active' => 'Y'];
            if($city){
                $openDayFilter['CityIDs'] = [$city->GetProperty('ID')];
            }
            if ($request->IsPropertySet('archive')) {
                $onlineEvents->loadArchive($request);
                $openDayList = OpenDayList::getAll(array_merge(
                    $openDayFilter,
                    ['DateLt' => GetCurrentDateTime()]
                ), ['Date DESC']);
                $data['MetaTitle'] = $page->GetProperty('Template') == 'page-open-day-events.html' ? 'Архив додов' : 'Архив вебинаров';
                $data['Archive'] = 1;
            } else {
                $onlineEvents->load(0, 0, $request);
                $openDayList = OpenDayList::getAll(array_merge(
                    $openDayFilter,
                    ['DateGte' => GetCurrentDateTime()]
                ));

                $data['MetaTitle'] = $page->GetProperty('MetaTitle');

                if (isset($cityTitleInRodCase)){
                    $data['MetaTitle'] = "Дни открытых дверей в вузах {$cityTitleInRodCase}";
                }
            }

            $data['OnlineEventList'] = $onlineEvents->GetItems();

            $openDayList->prepareForEventList();
            $data['OpenDayList'] = $openDayList->GetItems();

            //Selected type
            $data['SelectedEventType'] = 'webinar';
            if ($urlParser->fixedPath[0] === DATA_OPEN_DAY_ONLINE_EVENT_TYPE_PATH){
                $data['SelectedEventType'] = 'openday';
            }
        }

        $onlineEvents->loadFirstEvent(3, $request);
        $data['FirstOnlineEventList'] = $onlineEvents->GetItems();

        return $data;
    }

    /**
     * Вузы
     *
     * @param              $module
     * @param \Page        $page
     * @param \LocalObject $request
     *
     * @return array|bool|null
     */
    protected function universitiesPage($module, Page $page, LocalObject $request)
    {
        $data = [];
        $university = new University('data');
        $specialities = new Specialities('data');
        $listList = new ListList();

        //redirect to StaticPath
        if($request->IsPropertySet('universityID'))
        {
        	$staticPath = $university->getStaticPathByID($request->GetProperty('universityID'));
        	if($staticPath)
        	{
        	    Send301(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$staticPath.'/');
        	}
        }
        else if($request->IsPropertySet('specialityID'))
        {
        	$staticPath = $specialities->getStaticPathByID($request->GetProperty('specialityID'));
        	if($staticPath)
        	{
        		Send301(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$staticPath.'/');
        	}
        }
        //load ID by StaticPath
        $urlParser =& GetURLParser();
        if(count($urlParser->fixedPath) > 1)
        {
        	if(count($urlParser->fixedPath) > 2)
        	{
        	    //temp redirect from DATA_SPECIALITIES_PAGE to DATA_UNIVERSITY_PAGE
                if ($urlParser->fixedPath[0] == DATA_SPECIALITIES_PAGE){
                    Send301(GetUrlPrefix() .
                        DATA_UNIVERSITY_PAGE . '/' .
                        $urlParser->fixedPath[1] . '/' .
                        $urlParser->fixedPath[2] . '/' .
                        $urlParser->fixedPath[3]);
                }
                //temp redirect end

        	    //check section or speciality
                if (in_array($urlParser->fixedPath[2], DATA_UNIVERSITY_PAGES)){
                    $request->SetProperty('universityID', $university::getIDByStaticPath($urlParser->fixedPath[1]));
                    $request->SetProperty('Section', $urlParser->fixedPath[2]);
                }
                else{
                    $specialityInfo = $specialities->getIDByStaticPath($urlParser->fixedPath[1], $urlParser->fixedPath[2]);
                    if($specialityInfo)
                    {
                        $request->SetProperty('univerID', $specialityInfo["UniversityID"]);
                        $request->SetProperty('specialityID', $specialityInfo["SpecialityID"]);
                    }
                    else
                    {
                        Send404();
                    }
                }
        	}
        	elseif($urlParser->fixedPath[0] != DATA_SPECIALITIES_PAGE)
        	{
        		$universityID = $university::getIDByStaticPath($urlParser->fixedPath[1]);
        		if($universityID)
        		{
        			$request->SetProperty('universityID', $universityID);
        		}
        		else
        		{
        			$listID = $listList->getIDByStaticPath($urlParser->fixedPath[1]);
        			if($listID)
        			{
        				$request->SetProperty('ListID', $listID);
        			}
        			else
        			{
        				Send404();
        			}
        		}
        	}
        }

        $request->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));
        $city = null;
        $currentCityPath = $urlParser->GetSubDomain();
        if ($currentCityPath){
            if ($city = City::getByStaticPath($currentCityPath)){
                $data['CurrentCityID'] = $city->GetIntProperty('ID');
                $cityTitleInRodCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::RODIT);
                $cityTitleInPredlojCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::PREDLOJ);
            }
        }

        $customNavigation= [
            [
                'Title' => 'Главная',
                'PageURL' => '/',
            ],
            [
                'Title' => isset($cityTitleInRodCase) ? "Вузы {$cityTitleInRodCase}" : $page->GetProperty('Title'),
                'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
            ],
        ];

        if ($request->IsPropertySet('universityID')) {

            if ($request->IsPropertySet('BecomeAnEntrant')) {
                $user = new UserItem('user');
                $user->loadBySession();
                if ($user->IsPropertySet('UserID')) {
                    if ($request->GetProperty('BecomeAnEntrant') == 'Y') {
                        $university->becomeAnEntrant(
                            $user->GetIntProperty('UserID'),
                            $request->GetIntProperty('universityID'),
                        	null,
                            true
                        );
                    } else {
                        $university->becomeAnEntrant(
                            $user->GetIntProperty('UserID'),
                            $request->GetIntProperty('universityID'),
                        	null,
                            false
                        );
                    }
                }
            }

            $data = $university->getByID($request->GetIntProperty('universityID'), $request->GetProperty('BaseURL'));

            //redirect to subdomain
            if (!empty($data['CityPath']) && $currentCityPath != $data['CityPath']){
                Send301(URLParser::getPrefixWithSubDomain($data['CityPath']) . $urlParser->GetFullPathAsString());
            }

            $data['UniversityItemView'] = 1;
            $data['Section'] = $request->IsPropertySet('Section') ? $request->GetProperty('Section') : 'main';

            //online evens
            $onlineEvents = new OnlineEvents("data");
            $request->SetProperty('OrderDesc', 1);
            $onlineEvents->load(1, 1, $request);
            $data['OnlineEventList'] = $onlineEvents->GetItems();

            //open day
            $openDayList = OpenDayList::getAll([
                'UniversityID' => $request->GetIntProperty('universityID'),
                'Active' => 'Y',
            ]);
            $openDayList->prepareForEventList();
            $data['OpenDayList'] = $openDayList->GetItems();

            //questions
            $questionMessageList = new QuestionMessageList("question");
            $questionMessageList->load(new LocalObject(array(
                "Type" => "university",
                "AttachID" => $request->GetIntProperty('universityID'),
                "Status" => "public"
            )));
            $data['QuestionMessageList'] = $questionMessageList->GetItems();
            $url = PROJECT_PATH . $page->GetProperty('StaticPath');
            $data['QuestionPager'] = $questionMessageList->GetPagingAsArray($url, $url);

            $data['QuestionMessageShow'] = 1;
            if(!isset($data['QuestionUserID']) && GetFromConfig('DefaultUserID', 'question'))
            {
                $data['QuestionUserID'] = GetFromConfig('DefaultUserID', 'question');
                $data['QuestionUserTitle'] = "Эксперт портала Навигатор Поступления";
            }
            if($data['QuestionUserID'])
            {
                $user = new UserItem('user');
                if($user->loadByID($data['QuestionUserID']))
                {
                    $data['QuestionUserName'] = $user->GetProperty("UserName");
                    if($user->GetProperty("UserImage"))
                    {
                        $data['QuestionUserImage'] = PROJECT_PATH."images/navigator-user-56x56_8/".$user->GetProperty("UserImage");
                    }
                }
            }

            //other universities
            $university->loadRandom($request);
            $data['OtherUniversityList'] = $university->GetItems();

            $data['TabsCount'] = 2;
            if(count($data['OpenDayList']) > 0) $data['TabsCount']++;

            $customNavigation[] = [
                'Title' => !empty($data['ShortTitle']) ? $data['ShortTitle'] : $data['Title'],
                'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/' . $data['StaticPath'],
            ];

            switch ($request->GetProperty('Section')){
                case DATA_UNIVERSITY_PAGE_CONTACTS:
                    $data['MetaTitle'] = "{$data['Title']} - адрес на карте, сайт, телефон приемной комиссии {$data['ShortTitle']}";
                    $data['TitleH1'] = "Где находится {$data['ShortTitle']} - адрес, контакты приемной комиссии";
                    $customNavigation[] = [
                        'Title' => 'Контакты',
                    ];
                    break;

                case DATA_UNIVERSITY_PAGE_SPECIALITIES:
                    $specialities->load($request, 0);
                    $data['SpecialityList'] = $specialities->GetItems();

                    $data['TitleH1'] = "Факультеты, направления подготовки и проходные баллы в {$data['ShortTitle']}";
                    $data['MetaTitle'] = "{$data['ShortTitle']} - проходные баллы 2019-2020 года, стоимость обучения, направления подготовки в {$data['ShortTitle']}";
                    $customNavigation[] = [
                        'Title' => 'Направления подготовки',
                    ];
                    break;

                default:
                    $year = date('Y');
                    $data['TitleH1'] = $data['Title'];
                    $data['MetaTitle'] = $data['ShortTitle'] . " {$year} - " . $data['Title'];
                    $data['MetaDescription'] = "Как поступить в ".$data['Title'].". Информация для абитуриентов: направления, специальности, факультеты. Проходной балл, конкурс и льготы.";
            }

            $data['CustomNavigation'] = $customNavigation;

        }
        elseif ($request->IsPropertySet('specialityID')) {
            if ($request->IsPropertySet('BecomeAnEntrant') and $request->IsPropertySet('univerID')) {
                $user = new UserItem('user');
                $user->loadBySession();
                if ($user->IsPropertySet('UserID')) {
                    if ($request->GetProperty('BecomeAnEntrant') == 'Y') {
                        $university->becomeAnEntrant(
                            $user->GetIntProperty('UserID'),
                            $request->GetIntProperty('univerID'),
                        	$request->GetIntProperty('specialityID'),
                            true
                        );
                    } else {
                        $university->becomeAnEntrant(
                            $user->GetIntProperty('UserID'),
                            $request->GetIntProperty('univerID'),
                        	$request->GetIntProperty('specialityID'),
                            false
                        );
                    }
                }
            }

            $data = $specialities->getByID($request->GetIntProperty('specialityID'), $request->GetProperty('BaseURL'), $request->GetIntProperty('StudyYear'));
            $data['SpecialityItemView'] = 1;

            if ($univer = $university->getByID($data['UniversityID'], $request->GetProperty('BaseURL'))) {
            	$data['UniversityTitle'] = $univer['Title'];
            	$data['UniversityTitleInPrepositionalCase'] = $univer['TitleInPrepositionalCase'];
                $data['UniversityShortTitle'] = $univer['ShortTitle'];
                $data['UniversityURL'] = $univer['UniversityURL'];
                $data['Address'] = $univer['Address'];
                $data['Latitude'] = $univer['Latitude'];
                $data['Longitude'] = $univer['Longitude'];
                $data['PhoneSelectionCommittee'] = $univer['PhoneSelectionCommittee'];
                $data['Website'] = $univer['Website'];
                if(isset($univer['ImagesList'])){
                	$data['ImagesList'] = $univer['ImagesList'];
                }
                $data['QuestionMessageShow'] = 1;

                //redirect to subdomain
                if (!empty($univer['CityPath']) && $currentCityPath != $univer['CityPath']){
                    Send301(URLParser::getPrefixWithSubDomain($univer['CityPath']) . $urlParser->GetFullPathAsString());
                }
            }

            $specialities->loadByUniversityID($request, $data['UniversityID'], $data['SpecialityID']);
            $data['OtherSpecialities'] = $specialities->GetItems();

            $professions = new Professions("data");
            $request->SetProperty('BaseURL', PROJECT_PATH . 'profession');
            $professions->load($request);
            $data['ProfessionList'] = $professions->GetItems();
            $url = $data['SpecialityURL'];
            $data['ProfessionListPager'] = $professions->GetPagingAsHTML($url, $url, '#tab-2');

            $request->SetProperty("universityID", $request->GetIntProperty('univerID'));
            $onlineEvents = new OnlineEvents("data");
            $onlineEvents->load(1, 1, $request);
            $data['OnlineEventList'] = $onlineEvents->GetItems();

            $data['TabsCount'] = 1;
            if(count($data['ProfessionList']) > 0) $data['TabsCount']++;

			$questionMessageList = new QuestionMessageList("question");
			$questionMessageList->load(new LocalObject(array(
				"Type" => "speciality",
				"AttachID" => $request->GetIntProperty('specialityID'),
				"Status" => "public"
			)));
			$data['QuestionMessageList'] = $questionMessageList->GetItems();
			$url = PROJECT_PATH . $page->GetProperty('StaticPath');
			$data['QuestionPager'] = $questionMessageList->GetPagingAsArray($url, $url);

			$data['QuestionMessageShow'] = 1;
			if(!isset($data['QuestionUserID']) && GetFromConfig('DefaultUserID', 'question'))
			{
				$data['QuestionUserID'] = GetFromConfig('DefaultUserID', 'question');
				$data['QuestionUserTitle'] = "Эксперт портала Навигатор Поступления";
			}
			if($data['QuestionUserID'])
			{
				$user = new UserItem('user');
				if($user->loadByID($data['QuestionUserID']))
				{
					$data['QuestionUserName'] = $user->GetProperty("UserName");
					if($user->GetProperty("UserImage"))
					{
						$data['QuestionUserImage'] = PROJECT_PATH."images/navigator-user-56x56_8/".$user->GetProperty("UserImage");
					}
				}
			}

            $data['Navigation'] = [
                [
                    'Title' => isset($cityTitleInRodCase) ? "Вузы {$cityTitleInRodCase}" : $page->GetProperty('Title'),
                    'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
                ],
                [
                    'Title'   => $data['UniversityShortTitle'],
                    'PageURL' => $univer['UniversityURL'],
                ]
            ];

            $data['MetaTitle'] = "Специальность ".$data['Title']." в ".$data['UniversityTitleInPrepositionalCase'];
            $data['MetaDescription'] = "Информация о специальности ".$data['Title']." в ".$data['UniversityTitle'].": количество бюджетных мест, срок и стоимость обучения, экзамены.";

        }
        elseif ($urlParser->fixedPath[0] == DATA_SPECIALITIES_PAGE){
            $urlFilter = new URLFilter();
            $staticPath = $urlParser->fixedPath[1];
            if ($request->ValidateNotEmpty('DirectionID')){
                $directionData = Direction::getData($request->GetIntProperty('DirectionID'));
                if ($directionData){
                    Send301(GetUrlPrefix() . DATA_SPECIALITIES_PAGE . "/{$directionData['StaticPath']}");
                    exit();
                }
            }

            if (!empty($staticPath)){
                $directionData = Direction::getByStaticPath($staticPath);
                if (!$directionData){
                    //temp redirect from DATA_SPECIALITIES_PAGE to DATA_UNIVERSITY_PAGE
                    if ($universityId = University::getIDByStaticPath($staticPath)){
                        Send301(GetUrlPrefix() . DATA_UNIVERSITY_PAGE . "?universityID={$universityId}");
                    }
                    //temp redirect end

                    Send404();
                }

                $filter = null;
                $data['DirectionView'] = true;
                $data = array_merge($data, $directionData);
                $data['MetaTitle'] = "Специальность {$directionData['Title']}";
                $data['MetaDescription'] = "Специальность {$directionData['Title']}, список и описание специальностей, на которые можно поступить";

                $data['Navigation'] = [
                    [
                        'Title'   => $data['UniversityTitle'],
                        'PageURL' => $page->GetPageURL(false),
                    ],
                ];

                $filter = [
                    'Direction' => $directionData['DirectionID'],
                ];

                if (isset($city)){
                    $filter['CityIDs'] = [$city->GetIntProperty('ID')];
                    $data['MetaTitle'] = "Специальность {$directionData['Title']} в вузах {$cityTitleInRodCase}";
                    $data['MetaDescription'] = "Специальность {$directionData['Title']} в вузах {$cityTitleInRodCase}, список и описание специальностей, на которые можно поступить в {$cityTitleInPredlojCase}";
                }

                $specialities->load(new LocalObject([
                    'BaseURL' => PROJECT_PATH . DATA_UNIVERSITY_PAGE,
                    'SpecialFilter' => $filter
                ]));
                $specialities->prepare();
                $data['SpecialityList'] = $specialities->GetItems();

                if(isset($city) && empty($data['SpecialityList'])){
                    Send404();
                }

                $url = PROJECT_PATH . $page->GetProperty('StaticPath') . "/{$directionData['StaticPath']}";
                $data['SpecialityListPager'] = $specialities->GetPagingAsHTML($url, $url);

                $data['TitleH1'] = $data['MetaTitle'];
            }
            else{
                $filter = null;
                $data['DirectionListView'] = true;
                $data['MetaTitle'] = "Специальности вузов и направления подготовки бакалавриата";
                $data['TitleH1'] = "Специальности вузов - направления подготовки";

                if (isset($city)){
                    $filter['CityIDs'] = [$city->GetIntProperty('ID')];
                    $data['MetaTitle'] = "Специальности вузов {$cityTitleInRodCase}, направления подготовки бакалавриата";
                    $data['TitleH1'] = "Специальности вузов - направления подготовки в {$cityTitleInPredlojCase}";
                }

                $directionList = DirectionList::getAll($filter);
                $data['DirectionList'] = $directionList->GetItems();

                $url = PROJECT_PATH . $page->GetProperty('StaticPath') . '?' . $urlFilter->GetForURL();
                $data['ProfessionPager'] = $directionList->GetPagingAsHTML($url, $url);
            }

            $data['BaseURL'] = PROJECT_PATH . DATA_SPECIALITIES_PAGE . '/';
        }
        else {
            if (isset($city)){
                $data['MetaTitle'] = "Вузы {$cityTitleInRodCase}, поступление и средний балл ЕГЭ по вузам {$cityTitleInRodCase} в 2020 году";
                $data['TitleH1'] = "Вузы {$cityTitleInRodCase} - университеты и институты";
            }

            $data['Navigation'] = [
                [
                    'Title' => isset($cityTitleInRodCase) ? "Вузы {$cityTitleInRodCase}" : $data['Title'],
                    'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
                ],
            ];
        	$listList->loadForUniversityList(PROJECT_PATH . DATA_UNIVERSITY_PAGE, $request->GetIntProperty("ListID"));
        	//filter lists
            if ($city){
                $listList->_items = ListListService::filterByCity($listList, $city);
            }

        	$data['ListList'] = $listList->GetItems();
        	if($request->IsPropertySet("ListID")){
        	    $listID = $request->GetIntProperty("ListID");
        		$listInfo = $listList->getInfo($listID);
        		if($listInfo){
        			$data['Description'] = $listInfo["Description"];
        			$data['MetaTitle'] = $listInfo["MetaTitle"];
        			$data['MetaDescription'] = $listInfo["MetaDescription"];

                    if ($city){
                        $data['MetaTitle'] = "{$listInfo["Title"]} {$cityTitleInRodCase}, в которые можно поступить в 2020 году";
                        $data['TitleH1'] = "{$listInfo["Title"]} {$cityTitleInRodCase}";
                        $data['Description'] = $data['MetaTitle'];
                    }

        			if($listInfo['Type'] == "filter"){
                        $filter = $listList->getFilterArray($listID);
                        if ($city){
                            $filter['CityID'] = $city->GetIntProperty('ID');
                        }

                        $university->load(new LocalObject(['UniverFilter' => $filter]), 1, false);
                        if ($university->GetCountItems() < 1){
                            Send404();
                        }

        				$request->RemoveProperty("ListID");
        				$request->SetProperty("UniverFilter", $listList->getFilterArray($listID));
        			}
        			else if($listInfo['Type'] == "manual"){
        				$data['ListID'] = $listID;
        			}

        			$data['Navigation'][] = [
                        'Title' => $city ? $data['TitleH1'] : $listInfo["Title"],
                        'StaticPath' => '',
                    ];
        		}
        	}
        	
        	$univerFilter = $request->GetProperty("UniverFilter");
        	if(!isset($univerFilter)) {
        	    $univerFilter = array();
        	}
        	$subject = new Subject();
        	$subject->load();
        	
        	//add filters from GET parameters
        	foreach(array('Region', 'BigDirection', 'Profession', 'Text', 'Military', 'Delay', 'Hostel', 'AdditionalExam') as $param){
        	    if($request->IsPropertySet($param)){
        	        $univerFilter[$param] = $request->GetProperty($param);
        	    }
        	}
        	if($request->IsPropertySet('Subject')){
        	    $selectedParams = $request->GetProperty('Subject');
        	    $subjectParam = array();
        	    foreach($subject->GetItems() as $sbj){
        	        $subjectParam[$sbj['SubjectID']] = 0;
        	        if(isset($selectedParams[$sbj['SubjectID']])){
        	            $subjectParam[$sbj['SubjectID']] = $selectedParams[$sbj['SubjectID']];
        	        }
        	    }
        	    $univerFilter['Subject'] = $subjectParam;
        	}

        	if ($city){
                $univerFilter['CityID'] = $city->GetIntProperty('ID');
            }

        	$request->SetProperty('UniverFilter', $univerFilter);
        	$request->SetProperty('SpecialitiesOrder', 'Title ASC');

            $university->load($request);
            $data['Page'] = $university->GetCurrentPage();
            $data['UniversityList'] = $university->GetItems();
            $url = PROJECT_PATH . $page->GetProperty('StaticPath') . '/';
            $data['UniversityPager'] = $university->GetPagingAsArray($url, $url);

            $bigDirections = new BigDirection();
            $bigDirections->load();
            $data['UniverBigDirectionList'] = $bigDirections->getItems(
            		isset($univerFilter['BigDirection']) ? $univerFilter['BigDirection'] : []
            );
            
            $region = new Region();
            $region->load();
            $regions = $region->getItems(
                isset($univerFilter['Region']) ? $univerFilter['Region'] : []
            );

            $regionTitles = array();
            for ($i = 0; $i < count($regions); $i++) {
                $regions[$i]['Title'] = str_replace('г. ', '', $regions[$i]['Title']);
                $regionTitles[] = $regions[$i]['Title'];
            }

            array_multisort($regionTitles, SORT_ASC, $regions);

            $data['UniverRegionList'] = $regions;

            $profession = new Professions($module);
            $profession->load(new LocalObject(), 0);
            $data['UniverProfessionList'] = $profession->getItems(
            		isset($univerFilter['Profession']) ? $univerFilter['Profession'] : []
            );

            $achievement = new Achievement($module);
            $achievement->loadList();
            $data['AchievementList'] = $achievement->getItems(
            		isset($univerFilter['Achievement']) ? $univerFilter['Achievement'] : []
            );

            $data['StudTypeList'] = SpecialityStudy::getTypes(true,
            		isset($univerFilter['StudyType']) ? $univerFilter['StudyType'] : []
            );
            
            $data['SubjectList'] = $subject->GetItems(array(), array(), isset($univerFilter['Subject']) ? $univerFilter['Subject'] : []);
            $data['Military'] = $request->GetProperty('Military');
            $data['Delay'] = $request->GetProperty('Delay');
            $data['Hostel'] = $request->GetProperty('Hostel');
            $data['AdditionalExam'] = $request->GetProperty('AdditionalExam');

            //$data['MaxWidth'] = 720;//TODO: remove it for adaptive page
        }

        return $data;
    }

    /**
     * Выставка
     *
     * @param              $module
     * @param \Page        $page
     * @param \LocalObject $request
     *
     * @return array
     */
    protected function exhibitionPage($module, Page $page, LocalObject $request, $version="old")
    {
        $user = new UserItem(null);
        $user->loadBySession();
        $session =& GetSession();

        $exhibition = new PublicExhibition($module);
        
        //ticket page
        if ($request->IsPropertySet('Registration'))
        {
        	$ticketPage = $exhibition->getTicketPage($request->GetProperty('Registration'));
        	if($ticketPage)
        	{
        		print($ticketPage);
        		die();
        	}
        	Send404();
        }

        $staticPath = $page->GetProperty('StaticPath');
        $exhibitionUrlParts = [
            'vistavka',
            'obrazovanie',
            'proforient',
            'postuplenie'
        ];
        if (in_array($staticPath, $exhibitionUrlParts)) {
            $staticPath = 'exhibition';
            $page = new Page();
            $page->LoadByStaticPath($staticPath);
            $requestURI = '/'.$staticPath.'/';
        } else {
            $requestURI = $_SERVER['REQUEST_URI'];
        }

        $exhibition->loadCurrent($page->GetProperty('PageID'));
        $data = $exhibition->GetProperties();

        //Change logo
        if (isset($data['PropertyHeaderLogotype']) && $data['PropertyHeaderLogotype'] == 'Y'){
            $data['HeaderLogotype'] = 'prof';
        }
        
        $currentCity = null;

        if (strpos($requestURI, '/?Registration') !== false)
        {
        	Send301(str_replace('/?Registration', '/Registration', $requestURI));
        }
        if (strpos($requestURI, '/RegistrationSuccess2') !== false)
        {
            $requestURI = str_replace('/RegistrationSuccess2', '/?RegistrationSuccess2', $requestURI);
            $request->SetProperty('RegistrationAdditionalForm', 1);
        }
        elseif (strpos($requestURI, '/Registration_off') !== false)
        {
            $requestURI = str_replace('/Registration_off', '/?Registration_off', $requestURI);
            $request->SetProperty('OfflineRegistration', 1);
        }
        elseif (strpos($requestURI, '/Registration') !== false)
        {
        	$requestURI = str_replace('/Registration', '/?Registration', $requestURI);
        	$request->SetProperty('Registration', 1);
        }
        elseif (strpos($requestURI, '/RegisteredSuccess') !== false)
        {
            $requestURI = str_replace('/RegisteredSuccess', '/?RegisteredSuccess', $requestURI);
            $request->SetProperty('Registration', 1);
        }
        $path = str_replace(PROJECT_PATH, '', $requestURI);
        $path = str_replace($page->GetProperty('StaticPath'), '', $path);
        $path = explode('?', $path)[0];
        $path = str_replace(HTML_EXTENSION, '', $path);
        if (!empty($path)) {
            if ($path = explode('/', trim($path, '/'))) {
            	$currentCity = $path[0];
            }
        }
        
        $currentCityInfo = null;
        $exhibition->loadCityList($currentCity);
        foreach ((array)$exhibition->GetProperty('CityList') as $city) {
            if ($city['Selected'] == 1) {
                $data = array_merge($data, $city);
                $currentCityInfo = $city;
                $exhibition->SetProperty('CityID', $city['CityID']);
                $request->SetProperty('city', $city['CityTitle']);
                $request->SetProperty('GUID', $city['GUID']);

                //prepare schedule
                if($version == "landing") {
                    $data['RoomList'] = $exhibition->getRoomList($city);
                    $scheduleInfo = $exhibition->getCitySchedule($city);
                    $data['Schedule'] = $scheduleInfo['TimeList'];
                    $data['ScheduleShowMore'] = $scheduleInfo['ShowMore'];
                    $data['ScheduleLineLimit'] = $scheduleInfo['LineLimit'];
                }
                elseif($version = "oldWithGroupingByDate") {
                    $data['Schedule'] = $exhibition->getCityScheduleOld($city, true);
                }
                else {
                    $data['Schedule'] = $exhibition->getCityScheduleOld($city);
                }
                
                $cityDate = new DateTime($city['Date'], new DateTimeZone('Europe/Moscow'));
                $data['DateDay'] = $cityDate->format("d");
                $data['DateMonth'] = GetTranslation("date-".$cityDate->format("F"));
                
                break;
            }
        }

        $data['HeadImageMainPath'] = $exhibition->GetProperty('HeadImageMainPath');
        
        if (empty($data['CityID'])) {
            Send404();
        }
        
        //fix canonical URL
        if($currentCity != null && isset($currentCityInfo['Default'])){
        	if ($currentCityInfo['Default'] == 1){
				$url = GetCurrentProtocol().$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL'];
				$data['CanonicalURL'] = substr($url, 0, strrpos($url, $currentCity));
			}
        }

        $data['BaseURL'] = PROJECT_PATH . ($staticPath != INDEX_PAGE ? $staticPath . '/' : '');
        if (!empty($currentCity)) {
            $data['BaseURLCity'] = $data['BaseURL'] . $currentCity . '/';
        } elseif ($staticPath == INDEX_PAGE) {
            $data['BaseURLCity'] = $data['BaseURL'];
        } else {
            $data['BaseURLCity'] = $data['BaseURL'] . HTML_EXTENSION;
        }

        //Load fill fields from session
		$removeFromSession = null;
		if ($page->GetProperty('Template') == 'page-exhibition2.html' || $page->GetProperty('Template') == 'page-exhibition3.html'){
			if ($session->IsPropertySet('RegisterFormList')){
				$data['RegisterFormList'] = $session->GetProperty('RegisterFormList');
				$removeFromSession = function (){
					$session = GetSession();
					$session->RemoveProperty('RegisterFormList');
					$session->SaveToDB();
				};
			}
		}

        $data['CityList'] = $exhibition->GetProperty('CityList');
        $data['UniversityList'] = $exhibition->getUniversities();
        $data['MainPartnerList'] = $exhibition->getMainPartners();
        $data['PartnerList'] = $exhibition->getPartners();
        
        if ($request->IsPropertySet('Schedule')) {
            $data['ExhibitionScheduleView'] = 1;
        }
        elseif ($request->IsPropertySet('OfflineRegistration')) {
            $data['OfflineRegistrationView'] = 1;
            
            if($request->IsPropertySet('RegistrationSubmit')) {
                if ($exhibition->registration($request, $user, $currentCityInfo, $page->GetProperty('StaticPath'), true, $removeFromSession)) {
                    $exhibition->addVisit($currentCityInfo, 'Зона регистрации');
                    $data['MessageList'] = $exhibition->GetMessagesAsArray();
                } else {
                    $data = array_merge($data, $request->GetProperty('RegisterFormList')[0]);
                }
            }
        }
        elseif ($request->IsPropertySet('RegistrationAdditionalForm')) {
            $data['RegistrationAdditionalView'] = 1;
            $registrationIDs = explode(',', $request->GetProperty('ID'));
            $data['RegistrationList'] = $exhibition->getRegistrationListInfo($registrationIDs);
            
            $bigDirections = new BigDirection();
            $bigDirections->load();
            
            $university = new University("data");
            $university->loadForSelect();
            
            for($i=0; $i<count($data['RegistrationList']); $i++){
                $data['RegistrationList'][$i]['BigDirectionList'] = $bigDirections->getItems();
                $data['RegistrationList'][$i]['UniversityList'] = $university->getItems();
                $data['RegistrationList'][$i]['Multiple'] = (count($data['RegistrationList']) > 1) ? 1 : 0;
            }
        }
        elseif ($request->IsPropertySet('Registration')) {
            $data['RegistrationView'] = 1;
            
            if (isset($data['RegistrationID']) and $data['RegistrationID'] > 0) {
                header('Location: ' . $data['BaseURLCity'] . '?Schedule');
                exit;
            }

            if ($request->IsPropertySet('RegistrationSubmit')) {
                $page->SetProperty('WithoutBanners', true);
                if ($exhibition->registration($request, $user, $currentCityInfo, $page->GetProperty('StaticPath'), true, $removeFromSession)) {
                    $data['RegistrationSuccess'] = 1;
                    $data['UserItemID'] = true;
                    $data['MessageList'] = $exhibition->GetMessagesAsArray();

                    //Analytic system
                    AnalyticSystem\Sender::sendEventLeadFromBlog('exhibition',
                        $request->GetProperty('RegisterForm')['UserName'][0],
                        $request->GetProperty('RegisterForm')['UserLastName'][0],
                        $request->GetProperty('RegisterForm')['UserPhone'][0],
                        $request->GetProperty('RegisterForm')['UserEmail'][0],
                        $request->GetProperty('RegisterForm')['UserWho'][0]);
                    //Analytic system end
                } else {
                    $data = array_merge($data, $request->GetProperties());
                    $data['ErrorList'] = $exhibition->GetErrorsAsArray();
                }
            }
            
            if ($request->IsPropertySet('RegistrationAdditionalFormSubmit')) {
                //additional form submit
                $exhibition->addAdditionalFields($request);
            }

            // Для авторизации в соцсетях на js для заполнения форм
            $data['JavaScriptList'] = [
                ['Path' => 'https://connect.facebook.net/ru_RU/sdk.js'],
                ['Path' => PROJECT_PATH . 'website/' . WEBSITE_FOLDER . '/template/js/socialauth.js'],
            ];

            $fb = SocialAuth\SocialAuthFactory::createSocial('fb');
            $data['FB_APP_ID'] = $fb->getAppId();
        }
        

        $data['MetaTitle'] = $data['Title'] . ' | ' . $data['CityTitle'];
        if ($user && $user->GetProperty('UserID')) {
            $data['UserRegistered'] = $exhibition->checkRegistered($user, $request->GetProperty('city'));
        }
        
        // process city detection
        if ($request->IsPropertySet('SetCity')){
            $session->SetProperty('SelectedCity', $currentCityInfo['CityTitle']);
            $session->SaveToDB();
        }
        if($session->IsPropertySet('SelectedCity')) {
            $data['SelectedCity'] = $session->GetProperty('SelectedCity');
        }
        else {
            if(!$session->IsPropertySet('DetectedCity')){
                $detectedCity = "";
                $ipinfo = GetIPInfo(getClientIP());
                if($ipinfo && $ipinfo->city) {
                    $detectedCity = $ipinfo->city;
                }
                $session->SetProperty('DetectedCity', $detectedCity);
                $session->SaveToDB();
            }
            $data['DetectedCity'] = $session->GetProperty('DetectedCity');
        }
        
        $data['CurrentPageID'] = $page->GetIntProperty('PageID');

        if ($page->GetProperty('Template') == 'page-exhibition4_online.html' || $page->GetProperty('Template') == 'page-exhibition5_online.html') {
            $data['HideBannerTop'] = true;
        }
        
        return $data;
    }

    protected function onlineExhibitionPage($module, Page $page, LocalObject $request, $version="old")
    {
        $customNavigation = [[
            'Title' => 'Главная',
            'PageURL' => GetUrlPrefix(),
        ]];

        $data = [];
        $urlParser = GetURLParser();
        $university = new University();
        $onlineEvents = new OnlineEvents();

        $staticPath = $urlParser->fixedPath[1];

        if(count($urlParser->fixedPath) == 1){
            Send404();
        }
        if(count($urlParser->fixedPath) == 2)
        {
            $exhibition = OnlineExhibition::getByStaticPath($staticPath);
            if (!$exhibition){
                Send404();
            }
            $onlineEventsIds = OnlineExhibitionParticipantList::getOnlineEventsIds($exhibition->ID);
            OnlineExhibition::prepareForTemplate($exhibition);
            $participantList = OnlineExhibitionParticipantList::getAll(['OnlineExhibitionIds' => [$exhibition->ID]]);
            $participantList->prepareForTemplate();

            $data = $exhibition->GetProperties();
            $data['ParticipantList'] = $participantList->GetItems();
            $data['BaseURL'] = GetUrlPrefix() . $page->GetProperty('StaticPath') . "/{$staticPath}/";
        }
        elseif(count($urlParser->fixedPath) == 3){
            $customNavigation[] = [
                'Title' => 'Выставка',
                'PageURL' => $page->GetPageURL() . "/{$staticPath}",
            ];

            if ($urlParser->fixedPath[2] == 'events'){
                $customNavigation[] = [
                    'Title' => 'Трансляции',
                ];

                $exhibition = OnlineExhibition::getByStaticPath($staticPath);
                $onlineEventsIds = OnlineExhibitionParticipantList::getOnlineEventsIds($exhibition->ID);

                $data['EventsView'] = true;
                if ($request->IsPropertySet('archive')){
                    $data['Archive'] = true;
                }
            }
            else{
                $id = $urlParser->fixedPath[2];
                $participant = OnlineExhibitionParticipant::get($id);
                $onlineEventsIds = $participant->getOnlineEventIDs();
                $participant->prepareForTemplate();
                $data = $participant->GetProperties();
                $universityRow = $university->getByID($participant->UniversityID);
                $data['UniversityShortTitle'] = !empty($universityRow['ShortTitle']) ? $universityRow['ShortTitle'] : $universityRow['Title'];

                $data['ParticipantView'] = true;
                $customNavigation[] = [
                    'Title' => $participant->Title,
                    'PageURL' => $page->GetPageURL(),
                ];

                //questions
                $data['QuestionUserID'] = $universityRow['QuestionUserID'];
                $questionMessageList = new QuestionMessageList("question");
                $questionMessageList->load(new LocalObject(array(
                    "Type" => "university",
                    "AttachID" => $participant->UniversityID,
                    "Status" => "public"
                )));
                $data['QuestionMessageList'] = $questionMessageList->GetItems();
                $data['QuestionPager'] = $questionMessageList->GetPagingAsArray('');
                $data['QuestionUserTitle'] = $universityRow['QuestionUserTitle'];

                $data['QuestionMessageShow'] = 1;
                if(!isset($data['QuestionUserID']) && GetFromConfig('DefaultUserID', 'question'))
                {
                    $data['QuestionUserID'] = GetFromConfig('DefaultUserID', 'question');
                    $data['QuestionUserTitle'] = "Эксперт портала Навигатор Поступления";
                }
                if($data['QuestionUserID'])
                {
                    $user = new UserItem('user');
                    if($user->loadByID($data['QuestionUserID']))
                    {
                        $data['QuestionUserName'] = $user->GetProperty("UserName");
                        if($user->GetProperty("UserImage"))
                        {
                            $data['QuestionUserImage'] = PROJECT_PATH."images/navigator-user-56x56_8/".$user->GetProperty("UserImage");
                        }
                    }
                }
            }
        }

        if ($data['Archive']){
            $onlineEvents->loadArchive(new LocalObject([
                'Ids' => $onlineEventsIds,
                'BaseURL' => PROJECT_PATH . $page->GetProperty('StaticPath'),
            ]));
        }
        elseif (!empty($onlineEventsIds)){
            $onlineEvents->load(0,0, new LocalObject(['Ids' => $onlineEventsIds]));
        }

        $data['OnlineEventList'] = $onlineEvents->GetItems();
        $data['CustomNavigation'] = $customNavigation;
        return $data;
    }

    protected function openDayPage($module, Page $page, LocalObject $request, $version="old")
    {
        $user = new UserItem(null);
        $user->loadBySession();
        $session =& GetSession();
        $city = null;
        $urlParser = new URLParser();

        if ($request->IsPropertySet('ID')){

            if ($openDay = OpenDay::load($request->GetIntProperty('ID'))){
                $url = $page->GetPageURL() . "/" . $openDay->GetProperty('StaticPath');
                if ($request->IsPropertySet('SignIn')){
                    $url .= '/Registration/';
                }

                Send302($url);
            }
        }

        if ($request->IsPropertySet('Registration'))
        {
            $ticketPage = OpenDayRegistration::getTicketPage($request->GetProperty('Registration'));
            if($ticketPage)
            {
                print($ticketPage);
                die();
            }
            Send404();
        }

        $requestURI = $_SERVER['REQUEST_URI'];
        if (strpos($requestURI, '/?Registration') !== false)
        {
            Send301(str_replace('/?Registration', '/Registration', $requestURI));
        }
        if (strpos($requestURI, '/RegistrationSuccess2') !== false)
        {
            $requestURI = str_replace('/RegistrationSuccess2', '/?RegistrationSuccess2', $requestURI);
            $request->SetProperty('RegistrationAdditionalForm', 1);
        }
        elseif (strpos($requestURI, '/Registration_off') !== false)
        {
            $requestURI = str_replace('/Registration_off', '/?Registration_off', $requestURI);
            $request->SetProperty('OfflineRegistration', 1);
        }
        elseif (strpos($requestURI, '/Registration') !== false)
        {
            $requestURI = str_replace('/Registration', '/?Registration', $requestURI);
            $request->SetProperty('Registration', 1);
        }
        elseif (strpos($requestURI, '/RegisteredSuccess') !== false)
        {
            $requestURI = str_replace('/RegisteredSuccess', '/?RegisteredSuccess', $requestURI);
            $request->SetProperty('Registration', 1);
        }
        $path = str_replace(PROJECT_PATH, '', $requestURI);
        $path = str_replace($page->GetProperty('StaticPath'), '', $path);
        $path = explode('?', $path)[0];
        $path = str_replace(HTML_EXTENSION, '', $path);
        $path = explode('/', trim($path, '/'));
        $staticPath = $path[0] ?? null;

        $currentCityPath = $urlParser->GetSubDomain();
        if ($currentCityPath){
            if ($city = City::getByStaticPath($currentCityPath)){
                $data['CurrentCityID'] = $city->GetIntProperty('ID');
            }
        }

        if (!$staticPath || !$openDay = OpenDay::loadByStaticPath($staticPath)){
            Send404();
            exit();
        }

        //redirect to subdomain
        if ($currentCityPath != $openDay->GetProperty('CityPath')){
            Send301(URLParser::getPrefixWithSubDomain($openDay->GetProperty('CityPath')) . $urlParser->GetFullPathAsString());
        }

        $data = $openDay->GetProperties();

        //Change logo
        if (isset($data['PropertyHeaderLogotype']) && $data['PropertyHeaderLogotype'] == 'Y'){
            $data['HeaderLogotype'] = 'prof';
        }

        $data['BaseURL'] = PROJECT_PATH . ($staticPath != INDEX_PAGE ? $page->GetProperty('StaticPath') . '/' . $staticPath . '/' : '');

        //TODO Load fill fields from session
        /*$removeFromSession = null;
        if ($page->GetProperty('Template') == 'page-exhibition2.html' || $page->GetProperty('Template') == 'page-exhibition3.html'){
            if ($session->IsPropertySet('RegisterFormList')){
                $data['RegisterFormList'] = $session->GetProperty('RegisterFormList');
                //Add to $openDayRegistration->registration()
                $removeFromSession = function (){
                    $session = GetSession();
                    $session->RemoveProperty('RegisterFormList');
                    $session->SaveToDB();
                };
            }
        }*/

        $data['MainPartnerList'] = OpenDayPartner::getAll([
            'OpenDayID' => $openDay->GetIntProperty('ID'),
            'Type' => OpenDayPartner::TYPE_MAIN
        ])
        ->GetItems();
        $data['CommonPartnerList'] = OpenDayPartner::getAll([
            'OpenDayID' => $openDay->GetIntProperty('ID'),
            'Type' => OpenDayPartner::TYPE_COMMON
        ])
        ->GetItems();
        $data['SlideList'] = OpenDaySlide::getAll($openDay->GetIntProperty('ID'))->GetItems();

        //prepare schedule
        //TODO landing version
        if($version == "landing") {
            /*$data['RoomList'] = $exhibition->getRoomList($city);
            $scheduleInfo = $exhibition->getCitySchedule($city);
            $data['Schedule'] = $scheduleInfo['TimeList'];
            $data['ScheduleShowMore'] = $scheduleInfo['ShowMore'];
            $data['ScheduleLineLimit'] = $scheduleInfo['LineLimit'];*/
        }
        else{
            $data['Schedule'] = $openDay->getScheduleList();
        }

        if ($request->IsPropertySet('Schedule')) {
            $data['OpenDayScheduleView'] = 1;
        }
        elseif ($request->IsPropertySet('OfflineRegistration')) {
            $data['OfflineRegistrationView'] = 1;
            if($request->IsPropertySet('RegistrationSubmit')) {
                $openDayRegistration = new OpenDayRegistration();
                if ($openDayRegistration->registration($request, $openDay, $user, $page->GetProperty('StaticPath'), true)) {
                    OpenDay::addVisit($openDayRegistration->GetProperty('RegistrationID'), $openDay->GetIntProperty('ID'));
                    $data['MessageList'] = $openDayRegistration->GetMessagesAsArray();
                } else {
                    $data = array_merge($data, $request->GetProperty('RegisterFormList')[0]);
                }
            }
        }
        //TODO registration addition form
        elseif ($request->IsPropertySet('RegistrationAdditionalForm')) {
            $data['RegistrationAdditionalView'] = 1;
            $registrationIDs = explode(',', $request->GetProperty('ID'));
            $data['RegistrationList'] = OpenDayRegistration::getRegistrationListInfo($registrationIDs);

            $bigDirections = new BigDirection();
            $bigDirections->load();

            $university = new University("data");
            $university->loadForSelect();

            for($i=0; $i<count($data['RegistrationList']); $i++){
                $data['RegistrationList'][$i]['BigDirectionList'] = $bigDirections->getItems();
                $data['RegistrationList'][$i]['UniversityList'] = $university->getItems();
                $data['RegistrationList'][$i]['Multiple'] = (count($data['RegistrationList']) > 1) ? 1 : 0;
            }
        }
        elseif ($request->IsPropertySet('Registration')) {
            $data['RegistrationView'] = 1;

            if (isset($data['RegistrationID']) and $data['RegistrationID'] > 0) {
                header('Location: ' . $data['BaseURL'] . '?Schedule');
                exit;
            }

            if ($request->IsPropertySet('RegistrationSubmit')) {
                $page->SetProperty('WithoutBanners', true);
                $openDayRegistration = new OpenDayRegistration();
                if ($openDayRegistration->registration($request, $openDay, $user, $page->GetProperty('StaticPath'), true)) {
                    $data['RegistrationSuccess'] = 1;
                    $data['MessageList'] = $openDayRegistration->GetMessagesAsArray();

                    //Analytic system
                    /*AnalyticSystem\Sender::sendEventLeadFromBlog('exhibition',
                        $request->GetProperty('RegisterForm')['UserName'][0],
                        $request->GetProperty('RegisterForm')['UserLastName'][0],
                        $request->GetProperty('RegisterForm')['UserPhone'][0],
                        $request->GetProperty('RegisterForm')['UserEmail'][0],
                        $request->GetProperty('RegisterForm')['UserWho'][0]);*/
                    //Analytic system end
                } else {
                    $data = array_merge($data, $request->GetProperties());
                    $data['ErrorList'] = $openDayRegistration->GetErrorsAsArray();
                }
            }

            if ($request->IsPropertySet('RegistrationAdditionalFormSubmit')) {
                //additional form submit
                OpenDayRegistration::addAdditionalFields($request);
            }

            // Для авторизации в соцсетях на js для заполнения форм
            $data['JavaScriptList'] = [
                ['Path' => 'https://connect.facebook.net/ru_RU/sdk.js'],
                ['Path' => PROJECT_PATH . 'website/' . WEBSITE_FOLDER . '/template/js/socialauth.js'],
            ];

            $fb = SocialAuth\SocialAuthFactory::createSocial('fb');
            $data['FB_APP_ID'] = $fb->getAppId();
        }


        $data['MetaTitle'] = $data['Title'];
        if ($user && $user->GetProperty('UserID')) {
            $data['UserRegistered'] = OpenDayRegistration::checkRegistered($openDay->GetIntProperty('ID'), $user);
        }

        // process city detection
        if ($request->IsPropertySet('SetCity')){
            $session->SetProperty('SelectedCity', $openDay->GetProperty('CityTitle'));
            $session->SaveToDB();
        }
        if($session->IsPropertySet('SelectedCity')) {
            $data['SelectedCity'] = $session->GetProperty('SelectedCity');
        }
        else {
            if(!$session->IsPropertySet('DetectedCity')){
                $detectedCity = "";
                $ipinfo = GetIPInfo(getClientIP());
                if($ipinfo && $ipinfo->city) {
                    $detectedCity = $ipinfo->city;
                }
                $session->SetProperty('DetectedCity', $detectedCity);
                $session->SaveToDB();
            }
            $data['DetectedCity'] = $session->GetProperty('DetectedCity');
        }

        $data['CurrentPageID'] = $page->GetIntProperty('PageID');

        return $data;
    }

    /**
     * Профессии
     *
     * @param              $module
     * @param \Page        $page
     * @param \LocalObject $request
     *
     * @return array
     */
    protected function professionPage($module, Page $page, LocalObject $request)
    {
        $data = [];
        $urlParser = GetURLParser();
        $professions = new Professions("data");
        $currentCityPath = $urlParser->GetSubDomain();
        $city = null;

        if ($currentCityPath){
            if ($city = City::getByStaticPath($currentCityPath)){
                $data['CurrentCityID'] = $city->GetIntProperty('ID');
                $cityTitleInPredlojCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::PREDLOJ);
                $cityTitleInRodCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::RODIT);
                $data['CityTitleInPredlojCase'] = $cityTitleInPredlojCase;
            }
        }

        //redirect to StaticPath
        if($request->IsPropertySet('ProfessionID'))
        {
        	$path = $professions->getStaticPathByID($request->GetProperty('ProfessionID'));
        	if($path)
        	{
        		Send301(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$path.HTML_EXTENSION);
        	}
        }
        //load ID by StaticPath
        $urlParser =& GetURLParser();
        if(count($urlParser->fixedPath) > 1)
        {
            if(count($urlParser->fixedPath) > 2){
                if (in_array($urlParser->fixedPath[2], DATA_PROFESSION_PAGES)){
                    $request->SetProperty('Section', $urlParser->fixedPath[2]);
                }
            }

        	$path = $urlParser->fixedPath[1];
        	$professionID = $professions->getIDByStaticPath($path);
        	if($professionID)
        	{
        		$request->SetProperty('ProfessionID', $professionID);
        	}
        	else
        	{
        		Send404();
        	}
        }

        $request->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));

        $customNavigation= [
            [
                'Title' => 'Главная',
                'PageURL' => '/',
            ],
            [
                'Title' => isset($cityTitleInRodCase) ? "Профессии {$cityTitleInRodCase}" : $page->GetProperty('Title'),
                'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
            ],
        ];

        if ($request->IsPropertySet('ProfessionID')) {

        	if ($request->IsPropertySet('Select')) {
        		$user = new UserItem('user');
        		$user->loadBySession();
        		if ($user->IsPropertySet('UserID')) {
        			if ($request->GetProperty('Select') == 'Y') {
        				$professions->selectForUser($request->GetIntProperty('ProfessionID'), $user->GetIntProperty('UserID'), true);
        			} else {
        				$professions->selectForUser($request->GetIntProperty('ProfessionID'), $user->GetIntProperty('UserID'), false);
        			}
        		}
        	}

            $info = $professions->getItemInfo($request->GetProperty('ProfessionID'), $request->GetProperty('BaseURL'));

        	//meta
            if ($request->GetProperty('Section') == DATA_PROFESSION_PAGE_UNIVERSITY){
                $data['MetaTitle'] = "{$info["Title"]} - список вузов, в которых можно получить профессию {$info["Title"]}";
                $data['TitleH1'] = "{$info["Title"]}: список вузов где получить профессию";
            }
            else{
                $data['MetaTitle'] = "Все о профессии ".$info["Title"];
                $data['MetaDescription'] = "Все о профессии ".$info["Title"].": описание, характеристика сотрудника, необходимые навыки и средняя зарплата.";

                if ($city && isset($cityTitleInPredlojCase) && !empty($info['TitleInParentCase'])){
                    $request->SetProperty('SpecialFilter', ['CityIDs' => [$city->GetProperty('ID')]]);

                    $data['MetaTitle'] = "Где обучают на {$info['TitleInParentCase']} в {$cityTitleInPredlojCase}";
                    $data['TitleH1'] = "Обучение на {$info['TitleInParentCase']} в {$cityTitleInPredlojCase}";
                }
            }

            foreach ($info as $key => $value) {
                $data[$key] = $value;
            }
            $data['OtherProfession'] = $professions->getOtherProfessionList(
            	$request->GetProperty('ProfessionID'), $request->GetProperty('BaseURL')
			);

            $urlFilter = new URLFilter();
            $urlFilter->LoadFromObject($request, ["SpecDirection","SpecSortOrder"]);

            $university = new University();
            $request->SetProperty('BaseURL', PROJECT_PATH . 'university');
            $universityFilter = ['Profession' => $professionID];
            if ($city){
                $universityFilter['CityID'] = $city->GetProperty('ID');
            }
            $university->load(new LocalObject([
                'BaseURL' => "/" . DATA_PROFESSION_PAGE_UNIVERSITY,
                'UniverFilter' => $universityFilter,
            ]));
            $data['UniversityList'] = $university->GetItems();
            $url = $info['ProfessionURL'] . DATA_PROFESSION_PAGE_UNIVERSITY . '/?' . $urlFilter->GetForURL();
            $data['UniversityPager'] = $university->GetPagingAsArray($url, $url, '#tab-2');
            $data['SpecialityParamsForURL'] = $urlFilter->GetForURL();
            $data['SpecSortOrder'] = $request->IsPropertySet('SpecSortOrder')?$request->GetProperty('SpecSortOrder'):'title';

            $onlineEvents = new OnlineEvents("data");
            $onlineEvents->load(0, 0, $request);
            $data['OnlineEventList'] = $onlineEvents->GetItems();

            $data['TabsCount'] = 2;
            if(count($data['OnlineEventList']) > 0) $data['TabsCount']++;

            $customNavigation[] = [
                'Title' => isset($cityTitleInPredlojCase) ? "{$info["Title"]} в {$cityTitleInPredlojCase}" : $info["Title"],
                'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
            ];

            if ($request->GetProperty('Section') == DATA_PROFESSION_PAGE_UNIVERSITY){
                $customNavigation[] = [
                    'Title' => "Вузы",
                    'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/' . $info['StaticPath'] . '/' . DATA_PROFESSION_PAGE_UNIVERSITY . '/#tab-2',
                ];
            }

            $data['Section'] = $request->IsPropertySet('Section') ? $request->GetProperty('Section') : 'main';
        }
        else {
            $urlFilter = new URLFilter();
            $urlFilter->LoadFromObject($request, array("TextSearch", "SortOrder"));
            if ($city){
                $request->SetProperty('CityIDs', [$city->GetProperty('ID')]);

                $cityTitleInRodCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::RODIT);
                $titleInPredlojCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::PREDLOJ);

                $data['MetaTitle'] = "Список профессий на которые готовят вузы {$cityTitleInRodCase}";
                $data['TitleH1'] = "Профессии в {$titleInPredlojCase}";
            }

            foreach(Professions::FILTER_PARAMS as $param){
                if($request->IsPropertySet($param)){
                    $professionFilter[$param] = $request->GetProperty($param);
                }
            }

            if (!empty($professionFilter)){
				$request->SetProperty('ProfessionFilter', $professionFilter);
			}

            $professions->load($request);
            $data['Page'] = $professions->GetCurrentPage();
            $data['ProfessionList'] = $professions->GetItems();

			$data['ProfessionLike'] = $request->GetIntProperty('ProfessionLike');
			$data['ProfessionLikeTitle'] = $professions->getTitleByID($request->GetIntProperty('ProfessionLike'));

            $data['ProfessionSortOrder'] = $request->GetProperty('SortOrder');
            if(!$data['ProfessionSortOrder']) $data['ProfessionSortOrder'] = 'title';

            $industry = new Industry();
            $industry->load();
            $data['IndustryList'] = $industry->getItems(
                isset($professionFilter['Industry']) ? $professionFilter['Industry'] : []
            );

			$whoWork = new WhoWork();
			$data['WhoWorkList'] = $whoWork->getForFilter(3, isset($professionFilter['WhoWork']) ? $professionFilter['WhoWork'] : []);
			
			$wantWork = new WantWork();
			$data['WantWorkList'] = $wantWork->getForFilter(3, isset($professionFilter['WantWork']) ? $professionFilter['WantWork'] : []);
            
            $data['WageLevelList'] = $professions->getWageLevel(
                isset($professionFilter['WageLevel']) ? $professionFilter['WageLevel'] : []
            );

            $data['ScheduleList'] = $professions->getScheduleList(
                isset($professionFilter['Schedule']) ? $professionFilter['Schedule'] : []
            );

            $operation = new Operation();
            $operation->load();
            $data['OperationList'] = $operation->getItems(
                isset($professionFilter['Operation']) ? $professionFilter['Operation'] : []
            );

            $url = PROJECT_PATH . $page->GetProperty('StaticPath') . '?' . $urlFilter->GetForURL();
            $data['ProfessionPager'] = $professions->GetPagingAsHTML($url, $url);
            $data['ProfessionParamsForURL'] = $urlFilter->GetForURL();

            $data['TextSearch'] = $request->GetProperty('TextSearch');
            $data['SortOrder'] = $request->GetProperty('SortOrder');
        }

        $data['CustomNavigation'] = $customNavigation;
        return $data;
    }

    /**
     * Статьи
     *
     * @param              $module
     * @param \Page        $page
     * @param \LocalObject $request
     *
     * @return array
     */
    protected function articlePage($module, Page $page, LocalObject $request)
    {
        $data = [];
        $articles = new Articles("data");

        //redirect to StaticPath
        if($request->IsPropertySet('ArticleID'))
        {
        	$path = $articles->getStaticPathByID($request->GetProperty('ArticleID'));
        	if($path)
        	{
        		Send301(PROJECT_PATH.$page->GetProperty('StaticPath').'/'.$path.'/');
        	}
        }
        //load ID by StaticPath
        $urlParser =& GetURLParser();
        if(count($urlParser->fixedPath) > 1)
        {
        	$path = $urlParser->fixedPath[1];
        	$articleID = $articles->getIDByStaticPath($path);
        	if($articleID)
        	{
        		$request->SetProperty('ArticleID', $articleID);
        	}
        	else
        	{
        		Send404();
        	}
        }

        $request->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));

        $data['PopularList'] = $articles->GetPopularList($request);
        if ($request->IsPropertySet('Tag')){
            if ($articleTag = ArticleTag::getByStaticPath($request->GetProperty('Tag'))){
                $data['MetaTitle'] = "Статьи – {$articleTag->GetProperty('Title')}";
                $request->SetProperty('TagID', $articleTag->GetIntProperty('TagID'));
            }
        }
        $data['TagList'] = $articles->GetTagList($request->GetProperty("TagID"));

        if ($request->IsPropertySet('ArticleID')) {
            if ($urlParser->GetSubDomain()){
                Send404();
            }
            $info = $articles->getItemInfo($request->GetProperty('ArticleID'));
            $info['Content'] = $this->PrepareContent($info['Content'], $page);
            foreach ($info as $key => $value) {
                $data[$key] = $value;
            }

            //Check index
            if ($info['ToIndex'] == 'N'){
                $data['MetaNoindex'] = true;
            }

            //Preview
            if ($request->GetProperty('Preview') == 'true'){
                $data['BestArticle'][] = $info;
                $data['SimilarList'] = [$info,$info,$info];
            }
            else{
                if ($info['Active'] == 'N'){
                    Send404();
                }
                $data['BestArticle'][] = $articles->getItemInfo($articles::getBestItemID());
                $data['SimilarList'] = $articles->getSimilarList();
                $articles::addView($request->GetProperty('ArticleID'));
            }

            $data['BaseURL'] = PROJECT_PATH . 'article';
            $questionMessageList = new QuestionMessageList("question");
            $questionMessageList->load(new LocalObject(array(
                "Type" => "article",
                "AttachID" => $request->GetIntProperty('ArticleID'),
                "Status" => "public"
            )));
            $authorList = new DataAuthorList();
            $authorList->LoadAuthorList();
            if ($authorInfo = $authorList->getAssocItems(DataAuthor::PRIMARY_KEY)[$info['AuthorID']]){
                $data['AuthorImagePreviewPath'] = $authorInfo['AuthorImagePreviewPath'];
            }
            $data['QuestionMessageList'] = $questionMessageList->getItemsWithAuthorInfo($authorList);
            $url = PROJECT_PATH . $page->GetProperty('StaticPath');
            $data['ItemURL'] = urlencode(GetUrlPrefix() . 'article/' . $info['StaticPath'] . '/');
            $data['QuestionPager'] = $questionMessageList->GetPagingAsArray($url, $url);


            $data['QuestionPager'] = $questionMessageList->GetPagingAsArray($url, $url);

            $data['MetaPreviewImageURL'] = GetCurrentProtocol() . $_SERVER["HTTP_HOST"] . $info['ArticleMainImageBestPath'];
        } else {
            $request->AppendFromArray([
                'ArticleFilter' => [
                    'ArticleSearch' => $request->GetProperty('ArticleSearch')
                ]
            ]);
            $data['ArticleSearch'] = $request->GetProperty('ArticleSearch');

            $urlFilter = new URLFilter();
            $urlFilter->LoadFromObject($request, ["TagID"]);

            $articles->load($request, 18);
            $data['ArticleList'] = $articles->GetItems();
            $data['TagID'] = $request->GetProperty('TagID');

            $url = PROJECT_PATH . $page->GetProperty('StaticPath') . '?' . $urlFilter->GetForURL();
            $data['ArticlePager'] = $articles->GetPagingAsArray($url, $url);
            $data['ArticleParamsForURL'] = $urlFilter->GetForURL();

            foreach ($data['ArticlePager'] as $index => $articlePage) {
                if ($articlePage['Selected'] && !$articlePage['Last'] && !$data['ArticlePager'][$index + 1]['Selected']) {
                    $data['ArticleNextPage'] = $data['ArticlePager'][$index + 1]['Page'];
                    $data['ArticleNextPageURL'] = $data['ArticlePager'][$index + 1]['URL'];
                    break;
                }
            }

            $data['BestArticle'][] = $articles->getItemInfo($articles::getBestItemID());
            $data['SimilarList'] = $articles->getSimilarList();
            $data['BaseURL'] = PROJECT_PATH . 'article';
        }

        return $data;
    }
    
    function ProcessLinks($module, string $subDomain = null)
    {
    	$data = array();
    	if ($subDomain){
            $city = City::getByStaticPath($subDomain);
            $cityId = $city->GetIntProperty('ID');

            //university
            $data[] = DATA_UNIVERSITY_PAGE . "/";
            $universityLinks = University::getForSiteMap(DATA_UNIVERSITY_PAGE, $cityId);
            foreach ($universityLinks as $link){
                $universityLinks[] = $link . DATA_UNIVERSITY_PAGE_CONTACTS;
                $universityLinks[] = $link . DATA_UNIVERSITY_PAGE_SPECIALITIES;
            }
            $data = array_merge($data, $universityLinks);

            //university list
            $data = array_merge($data, ListList::getForSiteMap(DATA_UNIVERSITY_PAGE));

            //speciality
            $data = array_merge($data, Specialities::getForSiteMap(DATA_UNIVERSITY_PAGE, $cityId));

            //profession
            $pageList = new PageList();
            $pageList->LoadPageListForSelection("page-profession.html", 0);
            $result = $pageList->GetItems();
            for ($i = 0; $i < count($result); $i++)
            {
                $staticPath = $result[$i]['StaticPath'];
                $data[] = $staticPath;

                $request = new LocalObject([
                    'BaseURL' => $staticPath,
                    'CityIDs' => [$cityId],
                ]);
                $professions = new Professions("data");
                $professions->load($request, 0);
                $items = $professions->GetItems();
                for($j=0; $j<count($items); $j++)
                {
                    $data[] = $items[$j]['ProfessionURL'];
                }
            }

            //direction
            $data[] = DATA_SPECIALITIES_PAGE . "/";
            $directionList = DirectionList::getAll(['CityIDs' => [$cityId]], 0);
            foreach ($directionList->GetItems() as $direction){
                $data[] = DATA_SPECIALITIES_PAGE . "/{$direction['StaticPath']}/";
            }
        }
    	else{
            $pageList = new PageList();
            $pageList->LoadPageListForSelection("page-article.html", 0);
            $result = $pageList->GetItems();
            for ($i = 0; $i < count($result); $i++)
            {
                $staticPath = $result[$i]['StaticPath'];
                $request = new LocalObject();
                $request->SetProperty('BaseURL', $staticPath);
                $articles = new Articles("data");
                $articles->load($request, 0);
                $items = $articles->GetItems();
                for($j=0; $j<count($items); $j++)
                {
                    $data[] = $items[$j]['ArticleURL'];
                }
            }

            $pageList = new PageList();
            $pageList->LoadPageListForSelection("page-profession.html", 0);
            $result = $pageList->GetItems();
            for ($i = 0; $i < count($result); $i++)
            {
                $staticPath = $result[$i]['StaticPath'];
                $request = new LocalObject();
                $request->SetProperty('BaseURL', $staticPath);
                $professions = new Professions("data");
                $professions->load($request, 0);
                $items = $professions->GetItems();
                for($j=0; $j<count($items); $j++)
                {
                    $data[] = $items[$j]['ProfessionURL'];
                }
            }

            $pageList = new PageList();
            $pageList->LoadPageListForSelection("page-online-events.html", 0);
            $result = $pageList->GetItems();
            for ($i = 0; $i < count($result); $i++)
            {
                $staticPath = $result[$i]['StaticPath'];
                $request = new LocalObject();
                $request->SetProperty('BaseURL', $staticPath);
                $events = new OnlineEvents("data");
                $events->loadArchive($request);
                $items = $events->GetItems();
                for($j=0; $j<count($items); $j++)
                {
                    for($k=0; $k<count($items[$j]['Children']); $k++)
                    {
                        $data[] = $items[$j]['Children'][$k]['OnlineEventURL'];
                    }
                }
                $events->load(0, 0, $request);
                $items = $events->GetItems();
                for($j=0; $j<count($items); $j++)
                {
                    for($k=0; $k<count($items[$j]['Children']); $k++)
                    {
                        $data[] = $items[$j]['Children'][$k]['OnlineEventURL'];
                    }
                }
            }

            $pageList = new PageList();
            $pageList->LoadPageListForSelection(array("page-exhibition.html", "page-exhibition2.html", "page-exhibition3.html", "page-exhibition-landing.html"), 0);
            $result = $pageList->GetItems();
            for ($i = 0; $i < count($result); $i++)
            {
                $staticPath = $result[$i]['StaticPath'];
                $exhibition = new PublicExhibition("data");
                $exhibition->loadCurrent($result[$i]['PageID']);
                $exhibition->loadCityList();
                $cityList = $exhibition->GetProperty('CityList');
                for($j=0; $j<count($cityList); $j++)
                {
                    $data[] = $staticPath.'/'.$cityList[$j]['StaticPath'];
                }
            }

            //direction
            $data[] = DATA_SPECIALITIES_PAGE . "/";
            $directionList = DirectionList::getAll(null, 0);
            foreach ($directionList->GetItems() as $direction){
                $data[] = DATA_SPECIALITIES_PAGE . "/{$direction['StaticPath']}/";
            }
        }
    
    	return $data;
    }
    
    protected function Send302UTM($path)
    {
    	$utm = array();
    	if($_GET['utm_source']) $utm[] = "utm_source=".$_GET['utm_source'];
    	if($_GET['utm_medium']) $utm[] = "utm_medium=".$_GET['utm_medium'];
    	if($_GET['utm_campaign']) $utm[] = "utm_campaign=".$_GET['utm_campaign'];
    	if($_GET['utm_term']) $utm[] = "utm_term=".$_GET['utm_term'];
    	if($_GET['utm_content']) $utm[] = "utm_content=".$_GET['utm_content'];
    	Send302($path.(count($utm)>0?("?".implode("&", $utm)):""));
    }
    
    protected function PrepareContent($content, Page $page = null)
    {
        $result = $content;
        $currentPos = 0;
        $start = strpos($result, "<div class=\"article-inline\"", $currentPos);
        while($start !== FALSE)
        {
            $end = strpos($result, "</div>", $start) + 6;
            $currentPos = $end;
            if($end !== FALSE) 
            {
                $toReplace = substr($result, $start, ($end-$start));
                
                $needle = "data-article=\"";
                $idStart = strpos($toReplace, $needle);
                $idEnd = strpos($toReplace, "\"", $idStart + strlen($needle));
                $articleID = intval(substr($toReplace, $idStart + strlen($needle), $idEnd - $idStart - strlen($needle)));
                $replace = "";
                if($articleID)
                {
                    $articles = new Articles("data");
                    $inlineArticle = $articles->getItemInfo($articleID);
                    if($inlineArticle)
                    {
                        $url = '../'. $inlineArticle['StaticPath'] . '/';
                        $formAction = "/module/data/ajax_public.php";
                        $recaptchaSite = GetFromConfig('RecaptchaSite', 'google');
                        if ($page){
                            $url = $page->GetPageURL() . "/{$inlineArticle['StaticPath']}/";
                        }

                        $replace = '
                            <div class="article-inline-block">
                                <div class="block-article__text">
                                    <a href="' . $url . '" class="block-article__image">
                                        <img src="'.$inlineArticle['ArticleMainImageInsertPath'].'" alt=""/>
                                    </a>
                                    <div class="content">
                                        <a href="' . $url . '" class="block-article__header">'.$inlineArticle['Title'].'</a>
                                        <div>
                                            <div class="meta-info">
                                                <div class="item date">
                                                    <span class="text">'.$inlineArticle['PreviewDate'].'</span>
                                                </div>
                                                <div class="item view">
                                                    <span class="icon view"></span>
                                                    <span class="text">'.$inlineArticle['ViewCount'].'</span>
                                                </div>';
                        if($inlineArticle['ShareCountAll'] > 0)
                        {
                            $replace.='
                                <div class="item">
                                    <span class="icon repost"></span>
                                    <span class="count">'.$inlineArticle['ShareCountAll'].'</span>
                                </div>
                            </div>
                                ';
                        }
                        $replace.='
                                <div class="read-later time-to-read" data-side-toggle="#form-read-later-'.$inlineArticle['ArticleID'].'">
                                    Читать позже
                                </div>
                            </div>
                                </div>
                                    </div>
                                        <div class="time-to-read">
                                            <form action="' . $formAction . '" class="send-to" auto-save-form id="form-read-later-'.$inlineArticle['ArticleID'].'">
                                                <div class="error-list alert-danger alert" style="display: none"></div>
                    
                                                <b>Получите материал на почту и вернитесь к нему позже.</b>
                    
                                                <div class="form-group">
                                                    <input type="email" name="Email" required class="form-control" id="Email">
                                                    <label for="Email">Email</label>
                                                </div>
                    
                                                <div class="checkbox-filter">
                                                    <div class="checkbox active">
                                                        <a href="#">
                                                            <span class="checkmark"></span>
                                                            Согласие на обработку персональных данных
                                                        </a>
                                                        <input type="hidden" name="Terms" value="true">
                                                    </div>
                                                </div>
                    
                                                <div class="g-recaptcha" data-sitekey="'.$recaptchaSite.'"></div>
                                                <input type="hidden" name="Action" value="SendReadLater">
                                                <input type="hidden" name="ItemID" value="'.$inlineArticle['ArticleID'].'">
                                                <input type="submit" class="btn main-btn" value="Отправить">
                                            </form>
                                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                                        </div>
                                </div>';
                    }
                }
                
                $result = substr($result, 0, $start).$replace.substr($result, $end);
                $currentPos = $start + strlen($replace);
            }
            $start = strpos($result, "<div class=\"article-inline\"", $currentPos);
        }
        return $result;
    }
}