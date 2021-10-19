<?php
require_once(dirname(__FILE__)."/../include/init.php");

$delimiter = ';';
$enclosure = '"';

$file = fopen('region.csv', 'r');
$stmt = GetStatement();

$success = 0;
$fail = 0;

while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
    if ($data[0] == 'id вуза') {
        continue;
    }
    
    $importID = $data[0];
    $regionTitle = $data[1];
    
    $item = $stmt->FetchRow("SELECT UniversityID FROM data_university WHERE 
        (ImportID='".$importID."' OR ImportID='0".$importID."' OR ImportID='00".$importID."') LIMIT 1");
    if($item){
        $region = $stmt->FetchRow("SELECT RegionID FROM data_region WHERE Title=".Connection::GetSQLString($regionTitle)." LIMIT 1");
        if($region){
            $query = "UPDATE data_university SET RegionID=".intval($region["RegionID"])." WHERE UniversityID=".intval($item["UniversityID"]);
            if($stmt->Execute($query)){
                $success++;
            }
            else {
                print_r("SQL_ERROR: ".$query."<br/>");
                $fail++;
            }
        }
        else {
            print_r("NO_REGION: Title=".$region."<br/>");
            $fail++;
        }
    }
    else {
        print_r("NOT_FOUND: ImportID=".$importID."<br/>");
        $fail++;
    }    
}

print_r("TOTAL: success=".$success.", fail=".$fail."<br/>");

fclose($file);

?>