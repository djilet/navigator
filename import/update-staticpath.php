<?php
require_once(dirname(__FILE__)."/../include/init.php");
es_include("logger.php");

$logger = new Logger(PROJECT_DIR."var/log/staticpath.log");
$stmt = GetStatement();

/*$list = $stmt->FetchList("SELECT u.UniversityID, u.ShortTitle, u.StaticPath FROM data_university u");
for($i=0; $i<count($list); $i++)
{
	$title = $list[$i]['ShortTitle'];
	$staticPath = RuToStaticPath($title);
	if($staticPath != $list[$i]['StaticPath'])
	{
		$logger->info("RewriteRule ^university/".$list[$i]['StaticPath']."$ http://propostuplenie.ru/university/".$staticPath." [R=301,L]");
		$stmt->Execute("UPDATE data_university SET StaticPath=".Connection::GetSQLString($staticPath)." WHERE UniversityID=".intval($list[$i]['UniversityID']));
	}
	
}

$list = $stmt->FetchList("SELECT s.SpecialityID, s.Title, s.StaticPath, u.StaticPath as UniversityPath FROM data_speciality s LEFT JOIN data_university u ON s.UniversityID=u.UniversityID");
for($i=0; $i<count($list); $i++)
{
	$title = $list[$i]['Title'];
	$staticPath = RuToStaticPath($title);
	if($staticPath != $list[$i]['StaticPath'])
	{
		$logger->info("RewriteRule ^university/".$list[$i]['UniversityPath']."/".$list[$i]['StaticPath']."$ http://propostuplenie.ru/university/".$list[$i]['UniversityPath']."/".$staticPath." [R=301,L]");
		$stmt->Execute("UPDATE data_speciality SET StaticPath=".Connection::GetSQLString($staticPath)." WHERE SpecialityID=".intval($list[$i]['SpecialityID']));
	}
}

$list = $stmt->FetchList("SELECT p.ProfessionID, p.Title, p.StaticPath FROM data_profession p");
for($i=0; $i<count($list); $i++)
{
	$title = $list[$i]['Title'];
	$staticPath = RuToStaticPath($title);
	if($staticPath != $list[$i]['StaticPath'])
	{
		$logger->info("RewriteRule ^profession/".$list[$i]['StaticPath']."$ http://propostuplenie.ru/profession/".$staticPath." [R=301,L]");
		$stmt->Execute("UPDATE data_profession SET StaticPath=".Connection::GetSQLString($staticPath)." WHERE ProfessionID=".intval($list[$i]['ProfessionID']));
	}
}*/

$list = $stmt->FetchList("SELECT o.OnlineEventID, o.Title, o.StaticPath FROM data_online_event o");
for($i=0; $i<count($list); $i++)
{
	$title = $list[$i]['Title'];
	$staticPath = RuToStaticPath($title);
	if($staticPath != $list[$i]['StaticPath'])
	{
		$stmt->Execute("UPDATE data_online_event SET StaticPath=".Connection::GetSQLString($staticPath)." WHERE OnlineEventID=".intval($list[$i]['OnlineEventID']));
	}
}



?>
