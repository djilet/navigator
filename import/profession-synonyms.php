<?php 
require_once(dirname(__FILE__) . "/../include/init.php");

$delimiter = ';';
$enclosure = '"';

$file = fopen(__DIR__ . '/source/profession-synonyms.csv', 'r');
$stmt = GetStatement();

while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
    
    if (intval($data[0]) == 0) {
        continue;
    }
    
    $stmt->Execute('UPDATE data_profession SET Synonyms='.Connection::GetSQLString($data[2]). 'WHERE ImportID='.intval($data[0]));
}

?>