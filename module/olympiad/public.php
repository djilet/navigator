<?php

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__) . "/include/main.php");
require_once(dirname(__FILE__) . "/include/main_list.php");
require_once(dirname(__FILE__) . "/include/olympiad.php");
require_once(dirname(__FILE__) . "/include/olympiad_list.php");
require_once(dirname(__FILE__) . "/include/class_number.php");
require_once(dirname(__FILE__) . "/include/profile.php");
require_once(dirname(__FILE__) . "/../data/include/public/Region.php");
require_once(dirname(__FILE__) . "/../data/include/public/Subject.php");
es_include("modulehandler.php");

use Module\Olympiad\Main;
use Module\Olympiad\MainList;
use Module\Olympiad\Olympiad;
use Module\Olympiad\OlympiadList;
use Module\Olympiad\Profile;
use Module\Olympiad\ClassNumber;

class OlympiadHandler extends \ModuleHandler{
    public function ProcessHeader($module, \Page $page = null){

    }

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

	function ShowHTML(){
		//init
		$request = new \LocalObject(array_merge($_GET, $_POST));
		$publicPage = new \PublicPage($this->module);

		$this->tmplPrefix = 'olympiad-tmpl/page_';
		$this->header["Template"] = $this->tmplPrefix."olympiad.html";

		$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);

		$content->SetVar('TitleH1', (!empty($this->header['TitleH1']) ? $this->header['TitleH1'] : $this->header['Title']));
		$navigation = [
			[
				'Title'      => 'Главная',
				'PageURL' => PROJECT_PATH,
			],
			[
				'Title'      => 'Олипиады',
				'PageURL' => $this->baseURL,
			],
		];

        $main = new Main();
        $mainList = new MainList();
		$olympiad = new Olympiad();
		$olympiadList = new OlympiadList();
		$olympiadClass = new ClassNumber();
		$olympiadProfile = new Profile();

		//load ID by StaticPath
		$urlParser =& GetURLParser();
		if(count($urlParser->fixedPath) > 1){
			$path = $urlParser->fixedPath[1];
			$mainID = $main::getIDByStaticPath($path);

			if (isset($urlParser->fixedPath[2])){
                $olympiadID = $olympiad::getIdByStaticPath($mainID, $urlParser->fixedPath[2]);
            }


			if (!empty($olympiadID)){
                $request->SetProperty('OlympiadID', $olympiadID);
            }
			if($mainID){
				$request->SetProperty('MainID', $mainID);
			}
			else{
				Send404();
			}
		}

		if ($request->IsPropertySet('OlympiadID')){
            $main->load($request->GetProperty('MainID'));
            $content->LoadFromObject($main);

            $olympiad->load($request->GetProperty('OlympiadID'));
            $content->LoadFromObject($olympiad);

            $olympiadList->load(new LocalObject([
                    'OlympiadFilter' => [
                        'MainID' => $main->GetProperty('MainID'),
                        'ExcludeIDs' => [$olympiad->GetProperty('OlympiadID')],
                ],
            ]));

            $content->SetLoop('OtherOlympiad', $olympiadList->GetItems());
            $content->SetVar('MainStaticPath', $main->GetProperty('StaticPath'));

            $navigation[] = [
                'Title'      => $main->GetProperty('Name'),
                'PageURL' => $this->baseURL . '/' . $main->GetProperty('StaticPath'),
            ];
            $navigation[] = [
                'Title'      => $olympiad->GetProperty('Name'),
            ];
        }
		elseif ($request->IsPropertySet('MainID')){
            $main->load($request->GetProperty('MainID'));
            $content->LoadFromObject($main);

            $olympiadList->load(new LocalObject([
                'OlympiadFilter' => [
                    'MainID' => $request->GetProperty('MainID'),
                ]
            ]), 0);

            $content->SetLoop('OlympiadList', $olympiadList->GetItems());
            $url = $this->baseURL . '/' . $main->GetProperty('StaticPath');
            $content->SetLoop('OlympiadPager', $olympiadList->GetPagingAsArray($url, $url));

            //print_r($content);

			/*$olympiadList->LoadOtherListById($request->GetIntProperty('OlympiadID'), $this->baseURL);
			$content->SetLoop('OtherOlympiad', $olympiadList->GetItems());
			$content->SetVar('OlympiadStaticPath', $olympiadList->item->GetProperty('StaticPath'));*/
			$navigation[] = [
				'Title'      => $main->GetProperty('Name'),
                'PageURL' => $this->baseURL . $main->GetProperty('StaticPath'),
			];

		}
		else{
            $mainList->loadForFilter($request);
			$content->SetLoop('MainList', $mainList->GetItems());
			$url = PROJECT_PATH;
			$content->SetLoop('MainPager', $mainList->GetPagingAsArray($url, $url));

			//Class
			$olympiadClass->load();
			$content->SetLoop('ClassList', $olympiadClass->getListForTemplate([], ClassNumber::getStaticList()));

			//Region
            $regionList = new Region();
			$content->SetLoop('RegionList', $regionList->getListForTemplate([], Region::getStaticList()));

			//Profile
			$content->SetLoop('ProfileList', $olympiadProfile->getListForTemplate([], $olympiadProfile::getStaticList()));

			//Subject
			$subject = New Subject();
			$subject->load();
			$content->SetLoop('SubjectList', $subject->getListForTemplate());

			//Level
			$content->SetLoop('LevelList', $olympiadList::getLevelList());
		}

		//Output
		$content->SetVar('PageID', $this->pageID);
		$content->SetVar('BaseURL', $this->baseURL);
		$content->SetLoop('Navigation', $navigation);
		$publicPage->Output($content);
	}
}