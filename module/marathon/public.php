<?php
require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__) . "/include/user.php");
require_once(dirname(__FILE__) . "/include/user_info_item.php");
require_once(dirname(__FILE__) . "/include/stage.php");
require_once(dirname(__FILE__) . "/include/map.php");
require_once(dirname(__FILE__) . "/include/map_step.php");
require_once(dirname(__FILE__) . "/include/part.php");
require_once(dirname(__FILE__) . "/include/task.php");
require_once(dirname(__FILE__) . "/../users/include/user.php");
es_include("modulehandler.php");
use mikehaertl\wkhtmlto\Pdf;

class MarathonHandler extends ModuleHandler{
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
		$request = new LocalObject(array_merge($_GET, $_POST));
		$publicPage = new PublicPage($this->module);
		$this->tmplPrefix = 'marathon-tmpl/page_';
		
		$user = new UserItem('user');
		$user->loadBySession();
		if ($user->IsPropertySet('UserID')) {
		    $marathonUser = new MarathonUser($this->module);
		    $marathonUser->load($user->GetProperty('UserID'));
		    $maraphonUserID = $marathonUser->GetIntProperty('MarathonUserID');
            $maraphonUserXP = $marathonUser->GetIntProperty('XP');

			$redirect_path = PROJECT_PATH.$this->header['StaticPath'];
            if ($request->IsPropertySet('RedirectPath')){
				$redirect_path = urldecode($request->GetProperty('RedirectPath'));
			}

			$items = UserInfoItem::GetItemsName();
			if( !$marathonUser->isSetUserInfo($items) ){
				if($request->GetIntProperty('skip') > 0){
					$info_item = new UserInfoItem($request->GetProperty('ItemName'), $maraphonUserID);
					if( !$info_item->saveInfo(array(),true) ){
						Send404();
					}
					Send302($redirect_path);
				}
				elseif ($request->IsPropertySet('answers')){
					$info_item = new UserInfoItem($request->GetProperty('ItemName'), $maraphonUserID);
					if( !$info_item->saveInfo($request->GetProperty('answers')) ){
						Send404();
					}

					if (end($items) == $request->GetProperty('ItemName')){
					    $this->header["Template"] = $this->tmplPrefix."login.html";
						$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
						$content->SetVar('SocialShow', true);
					}
					else{
						Send302($redirect_path);
					}
				}
				else{
				    $this->header["Template"] = $this->tmplPrefix."login.html";
					$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
					$content->SetVar('NoneInfo', true);
				}
			}
			elseif($request->IsPropertySet('PartID')) {
		        //show stage part page
		        $part = new MarathonPart($this->module);
		        $part->loadByID($request->GetProperty('PartID'), $maraphonUserID);
		        if($part->GetProperty('PartID')){
		            if($part->GetProperty('Type') == 'webinar'){
		                $this->header["Template"] = $this->tmplPrefix."part_webinar.html";
		                $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		                $content->LoadFromObject($part);
		            }
		            elseif($part->GetProperty('Type') == 'video'){
		                $this->header["Template"] = $this->tmplPrefix."part_video.html";
		                $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		                $content->LoadFromObject($part);
		            }
		            elseif($part->GetProperty('Type') == 'tasks'){
		                $this->header["Template"] = $this->tmplPrefix."part_task.html";
		                $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		                $content->LoadFromObject($part);
		                
		                $task = new MarathonTask($this->module);
		                $taskComplete = false;
		                if($request->GetProperty('Action') == 'completeTask' && $request->IsPropertySet('TaskID')){
		                    //process form submit
		                    $taskInfo = $task->completeTask($request->GetProperty('TaskID'), $maraphonUserID, $request->GetProperty('Answer'));
		                    if($taskInfo){
		                        $taskComplete = true;
		                        $request->SetProperty('SkipID', $taskInfo['TaskSolutionID']);
		                    }
		                    else {
		                        $content->LoadErrorsFromObject($task);
		                    }
		                }
		                if($task->loadForUser($part, $maraphonUserID, $request->GetProperty('SkipID'), $request->GetProperty('DirectTaskID'))){
		                    if($request->GetIntProperty('SkipID') > 0 && $task->GetIntProperty('TaskSolutionID') == $request->GetIntProperty('SkipID') && $request->GetProperty('Action') != 'completeTask'){
		                        //return to main if skip current task
		                        Send302($redirect_path);
		                    }
		                    $content->LoadFromObject($task);
		                    $content->SetVar('PartID', $part->GetProperty('PartID'));
		                    if($task->IsPropertySet('PartComplete')){
		                        $part->completePart($part->GetProperty('PartID'), $maraphonUserID);
		                    }
		                    if($taskComplete && $task->IsPropertySet('PartLastTask')){
		                        Send302($redirect_path);
		                    }
		                }
		                else {
		                    Send302($redirect_path);
		                }
		            }
		            else {
		                Send302($redirect_path);
		            }
					$content->SetVar('RedirectPath', $redirect_path);
		        }
		        else {
		            Send302($redirect_path);
		        }
		    }
		    elseif($request->IsPropertySet('StageResult')) {
		        $stage = new MarathonStage($this->module);
		        if($stage->loadCompleteStage($request->GetProperty('StageResult'), $maraphonUserID)){
		            $this->header["Template"] = $this->tmplPrefix."stage_result.html";
		            $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		            $content->LoadFromObject($stage);
		            
		            $userStat = $stage->getUserStat($stage->GetProperty('NextStage'));
		            $content->SetVar("StatPeopleCount", $userStat['PeopleCount']);
					$content->SetVar('RedirectPath', $redirect_path);
		        }
		        else {
		            Send404();
		        }
		    }
            elseif($request->IsPropertySet('Map')){
                //show map stage
				$step = new MarathonMapStep($request->GetProperty('Map'), $maraphonUserID);
				if( $step->validate() ){
					if ($request->IsPropertySet('answers')){
						if( $step->validateAnswers($request->GetProperty('answers')) ){
							$step->saveStepForUser($request->GetProperty('answers'));

							if($dep_steps = $step->getDependentSteps()){
								foreach ($dep_steps as $index => $dep_step) {
									$answer_id = $step->getAnswerIdOnStepForUser($dep_step);
									if ($answer_id > 1){
										$step->cleanStepForUser($answer_id);
									}
								}
							}
						    //mark stage part as complete
							$part = new MarathonPart($this->module);
							$partID = $part->findByMapStep($request->GetProperty('Map'), $maraphonUserID);
							if($partID){
								$partResult = $part->completePart($partID, $maraphonUserID);

								if (MarathonStage::getLastPartID() == $partID){
									Send302( PROJECT_PATH.$this->header['StaticPath'].'?CompleteMarathon=1');
								}
							    elseif(isset($partResult['NextStage'])){
									$next_stage_path = PROJECT_PATH.$this->header['StaticPath'].'?StageResult='.$partResult['StageID'];
									if ($request->IsPropertySet('RedirectPath')){
										$next_stage_path .= '&RedirectPath=' . urlencode($redirect_path);
									}
							        Send302($next_stage_path);
							    }
							}
						}
						Send302($redirect_path);
					}
					else{
						Send302($redirect_path);
					}
				}
				else{
					Send404();
				}
            }
            elseif($request->IsPropertySet('ShowProgram')){
                //show program page
                $this->header["Template"] = $this->tmplPrefix."program.html";
                $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);

                $stage = new MarathonStage($this->module);
                $stageList = $stage->loadListForUser($maraphonUserID, $request->GetProperty("CustomPartID"));
                $content->SetLoop("StageList", $stageList);
                $content->SetVar("RedirectPath",urlencode(PROJECT_PATH . $this->header['StaticPath'] . '?ShowProgram=1'));
            }
			elseif($request->IsPropertySet('DownloadMap')){
				$map = new MarathonMap($maraphonUserID, $maraphonUserXP);
				$map_and_answers = new LocalObject($map->getMapAndAnswers());

				$popupPage = new PopupPage($this->module, false);
				$template = $popupPage->Load("marathon-tmpl/page_map2pdf.html");
				$template->LoadFromObject($map_and_answers);
				$template->SetVar('TemplatePath', PROJECT_DIR . 'website/' . WEBSITE_FOLDER . '/template/');
				$template->SetVar('Print', true);
				$content = $popupPage->Grab($template);

				$file_name = MarathonUser::getUserAttachmentName($maraphonUserID, 'pdf', 'map');

				$pdf = new Pdf(array(
					'margin-top'=> 5,
					'margin-bottom'=> 0,
					'margin-right' => 10,
					'margin-left' => 10,
				));
				$pdf->addPage($content);
				if ($pdf->saveAs(MARATHON_PDF_DIR . $file_name)) {
					Send302('website/' . WEBSITE_FOLDER . '/var/marathon/pdf/' . $file_name);
				}
				else{
					//Send404();
				    echo($pdf->getError());
					//throw new Exception('Could not create PDF: '.$pdf->getError());
				}
			}
		    else {
		        //show index page
		        $this->header["Template"] = $this->tmplPrefix."main.html";
		        $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);

		        $stage = new MarathonStage($this->module);
		        if(!$stage->loadForUser($maraphonUserID, $request->GetProperty("CustomPartID"))){
		            Send404();
		        }
		        $content->LoadFromObject($stage);
		        $partList = $stage->loadPartList($maraphonUserID);
		        $content->SetLoop("PartList", $partList);
		        
		        //map
				$map = new MarathonMap($maraphonUserID, $maraphonUserXP);
				$content->SetLoop("Map", $map->getMap());

				//bar
				//$map_bar = $map->getMapBar();
				//$content->SetVar("NextStepName", $map_bar['NextStepName']);
				//$content->SetVar("NextStepNeedXP", $map_bar['NeedXP']);
				//$content->SetVar("BarStatus", $map_bar['Status']);
				$content->SetVar("UserXP", $maraphonUserXP);

				//complete marathon
				if ($request->IsPropertySet('CompleteMarathon')){
					$content->SetVar("CompleteMarathon", 1);
				}
		    }
		}
		else {
		    $this->header["Template"] = $this->tmplPrefix."login.html";
		    $content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
		}

		$content->SetVar('StaticPath', $this->header['StaticPath']);
		$publicPage->Output($content);
	}
}

 ?>