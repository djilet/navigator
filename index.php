<?php
require_once(dirname(__FILE__)."/include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("module.php");

/*@var parser URLParser */
$urlParser =& GetURLParser();
$path = $urlParser->GetShortPathAsArray();

$fixedPath = $urlParser->GetFixedPathAsArray();

$levels = 0;
$header = array();

$page = new Page();
$pageFound = false;

$module = new Module();
$moduleFound = false;

$pathToModule = array();
$pathInsideModule = $path;

$pageDescriptionCount = abs(intval(GetFromConfig("PageDescriptionCount")));

if (isset($path[0]) AND preg_match('/^v([0-9]+)/ui', $path[0])) {
	$module->LoadForPublic("data", NULL, $pathToModule, $pathInsideModule, array(), NULL, array());
	exit(0);
}

$url = GetCurrentProtocol().$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'];
$header['CanonicalURL'] = explode('?', $url)[0];
if (isset($_SERVER['REDIRECT_URL'])){
    $header['CanonicalURL'] .= $_SERVER['REDIRECT_URL'];
}

//support UTM
if(isset($_GET['utm_source']))
{
	$session =& GetSession();
	$session->SetProperty("utm_source", $_GET['utm_source']);
	$session->SetProperty("utm_medium", $_GET['utm_medium']);
	$session->SetProperty("utm_campaign", (isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : ''));
	$session->SetProperty("utm_term", (isset($_GET['utm_term']) ? $_GET['utm_term'] : ''));
	$session->SetProperty("utm_content", (isset($_GET['utm_content']) ? $_GET['utm_content'] : ''));
	$session->SaveToDB();
}

//temporary redirect 50% exhibition
/*$tmpRedirectFind = '/exhibition/';
$tmpRedirectReplace = '/exhibition2/';
$tmpRedirectRequest = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if(substr($tmpRedirectRequest, 0, strlen($tmpRedirectFind)) === $tmpRedirectFind) {
    $session =& GetSession();
    if(!$session->IsPropertySet('RedirectExhibition')) {
        if(mt_rand(0,1)){
            $session->SetProperty('RedirectExhibition', 1);
        }
        else {
            $session->SetProperty('RedirectExhibition', 2);
        }
        $session->SaveToDB();
    }
    if($session->GetProperty('RedirectExhibition') == 1) {
        Send302($tmpRedirectReplace.substr($tmpRedirectRequest, strlen($tmpRedirectFind)));
    }
}*/

if (count($fixedPath) == 1 and ($fixedPath[0] == INDEX_PAGE))
{
	$newURL = $urlParser->GetRedirectURL();
	if ($newURL)
	{
		Send301($newURL);
	}
	// Load index page for selected language
	$pageFound = $page->LoadIndexPage();
	if ($pageFound)
	{
		if ($module->ModuleExists($page->GetProperty("Link")))
			$moduleFound = $page->GetProperty("Link");
		$levels = 1;
		$header["Title"] = $page->GetProperty("Title");
		$header["Description"] = $page->GetProperty("Description");
		$header["StaticPath"] = $page->GetProperty("StaticPath");
		$header["Template"] = $page->GetProperty("Template");
		$header["Content"] = $page->GetProperty("Content");
		for ($i = 1; $i < $pageDescriptionCount; $i++)
		{
			$header["Description".($i+1)] = $page->GetProperty("Description".($i+1));
		}
		$header["TitleH1"] = $page->GetProperty("TitleH1");
		$header["MetaTitle"] = $page->GetProperty("MetaTitle");
		$header["MetaKeywords"] = $page->GetProperty("MetaKeywords");
		$header["MetaDescription"] = $page->GetProperty("MetaDescription");
		$header["Navigation"] = $page->GetPathAsArray();
		$header["MenuImageCount"] = count($page->params);
		for ($i = 0; $i < count($page->params); $i++)
		{
			$header[$page->params[$i]["Name"]] = $page->GetProperty($page->params[$i]["Name"]);
			$header[$page->params[$i]["Name"]."Path"] = $page->GetProperty($page->params[$i]["Name"]."Path");
		}
		$header["IndexPage"] = 1;
	}
}
else
{
	// Try to find page by path
	$query = "SELECT PageID, StaticPath, Path2Root, Link,
		Title, MetaKeywords, MetaDescription, Template
		FROM `page` WHERE
			WebsiteID=".intval(WEBSITE_ID)."
			AND StaticPath IN (".implode(", ", Connection::GetSQLArray($fixedPath)).")
			AND LanguageCode=".Connection::GetSQLString(DATA_LANGCODE)."
			AND Path2Root<>'#' AND StaticPath IS NOT NULL
		ORDER BY Path2Root";
	$pageList = new PageList();
	$pageList->LoadFromSQL($query);
	$pages = array();
	$currentPageID = null;
	$modulePageID = null;
    $isSpecialPage = false;
    
    foreach ($pageList->GetItems() as $item)
	{
		$p = explode("#", $item["Path2Root"]);
		$c = count($p);
		// First page of the path is found
		if ($item["StaticPath"] == $fixedPath[0] && $c == 3)
		{
			$currentPageID = $item["PageID"];
			$levels++;
			if ($module->ModuleExists($item["Link"]))
			{
				$moduleFound = $item["Link"];
				$modulePageID = $item["PageID"];
			}
			$isSpecialPage = in_array(
			    $item["Template"],
                [
                    'page-exhibition.html',
                    'page-exhibition2.html',
                    'page-exhibition3.html',
                    'page-exhibition4.html',
                    'page-exhibition4_online.html',
                    'page-exhibition5_online.html',
                    'page-exhibition-landing.html',
                    'page-university.html',
                    'page-profession.html',
                    'page-article.html',
                    'page-online-events.html',
                    'page-online-exhibition.html',
                    'page-open-day.html'
                ]
            );
			
			continue;
		}
		// Find other pages
		if (!is_null($currentPageID) && count($fixedPath) > $levels)
		{
			if ($item["StaticPath"] == $fixedPath[$levels] && $p[$c - 2] == $currentPageID)
			{
				$currentPageID = $item["PageID"];
				$levels++;
				if ($module->ModuleExists($item["Link"]))
				{
					$moduleFound = $item["Link"];
					$modulePageID = $item["PageID"];
				}
			}
		}
	}
	
	if ($moduleFound && $modulePageID != $currentPageID)
		$moduleFound = false;
	
	if ($levels == count($fixedPath) || $moduleFound != false || $isSpecialPage)
	{
		// Static pages have text/html presentation only
		if ($urlParser->fileExtension != HTML_EXTENSION && $moduleFound == false && !$isSpecialPage)
		{
			Send404();
		}

		$pageFound = $page->LoadByID($currentPageID);
		
		// Redirect code
		if ($page->GetCountChildren() > 0)
		{
			$newURL = $urlParser->GetRedirectURL();
			if ($newURL)
			{
				Send301($newURL);
			}
		}
		
		$header["Title"] = $page->GetProperty("Title");
		$header["Description"] = $page->GetProperty("Description");
		$header["StaticPath"] = $page->GetProperty("StaticPath");
		$header["Template"] = $page->GetProperty("Template");
		$header["Content"] = $page->GetProperty("Content");
		for ($i = 1; $i < $pageDescriptionCount; $i++)
		{
			$header["Description".($i+1)] = $page->GetProperty("Description".($i+1));
		}
		$header["TitleH1"] = $page->GetProperty("TitleH1");
		$header["MetaTitle"] = $page->GetProperty("MetaTitle");
		$header["MetaKeywords"] = $page->GetProperty("MetaKeywords");
		$header["MetaDescription"] = $page->GetProperty("MetaDescription");
		$header["Navigation"] = $page->GetPathAsArray();
		$header["MenuImageCount"] = count($page->params);
		for ($i = 0; $i < count($page->params); $i++)
		{
			$header[$page->params[$i]["Name"]] = $page->GetProperty($page->params[$i]["Name"]);
			$header[$page->params[$i]["Name"]."Path"] = $page->GetProperty($page->params[$i]["Name"]."Path");
		}
	}

    //redirect from subdomain
    $allowTemplatesOnSubdomain = [
        'page-university.html',
        'page-profession.html',
    ];
	$allowModulesOnSubdomain = [
        'college',
        'data',
    ];

    if ($urlParser->GetSubDomain()){
        $redirect = false;
        if (!empty($moduleFound)){
            if (!in_array($moduleFound, $allowModulesOnSubdomain)){
                $redirect = true;
            }
        }
        elseif(!in_array($page->GetProperty('Template'), $allowTemplatesOnSubdomain)){
            $redirect = true;
        }

        if ($redirect){
            Send301(URLParser::getPrefixWithSubDomain('') . $_SERVER['REQUEST_URI']);
        }
    }
    //redirect from subdomain end
}

$moduleList = $module->GetModuleList();
for ($i = 0; $i < count($moduleList); $i++)
{
	$data = $module->LoadForHeader($moduleList[$i]["Folder"], $page);
	if (is_array($data) && count($data) > 0)
	{
	    if (isset($data['Navigation'])) {
            $data['Navigation'] = array_merge($header['Navigation'], $data['Navigation']);
        }
		// Put module data to header/footer
		$header = array_merge($header, $data);
		// Put module data to content (page.html) of the static pages
		$page->AppendFromArray($data);
	}
}

// Load language scripts
$header["JavaScripts"] = array(
	array("JavaScriptFile" => PROJECT_PATH."include/language/language.js.php".($moduleFound != false ? "?Module=".$page->GetProperty('Link') : ""))
);
$header['CurrentPageURL'] = $page->GetPageURL(false);
$header['FullPageURL'] = GetCurrentProtocol() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if(isset($_GET['AlertMessage']))
{
    $header['AlertMessage'] = GetTranslation("alert-message-".$_GET['AlertMessage']);
}

if ($moduleFound != false)
{
	$pathToModule = array_slice($fixedPath, 0, $levels);
	$pathInsideModule = array_slice($path, $levels);

	$header['Module'] = $page->GetProperty('Link');
	$header['Content'] = $page->GetProperty('Content');

	if (!$module->LoadForPublic($moduleFound, $page->GetProperty("Template"), $pathToModule, $pathInsideModule, $header, $page->GetProperty("PageID"), $page->GetConfig()))
	{
		Send404();
	}
}
else if ($pageFound)
{
	$publicPage = new PublicPage();
	$content = $publicPage->Load($page->GetProperty("Template"), $header, $page->GetProperty("PageID"));
	$content->SetLoop("Navigation", $header["Navigation"]);
	$content->SetVar('CurrentPageURL', str_replace('index'.HTML_EXTENSION, '', $page->GetPageURL(false)));
	$content->LoadFromObject($page);

	if ($page->GetProperty("StaticPath") == INDEX_PAGE) {
		$content->SetVar("IndexPage", 1);
	}

	$publicPage->Output($content);
}
else {
	Send404();
}