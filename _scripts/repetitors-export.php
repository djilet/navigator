<?php 
require_once(dirname(__FILE__)."/../include/init.php");
include('simple_html_dom.php');

$regions = array(
		"adygeya" => "Адыгея",
		"altai" => "Алтай",
		"barnaul" => "Алтайский край",
		"amur" => "Амурская обл.",
		"arhangelsk" => "Архангельская обл.",
		"astrahan" => "Астраханская обл.",
		"ufa" => "Башкортостан",
		"belgorod" => "Белгородская обл.",
		"bryansk" => "Брянская обл.",
		"buryatia" => "Бурятия",
		"vladimir" => "Владимирская обл.",
		"volgograd" => "Волгоградская обл.",
		"vologda" => "Вологодская обл.",
		"vrn" => "Воронежская обл.",
		"dagestan" => "Дагестан",
		"birobidzhan" => "Еврейская АО",
		"zabaikal" => "Забайкальский край",
		"ivanovo" => "Ивановская обл.",
		"ingushetia" => "Ингушетия",
		"irkutsk" => "Иркутская обл.",
		"kbrdnblkr" => "Кабардино-Балкария",
		"kaliningrad" => "Калининградская обл.",
		"kalmykia" => "Калмыкия",
		"kaluga" => "Калужская обл.",
		"kamchatka" => "Камчатский край",
		"krchvchrks" => "Карачаево-Черкесия",
		"karelia" => "Карелия",
		"kemerovo" => "Кемеровская обл.",
		"kirov" => "Кировская обл.",
		"komi" => "Коми",
		"kostroma" => "Костромская обл.",
		"ksdr" => "Краснодарский край",
		"sochi" => "Сочи",
		"krsk" => "Красноярский край",
		"krym" => "Крым",
		"kurgan" => "Курганская обл.",
		"kursk" => "Курская обл.",
		"spb" => "Ленинградская обл.",
		"lipetsk" => "Липецкая обл.",
		"magadan" => "Магаданская обл.",
		"marijel" => "Марий Эл",
		"mordovia" => "Мордовия",
		"" => "Московская обл.",
		"murmansk" => "Мурманская обл.",
		"narianmar" => "Ненецкий АО",
		"nnov" => "Нижегородская обл.",
		"vnovgorod" => "Новгородская обл.",
		"nsk" => "Новосибирская обл.",
		"omsk" => "Омская обл.",
		"orenburg" => "Оренбургская обл.",
		"orel" => "Орловская обл.",
		"penza" => "Пензенская обл.",
		"prm" => "Пермский край",
		"primorie" => "Приморский край",
		"pskov" => "Псковская обл.",
		"rnd" => "Ростовская обл.",
		"ryazan" => "Рязанская обл.",
		"smr" => "Самарская обл.",
		"tolyatti" => "Тольятти",
		"saratov" => "Саратовская обл.",
		"sahalin" => "Сахалинская обл.",
		"ekt" => "Свердловская обл.",
		"alania" => "Северная Осетия",
		"smolensk" => "Смоленская обл.",
		"stavropol" => "Ставропольский край",
		"tambov" => "Тамбовская обл.",
		"kzn" => "Татарстан",
		"tver" => "Тверская обл.",
		"tomsk" => "Томская обл.",
		"tula" => "Тульская обл.",
		"tyva" => "Тыва",
		"tyumen" => "Тюменская обл.",
		"udmurtia" => "Удмуртия",
		"ulyanovsk" => "Ульяновская обл.",
		"habarovsk" => "Хабаровский край",
		"hakasia" => "Хакасия",
		"yugra" => "Ханты-Мансийский АО",
		"chel" => "Челябинская обл.",
		"chechnya" => "Чечня",
		"chuvashia" => "Чувашия",
		"chukotka" => "Чукотский АО",
		"yakutia" => "Якутия",
		"yanao" => "Ямало-Ненецкий АО",
		"yar" => "Ярославская обл.",
		"web" => "Интернет"
);

$stmt = GetStatement();

$query = "SELECT Region, Name, CodeShort FROM scripts_repetitors";
$list = $stmt->FetchList($query);

$file = fopen("/tmp/repetitors.csv","w");

$lessons = array();
for ($i=0; $i<count($list); $i++){
	if($i%50 == 0){
		$row = $list[$i];
		
		$html = str_get_html('<html>'.$row["CodeShort"].'</html>');
		$info = $html->find('p');
		
		for($j=2; $j<count($info); $j++){
			$pos = strpos($info[$j]->innertext, "руб. / ч");
			if($pos > 0){
				$line = explode(':', substr($info[$j]->innertext, 0, $pos));
				if(count($line) > 1){
					$lesson = trim($line[0]);
					if(!in_array($lesson, $lessons)){
						$lessons[] = $lesson;
					}
				}
			}
		}
	}
}

$csvRow = array();
$csvRow[] = "Регион";
$csvRow[] = "ФИО";
$csvRow[] = "Кто";
$csvRow[] = "Описание";
for($k=0; $k<count($lessons); $k++){
	$csvRow[] = $lessons[$k];
}
fputcsv($file, $csvRow);

for ($i=0; $i<count($list); $i++){
	$row = $list[$i];
	
	$html = str_get_html('<html>'.$row["CodeShort"].'</html>');
	$info = $html->find('p');
	$who = $info[0]->innertext;
	$description = $info[1]->innertext;
	
	$csvRow = array();
	$csvRow[] = $regions[$row["Region"]];
	$csvRow[] = $row["Name"];
	$csvRow[] = $who;
	$csvRow[] = str_replace('<br>', "\n", $description);
	for($k=0; $k<count($lessons); $k++){
		$csvRow[] = "";
	}
	
	for($j=2; $j<count($info); $j++){
		$pos = strpos($info[$j]->innertext, "руб. / ч");
		if($pos > 0){
			$line = explode(':', substr($info[$j]->innertext, 0, $pos));
			if(count($line) > 1){
				$lesson = trim($line[0]);
				$lessonColumn = array_search($lesson, $lessons);
				if($lessonColumn !== false){
					$price = intval(trim($line[1]));
					$csvRow[$lessonColumn + 4] = $price;
				}
			}
		}
	}

	fputcsv($file, $csvRow);
}

fclose($file);

?>