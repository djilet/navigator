<?php
require_once(dirname(__FILE__) . "/../include/init.php");
set_time_limit(0);
function SpecialityAddressImport(){
	$import = new Import\SpecialityAddressImport();
	if ($import->setImportFile(__DIR__ . '/source/address.csv') === false) {
		echo $import->getErrors();
		exit;
	}
	$stmt = GetStatement();
	while (($data = $import->getNext()) !== false) {
		if (!$data[0] || $data[0]=='Город'){ continue; }
		if ($data[94]){
			$specID = $stmt->FetchField("SELECT SpecialityID FROM data_speciality WHERE ImportID=".$data[5]);
			if ($specID){
				$query = "UPDATE data_speciality SET `Address`='".$data[94]."' WHERE `SpecialityID` = ".$specID;
				$stmt->Execute($query);
			}
		}
	}
	echo '<h1>Скрипт заполнения адресов у специальностей завершился успешно</h1>';
}
SpecialityAddressImport();