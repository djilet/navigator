<?php

abstract class BaseCurl
{
	protected $curlOption = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_POST => true,
	];

	protected function setCurlOption($option, $value){
		$this->curlOption[$option] = $value;
	}

	public function sendRequest(){
		try{
			$ch = curl_init();
			curl_setopt_array($ch, $this->curlOption);

			$response = curl_exec($ch);

			if (!$response){
				$curlError = curl_error($ch);
				throw new BaseCurlException($curlError);
			}
		}
		catch (BaseCurlException $e){
			throw $e;
		}
		finally{
			curl_close($ch);
		}

		//print_r(json_decode($response, true));
		return $response;
	}

}

class BaseCurlException extends Exception{

}