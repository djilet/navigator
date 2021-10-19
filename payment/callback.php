<?php
require_once(dirname(__FILE__)."/../include/init.php");
$request = array_merge($_GET, $_POST);

$billID = $request['orderNumber'];

if ($request['action'] == 'PaymentSuccess')
{
	Send301(PROJECT_PATH."successful-payment/");
}

Send301(PROJECT_PATH);

?>