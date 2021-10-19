<?php 
require_once 'vendor/autoload.php';
require_once(dirname(__FILE__)."/include/init.php");

$result = SendMailFromAdmin("tolstyh@gmail.com", "Тестовое сообщение", "<html><h1>Проверка!</h1><p>Это сообщение создано тестовым скриптом для отправки писем</p></html>");
print_r($result);

//Direct way
/*$apikey="6xgup7wfxo4wshfe58kdcmooz9dbudke1kp6x91e"; //API-key
$uni=new Unisender\ApiWrapper\UnisenderApi($apikey); 

$params = array(
    "email"=>"tolstyh@gmail.com",
    "sender_name"=>"Навигатор поступления",
    "sender_email"=>"noreply@propostuplenie.ru",
    "subject"=>"Тема от навигатора поступления",
    "body"=>"<html><h1>Крупный текст</h1><p>обычный текст</p></html>",
    "list_id"=>13560261
);
$result = json_decode($uni->sendEmail($params), true);
print_r($result["error"]);
*/

?>