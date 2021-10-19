<?php 

$handle = fopen("prepareevent.txt", "r");
if ($handle) {
	while (($line = fgets($handle)) !== false) {
		$cols = preg_split("/[\t]/", $line);
		print_r('array('."<br/>");
		print_r('&nbsp;		"Date" => "'.$cols[0].'",'."<br/>");
		print_r('&nbsp;		"Title" => "'.$cols[1].'",'."<br/>");
		print_r('&nbsp;		"Name" => "'.$cols[2].'",'."<br/>");
		print_r('&nbsp;		"Post" => "'.str_replace('"', '\"', $cols[3]).'"'."<br/>");
		print_r('),'."<br/>");
		
	}

	fclose($handle);
}

?>