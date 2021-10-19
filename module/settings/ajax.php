<?php 
define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
es_include("user.php");

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT))) {
    $result["SessionExpired"] = GetTranslation("your-session-expired");
    exit();
}
else {
    $request = new LocalObject(array_merge($_GET, $_POST));
}

echo json_encode($result);