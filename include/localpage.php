<?php

es_include("page.php");
es_include("pagelist.php");
es_include("language.php");
es_include("template.php");

class LocalPage
{
	var $includePaths;
	var $module;
	var $headerTmpl;
	var $footerTmpl;
	var $isAdmin;

	function LocalPage($module)
	{
		$this->includePaths = array();
		$this->module = $module;
	}

	function Load($file, $header = array(), $pageID = null)
	{
		$this->_InitHeader($header, $pageID);
		$this->_InitFooter($header, $pageID);
		return $this->_CreateTemplate($file, $header, $pageID);
	}

	function Output($contentTmpl)
	{
		$this->headerTmpl->pparse();
		$contentTmpl->pparse();
		$this->footerTmpl->pparse();
	}

	function Grab($contentTmpl)
	{
		$header = $this->headerTmpl->Grab();
		$content = $contentTmpl->Grab();
		$footer = $this->footerTmpl->Grab();
		return $header.$content.$footer;
	}

	function _InitHeader($header, $pageID)
	{
		if (isset($header['HeaderTemplate']))
			$this->headerTmpl = $this->_CreateTemplate($header['HeaderTemplate'], $header, $pageID);
		else
			$this->headerTmpl = $this->_CreateTemplate("_header.html", $header, $pageID);
		$this->headerTmpl->LoadFromArray($header);
		$this->headerTmpl->SetVar("PageID", $pageID);
	}

	function _InitFooter($header, $pageID)
	{
		if (isset($header['FooterTemplate']))
			$this->footerTmpl = $this->_CreateTemplate($header['FooterTemplate'], $header, $pageID);
		else
			$this->footerTmpl = $this->_CreateTemplate("_footer.html", $header, $pageID);
		$this->footerTmpl->LoadFromArray($header);
		$this->footerTmpl->SetVar("PageID", $pageID);
	}

	function _CreateTemplate($file, $header, $pageID)
	{
		$tmpl = new Template($file, array("INCLUDE_PATHS" => $this->includePaths));

		if ($this->isAdmin)
		{
			$tmpl->SetVar("PATH2MAIN", ADMIN_PATH."template/");
			if (!is_null($this->module))
			{
				$tmpl->SetVar("MODULE_NAME", $this->module);
				$tmpl->SetVar("MODULE_URL", GetCurrentProtocol().$_SERVER["HTTP_HOST"].ADMIN_PATH."module.php?load=".$this->module);
				$tmpl->SetVar("MODULE_PATH", PROJECT_PATH.'module/'.$this->module.'/');
				$tmpl->SetVar("PATH2MOD", PROJECT_PATH."module/".$this->module."/template/");
			}
			$tmpl->SetVar("CMS_BRAND", GetFromConfig("Brand"));
		}
		else
		{
			$tmpl->SetVar("PATH2MAIN", PROJECT_PATH."website/".WEBSITE_FOLDER."/template/");
		}

		if (strlen($GLOBALS["WebsiteLogo"]) > 0 && is_file(PROJECT_DIR."website/".WEBSITE_FOLDER."/template/".$GLOBALS["WebsiteLogo"]))
		{
			$tmpl->SetVar("WEBSITE_LOGO", PROJECT_PATH."website/".WEBSITE_FOLDER."/template/".$GLOBALS["WebsiteLogo"]);
		}

		/*@var language Language */
		$language =& GetLanguage();
		$translation = $language->LoadForTempate($file, $this->module, $this->isAdmin);
		foreach ($translation as $key => $value)
		{
			if(isset($value["Value"]))
				$tmpl->SetVar("LNG_".$key, $value["Value"]);
		}
		return $tmpl;
	}
}

class AdminPage extends LocalPage
{
	function AdminPage($module = null)
	{
		parent::LocalPage($module);

		$this->isAdmin = true;

		if (!is_null($this->module))
		{
			array_push($this->includePaths, PROJECT_DIR."module/".$this->module."/template/");
		}
		array_push($this->includePaths, PROJECT_DIR.ADMIN_FOLDER."/template/");
	}

	function _InitHeader($header, $pageID)
	{
		parent::_InitHeader($header, $pageID);
		
		$user = new User();
		$user->LoadBySession();
		
		$adminMenu = array();
        if($user->GetProperty("Role") == CONSULTANT)
        {
            $adminMenu[] = array(
                "Title" => GetTranslation("module-title", 'basetest'),
                "Link" => "module.php?load=basetest",
                "AdminMenuIcon" => "fa fa-question"
            );
        }
        elseif($user->GetProperty("Role") == PROFTEST)
        {
            $adminMenu[] = array(
                "Title" => GetTranslation("module-title", 'proftest'),
                "Link" => "module.php?load=proftest",
                "AdminMenuIcon" => "fa fa-flag"
            );
        }
		elseif($user->GetProperty("Role") == ONLINEEVENT)
		{
		    $adminMenu[] = array(
		        "Title" => GetTranslation("module-title", 'data'),
		        "Link" => "module.php?load=data",
		        "AdminMenuIcon" => "fa fa-database"
		    );
		}
		else if($user->GetProperty("Role") == PARTNER)
		{
		    $adminMenu[] = array(
		        "Title" => GetTranslation("module-title", 'document'),
		        "Link" => "module.php?load=document",
		        "AdminMenuIcon" => "fa fa-envelope"
		    );
		}
        else if($user->GetProperty("Role") == ROLE_UNIVERSITY)
        {
            $adminMenu[] = array(
                "Title" => 'Данные о вузе и направлениях',
                "Link" => "module.php?load=data",
                "AdminMenuIcon" => "fa fa-database"
            );

            $adminMenu[] = array(
                "Title" => 'Доды',
                "Link" => "module.php?load=data&Section=open_day",
                "AdminMenuIcon" => "fa fa-database"
            );

            $adminMenu[] = array(
                "Title" => 'Новости/статьи',
                "Link" => "module.php?load=data&Section=article",
                "AdminMenuIcon" => "fa fa-database"
            );

            $adminMenu[] = array(
                "Title" => 'Вопрос-ответ',
                "Link" => "module.php?load=question",
                "AdminMenuIcon" => "fa fa-question"
            );

            $adminMenu[] = array(
                "Title" => 'Заявки',
                "Link" => "module.php?load=data&Section=user_university",
                "AdminMenuIcon" => "fa fa-bell"
            );

            $adminMenu[] = array(
                "Title" => "Настройки",
                "Link" => "user.php?UserID={$user->GetProperty('UserID')}",
                "AdminMenuIcon" => "fa fa-user"
            );
        }
		else
		{
		    $adminMenu[] = array(
		        "Title" => GetTranslation("admin-menu-site-structure"),
		        "Link" => "page_tree.php",
		        "AdminMenuIcon" => "fa fa-sitemap"
		    );
		    $adminMenu[] = array(
		        "Title" => GetTranslation("admin-menu-template-variables"),
		        "Link" => "variable.php",
		        "AdminMenuIcon" => "fa fa-book"
		    );
		    $adminMenu[] = array(
		        "Title" => GetTranslation("module-title", 'data'),
		        "Link" => "module.php?load=data",
		        "AdminMenuIcon" => "fa fa-database"
		    );
		    es_include("module.php");
		    $module = new Module();
		    $pageList = new PageList();
		    $adminModuleList = $module->GetModuleList('', true);
		    
		    for ($i = 0; $i < count($adminModuleList); $i++)
		    {
		        if(isset($adminModuleList[$i]["NoPages"]))
		        {
		            $moduleIcon = $GLOBALS['moduleConfig'][$adminModuleList[$i]["Folder"]]["AdminMenuIcon"];
		            $adminMenu[] = array(
		                "Title" => $adminModuleList[$i]["AdminTitle"],
		                "Link" => $adminModuleList[$i]["Link"],
		                "Selected" => (isset($header["Navigation"][1]["Link"]) && isset($link) && substr($header["Navigation"][1]["Link"], 0, strlen($link)) == $link ? true : false),
		                "AdminMenuIcon" => $moduleIcon
		            );
		        }
		        else
		        {
		            $pageList->LoadPageListForModule($adminModuleList[$i]["Folder"]);
		            $pages = $pageList->GetItems();
		            
		            if(count($pages) == 1)
		            {
		                $page = $pages[0];
		                $modulePage = new Page();
		                $modulePage->LoadByID($page["PageID"]);
		                $config = $modulePage->GetConfig();
		                
		                if(isset($config["AdminMenuIcon"]) && strlen($config["AdminMenuIcon"]) > 0)
		                    $moduleIcon = $config["AdminMenuIcon"];
		                    elseif(isset($GLOBALS['moduleConfig'][$adminModuleList[$i]["Folder"]]) && isset($GLOBALS['moduleConfig'][$adminModuleList[$i]["Folder"]]["AdminMenuIcon"]))
		                    $moduleIcon = $GLOBALS['moduleConfig'][$adminModuleList[$i]["Folder"]]["AdminMenuIcon"];
		                    else
		                        $moduleIcon = "";
		                        
		                        $link = $adminModuleList[$i]["Link"]."&PageID=".$page["PageID"];
		                        $adminMenu[] = array(
		                            "Title" => $page["PageTitle"],
		                            "Link" => $link,
		                            "Selected" => (isset($header["Navigation"][1]["Link"]) && substr($header["Navigation"][1]["Link"], 0, strlen($link)) == $link ? true : false),
		                            "AdminMenuIcon" => $moduleIcon
		                        );
		            }
		            else if(count($pages) > 1)
		            {
		                $moduleIcon = $GLOBALS['moduleConfig'][$adminModuleList[$i]["Folder"]]["AdminMenuIcon"];
		                $adminMenu[] = array(
		                    "Title" => $adminModuleList[$i]["AdminTitle"],
		                    "Link" => $adminModuleList[$i]["Link"],
		                    "Selected" => false,
		                    "AdminMenuIcon" => $moduleIcon
		                );
		            }
		        }
		    }
		    $adminMenu[] = array(
		        "Title" => GetTranslation("admin-menu-robots"),
		        "Link" => "robots.php",
		        "AdminMenuIcon" => "fa fa-rocket"
		    );
		    $adminMenu[] = array(
		        "Title" => GetTranslation("admin-menu-sitemap"),
		        "Link" => "sitemap.php",
		        "AdminMenuIcon" => "fa fa-file-text"
		    );
		    $adminMenu[] = array(
		        "Title" => GetTranslation("admin-menu-user-list"),
		        "Link" => "user.php",
		        "AdminMenuIcon" => "fa fa-users"
		    );
		}
		
		for ($i = 0; $i < count($adminMenu); $i++)
		{
			if ($header['Navigation'][0]['Link'] == $adminMenu[$i]["Link"])
				$adminMenu[$i]["Selected"] = true;
		}
		$this->headerTmpl->SetLoop("AdminMenu", $adminMenu);
	}
}

class PublicPage extends LocalPage
{
	var $fMenu;
	var $sMenu;
	var $cMenu;

	function PublicPage($module = null)
	{
		parent::LocalPage($module);

		$this->isAdmin = false;

		array_push($this->includePaths, PROJECT_DIR."website/".WEBSITE_FOLDER."/template/".INTERFACE_LANGCODE."/");
		array_push($this->includePaths, PROJECT_DIR."website/".WEBSITE_FOLDER."/template/");
	}

	function _CreateTemplate($file, $header, $pageID)
	{
		$tmpl = parent::_CreateTemplate($file, $header, $pageID);

		$defineCurrent = true;
		if (isset($header["InsideModule"]))
			$defineCurrent = false;

		if (!$this->fMenu)
		{
			$pageList = new PageList();
			$result = $pageList->GetMenuList($pageID, $defineCurrent);
			$this->fMenu = $result["full"];
			$this->sMenu = $result["menu_successor"];
			$this->cMenu = $result["menu_current"];
		}

		if (is_array($this->fMenu) && count($this->fMenu))
		{
			foreach ($this->fMenu as $menu)
			{
				if (isset($menu["Children0"]))
					$tmpl->SetLoop("MENU_".$menu["StaticPath"], $menu["Children0"]);
			}
		}

		if (is_array($this->sMenu) && count($this->sMenu))
		{
			$tmpl->SetLoop("MENU_successor", $this->sMenu);
		}

		if (is_array($this->cMenu) && count($this->cMenu))
		{
			$tmpl->SetLoop("MENU_current", $this->cMenu);
		}

		return $tmpl;
	}
}

class PopupPage extends LocalPage
{
	var $fMenu;
	var $sMenu;
	var $cMenu;

	function PopupPage($module = null, $isAdmin = true)
	{
		parent::LocalPage($module);

		$this->isAdmin = $isAdmin;

		if ($isAdmin)
		{
			if (!is_null($this->module))
			{
				array_push($this->includePaths, PROJECT_DIR."module/".$this->module."/template/");
			}
			array_push($this->includePaths, PROJECT_DIR.ADMIN_FOLDER."/template/");
		}
		else
		{
			array_push($this->includePaths, PROJECT_DIR."website/".WEBSITE_FOLDER."/template/".INTERFACE_LANGCODE."/");
			array_push($this->includePaths, PROJECT_DIR."website/".WEBSITE_FOLDER."/template/");
		}
	}

	function Load($file, $header = array(), $pageID = null)
	{
		$tmpl = $this->_CreateTemplate($file, $header, $pageID);
		$tmpl->LoadFromArray($header);
		$tmpl->SetVar("PageID", $pageID);
		return $tmpl;
	}

	function _CreateTemplate($file, $header, $pageID)

	{
		$tmpl = parent::_CreateTemplate($file, $header, $pageID);

		if (!$this->isAdmin)
		{
			$defineCurrent = true;
			if (isset($header["InsideModule"]))
				$defineCurrent = false;

			if (!$this->fMenu)
			{
				$pageList = new PageList();
				$result = $pageList->GetMenuList($pageID, $defineCurrent);
				$this->fMenu = $result["full"];
				$this->sMenu = $result["menu_successor"];
				$this->cMenu = $result["menu_current"];
			}

			if (is_array($this->fMenu) && count($this->fMenu))
			{
				foreach ($this->fMenu as $menu)
				{
					if (isset($menu["Children0"]))
						$tmpl->SetLoop("MENU_".$menu["StaticPath"], $menu["Children0"]);
				}
			}

			if (is_array($this->sMenu) && count($this->sMenu))
			{
				$tmpl->SetLoop("MENU_successor", $this->sMenu);
			}

			if (is_array($this->cMenu) && count($this->cMenu))
			{
				$tmpl->SetLoop("MENU_current", $this->cMenu);
			}
		}

		return $tmpl;
	}

	function Output($contentTmpl)
	{
		$contentTmpl->pparse();
	}

	function Grab($contentTmpl)
	{
		return $contentTmpl->Grab();
	}
}

?>