<?php

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/user.php");
require_once(dirname(__FILE__) . "/include/user_list.php");
require_once(dirname(__FILE__) . "/../data/include/public/University.php");
require_once(dirname(__FILE__) . "/../data/include/public/Specialities.php");
require_once(dirname(__FILE__) . "/../data/include/public/Professions.php");
require_once(dirname(__FILE__) . "/../data/include/public/OnlineEvents.php");
es_include("modulehandler.php");

use SocialAuth\ISocialNetwork;
use SocialAuth\SocialAuthFactory;

class UsersHandler extends ModuleHandler
{
    public function processPublic()
    {
    	$session = GetSession();
        $publicPage = new PublicPage($this->module);
        $request = new LocalObject(array_merge($_POST, $_GET));

        $this->parseRequest($request);
    }

    private function parseRequest(LocalObject $request)
    {
    	$this->pathInsideModule = array_filter($this->pathInsideModule);
        if ($request->IsPropertySet('logout')) {
        	$this->logout();
        } elseif (isset($this->pathInsideModule[0]) and $this->pathInsideModule[0] == 'auth') {
            if (isset($this->pathInsideModule[1])) {

                if ($this->pathInsideModule[1] == 'authKey'){
                    if ($request->IsPropertySet('AuthKey')){
                        $user = new UserItem('user');
                        if ($user->AuthenticationByAuthKey($request->GetProperty('AuthKey'))){
                            $redirect_url = $request->IsPropertySet('redirect_url')
                                ? $request->GetProperty('redirect_url')
                                : GetDirPrefix();

                            header('Location: ' . $redirect_url);
                        }
                        else{
                            Send404();
                        }
                    }
                }
                else{
                    $social = SocialAuthFactory::createSocial($this->pathInsideModule[1]);
                    if ($social === false) {
                        Send404();
                    }
                    $this->authBySocial($social, $request);
                }
            }
        } elseif (isset($this->pathInsideModule[0]) and $this->pathInsideModule[0] == 'socialdata') {
            if (isset($this->pathInsideModule[1])) {
                $social = SocialAuthFactory::createSocial($this->pathInsideModule[1]);
                if ($social === false) {
                    Send404();
                }
                $this->getDataBySocial($social, $request);
            }
        } elseif (isset($this->pathInsideModule[0]) and $this->pathInsideModule[0] == 'restore' and isset($this->pathInsideModule[1])) {
            if($request->GetProperty("Action") == "restorepassword" && $request->IsPropertySet("NewPassword"))
            {
                $user = new UserItem($module);
                if($user->RestorePassword($this->pathInsideModule[1], $request->GetProperty("NewPassword")))
                {
                    header('Location: ' . GetDirPrefix()."?AlertMessage=restored");
                }
            }
            $template = $this->tmplPrefix."restore.html";
            $publicPage = new PublicPage($this->module);
            $content = $publicPage->Load($template, $this->header, $this->pageID);
            $content->SetVar('Code', $this->pathInsideModule[1]);
            $publicPage->Output($content);
        } else {
        	$session = GetSession();
        	
        	$isLoggedIn = false;
        	$userItem = $session->GetProperty('UserItem');
        	if (is_array($userItem) and !empty($userItem)) {
        		if (isset($userItem['UserID']) and $userItem['UserID'] > 0) {
        			$isLoggedIn = true;
        		}
        	}
        	
        	if($isLoggedIn){
        		$request->SetProperty('UserID', $userItem['UserID']);
        		
        		if(isset($this->pathInsideModule[0]) and $this->pathInsideModule[0] == 'edit') {
        			$template = $this->tmplPrefix."profileedit.html";
        			
        			$this->header["Template"] = $template;
        			$publicPage = new PublicPage($this->module);
        			$content = $publicPage->Load($template, $this->header, $this->pageID);
        			$content->LoadFromArray($userItem);
        			if(isset($userItem['UserImage'])) {
        				$content->SetVar('UserImagePath', PROJECT_PATH."images/navigator-user-80x80_8/".$userItem['UserImage']);
        			}
        			
        			if($request->GetProperty('Action') == 'updateprofile') {
        				$user = new UserItem($this->module);
        				$user->LoadFromArray($userItem);
        				if($user->updatePublic($request)) {
        					header('Location: '.PROJECT_PATH.'profile'.HTML_EXTENSION);
        				}
        				else {
        					$content->SetLoop('ErrorList', $user->GetErrorsAsArray());
        				}
        			}
        			else if($request->GetProperty('Action') == 'changepassword') {
        				$user = new UserItem($this->module);
        				if($user->changePassword($request)) {
        					header('Location: '.PROJECT_PATH.'profile'.HTML_EXTENSION);
        				}
        				else {
        					$content->SetLoop('ErrorList2', $user->GetErrorsAsArray());
        				}
        			}
        			
        			$publicPage->Output($content);
        		}
        		else {
        			$template = $this->tmplPrefix."profile.html";
        			
        			$this->header["Template"] = $template;
        			$publicPage = new PublicPage($this->module);
        			$content = $publicPage->Load($template, $this->header, $this->pageID);
        			$content->LoadFromArray($userItem);
        			if(isset($userItem['UserImage'])) {
        				$content->SetVar('UserImagePath', PROJECT_PATH."images/navigator-user-136x136_8/".$userItem['UserImage']);
        			}
        			
        			$request->SetProperty('BaseURL', PROJECT_PATH . 'university');
        			
        			//selected university list
        			$university = new University('data');
        			if ($request->GetProperty('BecomeAnEntrant') == 'N' && $request->IsPropertySet('UniversityID') && !$request->IsPropertySet('SpecialityID')) {
        				$university->becomeAnEntrant($userItem['UserID'], $request->GetIntProperty('UniversityID'), 0, false);
        			}
        			$university->loadForUser($request);
        			$content->LoadFromObjectList("UserUniversityList", $university);
        			
        			//other university list
        			$university = new University('data');
        			$university->loadRandom($request);
        			$content->LoadFromObjectList("OtherUniversityList", $university);
        			
        			//selected speciality list
        			$speciality = new Specialities('data');
        			if ($request->GetProperty('BecomeAnEntrant') == 'N' && $request->IsPropertySet('UniversityID') && $request->IsPropertySet('SpecialityID')) {
        				$university->becomeAnEntrant($userItem['UserID'], $request->GetIntProperty('UniversityID'), $request->GetIntProperty('SpecialityID'), false);
        			}
        			$speciality->loadForUser($request);
        			$content->LoadFromObjectList("UserSpecialityList", $speciality);
        			
        			//other speciality list
        			$speciality = new Specialities('data');
        			$speciality->loadRandom($request);
        			$content->LoadFromObjectList("OtherSpecialityList", $speciality);
        			
        			//selected profession list
        			$request->SetProperty('BaseURL', PROJECT_PATH . 'profession');
        			$professions = new Professions("data");
        			if ($request->GetProperty('RemoveProfession') && $request->IsPropertySet('ProfessionID')) {
        				$professions->selectForUser($request->GetProperty('ProfessionID'), $userItem['UserID'], false);
        			}
        			$professions->loadForUser($request);
        			$content->LoadFromObjectList("UserProfessionList", $professions);
        			
        			//online event list
        			$onlineEvents = new OnlineEvents("data");
        			if ($request->GetProperty('RemoveEvent') && $request->IsPropertySet('OnlineEventID')) {
        				$onlineEvents->unsignUser($request->GetProperty('OnlineEventID'), $userItem['UserID']);
        			}
        			$request->SetProperty('BaseURL', PROJECT_PATH . 'events');
        			$onlineEvents->loadForUser($userItem['UserID'], $request);
        			$content->LoadFromObjectList("UserEventList", $onlineEvents);
        			
        			//other event list
        			$onlineEvents = new OnlineEvents("data");
        			$onlineEvents->loadFirstEvent(3, $request);
        			$content->LoadFromObjectList("OtherEventList", $onlineEvents);
        			
        			$publicPage->Output($content);
        		}
        	}
        	else {
        		Send404();
        	}
        }
    }

    private function logout()
    {
        $userItem = new UserItem($this->module);
        $userItem->Logout();
        header('Location: ' . GetDirPrefix());
    }

    private function authBySocial(ISocialNetwork $socialNetwork, LocalObject $request)
    {
    	$session = GetSession();
        if ($request->IsPropertySet('redirect_url')) {
            $session->SetProperty('redirect_url', $request->GetProperty('redirect_url'));
            $session->SaveToDB();
            $request->RemoveProperty('redirect_url');
        }
        
        if ($request->CountProperties() > 0) {
        	$socialNetwork->saveToken();
            $userItem = new UserItem(null);
            $userItem->authBySocialID($socialNetwork);
            
            $redirect_url = $session->IsPropertySet('redirect_url')
                ? $session->GetProperty('redirect_url')
                : GetDirPrefix();
            $session->RemoveProperty('redirect_url');
            $session->SaveToDB();
            
            header('Location: ' . $redirect_url);
        } else {
            header('Location: '.$socialNetwork->getAuthUrl());
            exit(0);
        }
    }

    public function processHeader($module, Page $page = null)
    {
        $data = array();

        $session = GetSession();
        $userItem = $session->GetProperty('UserItem');
        if (is_array($userItem) and !empty($userItem)) {
            if (isset($userItem['UserID']) and $userItem['UserID'] > 0) {
                $data['UserItemID'] = $userItem['UserID'];
                $data['UserItemName'] = $userItem['UserName'];
                $data['UserItemEmail'] = $userItem['UserEmail'];
                $data['UserItemPhone'] = $userItem['UserPhone'];
                $data['UserItemWho'] = $userItem['UserWho'];
                $data['UserItemClassNumber'] = $userItem['ClassNumber'];
                $data['IsLoggedIn'] = 1;
                
                if(isset($userItem['UserImage'])) {
                	$data['UserItemImageSmallPath'] = PROJECT_PATH."images/navigator-user-40x40_8/".$userItem['UserImage'];
                }
            }
            
            if (empty($userItem['UserID']) and !empty($userItem['SocialID'])) {
                $data['UnfinishedRegistration'] = 1;
                $data['SocialName'] = $userItem['SocialFirstName'].' '.$userItem['SocialSurname'];
                $data['SocialEmail'] = $userItem['SocialEmail'];
                $data['SocialWhoAmI'] = $userItem['SocialWhoAmI'];
                $data['SocialClass'] = $userItem['SocialClass'];
                $data['SocialID'] = $userItem['SocialID'];
            }
        }

        return $data;
    }

    private function getDataBySocial(ISocialNetwork $socialNetwork, LocalObject $request)
    {
        $socialNetwork->setRedirectURL(GetUrlPrefix().'profile/socialdata/'.$socialNetwork->getSocialType().'/');
        if ($request->CountProperties() > 0) {
            $socialNetwork->saveToken();
            $user = $socialNetwork->getUserInfo();
            if (!empty($user)) {
                echo <<<LABEL
<script>
	var response = {
		session: {
			user: {
				first_name: "{$user['first_name']}",
				last_name: "{$user['last_name']}",
				email: "{$user['email']}"
			}
		}
	};

    if (window.parent.socialResultHundler) {
        window.parent.socialResultHundler(response);
    }
    else if(window.opener.socialResultHundler){
        window.opener.socialResultHundler(response)
    }
    
    window.close();
</script>
LABEL;
            } else {
                echo "Не удалось произвести авторизацию";
                exit(0);
            }

        } else {
            header('Location: '.$socialNetwork->getAuthUrl());
            exit(0);
        }
    }
}
