<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/data/init.php");
es_include('filesys.php');

$stmt = GetStatement();

$list = $stmt->FetchList('SELECT RegistrationID FROM event_registrations WHERE EventID IN (4,10) AND ShortLink IS NULL');
$result = 0;
for($i=0; $i<count($list); $i++)
{
	$longUrl = "http://propostuplenie.ru/exhibition?Registration=".$list[$i]['RegistrationID'];
	$postData = array('longUrl' => $longUrl);
	$jsonData = json_encode($postData);
	 
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key=AIzaSyD1RTvD0Y8Z33JITzYSp8LjjSs0Pb0SnLc');
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curlObj, CURLOPT_HEADER, 0);
	curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
	curl_setopt($curlObj, CURLOPT_POST, 1);
	curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);
	 
	$response = curl_exec($curlObj);
	$json = json_decode($response);
	curl_close($curlObj);
	 
	if(!isset($json->error))
	{
		$stmt->Execute('UPDATE event_registrations SET ShortLink='.Connection::GetSQLString($json->id).' WHERE RegistrationID='.$list[$i]['RegistrationID']);
		$result++;
	}
	else
	{
		print_r($json->error);
		break;
	}
}
echo "updated:".$result;

