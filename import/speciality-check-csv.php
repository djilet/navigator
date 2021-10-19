<?php
require_once(dirname(__FILE__) . "/../include/init.php");

$import = new Import\SpecialityImport();
if ($import->setImportFile(__DIR__ . '/source/speciality_2020_2021.csv') === false) {
    echo $import->getErrors();
    exit;
}

$found = 0;
$notfound = 0;
$missed = array();
$abbr = array();

$stmt = GetStatement();

while (($data = $import->getNext()) !== false) {

	if ($data[1] == 'id вуза' || empty($data[1])) {
        continue;
    }

    if ($id = $import->findUniversityByImportID($import->value('ИдВуза'))) {
	$found++;
    }
    else {
	if(array_key_exists($import->value('ИдВуза'), $missed)){
	    $missed[$import->value('ИдВуза')] += 1;
	}
	else {
	    $missed[$import->value('ИдВуза')] = 1;
	}
	$abbr[$import->value('ИдВуза')] = $import->value('АббревиатураВуза');

	$notfound++;
    }

}

foreach($missed as $id=>$count){
    $query = "UPDATE data_university SET ImportID=".Connection::GetSQLString($id)." WHERE ShortTitle=".Connection::GetSQLString($abbr[$id]).";";
    echo $query.'<br/>';
}

echo 'Found rows: '.$found.'<br/>';
echo 'Not found rows: '.$notfound.'<br/>';
