<?php

require_once(dirname(__FILE__) . "/init.php");
es_include("modulehandler.php");


class ShareHandler extends \ModuleHandler{

    protected $catalogUrl;
    protected $ajaxPath;

    public function ProcessHeader($module, \Page $page = null){
        $data = array();
		return $data;
    }

    public function ProcessPublic(){
        Send404();
    }
}