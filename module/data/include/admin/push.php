<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobject.php");
es_include("logger.php");

class DataPush extends LocalObject
{
	var $module;
	private $logger;
	private $iosPassPhrase = "";
	private $iosServerCertificatePath = "";
	private $iosEntrustRootCertificatePath = "";
	
	function DataPush($module, $data = array())
	{
		parent::LocalObject($data);
		$this->module = $module;
		$this->logger = new Logger(PROJECT_DIR."var/log/push.log");
		$this->iosServerCertificatePath = dirname(__FILE__)."/push/apns_server.cer";//release
		//$this->iosServerCertificatePath = dirname(__FILE__)."/push/apns_server_development.cer";//test
		$this->iosEntrustRootCertificatePath = dirname(__FILE__)."/push/apns_entrust_root.cer";
	}

	function Send()
	{
		if(!$this->Validate())
		{
			return false;
		}
		
		$stmt = GetStatement();
		$receiverList = array();
		if(is_array($this->GetProperty("ReceiverList")) && count($this->GetProperty("ReceiverList")))
		{
			$query = "SELECT t.Token 
						FROM `data_push_token` AS t 
						WHERE t.DeviceID IN(".implode(", ", Connection::GetSQLArray($this->GetProperty("ReceiverList"))).")
							AND Client=".$this->GetPropertyForSQL("Client");
		}
		else
		{
			$query = "SELECT Token FROM `data_push_token` WHERE Client=".$this->GetPropertyForSQL("Client");
		}

		$receiverList = $stmt->FetchList($query);
		$tokens = array();
		foreach ($receiverList as $receiver)
		{
			$tokens[] = $receiver["Token"];
		}
		if($this->GetProperty("Client") == CLIENT_ANDROID)
		{
			$this->SendAndroid($tokens);
		}
		elseif($this->GetProperty("Client") == CLIENT_IOS)
		{
			$this->SendIOS($tokens);
		}
	
		$this->AddMessage("push-done", $this->module);
		return true;
	}
	
	function Validate()
	{
		$stmt = GetStatement();
		
		if(!$this->ValidateNotEmpty("Text"))
			$this->AddError("push-text-empty", $this->module);
		
		if(is_array($this->GetProperty("ReceiverList")) && count($this->GetProperty("ReceiverList")))
		{
			for($i = 0; $i < count($this->GetProperty("ReceiverList")); $i++)
			{
				$this->_properties["ReceiverList"][$i] = trim($this->_properties["ReceiverList"][$i]);
			}
			$query = "SELECT COUNT(*) FROM `data_push_token` AS t 
						WHERE t.DeviceID IN(".implode(", ", Connection::GetSQLArray($this->GetProperty("ReceiverList"))).")
							AND t.Client=".$this->GetPropertyForSQL("Client");
			$receiverCount = $stmt->FetchField($query);
			if($receiverCount == 0)
				$this->AddError("push-receiver-list-empty", $this->module);
		}
		else
		{
			$query = "SELECT COUNT(*) FROM `data_push_token` WHERE Client=".$this->GetPropertyForSQL("Client");
			$receiverCount = $stmt->FetchField($query);
			if($receiverCount == 0)
				$this->AddError("push-receiver-list-empty", $this->module);
		}
		
		return !$this->HasErrors();
	}
	
	function RemoveToken($token, $client)
	{
		$stmt = GetStatement();
		$query = "DELETE FROM `data_push_token` WHERE Token=".Connection::GetSQLString($token)." AND Client=".Connection::GetSQLString($client);
		$stmt->Execute($query);
	}
	
	function UpdateToken($oldToken, $newToken, $client)
	{
		$stmt = GetStatement();
		$query = "UPDATE `data_push_token` SET Token=".Connection::GetSQLString($newToken)." 
					WHERE Client=".Connection::GetSQLString($client)." 
						AND Token=".Connection::GetSQLString($oldToken);
		$stmt->Execute($query);
	}
	
	function SendAndroid($tokens)
	{
		$this->logger->info(PHP_EOL.PHP_EOL.GetCurrentDateTime()." Sending push notifications to Android".PHP_EOL);
		require_once(dirname(__FILE__)."/push/fcm/FCMManager.php");
		require_once(dirname(__FILE__)."/push/fcm/FCMMessage.php");
		
		$sentCount = 0;
		$failedCount = 0;
		$tokenChunks = array_chunk($tokens, 1000);
		foreach ($tokenChunks as $chunk)
		{
			$message = new FCMMessage();
			$message->setToken( $chunk );
			$message->setTitle( $this->GetProperty("Title") );
			$message->setBody( $this->GetProperty("Text") );
			$target = $this->GetProperty("Target");
			if($target == "url")
			{
				$message->setURL( $this->GetProperty("URL") );
			}
			
			$response = FCMManager::send($message);
			$this->logger->info("Message:".PHP_EOL.print_r($message, true).PHP_EOL);
			$this->logger->info("Response:".PHP_EOL.print_r($response, true).PHP_EOL);
			//find and remove invalid tokens
			for($i = 0; $i < count($response->results); $i++)
			{
				if(isset($response->results[$i]->error) && in_array($response->results[$i]->error, array("MissingRegistration", "InvalidRegistration", "NotRegistered")))
					$this->RemoveToken($chunk[$i], CLIENT_ANDROID);
			}
			if ($response == false) 
			{
				$this->AddError("push-error", $this->module, array("Client" => CLIENT_ANDROID, "Error" => FCMManager::getError()));
				break;
			}
			else 
			{
				$sentCount += $response->success;
				$failedCount += $response->failure;
			}
		}
		$this->AddMessage("push-sent-count", $this->module, array("Client" => CLIENT_ANDROID, "Count" => $sentCount));
		if($failedCount > 0)
			$this->AddMessage("push-fail-count", $this->module, array("Client" => CLIENT_ANDROID, "Count" => $failedCount));
	}
	
	function SendIOS($tokens)
	{
		require_once(dirname(__FILE__)."/push/ApnsPHP/Autoload.php");
		try 
		{
			//$tokens = $this->CallIOSFeedback($tokens);
			
			$push = new ApnsPHP_Push(ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION, $this->iosServerCertificatePath);
			//$push->setProviderCertificatePassphrase($this->iosPassPhrase);
			$push->setRootCertificationAuthority($this->iosEntrustRootCertificatePath);
			
			$message = new ApnsPHP_Message();
			$listTokens = array();
			foreach ($tokens as $token)
			{
				$message->addRecipient($token);
			}
			
			$push->connect();
			
			$data = $this->BuildPushData();
			$message->setText($data["text"]);
			$message->setCustomProperty("target", $data["target"]);
			$message->setCustomProperty("url", $data["url"]);
			
			$push->add($message);
			$push->send();
			$push->disconnect();
			$failedCount = 0;
			
			$aErrorQueue = $push->getErrors();
			if (!empty($aErrorQueue))
			{
				$this->AddError("push-error", $this->module, array("Client" => CLIENT_IOS, "Error" => print_r($aErrorQueue, true)));
				if (is_array($aErrorQueue))
				{
					foreach($aErrorQueue as $error)
					{
						if (isset($error['ERRORS']) && is_array($error['ERRORS']))
						{
							foreach ($error['ERRORS'] as $m)
							{
								if (isset($m['statusMessage']) && $m['statusMessage'] == 'Invalid token')
								{
									$arrayID = $m['identifier'] - 1;
									if (isset($tokens[$arrayID]))
									{
										$failedCount++;
										$this->RemoveToken($tokens[$arrayID], CLIENT_IOS);
									}
								}
							}
						}
					}
				}
			}
			
			$sentCount = count($tokens) - $failedCount;
			
			$this->AddMessage("push-sent-count", $this->module, array("Client" => CLIENT_IOS, "Count" => $sentCount));
			if($failedCount > 0)
				$this->AddMessage("push-fail-count", $this->module, array("Client" => CLIENT_IOS, "Count" => $errorCount));
		}
		catch (ApnsPHP_Exception $e) 
		{
			$this->AddError("push-error", $this->module, array("Client" => CLIENT_IOS, "Error" => $e->getCode().' '.$e->getMessage()));
		}
	}
	
	function BuildPushData()
	{
		$title = $this->GetProperty("Title");
		$text = $this->GetProperty("Text");
		$target = $this->GetProperty("Target");
		$url = null;
		if($target == "url")
		{
			$url = $this->GetProperty("URL");
		}
		$data = array(
				"title" => $title,
				"text" => $text,
				"target" => $target,
				"url" => $url
		);
		return $data;
	}
	
	function CallIOSFeedback($tokens)
	{
		$feedback = new ApnsPHP_Feedback(ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION, $this->iosServerCertificatePath);
		//$feedback->setProviderCertificatePassphrase($this->iosPassPhrase);
		$feedback->setRootCertificationAuthority($this->iosEntrustRootCertificatePath);
			
		$feedback->connect();
			
		$deadTokens = $feedback->receive();
		if (!empty($deadTokens))
		{
			foreach ($deadTokens as $token) {
				$this->RemoveToken($token["deviceToken"], CLIENT_IOS);
				if(($key = array_search($token["deviceToken"], $tokens)) !== false)
				{
					unset($tokens[$key]);
				}
			}
		}
			
		$feedback->disconnect();
		
		return array_values($tokens);
	}
}

