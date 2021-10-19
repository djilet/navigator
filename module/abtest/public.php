<?php

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__) ."/../users/include/user.php");
require_once(dirname(__FILE__) . "/include/detector.php");
require_once(dirname(__FILE__) . "/../tracker/include/analytic_system/sender.php");
es_include("modulehandler.php");

use Module\Tracker\AnalyticSystem;

class AbTestHandler extends ModuleHandler{
    public function ProcessHeader($module, Page $page = null)
    {
        $session = GetSession();
        if ($user = $session->GetProperty('UserItem')){
            if ($session->GetProperty('AbTestSaved') !== true
                && $session->IsPropertySet('AbTest')
                && $user['UserID'] > 0){
                $abTest = $session->GetProperty('AbTest');
                if (AbTestDetector::saveForUser($user['UserID'],$abTest)){
                    $session->SetProperty('AbTestSaved', true);
                    $session->SaveToDB();
                }
            }
        }

        /*echo '<pre>';
        print_r($session);
        echo '</pre>';*/
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
		/*$publicPage = new PublicPage($this->module);
		$request = new LocalObject(array_merge($_GET, $_POST));
		$session = GetSession();


        $content->SetVar('BaseURL', $this->baseURL."/");
        $content->SetVar('PageTitle', $this->header["TitleH1"]?$this->header["TitleH1"]:$this->header["Title"]);
        $publicPage->Output($content);*/
	}
}