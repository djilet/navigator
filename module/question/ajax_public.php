<?php

require_once(dirname(__FILE__) . "/../../include/init.php");
require_once dirname(__FILE__) . '/include/message.php';
require_once dirname(__FILE__) . '/include/message_list.php';
require_once dirname(__FILE__) . '/../users/include/user.php';
require_once dirname(__FILE__) . '/../data/include/public/University.php';
require_once dirname(__FILE__) . '/../data/include/UniversityAgent.php';
require_once dirname(__FILE__) . '/../data/include/admin/author_list.php';
es_include("localpage.php");

$module = "question";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');

switch ($post->GetProperty("Action")) {
    case "loadQuestionMessages":
        $question = new QuestionMessageList($module);
        $authorList = new DataAuthorList();
		$result['status'] = 'success';
	
		$page = new Page();
		$page->LoadByID($post->GetIntProperty('PageID'));
		$url = $page->GetPageURL(false);
	
		$question->load($post);
        $authorList->LoadAuthorList();

		$popupPage = new PopupPage($module, false);
		$tpl = $popupPage->Load('_data/question_messages.html');
		$tpl->SetLoop('QuestionMessageList', $question->getItemsWithAuthorInfo($authorList));
		$tpl->SetLoop('QuestionPager', $question->GetPagingAsArray($url, $url));
		$user = new UserItem('user');
		$user->loadBySession();
		if ($user->IsPropertySet('UserID')){
			$tpl->SetVar('UserItemID', $user->GetProperty('UserID'));
		}
		$result['html'] = $popupPage->Grab($tpl);
		
		break;
		
    case "addQuestionMessage":
    	$user = new UserItem();
    	$user->loadBySession();

        $message = new QuestionMessage($module);
        $message->loadFromRequest($post, $user);

        //TODO something
        if ($user->ValidateNotEmpty('UserID')){
            if ($message->GetProperty('UserCommentsStatus') !== 'Y'){
                $message->AddError("user-comments-blocked", 'question');
            }
        }

        if(!$message->HasErrors() && $message->Save()){
            $result['status'] = 'success';
        }
        else{
            $result['status'] = 'error';
            $errors = $message->GetErrors();
            foreach ($errors as $index => $item) {
                $result['error_list'] .= $item;
                if ( $index !== count($errors) -1 ){
                    $result['error_list'] .= ",\n";
                }
            }
        }

    	break;
}

echo json_encode($result);
