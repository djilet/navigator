<?php
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/tracker.php");
require_once(dirname(__FILE__) . "/include/analytic_system/sender.php");
es_include("modulehandler.php");
use Module\Tracker\AnalyticSystem;

class TrackerHandler extends ModuleHandler{
	public $module = 'tracker';

	public function ProcessHeader($module, Page $page = null){
		//$tracker = new Tracker;
    	//$tracker->addAction();

        $data = array();
        $session = GetSession();

        //Analytics
        if ($page instanceof Page && $page->ValidateNotEmpty('Template')){
            //save first page
            if (!$session->IsPropertySet('FirstOpenPage')){
                $session->SetProperty('FirstOpenPage', $page->GetProperty('Template'));
            }

            //save last page
            if ($session->GetProperty('PrevPage') != $page->GetProperty('Template')){
                $session->SetProperty('LastOpenPage', $session->GetProperty('PrevPage'));
                $session->SetProperty('PrevPage', $page->GetProperty('Template'));
            }
        }

        $session->SaveToDB();
        //Analytics

        $data['SessionID'] = $session->_sessionID;
        foreach (AnalyticSystem\BaseSystem::AVAILABLE_SYSTEMS as $index => $name) {
            $key = GetFromConfig('ApiKey', $name);
            if (!empty($key)){
                $data[$name . 'Key'] = GetFromConfig('ApiKey', $name);
            }
        }

		return $data;
    }
}