<?php 
require_once(dirname(__FILE__)."/../include/init.php");
include('simple_html_dom.php');

$regions = array(
	/*"adygeya",
	"altai",
	"barnaul",
	"amur",
	"arhangelsk",
	"astrahan",
	"ufa",
	"belgorod",
	"bryansk",
	"buryatia",
	"vladimir",
	"volgograd",
	"vologda",
	"vrn",
	"dagestan",
	"birobidzhan",
	"zabaikal",
	"ivanovo",
	"ingushetia",
	"irkutsk",
	"kbrdnblkr",
	"kaliningrad",
	"kalmykia",
	"kaluga",
	"kamchatka",
	"krchvchrks",
	"karelia",
	"kemerovo",
	"kirov",
	"komi",
	"kostroma",
	"ksdr",
	"sochi",
	"krsk",
	"krym",
	"kurgan",
	"kursk",
	"spb",
	"lipetsk",
	"magadan",
	"marijel",
	"mordovia",
	"",
	"murmansk",
	"narianmar",
	"nnov",
	"vnovgorod",
	"nsk",
	"omsk",
	"orenburg",
	"orel",
	"penza",
	"prm",
	"primorie",
	"pskov",
	"rnd",
	"ryazan",
	"smr",
	"tolyatti",
	"saratov",
	"sahalin",
	"ekt",
	"alania",
	"smolensk",
	"stavropol",
	"tambov",
	"kzn",
	"tver",
	"tomsk",
	"tula",
	"tyva",
	"tyumen",
	"udmurtia",
	"ulyanovsk",
	"habarovsk",
	"hakasia",
	"yugra",
	"chel",
	"chechnya",
	"chuvashia",
	"chukotka",
	"yakutia",
	"yanao",
	"yar",*/
	"web"
);

$stmt = GetStatement();

for ($i=0; $i<count($regions); $i++){
	$base = "https://";
	if(strlen($regions[$i]) > 0) $base.=$regions[$i].".";
	$base .= "repetitors.info";
	$link = $base."/repetitor/";
	
	while($link != null){
		$html = file_get_html($link);
		
		foreach($html->find('table.AnkTB') as $element){
			$names = $element->find('.pnmst a');
			if(count($names) > 0) {
				$name = iconv("windows-1251", "utf-8", $names[0]->innertext);
					
				$query = "SELECT count(*) FROM scripts_repetitors WHERE Region=".Connection::GetSQLString($regions[$i])." AND Name=".Connection::GetSQLString($name);
				$exists = $stmt->FetchField($query);
				if($exists == 0){
					$repetitorCode = $element->innertext;
					$query = "INSERT INTO scripts_repetitors(Region,Name,Created,CodeShort)
					VALUES(".Connection::GetSQLString($regions[$i]).",".Connection::GetSQLString($name).",".Connection::GetSQLString(GetCurrentDateTime()).",".Connection::GetSQLString($repetitorCode).")";
					$stmt->Execute($query);
				}
			}
		}
		
		$link = null;
		$nextLinks = $html->find('.rarrow a');
		if(count($nextLinks) > 0){
			$link = $base.$nextLinks[0]->href;
		}
	}
}

?>