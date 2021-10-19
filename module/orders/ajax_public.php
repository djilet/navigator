<?php

require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/order.php");
require_once(dirname(__FILE__) ."/../tracker/include/analytic_system/sender.php");
es_include("localpage.php");

use Module\Tracker\AnalyticSystem;

$module = "orders";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');
$order = new Order($module);

switch ($post->GetProperty("Action")) {
    case "NewOrder":
        if ($order->Create($post)) {
            $result['status'] = 'success';
            $result['reload'] = 1;

            //Analytic system
            AnalyticSystem\Sender::sendEventLeadFromBlog('order', $post->GetProperty("FirstName"), $post->GetProperty("LastName"), $post->GetProperty("Phone"));
            //Analytic system end

        } else {
            $result['errors'] = $order->GetErrorsAsString();
            $result['errorNames'] = $order->getErrorNames();
        }

        break;
}

echo json_encode($result);
