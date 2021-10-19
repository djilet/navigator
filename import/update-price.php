<?php
require_once(dirname(__FILE__)."/../include/init.php");

$delimiter = ';';
$enclosure = '"';

$file = fopen('price.csv', 'r');
$stmt = GetStatement();

$success = 0;
$fail = 0;
$notfound = 0;

while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
    if ($data[0] == 'id профиля') {
        continue;
    }
    
    $importID = $data[0];
    $price = floatval(preg_replace("/[^0-9]/", "", $data[1]));
    
    $item = $stmt->FetchRow("SELECT SpecialityID FROM data_speciality WHERE ImportID=".Connection::GetSQLString($importID)." LIMIT 1");
    if($item){
        $query = "UPDATE data_speciality SET Price=".Connection::GetSQLString($price)." WHERE SpecialityID=".intval($item["SpecialityID"]);
        if($stmt->Execute($query)){
            $success++;
        }
        else {
            print_r("SQL_ERROR: ".$query."<br/>");
            $fail++;
        }
    }
    else {
        print_r("NOT_FOUND: ImportID=".$importID."<br/>");
        $notfound++;
    }    
}

print_r("TOTAL: success=".$success.", notfound=".$notfound.", fail=".$fail."<br/>");

fclose($file);

?>