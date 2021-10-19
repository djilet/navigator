<?php 
require_once(dirname(__FILE__) . "/../include/init.php");

$delimiter = ';';
$enclosure = '"';

$file = fopen('bigdirections.csv', 'r');
$stmt = GetStatement();

$sortOrder = $stmt->FetchField("SELECT MAX(SortOrder) FROM data_bigdirection");
if($sortOrder) $sortOrder++;
else $sortOrder = 1;

while (($data = fgetcsv($file, 0, $delimiter, $enclosure)) !== false) {
	if ($data[0] == 'Крупное направление') {
        continue;
    }
    
    $bigtitle = $data[0];
    $dtitle = $data[1];
    $did = $data[2];
    
    $directionID = $stmt->FetchField("SELECT DirectionID FROM data_direction WHERE Title=".Connection::GetSQLString($dtitle));
    if($directionID){
    	$bigDirectionID = $stmt->FetchField("SELECT BigDirectionID FROM data_bigdirection WHERE Title=".Connection::GetSQLString($bigtitle));
    	if(!$bigDirectionID){
    		$stmt->Execute("INSERT INTO data_bigdirection(Title,SortOrder) VALUES(".Connection::GetSQLString($bigtitle).",".$sortOrder.")");
    		$bigDirectionID = $stmt->GetLastInsertID();
    		$sortOrder++;
    	}
    	$stmt->Execute("UPDATE data_direction SET BigDirectionID=".intval($bigDirectionID).", Number=".Connection::GetSQLString($did)." WHERE DirectionID=".intval($directionID));
    }
}

fclose($file);

?>