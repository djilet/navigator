<?php 
require_once(dirname(__FILE__)."/../include/init.php");
include('simple_html_dom.php');

$link = "https://ru.repetitors.info/";
$html = file_get_html($link);

foreach($html->find('table#RUctTB a') as $element){
	$pos = strpos($element->href, '.');
	$region = substr($element->href, 8, $pos-8);
	
	echo '"'.$region.'" => "'.iconv("windows-1251", "utf-8", $element->innertext).'",<br/>';
}

?>