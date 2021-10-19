<?php
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/test.php");
require_once(dirname(__FILE__) ."/../users/include/user.php");
es_include("localpage.php");
es_include("urlfilter.php");


$module = "basetest";
$request = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'success');

$user = new UserItem('user');
$user->loadBySession();

$test = new BaseTest();
//TODO get PageID
if($request->IsPropertySet('ShowTest') && $testUserID = BaseTestUser::getIDByLinkID($request->GetProperty('ShowTest'))){
    $testUser = new BaseTestUser();
    $testUser->load($testUserID);
}
else{
    $testUser = $test->getActiveTestUserByUserID($request->GetIntProperty('PageID'), $user->GetIntProperty('UserID'));
}

$testUserID = $testUser->GetIntProperty('BaseTestUserID');

switch ($request->GetProperty("Action")) {
	case "loadProfession":
		$popupPage = new PopupPage($module, false);
		$tpl = $popupPage->Load('basetest-tmpl/profession_list.html');
		$tpl->LoadFromObject($request);
		$tpl->SetLoop('ProfessionList', $test::getOrderProfessionList($testUserID, $request));
		if ($testUser->GetIntProperty('FeedbackRating') < 1){
			$tpl->SetVar('FeedbackForm', true);
		}
        if ($user->GetProperty('EgeStatus') == 'Y'){
            $tpl->SetVar('ShowEge', true);
        }


		$result['html'] = $popupPage->Grab($tpl);
		break;

	case "Feedback":
		if ($request->IsPropertySet('Rating')){
			$test->saveFeedback($testUser, $request->GetProperty('Rating'), $request->GetProperty('Message'));
		}
		break;

	default:
		$result['Status'] = 'error';
		break;
}

echo json_encode($result);
