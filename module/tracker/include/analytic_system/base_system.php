<?php

namespace Module\Tracker\AnalyticSystem;

es_include('base_curl.php');

abstract class BaseSystem extends \BaseCurl
{
	//events name
    const EVENT_USER_SIGN_UP_OPEN = 'signup_beginning';
    const EVENT_USER_SIGN_IN_OPEN = 'login_beginning';
    const EVENT_USER_SIGN_UP = 'signup_done';
	const EVENT_USER_LOGIN = 'login_done';
	const EVENT_BASETEST_FEEDBACK = 'basetest_feedback_done';
	const EVENT_BASETEST_INIT_PAGE = 'basetest_page';
	const EVENT_BASETEST_RESULT_PAGE = 'basetest_results_page';
	const EVENT_LEAD_FROM_BLOG = 'lead_from_blog';
    const AVAILABLE_SYSTEMS = ['amplitude'];
    const SYSTEM_NAME = self::SYSTEM_NAME;

    protected $apiKey;

    public abstract function sendEvent($name, $properties = null);

    public function __construct(){
	    $this->apiKey = GetFromConfig('ApiKey', static::getSystemName());
    }

	public static function getInstance($name){
		switch ($name){
			case 'amplitude':
				return new Amplitude();
				break;

            case 'db':
                return new DataBase();
                break;

			default:
				return false;
				break;
		}
	}

    public static function getSystemName(){
	    return static::SYSTEM_NAME;
    }
}

class BaseSystemException extends \Exception{

}