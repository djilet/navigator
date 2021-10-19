<?php
require_once(dirname(__FILE__)."/include/init.php");
es_include("apiresponse.php");
es_include("user.php");
es_include("module.php");

$request = new LocalObject(array_merge($_GET, $_POST));
if ($s = $request->GetProperty('s'))
{
    $chunks = explode("/", $s);
    $method = $_SERVER["REQUEST_METHOD"];
    
    if($chunks[0] == "core")
    {
        $response = new ApiResponse();
        if(count($chunks) > 1 && $chunks[1] == "login" && $method == "POST")
        {
            $user = new User();
            if($user->LoadByRequest($request) && substr($user->GetProperty("Role"), 0, 4) === "api-")
            {
                if(!$user->GetProperty("PrivateKey"))
                {
                    $user->CreatePrivateKey();
                }
                $response->SetStatus("success");
                $response->SetCode("200");
                $response->SetData(array(
                    "UserID" => $user->GetProperty("UserID"),
                    "PrivateKey" => $user->GetProperty("PrivateKey"),
                    "Role" => $user->GetProperty("Role"),
                ));
            }
            else 
            {
                $response->LoadErrorsFromObject($user);
                $response->SetStatus("error");
                $response->SetCode("403");
            }
        }
        else
        {
            $response->SetStatus("error");
            $response->SetCode(404);
        }
        $response->Output();
        return true;
    }
    else 
    {
        $module = new Module();
        if(!$module->ProcessApiRequest($chunks[0], $method, $chunks, $request))
        {
            Send404();
        }
    }
}
else 
{
    Send404();
}

?>