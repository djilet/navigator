<?php 
require_once(dirname(__FILE__)."/include/init.php");

$result = SendSMSFromAdmin("79139303622", "С тестового сервера");
print_r($result);

?>