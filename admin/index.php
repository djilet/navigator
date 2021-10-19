<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("user.php");

$user = new User();
$request = new LocalObject(array_merge($_POST, $_GET));

function RedirectUserToDefautPage($request, $user)
{
    if ($request->GetProperty("ReturnPath"))
    {
        header("Location: ".$request->GetProperty("ReturnPath"));
    }
    else
    {
        if($user->GetProperty("Role") == "partner")
            header("Location: ".ADMIN_PATH."module.php?load=document");
        elseif($user->GetProperty("Role") == CONSULTANT)
            header("Location: ".ADMIN_PATH."module.php?load=basetest");
        elseif($user->GetProperty("Role") == PROFTEST)
            header("Location: ".ADMIN_PATH."module.php?load=proftest");
        else
            header("Location: ".ADMIN_PATH."module.php?load=data");
    }
    exit();
}

if ($request->GetProperty("Logout"))
{
	$user->Logout();
	$adminPage = new PopupPage();
	$content = $adminPage->Load("login.html");
	$content->LoadMessagesFromObject($user);
	$adminPage->Output($content);
}
else
{
	if ($user->LoadBySession() && $user->Validate(array(INTEGRATOR, ADMINISTRATOR, CONSULTANT, ONLINEEVENT, PARTNER, PROFTEST, ROLE_UNIVERSITY)))
	{
	    RedirectUserToDefautPage($request, $user);
	}

	if ($request->GetProperty("Login"))
	{
	    if ($user->LoadByRequest($request) && $user->Validate(array(INTEGRATOR, ADMINISTRATOR, CONSULTANT, ONLINEEVENT, PARTNER, PROFTEST, ROLE_UNIVERSITY)))
		{
		    RedirectUserToDefautPage($request, $user);
		}
	}
	$adminPage = new PopupPage();
	$content = $adminPage->Load("login.html");
	$content->LoadErrorsFromObject($user);
	$content->LoadFromObject($request, array("ReturnPath", "RememberMe", "Login"));
	$adminPage->Output($content);
}

?>