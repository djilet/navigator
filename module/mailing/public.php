<?php

require_once(dirname(__FILE__) . "/init.php");
es_include("modulehandler.php");

class MailingHandler extends ModuleHandler
{
    public function processPublic()
    {
    	Send404();
    }
}
