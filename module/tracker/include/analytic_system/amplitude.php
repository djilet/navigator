<?php

namespace Module\Tracker\AnalyticSystem;
require_once(dirname(__FILE__) . "/base_system.php");

class Amplitude extends BaseSystem
{
    const SYSTEM_NAME = 'amplitude';
    protected $baseUrl = 'https://api.amplitude.com/';
    protected $identifyPath = 'identify';
    protected $httpPath = 'httpapi';

    public function getBaseUrl(){
		return $this->baseUrl;
	}

	public function getApiKey(){
		return $this->apiKey;
	}

	public function sendEvent($name, $properties = null, $userID = null, $deviceID = null)
	{
		try{
			if (empty($userID) && empty($deviceID)){
				throw new BaseSystemException('empty-user-id');
			}

			//require fields
			$event['event_type'] = $name;
			$event['user_id'] = $userID;
			$event['device_id'] = $deviceID;

			//other
			if ($properties !== null){
				$event['event_properties'] = $properties;
			}

			$data = [
				'api_key' => $this->getApiKey(),
				'event' => json_encode($event),
			];

			$this->setCurlOption(CURLOPT_URL, $this->getBaseUrl() . $this->httpPath);
			$this->setCurlOption(CURLOPT_POSTFIELDS, $data);

			//echo $this->sendRequest() . ' : ' . $name;
			$response = $this->sendRequest();

			if ($response !== 'success'){
				throw new BaseSystemException('Response: ' . $response);
			}

			return true;
		}
		catch (BaseSystemException $e){
			throw $e;
		}
	}
}