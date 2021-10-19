<?php

require_once(dirname(__FILE__) . "/init.php");
es_include("modulehandler.php");

class QuestionHandler extends ModuleHandler
{
	public function processPublic()
	{
		Send404();
	}

    public function ProcessHeader($module, Page $page = null){
        $data = [
            'TemplateQuestionAnonUser' => false,
        ];

        $questionPages = new PageList();
        $questionPages->LoadPageListForModule('question');
        if (!empty($questionPages->_items)){
            $questionPage = new Page();
            $questionPage->LoadByID($questionPages->_items[0]['PageID']);
            $data['TemplateQuestionAnonUser'] = (bool) $questionPage->GetConfig()['AnonUser'];
        }

        return $data;
    }
}
