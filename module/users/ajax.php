<?php

define("IS_ADMIN", true);

require_once(dirname(__FILE__)."/../../include/init.php");
es_include("js_http_request/JsHttpRequest.php");
es_include("localpage.php");
es_include("user.php");

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/user.php");
require_once(dirname(__FILE__)."/include/user_list.php");

$module = "users";
$language =& GetLanguage();
$JsHttpRequest = new JsHttpRequest($language->GetHTMLCharset());
$post = new LocalObject(array_merge($_GET, $_POST));

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, MODERATOR)))
{
    $_RESULT["SessionExpired"] = GetTranslation("your-session-expired");
    exit();
}

switch ($post->GetProperty("Action"))
{

    case "SwitchItem":
        $userItem = new UserItem($post->GetProperty('Module'));
        $userItem->SwitchActive($post->GetProperty('ItemID'), $post->GetProperty('Active'));
        $_RESULT["Answer"] = true;
        break;

    default:
        $_RESULT['Answer'] = false;
}

?>