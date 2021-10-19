<?php

namespace Module\Tracker\AnalyticSystem;

require_once(dirname(__FILE__) . "/../../init.php");
require_once(dirname(__FILE__) . "/amplitude.php");
require_once(dirname(__FILE__) . "/db.php");
es_include("logger.php");

class Sender
{
	public static function sendEvent($name, $properties = null, array $systems = null){
		$session = GetSession();
		$userID = $session->GetProperty('UserItem')['UserID'];
		$sessionID = $session->_sessionID;

		try{
			if ($systems == null){
				$systems = BaseSystem::AVAILABLE_SYSTEMS;
			}

			foreach ($systems as $index => $systemName) {
				$system = BaseSystem::getInstance($systemName);
				$system->sendEvent($name, $properties, $userID, $sessionID);
			}
		}
		catch (\Exception $e){
			$logger = new \Logger(ANALYTIC_LOG_DIR . 'analytic.log');
			$logger->error($e->getMessage() . '; trace: ' . $e->getTraceAsString());
		}
	}

//Event helpers
	public static function sendEventLeadFromBlog($to, $firstName, $lastName, $phone, $email=null, $contactType=null){
        //Analytic system
        $session = GetSession();
        $send = false;

        if ($session->GetProperty('FirstOpenPage') == 'page-article.html'){
            $send = true;
            $event = 'BlogFirstPage';
        }
        elseif($session->GetProperty('LastOpenPage') == 'page-article.html'){
            $send = true;
            $event = 'BlogPrevPage';
        }

        if ($send && isset($event)){
            $data = [
                'To' => $to,
                'Event' => $event,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Phone' => $phone
            ];
            if($email != null){
                $data['Email'] = $email;
            }
            if($contactType != null){
                $data['ContactType'] = $contactType;
            }   
            self::sendEvent(
                BaseSystem::EVENT_LEAD_FROM_BLOG,
                $data,
                ['db']
            );
        }
        //Analytic system end
    }
}