<?php

define("IS_ADMIN", true);

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

$admin = new User();
$admin->LoadBySession();
$admin->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT, ROLE_UNIVERSITY));

switch ($post->GetProperty("Action")) {
    case "addQuestionMessage":
        //Set author
        if ($admin->getRole() === ROLE_UNIVERSITY){
            $agent = UniversityAgent::getByUserID($admin->GetIntProperty('UserID'));
            $post->SetProperty('AuthorID', $agent->AuthorID);
        }

        $message = new QuestionMessage($module);
        $message->loadFromRequest($post, null, $post->GetIntProperty('AuthorID'));

        if($message->Save()){
            $result['status'] = 'success';
        }
        else{
            $result['status'] = 'error';
            $errors = $message->GetErrors();
            foreach ($errors as $index => $item) {
                $result['error_list'] .= GetTranslation($item, $module);
                if ( $index !== count($errors) -1 ){
                    $result['error_list'] .= ",\n";
                }
            }
        }

    	break;
}

echo json_encode($result);
