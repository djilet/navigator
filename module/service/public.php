<?php

require_once(dirname(__FILE__) . "/init.php");
es_include("modulehandler.php");

class ServiceHandler extends ModuleHandler
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
    	$content->SetVar("Description2", $this->header["Description2"]);
    	$content->SetVar("Content", $this->content);
    	
    	$content->SetVar("StaticPath", $page->GetProperty("StaticPath"));    	
    	$content->SetVar("MenuImage1Path", $page->GetProperty("MenuImage1Path"));
    	
    	$config = $page->GetConfig();
    	$content->SetVar("PriceBefore", $config["PriceBefore"]);
    	$content->SetVar("PriceDiscount", $config["PriceDiscount"]);
    	$content->SetVar("PriceDiscountComment", $config["PriceDiscountComment"]);
    	$content->SetVar("PricePromocode", $config["PricePromocode"]);
    	$content->SetVar("YaTopGoal", $config["YaTopGoal"]);
    	$content->SetVar("YaBottomGoal", $config["YaBottomGoal"]);
    	$content->SetVar("GaTopGoal", $config["GaTopGoal"]);
    	$content->SetVar("GaBottomGoal", $config["GaBottomGoal"]);
    	 
    	$publicPage->Output($content);
    }
}
