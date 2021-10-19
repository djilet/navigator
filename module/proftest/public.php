<?php
require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__) . "/include/proftest.php");
require_once(dirname(__FILE__) . "/include/user.php");
require_once(dirname(__FILE__) . "/include/task.php");
require_once(dirname(__FILE__) ."/../users/include/user.php");
es_include("modulehandler.php");
use mikehaertl\wkhtmlto\Image;

class ProftestHandler extends ModuleHandler{
	function ProcessPublic(){
	    
	    $this->header["InsideModule"] = "marathon"; //need to include marathon css/js files
	    
	    $request = new LocalObject(array_merge($_GET, $_POST));
	    $publicPage = new PublicPage($this->module);

		$urlFilter = new URLFilter();

		$proftest = new Proftest();
		$proftest->loadByPage($this->pageID);


		if ($request->IsPropertySet('TestResult')){
			$this->header["Template"] = $this->tmplPrefix."result.html";
			$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);

			$userResult = ProftestUser::getResult(array($request->GetProperty('TestResult')));

			if (!empty($userResult)){
				if ($request->GetProperty('Action') == 'CreatePDF'){
					$popupPage = new PopupPage($this->module, false);
					$template = $popupPage->Load("proftest_pdf.html");
					$template->SetVar('PageTitle', $this->header["TitleH1"]?$this->header["TitleH1"]:$this->header["Title"]);

					$template->SetLoop('UserResult', $userResult);

					if ($request->IsPropertySet('Output')){
						$template->SetVar('TemplatePath', PROJECT_PATH . "website/" . WEBSITE_FOLDER . "/template/");
					}
					else{
						$template->SetVar('TemplatePath', PROJECT_DIR . 'website/' . WEBSITE_FOLDER . '/template/');
					}

					$content = $popupPage->Grab($template);

					if ($request->IsPropertySet('Output')){
						echo $content;
						exit();
					}
					$pdf = new Image($content);
                    $pdf->setOptions([
                        'quality' => '50',
                    ]);

                    if (!$pdf->send('proftest.png', true)) {
						//$pdf->getError();
					}
					exit();
				}
				else{
					$userResult = array_shift($userResult);
					$content->LoadFromArray($userResult);
					$urlFilter->LoadFromObject($request);
					$urlFilter->SetProperty('Action','CreatePDF');
					$content->SetVar('PdfPath', GetUrlPrefix() . $this->module . '?' .  $urlFilter->GetForURL());
				}
			}
			else{
				Send404();
			}
		}
		elseif($proftest->IsPropertySet("ProftestID")) {
			$user = new UserItem('user');
			$proftestUser = new ProftestUser($this->module);

			if ($request->IsPropertySet('AuthKey')){
				$user->AuthenticationByAuthKey($request->GetProperty('AuthKey'));
			}

			$user->loadBySession();
			if ($user->IsPropertySet('UserID')){
				$proftestUser->load($proftest->GetIntProperty("ProftestID"), $user->GetProperty('UserID'));
			}

			if ($proftestUser->IsPropertySet('ProftestUserID')){
				if ($request->IsPropertySet('Start')){
						$task = new ProftestTask($this->module);

						if ($request->GetProperty('Action') == 'reset'){
							$proftestUser->reset();
							Send302($this->baseURL."/");
						}
						elseif($request->GetProperty('Action') == 'completeTask' && $request->IsPropertySet('TaskID')){
							//process form submit
							$taskInfo = $task->completeTask($request->GetProperty('TaskID'), $proftestUser->GetProperty('ProftestUserID'), $request->GetProperty('Answer'));
							if($taskInfo){
								$taskComplete = true;
								$request->SetProperty('SkipID', $taskInfo['TaskSolutionID']);
							}
						}

						if($task->loadForUser($proftest, $proftestUser->GetProperty('ProftestUserID'), $request->GetProperty('SkipID'), $request->GetProperty('DirectTaskID'))){
							if($task->IsPropertySet('ProftestComplete')){
								$proftestUser->saveLinkID();
								$this->header["Template"] = $this->tmplPrefix."complete.html";
								$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
							}
							else {
								$this->header["Template"] = $this->tmplPrefix."task.html";
								$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
								$content->LoadFromObject($task);
								$content->LoadErrorsFromObject($task);
							}
						}
					}
				else{
					$this->header["Template"] = $this->tmplPrefix."preview.html";
					$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
					if ($proftestUser->IsPropertySet('FirstInit')){
						$content->SetVar('FirstInit',1);
					}
				}

				$proftest->loadByPage($this->pageID);
				$content->LoadFromObject($proftest);
			}
			else{
				$this->header["Template"] = $this->tmplPrefix."login.html";
				$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
			}
		}
	    else{
			Send404();
	    }
	    
	    $content->SetVar('BaseURL', $this->baseURL."/");
		$content->SetVar('PageTitle', $this->header["TitleH1"]?$this->header["TitleH1"]:$this->header["Title"]);
	    $publicPage->Output($content);
	}
}