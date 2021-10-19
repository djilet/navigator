<?php
require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/test.php");
require_once(dirname(__FILE__) ."/../users/include/user.php");
require_once(dirname(__FILE__) . "/../abtest/include/detector.php");
require_once(dirname(__FILE__) ."/../tracker/include/analytic_system/sender.php");
es_include("modulehandler.php");

use Module\Tracker\AnalyticSystem;

class BaseTestHandler extends ModuleHandler{
    public function ProcessHeader($module, Page $page = null)
    {
        $data = array();
        $session = GetSession();

        //BaseTestComplete
        /*if ($session->IsPropertySet('BaseTestUser')){
            $baseTestUser = $session->GetProperty('BaseTestUser');
            if (empty($baseTestUser['CompleteDate'])){
                $data['BaseTestNotComplete'] = true;
            }
        }
        else{
            $data['BaseTestNotComplete'] = true;

            $testUser = new BaseTestUser();
            $user = new UserItem('user');
            $user->loadBySession();

            if ($user->GetIntProperty('UserID') > 0){
                $testUserID = $testUser->getActiveIDByUserID($user->GetIntProperty('UserID'));

                if ($testUserID > 0){
                    $testUser->load($testUserID);
                    if (!empty($testUser->GetProperty('CompleteDate'))){
                        unset($data['BaseTestNotComplete']);
                        $testUser->updateToSession();
                    }
                }
            }

        }

        if ($page instanceof Page && $module == $page->GetProperty('Link')){
            unset($data['BaseTestNotComplete']);
        }*/

        return $data;
    }

    function ProcessPublic(){
		//TODO find out why it doesn't work
		$this->header["InsideModule"] = $this->module;
		$urlParser =& GetURLParser();

		if ($urlParser->IsHTML()){
			$this->ShowHTML();
		}
		else{
			Send404();
		}
	}

	public function ShowHTML(){
		//Depends
		$publicPage = new PublicPage($this->module);
		//$urlFilter = new URLFilter();
		$request = new LocalObject(array_merge($_GET, $_POST));
		$session = GetSession();
		$test = new BaseTest();

		$user = new UserItem('user');
		$user->loadBySession();

        //Common
        $this->header["Template"] = $this->tmplPrefix."index.html";
        $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);

        if($request->IsPropertySet('ShowTest') && $testUserID = BaseTestUser::getIDByLinkID($request->GetProperty('ShowTest'))){
            $testUser = $test->getTestUser($this->pageID, $testUserID);
            $content->SetVar('ShowTest', $request->GetProperty('ShowTest'));
            $this->openResultPage($request, $content, $testUser);
        }
        elseif ($session->IsPropertySet('BaseTestUser') || $request->GetProperty('Action') == 'Start'){
            //Check authorization

            //Ab testing
            if (AbTestDetector::IsVariantA(AbTestDetector::BASETEST_LANDING)){
                if ($user->IsPropertySet('UserID')){
                    $testUser = $test->getActiveTestUserByUserID($this->pageID, $user->GetIntProperty('UserID'));
                }
                else{
                    $testUserID = $session->GetProperty('BaseTestUser')['BaseTestUserID'];
                    $testUser = $test->getTestUser($this->pageID, $testUserID);
                }
            }
            else{
                if ($user->IsPropertySet('UserID')){
                    $testUser = $test->getActiveTestUserByUserID($this->pageID, $user->GetIntProperty('UserID'));
                }
                else{
                    $testUser = new LocalObject();
                }
            }
            //Ab testing end

            $testUserID = $testUser->GetIntProperty('BaseTestUserID');
            if ($testUserID > 0){

                //Switch Action
                switch ($request->GetProperty('Action')){
                    case "SaveAnswers":
                        $answers = $request->GetProperty('Answers');
                        if (!empty($answers)){
                            $test->saveQuestionResult($request->GetProperty('QuestionResultID'), $answers);
                        }
                        Send302(PROJECT_PATH.$this->header['StaticPath']);
                        break;

                    case "ResetTest":
                        $test->reset($testUser);
                        Send302($this->baseURL);
                        break;


                    case "Start":
                        $testUser->updateToSession();

                    default:
                        //Main page
                        $questionResult = $test->getQuestionResult($testUserID, $request->GetIntProperty('ChangeQuestionID'));

                        $questionList = new BaseTestQuestionList();
                        $questionList->loadForTestUser($testUserID, $questionResult->GetProperty('QuestionID'));
                        $stat = $questionList->getStatForTestUser();

                        if ($stat['CompletedCount'] < $stat['AllQuestionCount']){
                            $question = $test->getQuestion($questionResult->GetProperty('QuestionID'));
                            $optionList = $question->getOptionList($question->getData(), $questionResult->GetProperty('Answers'));

                            $content->LoadFromObject($question);
                            $content->SetLoop('QuestionList', $questionList->getItems());
                            $content->SetLoop('OptionList', $optionList);
                            $content->SetVar('AllQuestionCount', $stat['AllQuestionCount']);
                            $content->SetVar('CompleteQuestionCount', $stat['CompletedCount']);
                            $content->SetVar('QuestionResultID', $questionResult->GetIntProperty('QuestionResultID'));
                            if (!empty($stat['NextQuestion'])){
                                $content->SetVar('NextQuestionTitle', $stat['NextQuestion']['Title']);
                                $content->SetVar('NextQuestionNumber', $stat['NextQuestion']['SortOrder']);
                            }

                            //AnalyticSystem
                            AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_BASETEST_INIT_PAGE,
                                [
                                    'page_number' => $question->GetProperty('SortOrder'),
                                ]
                            );
                            //AnalyticSystem end

                        }
                        elseif($user->IsPropertySet('UserID')){
                            if ($this->config['OpenResult'] == true){
                                //Result page
                                if ($user->GetProperty('EgeStatus') == 'Y'){
                                    $content->SetVar('ShowEge', true);
                                }
                                if (empty($testUser->GetProperty('CompleteDate'))){
                                    $test->setCompleteTest($testUser);
                                }

                                if ($testUser->GetIntProperty('FeedbackRating') < 1){
                                    $content->SetVar('FeedbackForm', true);
                                }
                                $this->openResultPage($request, $content, $testUser);
                                //Analytic system
                                AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_BASETEST_RESULT_PAGE);
                                //Analytic system end
                            }
                            else{
                                if (empty($testUser->GetProperty('CompleteDate'))){
                                    $test->setCompleteTest($testUser);
                                }

                                $content->SetVar('ConsultationPage', true);
                            }
                        }
                        else{
                            $this->openLanding($content, $session, true);
                            $publicPage->footerTmpl->_vars['TestAfterText'] = true;

                            //Analytic system
                            $this->sendEvent($content, 'basetest_after');
                            //Analytic system end
                        }
                }

            }
            else{
                $this->openLanding($content, $session, true);

                //Analytic system
                $this->sendEvent($content, 'basetest_before');
                //Analytic system end
            }

        }
        else{
            $this->openLanding($content);
        }

        //Ab testing
        if (AbTestDetector::IsVariantA(AbTestDetector::BASETEST_LANDING) || $user->IsPropertySet('UserID')){
            $content->SetVar('BeginTest', true);
        }

        $content->SetVar('BaseURL', $this->baseURL."/");
        $content->SetVar('PageTitle', $this->header["TitleH1"]?$this->header["TitleH1"]:$this->header["Title"]);
        $publicPage->Output($content);
	}

	public function openLanding(Template &$content, Session &$session = null, $openForm = null){
        $content->SetVar('LandingPage', true);
        if ($openForm !== null){
            if ($session->IsPropertySet('AbTestSaved')){
                $content->SetVar('ShowModalSingIn', true);
            }
            else{
                $content->SetVar('ShowModalCheckIn', true);
            }
        }
    }

    public function sendEvent(Template &$content, $from){
        if ($content->GetVar('ShowModalSingIn') == true){
            AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_USER_SIGN_IN_OPEN,[
                'from' => $from,
            ]);
        }
        else{
            AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_USER_SIGN_UP_OPEN,[
                'from' => $from,
            ]);
        }
    }

    public function openResultPage(LocalObject &$request, Template &$content, BaseTestUser &$testUser){
        require_once(dirname(__FILE__) . "/../data/include/Industry.php");
        require_once(dirname(__FILE__) . "/../data/include/Operation.php");
        require_once(dirname(__FILE__) . "/../data/include/WantWork.php");
        require_once(dirname(__FILE__) . "/../data/include/WhoWork.php");

        $content->SetVar('ResultPage', true);
        $testUserID = $testUser->GetIntProperty('BaseTestUserID');

        $urlFilter = new URLFilter();
        $urlFilter->LoadFromObject($request, array("TextSearch", "SortOrder"));
        $professions = new Professions();

        $professionFilter = array();
        foreach(Professions::FILTER_PARAMS as $param){
            if($request->IsPropertySet($param)){
                $professionFilter[$param] = $request->GetProperty($param);
            }
        }

        if (!empty($professionFilter)){
            $request->SetProperty('ProfessionFilter', $professionFilter);
        }

        $content->SetLoop('ProfessionList', BaseTest::getOrderProfessionList($testUserID, $request));

        $industry = new Industry();
        $industry->load();
        $content->SetLoop('IndustryList', $industry->getItems(
            isset($professionFilter['Industry']) ? $professionFilter['Industry'] : []
        ));

        $whoWork = new WhoWork();
        $content->SetLoop('WhoWorkList', $whoWork->getForFilter(3, isset($professionFilter['WhoWork']) ? $professionFilter['WhoWork'] : []));

        $wantWork = new WantWork();
        $content->SetLoop('WantWorkList', $wantWork->getForFilter(3, isset($professionFilter['WantWork']) ? $professionFilter['WantWork'] : []));

        $content->SetLoop('WageLevelList', $professions->getWageLevel(
            isset($professionFilter['WageLevel']) ? $professionFilter['WageLevel'] : []
        ));

        $content->SetLoop('ScheduleList', $professions->getScheduleList(
            isset($professionFilter['Schedule']) ? $professionFilter['Schedule'] : []
        ));

        $operation = new Operation();
        $operation->load();
        $content->SetLoop('OperationList', $operation->getItems(
            isset($professionFilter['Operation']) ? $professionFilter['Operation'] : []
        ));
    }
}