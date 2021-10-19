<?php

use morphos\Russian\Cases;
use morphos\Russian\GeographicalNamesInflection;

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__) . "/include/college.php");
require_once(dirname(__FILE__) . "/include/college_speciality.php");
require_once(dirname(__FILE__) . "/include/college_bigdirection.php");
require_once(dirname(__FILE__) . "/include/college_admission_base.php");
require_once(dirname(__FILE__) . "/include/college_list.php");
require_once(dirname(__FILE__) . "/../data/include/public/Region.php");
require_once(dirname(__FILE__) . "/../data/include/City.php");
require_once(dirname(__FILE__) . "/../users/include/user.php");
es_include("modulehandler.php");

class CollegeHandler extends ModuleHandler{
	function ProcessPublic(){
		$this->header["InsideModule"] = $this->module;
		$urlParser =& GetURLParser();

		if ($urlParser->IsHTML()){
			$this->ShowHTML();
		}
		else{
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
        }

        return $data;
    }

    function ShowHTML(){
		$request = new LocalObject(array_merge($_GET, $_POST));
		$request->SetProperty('BaseURL', PROJECT_PATH . $this->header['StaticPath']);
		$urlParser =& GetURLParser();
		$url = PROJECT_PATH . $this->header['StaticPath'] . '/';
        $this->tmplPrefix = 'college-tmpl/page_';

        $page = new Page();
        $page->LoadByID($this->pageID);
        $publicPage = new PublicPage($this->module);
		$this->header["Template"] = $this->tmplPrefix."college.html";
		
		$college = new College();
		$specialities = new CollegeSpeciality();
		$admissionBase = new AdmissionBase();
		$collegeListList = new CollegeList();

		$collegeFilter = array();

		//load ID by StaticPath
		if(count($urlParser->fixedPath) > 1)
		{
			if(count($urlParser->fixedPath) > 2)
			{
                //check section or speciality
                if (in_array($urlParser->fixedPath[2], COLLEGE_COLLEGE_PAGES)){
                    $request->SetProperty('CollegeID', College::getIDByStaticPath($urlParser->fixedPath[1]));
                    $request->SetProperty('Section', $urlParser->fixedPath[2]);
                }
                else{
                    $filter = [
                        'StaticPath' => $urlParser->fixedPath[2],
                        'CollegeStaticPath' => $urlParser->fixedPath[1],
                    ];

                    if ($request->IsPropertySet('AdmissionBaseID')){
                        $filter['AdmissionBaseID'] = $request->GetProperty('AdmissionBaseID');
                    }
                    $specialities->load(new LocalObject([
                        'BaseURL' => $request->GetProperty('BaseURL'),
                        'SpecialFilter' => $filter,
                    ]), 0);

                    $speciality = null;
                    if ($specialities->GetCountItems() > 0){
                        $speciality = $specialities->GetItems()[0];
                    }

                    if($speciality)
                    {
                        $request->SetProperty('CollegeID', $speciality["CollegeID"]);
                        $request->SetProperty('CollegeSpecialityID', $speciality["CollegeSpecialityID"]);
                        $request->SetProperty('CollegeSpecialityTitle', $speciality["Title"]);
                    }
                    else
                    {
                        Send404();
                    }
                }
			}
			else
			{
				$collegeID = College::getIDByStaticPath($urlParser->fixedPath[1]);
				if($collegeID) {
					$request->SetProperty('CollegeID', $collegeID);
				}
				else{
					$listID = $collegeListList->getIDByStaticPath($urlParser->fixedPath[1]);
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

        $city = null;
        $currentCityPath = $urlParser->GetSubDomain();
        if ($currentCityPath){
            if ($city = City::getByStaticPath($currentCityPath)){
                $cityTitleInRodCase = GeographicalNamesInflection::getCase($city->GetProperty('Title'), Cases::RODIT);
            }
        }

        $data['Navigation'] = $this->header['Navigation'];
        if (isset($cityTitleInRodCase)){
            $data['Navigation'][1] = [
                'Title' => isset($cityTitleInRodCase) ? "Колледжи {$cityTitleInRodCase}" : $page->GetProperty('Title'),
                'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
            ];
        }

		//Load content
		if ($request->IsPropertySet('CollegeSpecialityID')){
            $data['Navigation'] = $this->header['Navigation'];
            if (isset($cityTitleInRodCase)){
                $data['Navigation'][1] = [
                    'Title' => isset($cityTitleInRodCase) ? "Колледжи {$cityTitleInRodCase}" : $page->GetProperty('Title'),
                    'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
                ];
            }

		    $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		    $content->SetLoop('Navigation', $data['Navigation']);
		    $content->SetVar('PageID',$this->pageID);
		    $content->SetVar('BaseURL', $request->GetProperty('BaseURL'));
		    
            $specialityInfo = $specialities->getByID($request->GetProperty('CollegeSpecialityID'), $request->GetProperty('BaseURL'));
            if ($specialityInfo){
                $content->SetVar('SpecialityItemView', 1);
            }
            else{
                Send404();
            }
			$content->LoadFromArray($specialityInfo);
			$data['TabsCount'] = 1;

			//AdmissionBase
			$content->SetLoop('AdmissionBaseList', CollegeSpeciality::GetOtherAdmissionBase($specialityInfo['CollegeSpecialityID']));

			//College
			if ($specialityCollege = $college->getByID($specialityInfo['CollegeID'], $request->GetProperty('BaseURL'))) {
                //redirect to subdomain
                if (!empty($specialityCollege['CityPath']) && $currentCityPath != $specialityCollege['CityPath']){
                    Send301(URLParser::getPrefixWithSubDomain($specialityCollege['CityPath']) . $urlParser->GetFullPathAsString());
                }

				$content->SetVar('CollegeTitle', (!empty($specialityCollege['ShortTitle']) ? $specialityCollege['ShortTitle'] : $specialityCollege['Title']));
				$content->SetVar('CollegeURL', $specialityCollege['CollegeURL']);
				$content->SetVar('Address', $specialityCollege['Address']);
				$content->SetVar('Latitude', $specialityCollege['Latitude']);
				$content->SetVar('Longitude', $specialityCollege['Longitude']);
				$content->SetVar('PhoneSelectionCommittee', $specialityCollege['PhoneSelectionCommittee']);
				$content->SetVar('Website', $specialityCollege['Website']);
				if(isset($specialityCollege['ImagesList'])){
					$content->SetLoop('ImagesList', $specialityCollege['ImagesList']);
				}
				$content->SetVar('QuestionMessageShow', 1);
			}

			//OtherSpeciality
            $request->SetProperty('OrderBy', 'Rand');
            $request->SetProperty('Limit', '3');
			$specialities->loadListByCollegeID($request);
			$content->SetLoop('OtherSpecialities', $specialities->GetItems());

			//QuestionMessageList
			$questionMessage = new QuestionMessageList("question");
			$questionMessage->load(new LocalObject(array(
				"Type" => "collegeSpeciality",
				"AttachID" => $request->GetIntProperty('CollegeSpecialityID'),
				"Status" => "public"
			)));
			$questionMessageList = $questionMessage->GetItems();
			$content->SetLoop('QuestionMessageList', $questionMessageList);
			$content->SetLoop('QuestionPager', $questionMessage->GetPagingAsArray($url, $url));
			$content->SetVar('QuestionMessageShow', 1);
			$data['TabsCount']++;

			if(!isset($questionMessageList['QuestionUserID']) && GetFromConfig('DefaultUserID', 'question'))
			{
				$questionUserID = GetFromConfig('DefaultUserID', 'question');
				$content->SetVar('QuestionUserID', $questionUserID);
				$content->SetVar('QuestionUserTitle', "Эксперт портала Навигатор Поступления");
			}
			if($questionUserID)
			{
				$user = new UserItem('user');
				if($user->loadByID($questionUserID))
				{
					$content->SetVar('QuestionUserName', $user->GetProperty("UserName"));
					if($user->GetProperty("UserImage"))
					{
						$content->SetVar('QuestionUserImage', PROJECT_PATH."images/navigator-user-56x56_8/".$user->GetProperty("UserImage"));
					}
				}
			}

            $content->SetVar('BaseURL', PROJECT_PATH . $this->header['StaticPath']);
            $content->SetVar('TabsCount', $data['TabsCount']);
			$content->SetVar('CollegePath', $specialityCollege['StaticPath']);

            $metaTitle = "Все о поступлении в ".$specialityCollege['Title'].": после 9 и после 11 класса: средний балл аттестата, количество мест, стоимость, общежитие.";
            $metaDescription = "Как поступить в ".$specialityCollege['Title'].". после 9 и после 11 класса. Информация для абитуриентов: специальности, профессии, адреса корпусов. Средний балл аттестата, конкурс и стоимость обучения.";
            if ($city){
                $metaTitle = "Специальность {$specialityInfo['Title']} в {$specialityCollege['TitleInPrepositionalCase']}";
            }

            $publicPage->headerTmpl->_vars['MetaTitle'] = $metaTitle;
            $publicPage->headerTmpl->_vars['MetaDescription'] = $metaDescription;
		}
		elseif($request->IsPropertySet('CollegeID')){
		    $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		    $content->SetVar('PageID',$this->pageID);
		    $content->SetVar('BaseURL', $request->GetProperty('BaseURL'));
		    
			$collegeInfo = $college->getByID($request->GetIntProperty('CollegeID'), $request->GetProperty('BaseURL'));
			if ($collegeInfo){
                //redirect to subdomain
                if (!empty($collegeInfo['CityPath']) && $currentCityPath != $collegeInfo['CityPath']){
                    Send301(URLParser::getPrefixWithSubDomain($collegeInfo['CityPath']) . $urlParser->GetFullPathAsString());
                }

				if( !is_numeric($collegeInfo['Scholarship']) ){
					$collegeInfo['ScholarshipText'] = $collegeInfo['Scholarship'];
					unset($collegeInfo['Scholarship']);
				}
				if( !is_numeric($collegeInfo['ScholarshipSocial']) ){
					$collegeInfo['ScholarshipSocialText'] = $collegeInfo['ScholarshipSocial'];
					unset($collegeInfo['ScholarshipSocial']);
				}

                $content->SetVar('CollegeItemView', 1);
                $content->SetVar('Section', $request->IsPropertySet('Section') ? $request->GetProperty('Section') : 'main');
            }
            else{
                Send404();
            }
			$content->LoadFromArray($collegeInfo);

			$college->loadForOtherList($request->GetProperty('BaseURL'), $request->GetIntProperty('CollegeID'), $collegeInfo['CollegeBigDirectionID']);
			$content->SetLoop('OtherCollegeList', $college->GetItems());

			$specialities->load(new LocalObject(array(
				'BaseURL' => $request->GetProperty('BaseURL'),
				'SpecialFilter' => array(
					'CollegeID' => $request->GetProperty('CollegeID')
				)
			)), 0);
			$content->SetLoop('SpecialityList', $specialities->GetItems());

			//QuestionMessageList
			$questionMessage = new QuestionMessageList("question");
			$questionMessage->load(new LocalObject(array(
				"Type" => "college",
				"AttachID" => $request->GetIntProperty('CollegeID'),
				"Status" => "public"
			)));
			$questionMessageList = $questionMessage->GetItems();
			$content->SetLoop('QuestionMessageList', $questionMessageList);
			$content->SetLoop('QuestionPager', $questionMessage->GetPagingAsArray($url, $url));
			$content->SetVar('QuestionMessageShow', 1);

            $questionUserID = null;
			if(!isset($questionMessageList['QuestionUserID']) && GetFromConfig('DefaultUserID', 'question'))
			{
				$questionUserID = GetFromConfig('DefaultUserID', 'question');
				$content->SetVar('QuestionUserID', $questionUserID);
				$content->SetVar('QuestionUserTitle', "Эксперт портала Навигатор Поступления");
			}
			if($questionUserID)
			{
				$user = new UserItem('user');
				if($user->loadByID($questionUserID))
				{
					$content->SetVar('QuestionUserName', $user->GetProperty("UserName"));
					if($user->GetProperty("UserImage"))
					{
						$content->SetVar('QuestionUserImage', PROJECT_PATH."images/navigator-user-56x56_8/".$user->GetProperty("UserImage"));
					}
				}
			}

			$data['TabsCount'] = 2;
			if(isset($data['QuestionMessageShow'])) $data['TabsCount']++;

            $titleH1 = $collegeInfo['Title'];
            $metaTitle = "Все о поступлении в ".$collegeInfo['Title'].": после 9 и после 11 класса: средний балл аттестата, количество мест, стоимость, общежитие.";
            $metaDescription = "Как поступить в ".$collegeInfo['Title'].". после 9 и после 11 класса. Информация для абитуриентов: специальности, профессии, адреса корпусов. Средний балл аттестата, конкурс и стоимость обучения.";

            $shortTitle = !empty($collegeInfo['ShortTitle']) ? $collegeInfo['ShortTitle'] : $collegeInfo['Title'];
            $data['Navigation'][] = [
                'Title' => $shortTitle,
            ];
            switch ($request->GetProperty('Section')){
                case COLLEGE_COLLEGE_PAGE_CONTACTS:
                    $titleH1= "Где находится {$shortTitle} - адрес, контакты приемной комиссии";
                    $metaTitle = "{$collegeInfo['Title']} - адрес на карте, сайт, телефон приемной комиссии {$collegeInfo['ShortTitle']}";

                    $data['Navigation'][] = [
                        'Title' => 'Контакты',
                    ];
                    break;

                case COLLEGE_COLLEGE_PAGE_SPECIALITIES:
                    $specialities->load($request, 0);
                    $data['SpecialityList'] = $specialities->GetItems();

                    $titleH1 = "Факультеты, направления подготовки и проходные баллы в {$shortTitle}";
                    $metaTitle = "{$shortTitle} - проходные баллы 2019-2020 года, стоимость обучения, направления подготовки в {$shortTitle}";

                    $data['Navigation'][] = [
                        'Title' => 'Направления подготовки',
                    ];
                    break;
            }

            $content->SetLoop('Navigation', $data['Navigation']);
            $content->SetVar('TitleH1', $titleH1);
            $publicPage->headerTmpl->_vars['MetaTitle'] = $metaTitle;
            $publicPage->headerTmpl->_vars['MetaDescription'] = $metaDescription;
		}
		else{
		    //ListList
		    $listInfo = null;
			$collegeListList->loadForCollegeList(PROJECT_PATH . 'college', $request->GetIntProperty("ListID"));

            if ($city){
                $filteredListList = [];
                foreach ($collegeListList->GetItems() as $list){
                    $filter = $collegeListList->getFilterArray($list['ListID']);
                    $filter['CityID'] = $city->GetIntProperty('ID');
                    $college->load(new LocalObject(['CollegeFilter' => $filter]), 1);
                    if ($college->GetCountItems() > 0){
                        $filteredListList[] = $list;
                    }
                }

                $collegeListList->_items = $filteredListList;
            }

			if($request->IsPropertySet("ListID")){
				$listID = $request->GetIntProperty("ListID");
				$listInfo = $collegeListList->getInfo($listID);
				if($listInfo){
					$request->RemoveProperty("ListID");
					$request->SetProperty("CollegeFilter", $collegeListList->getFilterArray($listID));
				}
			}

			if ($request->IsPropertySet('CollegeFilter')){
				$collegeFilter = $request->GetProperty('CollegeFilter');

                if ($city){
                    $collegeFilter['CityID'] = $city->GetIntProperty('ID');
                }

                $college->load(new LocalObject(['CollegeFilter' => $collegeFilter]), 1);
                if ($college->GetCountItems() < 1){
                    Send404();
                }
			}

			//add filters from GET parameters
			foreach(College::getFilterList() as $param){
				if($request->IsPropertySet($param)){
					$collegeFilter[$param] = $request->GetProperty($param);
				}
			}

            if ($city){
                $collegeFilter['CityID'] = $city->GetIntProperty('ID');
                $data['CurrentCityID'] = $city->GetIntProperty('ID');
            }

            $request->SetProperty('CollegeFilter', $collegeFilter);
			$college->load($request);
			$this->header['Page'] = $college->GetCurrentPage();
			$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
			$content->SetVar('PageID',$this->pageID);
			$content->SetVar('BaseURL', $request->GetProperty('BaseURL'));
			$content->SetLoop('CollegeListList', $collegeListList->GetItems());
			if($listInfo){
			    $content->SetVar('TitleH1', $listInfo["Title"]);
			    $content->SetVar('Description', $listInfo["Description"]);

                if ($city && isset($cityTitleInRodCase)){
                    $publicPage->headerTmpl->_vars['MetaTitle'] = "{$listInfo["Title"]} {$cityTitleInRodCase} - поступление после 9-го или 11-го класса в 2020 году, проходные баллы в {$listInfo["Title"]} {$cityTitleInRodCase}";
                    $content->SetVar('TitleH1', "{$listInfo["Title"]} {$cityTitleInRodCase}");
                    $data['Navigation'][] = [
                        'Title' => $listInfo["Title"],
                        'PageURL' => PROJECT_PATH . $page->GetProperty('StaticPath') . '/',
                    ];
                }
			}
            elseif ($city && isset($cityTitleInRodCase)){
                $publicPage->headerTmpl->_vars['MetaTitle'] = "Колледжи {$cityTitleInRodCase} , поступление после 9 и 11 класса в 2020 году";
                $content->SetVar('TitleH1', "Колледжи {$cityTitleInRodCase}");
            }
			else{
                $content->SetVar('TitleH1', $page->GetProperty('Title'));
            }
			
			//College
			$content->SetLoop('CollegeList', $college->GetItems());
			$content->SetLoop('CollegePager', $college->GetPagingAsArray($url, $url));

			//Regions
			$region = new Region();
			$region->loadForCollegeList();
			$content->SetLoop('RegionList', $region->getItems(
				(isset($collegeFilter['Region']) ? $collegeFilter['Region'] : [])
			));

			//CollegeBigDirection
			$bigDirection = new CollegeBigDirection();
			$bigDirection->load();
			$content->SetLoop('BigDirectionList', $bigDirection->getItems(
				(isset($collegeFilter['CollegeBigDirection']) ? $collegeFilter['CollegeBigDirection'] : [])
			));

			//AdmissionBase
			$admissionBase->load();
			$content->SetLoop('AdmissionBaseList', $admissionBase->getItems(
				(isset($collegeFilter['AdmissionBase']) ? $collegeFilter['AdmissionBase'] : [])
			));

			//Other
			foreach (['BestOfBest', 'Hostel', 'OVZ'] as $key => $name) {
				if (!empty($collegeFilter[$name])){
					$content->SetVar($name, $collegeFilter[$name]);
				}
			}

            $content->SetLoop('Navigation', $data['Navigation']);
            $content->SetVar('CurrentCityID', $data['CurrentCityID']);
		}

        $session = GetSession();
        if ($session->IsPropertySet('BaseTestUser')){
            $baseTestUser = $session->GetProperty('BaseTestUser');
            if (empty($baseTestUser['CompleteDate'])){
                $content->SetVar('BaseTestNotComplete', true);
            }
        }
        else{
            $content->SetVar('BaseTestNotComplete', true);
        }

		$publicPage->Output($content);
	}

    function ProcessLinks($module, string $subDomain = null){
        $data = array();
        if ($subDomain){
            $city = City::getByStaticPath($subDomain);
            $cityId = $city->GetIntProperty('ID');

            $pageList = new PageList();
            $pageList->LoadPageListForModule($module);
            $result = $pageList->GetItems();

            for ($i = 0; $i < count($result); $i++)
            {
                $staticPath = $result[$i]['PageStaticPath'];

                //college
                $data = array_merge($data, College::getForSiteMap($staticPath, $cityId));

                //speciality
                $data = array_merge($data, CollegeSpeciality::getForSiteMap($staticPath, $cityId));
            }
        }

	    return $data;
    }

    protected function indexPage($module, Page $page, LocalObject $request){
        $data = [];
        $urlParser = new URLParser();
        $currentCityPath = $urlParser->GetSubDomain();
        $city = null;
        $cityId = null;

        if ($currentCityPath){
            $city = City::getByStaticPath($currentCityPath);
            $cityId = $city->GetProperty('ID');
        }

        if ($city){
            $college = new College();
            $college->load(new LocalObject([
                'BaseURL' => PROJECT_PATH . COLLEGE_COLLEGE_PAGE,
                'CollegeFilter' => [
                    'CityID' =>$cityId
                ]
            ]), 5);

            $data['CollegeList'] = $college->GetItems();
        }

        return $data;
    }
}
