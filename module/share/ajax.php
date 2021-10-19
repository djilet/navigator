<?php
define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
es_include("user.php");


$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR))) {
    $result["SessionExpired"] = GetTranslation("your-session-expired");
    exit();
}

$request = new LocalObject(array_merge($_GET, $_POST));
$result = array();
$result['status'] = 'success';

switch ($request->GetProperty("Action"))
{
    default:
        $result['status'] = 'error';
        break;
}

echo json_encode($result);
exit();
