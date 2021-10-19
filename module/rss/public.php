<?php
require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/base_rss.php");
es_include("modulehandler.php");

class RssHandler extends ModuleHandler{

    function ProcessPublic(){
		$this->header["InsideModule"] = $this->module;
		$urlParser =& GetURLParser();

		if (isset($urlParser->fixedPath[1]) && $urlParser->IsXML()){
            $this->ShowXML($urlParser->fixedPath[1]);
        }
		else{
		    $this->ShowXML($urlParser->fixedPath[1]);
		    //Send404();
        }
	}

	public function ShowXML($listName){
        $language =& GetLanguage();

        $popupPage = New PopupPage($this->module, false);
        $popupPage->tmplPrefix = 'rss-tmpl/';
        $content = $popupPage->Load( $popupPage->tmplPrefix . "rss.xml");

        $listRSS = BaseRSS::getInstance($listName);
        if (!$listRSS instanceof BaseRSS){
            return false;
        }

        $listRSS->prepareTemplate($content, $this->header);

        $content->SetVar('CharSet',$language->GetHTMLCharset());
        $content->SetVar('ChannelLang', $language->_dataLanguageCode);
        $popupPage->Output($content);

	}
}