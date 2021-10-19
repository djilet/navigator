<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/college_awards.php");
require_once(dirname(__FILE__) . "/include/college_admission_base.php");
require_once(dirname(__FILE__) . "/include/college_bigdirection.php");
require_once(dirname(__FILE__) . "/../data/include/admin/region_list.php");
es_include("user.php");

$module = "college";

$result = array();

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT))){
    $result["SessionExpired"] = GetTranslation("your-session-expired");
    exit();
}
else{
    $request = new LocalObject(array_merge($_GET, $_POST));

    switch ($request->GetProperty("Action")){
        case "linked-awards":
            $list = new CollegeAwards();
            $list->LoadForSuggest($request);
            $result = $list->GetItems();
            break;

		case "linked-bigdirection":
            $list = new CollegeBigDirection();
            $list->LoadForSuggest($request);
            $result = $list->GetItems();
            break;

		case "linked-region":
            $list = new DataRegionList();
            $list->LoadForSuggest($request);
            $result = $list->GetItems();
            break;

		case "linked-admission-base":
            $list = new AdmissionBase();
            $list->LoadForSuggest($request);
            $result = $list->GetItems();
            break;
    }

}

echo json_encode($result);