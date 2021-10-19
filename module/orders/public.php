<?php

require_once(dirname(__FILE__) . "/init.php");
es_include("modulehandler.php");

class OrdersHandler extends ModuleHandler
{
    public function processPublic()
    {
    	$session = GetSession();
        $publicPage = new PublicPage($this->module);
        $request = new LocalObject(array_merge($_POST, $_GET));

        $this->parseRequest($request);
    }

    private function parseRequest(LocalObject $request)
    {
    	$page = new Page();
    	$page->LoadByID($this->pageID);
    	
    	$this->header["Template"] = $this->tmplPrefix."form.html";
    	$publicPage = new PublicPage($this->module);
    	$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
    	$content->SetLoop("Navigation", $this->header["Navigation"]);
    	$content->SetVar("PageID", $this->pageID);
    	
    	$content->SetVar("TitleH1", $this->header["TitleH1"]);
    	$content->SetVar("Description", $this->header["Description"]);
    	$content->SetVar("Content", $this->content);
    	
    	$content->SetVar("StaticPath", $page->GetProperty("StaticPath"));    	
    	$content->SetVar("MenuImage1Path", $page->GetProperty("MenuImage1Path"));

    	//Template settings
        foreach ($this->config as $property => $value) {
            $content->SetVar($property, $value);
        }
        
        $countryList = array(
            array("Title" => "Австралия"),
            array("Title" => "Австрия"),
            array("Title" => "Бельгия"),
            array("Title" => "Болгария"),
            array("Title" => "Великобритания"),
            array("Title" => "Германия"),
            array("Title" => "Голландия"),
            array("Title" => "Ирландия"),
            array("Title" => "Испания"),
            array("Title" => "Италия"),
            array("Title" => "Канада"),
            array("Title" => "Кипр"),
            array("Title" => "Китай"),
            array("Title" => "Мальта"),
            array("Title" => "Новая Зеландия"),
            array("Title" => "ОАЭ"),
            array("Title" => "Португалия"),
            array("Title" => "Сингапур"),
            array("Title" => "США"),
            array("Title" => "Франция"),
            array("Title" => "Чехия"),
            array("Title" => "Швейцария"),
            array("Title" => "Шотландия"),
            array("Title" => "Южная Корея"),
            array("Title" => "Япония"),
        );
        $content->SetLoop("CountryList", $countryList);
    	 
    	$publicPage->Output($content);
    }
}
