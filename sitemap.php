<?php
require_once(dirname(__FILE__)."/include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("module.php");

$prefix = 'https://'.$_SERVER['HTTP_HOST'];
$urlParser = new URLParser();

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

if (!$urlParser->GetSubDomain()){
    $pageList = new PageList();
    $pages = $pageList->GetPageTree();
    processMenu($prefix, $pages['full']);
}

$module = new Module();
$moduleList = $module->GetModuleList();
for ($i = 0; $i < count($moduleList); $i++)
{
	$links = $module->LoadLinks($moduleList[$i]["Folder"], $urlParser->GetSubDomain());
	if($links)
	{
		for($j=0; $j<count($links); $j++)
		{
			addURL($prefix, PROJECT_PATH.$links[$j]);
		}
	}
}

echo '</urlset>';

function addURL($prefix, $url)
{
	$path = parse_url($url, PHP_URL_HOST) ? $url : $prefix.$url;
	if(substr($path, -1) != '/' && strpos($path, '?') === false) $path .= '/';
	echo '<url><loc>'.$path.'</loc></url>';
}

function processMenu($prefix, $menuList)
{
	for($i=0; $i<count($menuList); $i++)
	{
		if($menuList[$i]['PageURL'])
		{
			addURL($prefix, $menuList[$i]['PageURL']);
		}
		if(isset($menuList[$i]['Children']))
		{
			processMenu($prefix, $menuList[$i]['Children']);
		}
	}
}
