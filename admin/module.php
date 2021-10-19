<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
require_once(dirname(__FILE__)."/../module/data/include/UniversityAgent.php");
es_include("user.php");
es_include("localpage.php");
es_include("page.php");
es_include("pagelist.php");

$user = new User();
$user->LoadBySession();
$request = new LocalObject(array_merge($_GET, $_POST));

//Validate UniversityAgent
if ($user->getRole() === ROLE_UNIVERSITY){
    $agent = UniversityAgent::getByUserID($user->GetIntProperty('UserID'));
    if (!$agent || !$agent->isActive()){
        Send302(ADMIN_PATH . "user.php?UserID={$agent->UserID}");
    }
}

//TODO: move module dependent permissions from this file
if($request->GetProperty('load') == 'data')
{
    $user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ROLE_UNIVERSITY));
}
elseif($request->GetProperty('load') == 'question')
{
    $user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ROLE_UNIVERSITY));
}
elseif($request->GetProperty('load') == 'basetest')
{
    $user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, CONSULTANT));
}
elseif($request->GetProperty('load') == 'proftest')
{
    $user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, PROFTEST));
}
elseif($request->GetProperty('load') == 'document')
{
    $user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT, PARTNER));
}
else
{
    $user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT));
}

// Function to determine correct PageID for DATA_LANGCODE
function DefineInitialPage(LocalObject $request)
{
	$page = new Page();
	if ($page->LoadByID($request->GetProperty("PageID")))
	{
		if ($page->GetProperty("Type") == 2 && $page->GetProperty("Link") == $request->GetProperty('load'))
		{
			return $page;			
		}
	}
	return null;
}

$adminFile = dirname(__FILE__)."/../module/".$request->GetProperty('load')."/admin.php";

if ($request->GetProperty('load') && is_file($adminFile))
{
	$moduleURL = "module.php?load=".$request->GetProperty('load');
	require_once($adminFile);
}
else
{
	echo "Module is not specified";
}

?>