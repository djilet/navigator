<?php

require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/order.php");
es_include("localpage.php");

$module = "service";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');
$serviceOrder = new ServiceOrder($module);

switch ($post->GetProperty("Action")) {
    case "NewOrder":
        if ($serviceOrder->Create($post)) {
        	$formToSubmit = $serviceOrder->AddBill($post);
        	if($formToSubmit) {
        		$result['status'] = 'success';
        		$result['paymentForm'] = $formToSubmit;
        	}
        	else {
        		$result['errors'] = $serviceOrder->GetErrorsAsString();
        		$result['errorNames'] = $serviceOrder->getErrorNames();
        	}
        } else {
            $result['errors'] = $serviceOrder->GetErrorsAsString();
            $result['errorNames'] = $serviceOrder->getErrorNames();
        }

        break;
}

echo json_encode($result);
