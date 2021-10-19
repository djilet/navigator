<?php 
require_once(dirname(__FILE__) . "/../include/init.php");

$delimiter = ';';
$enclosure = '"';

$file = fopen(__DIR__ . '/source/college-synonyms.csv', 'r');
$stmt = GetStatement();

while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
    
    if (intval($data[0]) == 0) {
        continue;
    }
    
    $stmt->Execute('UPDATE college_college SET Synonyms='.Connection::GetSQLString($data[1]). 'WHERE ImportID='.intval($data[0]));
}

?>