<?php

require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/user.php");
es_include("localpage.php");

$module = "users";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');
$user = new UserItem($module);

switch ($post->GetProperty("Action")) {
    case "Registration":
        if ($user->Registration($post)) {
            $result['status'] = 'success';
            $result['reload'] = 1;
        } else {
            $result['errors'] = $user->GetErrorsAsString();
            $result['errorNames'] = $user->getErrorNames();
        }

        break;

    case "Auth":
        if ($user->Authentication($post->GetProperty('Email'), $post->GetProperty('Pass'))) {
            $result['status'] = 'success';
            $result['reload'] = 1;
        } else {
            $result['errors'] = $user->GetErrorsAsString();
            $result['errorNames'] = $user->getErrorNames();
        }

        break;

    case "AuthByEmail":
        if ($id = $user->getIDByEmail($post->GetProperty('Email'))){
            $user->loadByID($id);
            if ($authKey = $user->createAuthKey()){

                //email notification to user
                $template = new Page();
                if($template->LoadByStaticPath("user-sign-in"))
                {
                    $authLink = GetUrlPrefix() . 'profile/auth/authKey/?AuthKey=' . $authKey;
                    $authLink .= '&redirect_url=' . urlencode($post->GetProperty('RedirectUrl'));

                    $content = $template->GetProperty("Content");
                    $content = str_replace("[AuthLink]", $authLink, $content);

                    SendMailFromAdmin($user->GetProperty('UserEmail'), "Навигатор поступления: вход на сайт", $content);

                    $result['status'] = 'success';
                    $result['message'] = 'Ссылка для авторизации отправлена на указанный адрес электронной почты';
                }
            }
        }

        if ($user->HasErrors()){
            $result['errors'] = $user->GetErrorsAsString();
            $result['errorNames'] = $user->getErrorNames();
        }

        break;
        
    case "Restore":
        if ($user->SendRestoreLink($post->GetProperty('Email'))) {
            $result['status'] = 'success';
            $result['message'] = 'Ссылка на восстановление пароля отправлена на указанный адрес электронной почты';
        } else {
            $result['message'] = 'Указанный адрес электронной почты не зарегистрирован в системе';
        }
        
        break;
}

echo json_encode($result);
