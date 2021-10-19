<?php 

$URL = 'https://propostuplenie.ru';

if(isSiteAvailible($URL)){
    echo 'site is up';
}else{
    echo 'site is down';
    sendSMS("79139303622", "propostuplenie is down");
    shell_exec('/var/www/monitoring/restart/remote-run.sh');
}

function isSiteAvailible($url) {    
    // Проверка правильности URL
    if(!filter_var($url, FILTER_VALIDATE_URL)){
        return false;
    }
    
    // Инициализация cURL
    $curlInit = curl_init($url);
    
    // Установка параметров запроса
    curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
    curl_setopt($curlInit,CURLOPT_HEADER,true);
    curl_setopt($curlInit,CURLOPT_NOBODY,true);
    curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
    
    // Получение ответа
    $response = curl_exec($curlInit);
    $httpcode = curl_getinfo($curlInit, CURLINFO_HTTP_CODE);
    
    // закрываем CURL
    curl_close($curlInit);
    
    return ($response && ($httpcode == 200)) ? true : false;
}

function sendSMS($phone, $text)
{
    $link = "http://lk.rapporto.ru:9002/navigator";
    if( $link && !empty($phone) )
    {
        $link .= "?msisdn=".$phone."&message=".urlencode($text);
        $response = trim(file_get_contents($link));
    }
}

?>