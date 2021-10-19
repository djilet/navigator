<?php

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/price_list.php");
es_include("modulehandler.php");

class DocumentHandler extends ModuleHandler
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
    	
    	$publicPage = new PublicPage($this->module);
    	if($request->IsPropertySet("RegionID") && $request->IsPropertySet("UniversityCount"))
    	{
    		$this->header["Template"] = $this->tmplPrefix."form2.html";
    		$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
    		$content->SetLoop("Navigation", $this->header["Navigation"]);
    		$content->SetVar("PageID", $this->pageID);
    		 
    		$content->SetVar("TitleH1", $this->header["TitleH1"]);
    		
    		$content->SetVar("RegionID", $request->GetProperty("RegionID"));
    		$content->SetVar("UniversityCount", $request->GetProperty("UniversityCount"));
    	}
    	else
    	{
    		$this->header["Template"] = $this->tmplPrefix."form1.html";
    		$content = $publicPage->Load($this->header["Template"], $this->header, $this->pageID);
    		$content->SetLoop("Navigation", $this->header["Navigation"]);
    		$content->SetVar("PageID", $this->pageID);
    		 
    		$content->SetVar("TitleH1", $this->header["TitleH1"]);
    		$content->SetVar("Description", $this->header["Description"]);
    		$content->SetVar("Content", $this->content);
    		 
    		$content->SetVar("StaticPath", $page->GetProperty("StaticPath"));
    		$content->SetVar("MenuImage1Path", $page->GetProperty("MenuImage1Path"));
    	}
    	
    	$priceList = new PriceList($this->module);
    	$priceList->load();
    	$content->LoadFromObjectList("PriceList", $priceList);
    	
    	$publicPage->Output($content);
    }
}
