<?php

/**
 * Date:    24.07.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */
class FCMManager
{
	private static $SERVER_KEY = 'AAAAHSaIhxU:APA91bFxTWtLvsUGQEUy2M-7VZp5-EpZMag8yguYN4jiphOrX326vNjJO8RGqQArMUw0o0H9GVFkTsDN0yLqVCPa3Z2QAWcJT9osiKFHZDB5mJnIIU8KlqGVv5JVYSQ1sfHZO6r9KFWS';
	private static $error;
	
	public static function send(FCMMessage $message)
	{
		self::$error = null;
		
		$headers = array(
			'Authorization: key=' . self::$SERVER_KEY,
			'Content-Type: application/json'
		);

		$fields = array(
			'notification' => array(
				'title' => $message->getTitle(),
				'body'  => $message->getBody()
			)
		);
		
		$token = $message->getToken();
		if (empty($token)) {
			self::$error = 'Empty token';
			return false;
		} else if (is_array($token)) {
			$fields['registration_ids'] = $token;
		} else {
			$fields['to'] = $token;
		}
		
		$data = $message->getData();
		if (!empty($data) AND is_array($data)) {
			$fields['data'] = $data;
		}

		//Initializing curl to open a connection
		$ch = curl_init();

		//Setting the curl url
		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');

		//setting the method as post
		curl_setopt($ch, CURLOPT_POST, true);

		//adding headers 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//disabling ssl support
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		//adding the fields in json format 
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

		//finally executing the curl request 
		$result = curl_exec($ch);
		if ($result === FALSE) {
			self::$error = curl_error($ch);
		}

		//Now close the connection
		curl_close($ch);

		//and return the result 
		return json_decode($result);
	}

	/**
	 * @return mixed
	 */
	public static function getError()
	{
		return self::$error;
	}
	
}