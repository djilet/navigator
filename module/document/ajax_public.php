<?php

require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/order.php");
es_include("localpage.php");

$module = "document";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');
$order = new DocumentOrder($module);

switch ($post->GetProperty("Action")) {
    case "NewOrder":
    	$post->SetProperty("Universities", implode("; ", $post->GetProperty("University")));
    	if ($order->Create($post)) {
        	$formToSubmit = $order->AddBill($post);
        	if($formToSubmit) {
        		$result['status'] = 'success';
        		$result['paymentForm'] = $formToSubmit;
        	}
        	else {
        		$result['errors'] = $order->GetErrorsAsString();
        		$result['errorNames'] = $order->getErrorNames();
        	}
        } else {
            $result['errors'] = $order->GetErrorsAsString();
            $result['errorNames'] = $order->getErrorNames();
        }

        break;
}

echo json_encode($result);
