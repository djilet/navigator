<?php

/*
 * All function from this controller should be moved to public.php ProcessApi function
 */

require_once(dirname(__FILE__)."/../../include/device.php");

require_once(dirname(__FILE__)."/../../include/api_v1/area_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/region_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/direction_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/university.php");
require_once(dirname(__FILE__)."/../../include/api_v1/university_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/subject_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/speciality_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/speciality.php");
require_once(dirname(__FILE__)."/../../include/api_v1/type_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/social_auth.php");
require_once(dirname(__FILE__)."/../../include/api_v1/eventregistration.php");
require_once(dirname(__FILE__)."/../../include/api_v1/online_event.php");
require_once(dirname(__FILE__)."/../../include/api_v1/online_event_list.php");
require_once(dirname(__FILE__)."/../../include/api_v1/user.php");
require_once(dirname(__FILE__)."/../../include/api_v1/exhibition.php");
es_include("apiresponse.php");

class ApiController
{
	private $path;
	private $requestMethod;
	private $module;
	private $response;
	private $core;

	private $userId;

	public function __construct($module, $path)
	{
		$this->module = $module;
		$this->path = $path;
		$this->requestMethod = $_SERVER["REQUEST_METHOD"];
		$this->response = new ApiResponse();
	}

	public function ProcessRequest(LocalObject $request)
	{
		$stmt = GetStatement();
		$this->userId = $stmt->FetchField('SELECT ItemID FROM `user_item2device` WHERE Device='.$request->GetPropertyForSQL('AuthDeviceID'));

		if($this->ValidateRequest($request))
		{
			if(count($this->path) == 1 && $this->path[0] == "devices")
			{
				if($this->requestMethod == "POST")
				{
					$this->RegisterDevice($request);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "POST"));
				}
			}
			elseif(count($this->path) == 2 && $this->path[0] == "devices")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetDevice($this->path[1], $request);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 4 && $this->path[0] == "devices" && $this->path[2] == "push" && in_array($this->path[3], array(CLIENT_ANDROID, CLIENT_IOS)))
			{
				if($this->requestMethod == "PUT")
				{
					$this->SavePushToken($request->GetProperty("Token"), $this->path[3], $this->path[1], $request->GetProperty("AuthDeviceID"));
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "PUT"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "areas")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetAreaList($request->GetProperty("AddRegions"));
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "areas-university")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetAreaListWithUniversity($request);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "regions")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetRegionList($request->GetProperty("AreaID"));
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "directions")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetDirectionList(
						$request->GetProperty("UniversityID"),
						$request->GetProperty("DirectionID"),
						$request->GetIntProperty("Professions"),
					    $request->GetProperty("TitleSearch")
					);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "universities")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetUnivercsityList($request->GetProperty("RegionID"), $request->GetProperty("DirectionID"), $request->GetProperty("TypeID"));
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 2 && $this->path[0] == "universities")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetUniversityInfo($this->path[1]);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 3 && $this->path[0] == "universities" && $this->path[2] == "user_status")
			{
				if ($this->requestMethod == "PUT") {
					$this->SaveUniversityUserStatus($this->path[1], $request);
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "PUT"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "subjects")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetSubjectList($request->GetProperty("RegionID"), $request->GetProperty("DirectionID"), $request->GetProperty("UniversityID"));
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "specialities")
			{
				if($this->requestMethod == "GET")
				{
					$scores = array();
					foreach($request->GetProperties() as $key=>$value){
						if(substr($key, 0, 7) === "Subject"){
							$scores[substr($key, 7)] = $value;
						}
					}
					$this->GetSpecialityList($request->GetProperty("RegionID"), $request->GetProperty("DirectionID"), $request->GetProperty("UniversityID"), $scores);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 2 && $this->path[0] == "specialities")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetSpecialityInfo($this->path[1]);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "types")
			{
				if($this->requestMethod == "GET")
				{
					$this->GetTypeList($request->GetProperty("RegionID"));
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "auth")
			{
				if ($this->requestMethod == "POST") {
					$this->socialAuth($request);
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "POST"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "exhibition")
			{
				if ($this->requestMethod == "GET") {
					$this->GetExhibitionList($request);
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}

			elseif(count($this->path) == 3 && $this->path[0] == "exhibition" && $this->path[2] == "barcode")
			{
				$exhibition = new DataExhibition();
				$exhibition->barcode($this->userId,  $this->path[1]);
				exit;
			}

			elseif(count($this->path) == 2 && $this->path[0] == "eventregister")
			{
				if($this->requestMethod == "POST")
				{
					$this->EventRegister($this->path[1], $request);
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "POST"));
				}
			}
			elseif(count($this->path) == 1 && $this->path[0] == "online_events")
			{
				if ($this->requestMethod == "GET") {
					$this->GetOnlineEventList($request);
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "GET"));
				}
			}
			elseif(count($this->path) == 3 && $this->path[0] == "online_events" && $this->path[2] == "user_status")
			{
				if ($this->requestMethod == "PUT") {
					$this->SaveOnlineEventUserStatus($this->path[1], $request);
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "PUT"));
				}
			}



			elseif(count($this->path) == 2 && $this->path[0] == "user" && $this->path[1] == "auth")
			{
				if ($this->requestMethod == "GET") {
					$user = new DataUser($this->module);
					if ($row = $user->isAuth($request->GetProperty("AuthDeviceID"))) {
						$this->response->SetStatus(true);
						$this->response->SetData($row);
						$this->response->SetCode(200);
					} else {
						$this->response->SetStatus(false);
						$this->response->SetCode(200);
					}

				} else if ($this->requestMethod == "POST") {
					$user = new DataUser($this->module);
					if ($user->auth($request->GetProperty('Email'), $request->GetProperty('Password'), $request->GetProperty("AuthDeviceID"))) {
						$this->response->SetStatus("success");
						$this->response->SetCode(200);
					} else {
						$this->response->SetStatus("error");
						$this->response->SetCode(405);
						$this->response->AddError("api-user-login-error", $this->module);
					}
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "POST, GET"));
				}
			}

			// user auth social
			elseif(count($this->path) == 3 && $this->path[0] == "user" && $this->path[1] == "auth")
			{
				if ($this->requestMethod == "POST") {

					$user = new DataUser($this->module);
					if ($row = $user->authBySocialId($request)) {
						$this->response->SetStatus(true);
						$this->response->SetData($row);
						$this->response->SetCode(200);
					} else {
						$this->response->SetStatus("error");
						$this->response->SetCode(405);
						$this->response->AddError("api-user-login-error", $this->module);
					}

				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "POST"));
				}
			}

			elseif(count($this->path) == 2 && $this->path[0] == "user" && $this->path[1] == "register")
			{
				if ($this->requestMethod == "POST") {
					$user = new DataUser($this->module);
					if ($user->reg($request)) {
						$this->response->SetStatus("success");
						$this->response->SetCode(200);
					} else {
						$this->response->SetStatus("error");
						$this->response->SetCode(405);
						$this->response->LoadErrorsFromObject($user);
					}
				} else {
					$this->response->SetStatus("error");
					$this->response->SetCode(405);
					$this->response->AddError("api-incorrect-request-method", $this->module, array("Allowed" => "POST"));
				}
			}
			else
			{
				$this->response->SetStatus("error");
				$this->response->SetCode(404);
				$this->response->AddError("api-resource-not-found", $this->module);
			}
		}
		$this->response->Output();
	}

	private function ValidateRequest(LocalObject $request)
	{
		if(!$request->ValidateNotEmpty("AuthDeviceID"))
		{
			$this->response->SetStatus("error");
			$this->response->SetCode(400);
			$this->response->AddError("api-device-id-empty", $this->module);
			return false;
		}
		if(!(count($this->path) == 1 && $this->path[0] == "devices" && $this->requestMethod == "POST"))
		{
			$device = new DataDevice($this->module);
			if($device->IsDeviceRegistered($request->GetProperty("AuthDeviceID")))
			{
			    if($device->CheckRequestSign($request) || GetFromConfig("DevMode"))
				{
					return true;
				}
				else
				{
					$this->response->SetStatus("error");
					$this->response->SetCode(400);
					$this->response->AddError("api-request-sign-incorrect", $this->module);
				}
			}
			else
			{
				$this->response->SetStatus("error");
				$this->response->SetCode(401);
				$this->response->AddError("api-device-is-not-registered", $this->module);
			}
		}
		else
		{
			return true;
		}
		return false;
	}

	private function RegisterDevice(LocalObject $request)
	{
		if(!$request->ValidateNotEmpty("AuthDeviceID"))
		{
			$this->response->SetStatus("error");
			$this->response->SetCode(400);
			$this->response->AddMessage("api-device-id-empty", $this->module);
			return;
		}
		if(!$request->ValidateNotEmpty("Client"))
		{
			$this->response->SetStatus("error");
			$this->response->SetCode(400);
			$this->response->AddMessage("api-client-empty", $this->module);
			return;
		}

		$device = new DataDevice($this->module);
		$privateKey = $device->Register($request->GetProperty("AuthDeviceID"), $request->GetProperty("Client"));
		if($privateKey)
		{
			$this->response->SetStatus("success");
			$this->response->SetCode(201);
			$this->response->SetData(array("PrivateKey" => $privateKey));
		}
		else
		{
			$this->response->SetStatus("error");
			$this->response->SetCode(500);
			$this->response->AddMessage("api-internal-server-error", $this->module);
		}
	}

	private function GetDevice($deviceID, LocalObject $request)
	{
		if($deviceID == $request->GetProperty("AuthDeviceID"))
		{
			$device = new DataDevice($this->module);
			$device->LoadByID($deviceID);
			$this->response->SetStatus("success");
			$this->response->SetCode(200);
			$this->response->SetData($device->GetProperties());
		}
		else
		{
			$this->response->SetStatus("error");
			$this->response->SetCode(403);
			$this->response->AddError("api-access-denied", $this->module);
		}
	}

	private function GetAreaList($addRegions)
	{
		$list = new DataAreaList($this->module);
		$list->LoadAreaList(json_decode($addRegions));
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetAreaListWithUniversity(LocalObject $request)
	{
		$list = new DataAreaList($this->module);
		$list->LoadAreaWithUniversityList($request);
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetRegionList($areaID)
	{
		$list = new DataRegionList($this->module);
		$list->LoadRegionList(json_decode($areaID));
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetDirectionList($universityID, $directionID, $withProfession = false, $search = null)
	{
		$list = new DataDirectionList($this->module);
		$list->LoadDirectionList(json_decode($universityID), json_decode($directionID), $withProfession, $search);
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetUnivercsityList($regionID, $directionID, $typeID)
	{
		$list = new DataUniversityList($this->module);
		$list->LoadUniversityList(json_decode($regionID), json_decode($directionID), json_decode($typeID));
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetSubjectList($regionID, $directionID, $universityID)
	{
		$list = new DataSubjectList($this->module);
		$list->LoadSubjectList(json_decode($regionID), json_decode($directionID), json_decode($universityID));
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetSpecialityList($regionID, $directionID, $universityID, $scores)
	{
		$list = new DataSpecialityList($this->module);
		$list->LoadSpecialityList(json_decode($regionID), json_decode($directionID), json_decode($universityID), $scores);
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function GetSpecialityInfo($specialityID)
	{
		$item = new DataSpeciality($this->module);
		$item->LoadByID($specialityID);
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($item->GetProperties());
	}

	private function GetTypeList($regionID)
	{
		$list = new DataTypeList($this->module);
		$list->LoadTypeList(json_decode($regionID));
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($list->GetItems());
	}

	private function SavePushToken($token, $client, $deviceID, $authDeviceID)
	{
		if($deviceID == $authDeviceID)
		{
			$device = new DataDevice($this->module);
			$result = $device->SavePushToken($token, $client, $deviceID);
			if($result === true)
			{
				$this->response->SetStatus("success");
				$this->response->SetCode(201);
			}
			elseif($result === false)
			{
				$this->response->SetStatus("error");
				$this->response->SetCode(400);
			}
		}
		else
		{
			$this->response->SetStatus("error");
			$this->response->SetCode(403);
			$this->response->AddError("api-access-denied", $this->module);
		}
	}
	
	private function GetUniversityInfo($univerID)
	{
		$item = new DataUniversity($this->module);
		$item->loadByID(intval($univerID), $this->userId);
		$this->response->SetStatus("success");
		$this->response->SetCode("200");
		$this->response->SetData($item->GetProperties());
	}

	private function EventRegister($eventID, LocalObject $request)
    {
        $registration = new DataEventRegistration($this->module);
        $result = $registration->Create($request->GetProperty("AuthDeviceID"), $eventID,
            $request->GetProperty("FirstName"),
            $request->GetProperty("LastName"),
            $request->GetProperty("City"),
            $request->GetProperty("Who"),
            $request->GetProperty("Class"),
            $request->GetProperty("Phone"),
            $request->GetProperty("Email"));
        if($result)
        {
            $this->response->SetStatus("success");
            $this->response->SetCode(201);
        }
        else
        {
            $this->response->SetStatus("error");
            $this->response->SetCode(500);
            $this->response->AddMessage("api-internal-server-error", $this->module);
        }
    }

    private function GetOnlineEventList($request)
    {
        $onlineEventList = new DataOnlineEventList($this->module);
        $onlineEventList->LoadOnlineEventList($request);
        $this->response->SetStatus("success");
        $this->response->SetCode("200");
        $this->response->SetData($onlineEventList->GetItems());
    }

    private function GetExhibitionList(LocalObject $request)
    {
        $exhibition = new DataExhibition();
        $this->response->SetStatus("success");
        $this->response->SetCode("200");
        $this->response->SetData($exhibition->getList($request));
    }

    private function SaveOnlineEventUserStatus($onlineEventID, $request)
    {
    	if(!in_array($request->GetProperty("Status"), array("signed", "watched")))
    	{
    		$this->response->SetStatus("error");
    		$this->response->SetCode(400);
    	}
    	else
    	{
    		$onlineEvent = new DataOnlineEvent($this->module);
    		if($onlineEvent->SaveUserStatus($onlineEventID, $this->userId, $request))
    		{
    			$this->response->SetStatus("success");
    			$this->response->SetCode("201");
    		}
    		else
    		{
    			$this->response->SetStatus("error");
    			$this->response->SetCode(500);
    			$this->response->AddMessage("api-internal-server-error", $this->module);
    		}
    	}
    }
    
    private function SaveUniversityUserStatus($universityID, $request)
    {
    	$university = new DataUniversity($this->module);
    	if($university->SaveUserStatus($universityID, $this->userId, $request))
    	{
    		$this->response->SetStatus("success");
    		$this->response->SetCode("201");
    	}
    	else
    	{
    		$this->response->SetStatus("error");
    		$this->response->SetCode(500);
    		$this->response->AddMessage("api-internal-server-error", $this->module);
    	}
    }
}